<?php
/**
 * Integración de scripts en el checkout por bloques.
 *
 * @package CondicionesContratacionWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Registra el script que pinta las casillas en el checkout por bloques.
 */
class CCWOO_Blocks_Integration implements IntegrationInterface {

	/**
	 * Nombre de la integración. Determina la clave de los datos en JavaScript.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'ccwoo-terms';
	}

	/**
	 * Registra los recursos necesarios.
	 *
	 * @return void
	 */
	public function initialize() {
		wp_register_script(
			'ccwoo-checkout-blocks',
			CCWOO_PLUGIN_URL . 'assets/js/checkout-blocks.js',
			array( 'wp-element', 'wp-plugins', 'wp-data', 'wp-i18n', 'wc-blocks-checkout', 'wc-settings' ),
			CCWOO_VERSION,
			true
		);

		wp_set_script_translations( 'ccwoo-checkout-blocks', 'condiciones-contratacion-woocommerce', CCWOO_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Handles de los scripts del front.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'ccwoo-checkout-blocks' );
	}

	/**
	 * Handles de los scripts del editor.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * Datos que se exponen al script mediante `getSetting( 'ccwoo-terms_data' )`.
	 *
	 * @return array
	 */
	public function get_script_data() {
		$checkboxes = array();

		foreach ( CCWOO_Checkboxes::get_active() as $item ) {
			$checkboxes[] = array(
				'id'       => $item['id'],
				'text'     => wp_kses( $item['text'], CCWOO_Checkboxes::allowed_html() ),
				'required' => (bool) $item['required'],
				'message'  => sprintf(
					/* translators: %s: texto de la condición que el cliente debe aceptar. */
					__( 'Debes aceptar: %s', 'condiciones-contratacion-woocommerce' ),
					wp_strip_all_tags( $item['text'] )
				),
			);
		}

		return array(
			'namespace'  => CCWOO_Checkout_Blocks::EXTENSION_NAMESPACE,
			'checkboxes' => $checkboxes,
			'i18n'       => array(
				'requiredMark' => __( '(obligatoria)', 'condiciones-contratacion-woocommerce' ),
			),
		);
	}
}
