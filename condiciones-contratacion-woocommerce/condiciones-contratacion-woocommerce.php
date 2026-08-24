<?php
/**
 * Plugin Name:          Condiciones de contratación para WooCommerce
 * Plugin URI:           https://github.com/damasovelazquez/condiciones-contratacion-woocommerce
 * Description:          Añade al checkout de WooCommerce tantas casillas de aceptación de condiciones como necesites, con texto propio, orden configurable y carácter obligatorio u opcional.
 * Version:              1.2.0
 * Requires at least:    6.5
 * Requires PHP:         7.4
 * Author:               Dámaso Velázquez
 * Author URI:           https://webprogramacion.es
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          condiciones-contratacion-woocommerce
 * Domain Path:          /languages
 * Requires Plugins:     woocommerce
 * WC requires at least: 8.2
 * WC tested up to:      9.4
 *
 * @package CondicionesContratacionWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CCWOO_VERSION', '1.2.0' );
define( 'CCWOO_PLUGIN_FILE', __FILE__ );
define( 'CCWOO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCWOO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CCWOO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CCWOO_MIN_WC_VERSION', '8.2' );

/**
 * Declara la compatibilidad con las funcionalidades modernas de WooCommerce.
 *
 * Debe ejecutarse en `before_woocommerce_init`, antes de que WooCommerce
 * evalúe la compatibilidad de los plugins activos.
 *
 * @return void
 */
function ccwoo_declare_woocommerce_compatibility() {
	if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		return;
	}

	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CCWOO_PLUGIN_FILE, true );
	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', CCWOO_PLUGIN_FILE, true );
}
add_action( 'before_woocommerce_init', 'ccwoo_declare_woocommerce_compatibility' );

/**
 * Comprueba si WooCommerce está activo y en una versión soportada.
 *
 * @return bool
 */
function ccwoo_is_woocommerce_supported() {
	if ( ! class_exists( 'WooCommerce' ) || ! defined( 'WC_VERSION' ) ) {
		return false;
	}

	return version_compare( WC_VERSION, CCWOO_MIN_WC_VERSION, '>=' );
}

/**
 * Muestra un aviso en el administrador cuando falta WooCommerce o su versión es antigua.
 *
 * @return void
 */
function ccwoo_requirements_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		$message = sprintf(
			/* translators: %s: nombre del plugin. */
			esc_html__( '%s necesita que WooCommerce esté instalado y activo para funcionar.', 'condiciones-contratacion-woocommerce' ),
			'<strong>' . esc_html__( 'Condiciones de contratación para WooCommerce', 'condiciones-contratacion-woocommerce' ) . '</strong>'
		);
	} else {
		$message = sprintf(
			/* translators: 1: nombre del plugin, 2: versión mínima de WooCommerce requerida. */
			esc_html__( '%1$s necesita WooCommerce %2$s o superior.', 'condiciones-contratacion-woocommerce' ),
			'<strong>' . esc_html__( 'Condiciones de contratación para WooCommerce', 'condiciones-contratacion-woocommerce' ) . '</strong>',
			esc_html( CCWOO_MIN_WC_VERSION )
		);
	}

	printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses( $message, array( 'strong' => array() ) ) );
}

/**
 * Arranca el plugin una vez cargados todos los plugins.
 *
 * @return void
 */
function ccwoo_bootstrap() {
	require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-logger.php';

	if ( ! ccwoo_is_woocommerce_supported() ) {
		CCWOO_Logger::error(
			sprintf(
				'Requisitos no cumplidos: se necesita WooCommerce %s o superior. Detectado: %s.',
				CCWOO_MIN_WC_VERSION,
				defined( 'WC_VERSION' ) ? WC_VERSION : 'WooCommerce no activo'
			)
		);

		add_action( 'admin_notices', 'ccwoo_requirements_notice' );

		return;
	}

	require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-plugin.php';

	CCWOO_Plugin::instance();
}
add_action( 'plugins_loaded', 'ccwoo_bootstrap' );

/**
 * Crea las opciones por defecto en la activación.
 *
 * @return void
 */
function ccwoo_activate() {
	add_option( 'ccwoo_checkboxes', array() );
	add_option( 'ccwoo_disable_wc_terms', 'no' );
}
register_activation_hook( __FILE__, 'ccwoo_activate' );
