<?php
/**
 * Integración con el checkout por bloques y el Store API.
 *
 * @package CondicionesContratacionWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra las casillas en el checkout por bloques y las valida en el Store API.
 */
class CCWOO_Checkout_Blocks {

	/**
	 * Espacio de nombres usado en los datos de extensión del Store API.
	 *
	 * @var string
	 */
	const EXTENSION_NAMESPACE = 'ccwoo-terms';

	/**
	 * Registra los hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_blocks_checkout_block_registration', array( $this, 'register_integration' ) );
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_endpoint_data' ) );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'process_order' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'maybe_remove_terms_block' ), 10, 2 );
	}

	/**
	 * Registra la integración de JavaScript del checkout por bloques.
	 *
	 * @param object $registry Registro de integraciones del bloque de checkout.
	 *
	 * @return void
	 */
	public function register_integration( $registry ) {
		if ( ! interface_exists( '\Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' ) ) {
			return;
		}

		require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-blocks-integration.php';

		$registry->register( new CCWOO_Blocks_Integration() );
	}

	/**
	 * Declara los datos de extensión que el checkout del Store API acepta.
	 *
	 * @return void
	 */
	public function register_endpoint_data() {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => class_exists( '\Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema' )
					? \Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema::IDENTIFIER
					: 'checkout',
				'namespace'       => self::EXTENSION_NAMESPACE,
				'schema_callback' => array( $this, 'get_endpoint_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * Esquema de los datos de extensión: un booleano por casilla activa.
	 *
	 * @return array
	 */
	public function get_endpoint_schema() {
		$schema = array();

		foreach ( CCWOO_Checkboxes::get_active() as $item ) {
			$schema[ $item['id'] ] = array(
				'description' => __( 'Aceptación de una de las condiciones de contratación.', 'condiciones-contratacion-woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
			);
		}

		return $schema;
	}

	/**
	 * Valida las casillas obligatorias y guarda las aceptaciones del pedido.
	 *
	 * @param WC_Order        $order   Pedido creado desde el Store API.
	 * @param WP_REST_Request $request Petición del checkout.
	 *
	 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException Cuando falta una aceptación obligatoria.
	 *
	 * @return void
	 */
	public function process_order( $order, $request ) {
		$items = CCWOO_Checkboxes::get_active();

		if ( empty( $items ) ) {
			return;
		}

		$data     = $this->get_request_data( $request );
		$accepted = array();
		$missing  = array();

		foreach ( $items as $item ) {
			$is_accepted = ! empty( $data[ $item['id'] ] );

			if ( $is_accepted ) {
				$accepted[] = $item['id'];
			} elseif ( $item['required'] ) {
				$missing[] = wp_strip_all_tags( $item['text'] );
			}
		}

		if ( ! empty( $missing ) ) {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
				'ccwoo_terms_not_accepted',
				sprintf(
					/* translators: %s: lista de condiciones que el cliente debe aceptar, separadas por comas. */
					esc_html__( 'Debes aceptar las siguientes condiciones: %s', 'condiciones-contratacion-woocommerce' ),
					esc_html( implode( ', ', $missing ) )
				),
				400
			);
		}

		CCWOO_Order_Acceptances::record( $order, $accepted );
	}

	/**
	 * Extrae los datos de extensión del plugin de la petición del Store API.
	 *
	 * @param WP_REST_Request $request Petición del checkout.
	 *
	 * @return array
	 */
	protected function get_request_data( $request ) {
		$extensions = isset( $request['extensions'] ) ? $request['extensions'] : array();

		if ( ! is_array( $extensions ) || ! isset( $extensions[ self::EXTENSION_NAMESPACE ] ) ) {
			return array();
		}

		$data = $extensions[ self::EXTENSION_NAMESPACE ];

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Elimina el bloque de términos de WooCommerce cuando está desactivado en los ajustes.
	 *
	 * @param string $content Contenido renderizado del bloque.
	 * @param array  $block   Datos del bloque.
	 *
	 * @return string
	 */
	public function maybe_remove_terms_block( $content, $block ) {
		if ( ! isset( $block['blockName'] ) || 'woocommerce/checkout-terms-block' !== $block['blockName'] ) {
			return $content;
		}

		if ( ! CCWOO_Checkboxes::is_wc_terms_disabled() ) {
			return $content;
		}

		return '';
	}
}
