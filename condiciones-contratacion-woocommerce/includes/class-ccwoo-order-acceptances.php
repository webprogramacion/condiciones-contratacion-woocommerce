<?php
/**
 * Registro de las aceptaciones en el pedido.
 *
 * @package CondicionesContratacionWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Guarda las condiciones aceptadas en el pedido y las muestra en el administrador.
 */
class CCWOO_Order_Acceptances {

	/**
	 * Clave del metadato del pedido.
	 *
	 * @var string
	 */
	const META_KEY = '_ccwoo_acceptances';

	/**
	 * Registra los hooks.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
	}

	/**
	 * Guarda en el pedido el estado de cada casilla configurada.
	 *
	 * Se almacena el texto vigente en el momento de la compra, de forma que los
	 * pedidos antiguos conserven lo que el cliente aceptó aunque el texto cambie
	 * después. De cada casilla aceptada se guarda además la fecha y la IP desde
	 * la que se aceptó, como evidencia del consentimiento.
	 *
	 * @param WC_Order $order        Pedido en creación.
	 * @param array    $accepted_ids Identificadores de las casillas aceptadas.
	 *
	 * @return void
	 */
	public static function record( $order, $accepted_ids ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$items = CCWOO_Checkboxes::get_active();

		if ( empty( $items ) ) {
			return;
		}

		$accepted_ids = array_map( 'strval', (array) $accepted_ids );
		$timestamp    = current_time( 'mysql', true );
		$ip           = self::get_client_ip();
		$records      = array();

		foreach ( $items as $item ) {
			$accepted = in_array( (string) $item['id'], $accepted_ids, true );

			$records[] = array(
				'id'       => $item['id'],
				'text'     => $item['text'],
				'required' => $item['required'] ? 1 : 0,
				'accepted' => $accepted ? 1 : 0,
				'date'     => $accepted ? $timestamp : '',
				'ip'       => $accepted ? $ip : '',
			);
		}

		$order->update_meta_data( self::META_KEY, $records );
	}

	/**
	 * Obtiene la IP desde la que se realiza la compra.
	 *
	 * Se usa la utilidad de WooCommerce, que ya tiene en cuenta las cabeceras de
	 * los proxies y CDN habituales. La dirección se valida antes de guardarla.
	 *
	 * @return string IP del cliente, o cadena vacía si no se ha podido determinar.
	 */
	protected static function get_client_ip() {
		if ( class_exists( 'WC_Geolocation' ) ) {
			$ip = WC_Geolocation::get_ip_address();
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		} else {
			$ip = '';
		}

		$ip = filter_var( $ip, FILTER_VALIDATE_IP );

		return $ip ? $ip : '';
	}

	/**
	 * Devuelve las aceptaciones guardadas en un pedido.
	 *
	 * @param WC_Order $order Pedido.
	 *
	 * @return array
	 */
	public static function get( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return array();
		}

		$records = $order->get_meta( self::META_KEY );

		return is_array( $records ) ? $records : array();
	}

	/**
	 * Registra el metabox en la pantalla del pedido (HPOS y almacenamiento clásico).
	 *
	 * @return void
	 */
	public function register_meta_box() {
		add_meta_box(
			'ccwoo-order-acceptances',
			__( 'Condiciones aceptadas', 'condiciones-contratacion-woocommerce' ),
			array( $this, 'render_meta_box' ),
			array( 'shop_order', 'woocommerce_page_wc-orders' ),
			'normal',
			'default'
		);
	}

	/**
	 * Imprime el contenido del metabox.
	 *
	 * @param WP_Post|WC_Order $post_or_order Objeto de la pantalla actual.
	 *
	 * @return void
	 */
	public function render_meta_box( $post_or_order ) {
		$order = $post_or_order instanceof WP_Post ? wc_get_order( $post_or_order->ID ) : $post_or_order;

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$records = self::get( $order );

		if ( empty( $records ) ) {
			printf(
				'<p>%s</p>',
				esc_html__( 'Este pedido no tiene aceptaciones de condiciones registradas.', 'condiciones-contratacion-woocommerce' )
			);

			return;
		}

		$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		echo '<ul class="ccwoo-order-acceptances">';

		foreach ( $records as $record ) {
			$accepted = ! empty( $record['accepted'] );
			$required = ! empty( $record['required'] );
			$text     = isset( $record['text'] ) ? $record['text'] : '';
			$date     = ! empty( $record['date'] ) ? get_date_from_gmt( $record['date'], $date_format ) : '';
			$ip       = ! empty( $record['ip'] ) ? $record['ip'] : '';

			echo '<li style="margin-bottom:1em;">';

			printf(
				'<strong>%s</strong> ',
				$accepted
					? esc_html__( 'Aceptada', 'condiciones-contratacion-woocommerce' )
					: esc_html__( 'No aceptada', 'condiciones-contratacion-woocommerce' )
			);

			printf(
				'<em>(%s)</em><br />',
				$required
					? esc_html__( 'obligatoria', 'condiciones-contratacion-woocommerce' )
					: esc_html__( 'opcional', 'condiciones-contratacion-woocommerce' )
			);

			echo wp_kses( $text, CCWOO_Checkboxes::allowed_html() );

			$evidence = '';

			if ( $date && $ip ) {
				$evidence = sprintf(
					/* translators: 1: fecha y hora de la aceptación. 2: dirección IP desde la que se aceptó. */
					__( 'Aceptada el %1$s desde la IP %2$s', 'condiciones-contratacion-woocommerce' ),
					$date,
					$ip
				);
			} elseif ( $date ) {
				$evidence = sprintf(
					/* translators: %s: fecha y hora de la aceptación. */
					__( 'Aceptada el %s', 'condiciones-contratacion-woocommerce' ),
					$date
				);
			} elseif ( $ip ) {
				$evidence = sprintf(
					/* translators: %s: dirección IP desde la que se aceptó. */
					__( 'Aceptada desde la IP %s', 'condiciones-contratacion-woocommerce' ),
					$ip
				);
			}

			if ( $evidence ) {
				printf( '<br /><small>%s</small>', esc_html( $evidence ) );
			}

			echo '</li>';
		}

		echo '</ul>';
	}
}
