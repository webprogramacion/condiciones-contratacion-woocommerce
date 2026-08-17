<?php
/**
 * Clase principal del plugin.
 *
 * @package CondicionesContratacionWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orquesta la carga de los componentes del plugin.
 */
class CCWOO_Plugin {

	/**
	 * Instancia única.
	 *
	 * @var CCWOO_Plugin|null
	 */
	protected static $instance = null;

	/**
	 * Devuelve la instancia única del plugin.
	 *
	 * @return CCWOO_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->includes();
		$this->init_hooks();
		$this->init_components();
	}

	/**
	 * Carga los archivos de clases.
	 *
	 * @return void
	 */
	protected function includes() {
		require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-checkboxes.php';
		require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-order-acceptances.php';
		require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-checkout-classic.php';
		require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-checkout-blocks.php';

		if ( is_admin() ) {
			require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-settings-page.php';
		}
	}

	/**
	 * Registra los hooks generales del plugin.
	 *
	 * @return void
	 */
	protected function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_filter( 'plugin_action_links_' . CCWOO_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );

		if ( is_admin() ) {
			add_filter( 'woocommerce_get_settings_pages', array( $this, 'register_settings_page' ) );
		}
	}

	/**
	 * Instancia los componentes que registran sus propios hooks.
	 *
	 * @return void
	 */
	protected function init_components() {
		new CCWOO_Order_Acceptances();
		new CCWOO_Checkout_Classic();
		new CCWOO_Checkout_Blocks();
	}

	/**
	 * Carga las traducciones del plugin.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'condiciones-contratacion-woocommerce', false, dirname( CCWOO_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Registra la pestaña de ajustes dentro de los ajustes de WooCommerce.
	 *
	 * @param array $pages Páginas de ajustes de WooCommerce.
	 *
	 * @return array
	 */
	public function register_settings_page( $pages ) {
		$pages[] = new CCWOO_Settings_Page();

		return $pages;
	}

	/**
	 * Añade un enlace directo a los ajustes en la lista de plugins.
	 *
	 * @param array $links Enlaces de acción del plugin.
	 *
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=condiciones_contratacion' );

		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Ajustes', 'condiciones-contratacion-woocommerce' ) )
		);

		return $links;
	}
}
