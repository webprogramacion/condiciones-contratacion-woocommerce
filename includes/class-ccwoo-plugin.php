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
	 * Mensaje del fallo de arranque, si lo ha habido.
	 *
	 * @var string
	 */
	protected $startup_error = '';

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
		require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-logger.php';
		require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-checkboxes.php';
		require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-order-acceptances.php';
		require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-checkout-classic.php';
		require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-checkout-blocks.php';

		/*
		 * La pestaña de ajustes NO se carga aquí: extiende WC_Settings_Page, una clase
		 * que WooCommerce incluye a mano (no la resuelve su autoloader) justo antes de
		 * disparar `woocommerce_get_settings_pages`. Cargarla en `plugins_loaded` haría
		 * que la clase no llegara a definirse. Se carga en register_settings_page().
		 */
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
		try {
			new CCWOO_Order_Acceptances();
			new CCWOO_Checkout_Classic();
			new CCWOO_Checkout_Blocks();

			CCWOO_Logger::debug( 'Componentes inicializados correctamente.' );
		} catch ( Throwable $exception ) {
			// Un fallo aquí dejaría el sitio en blanco: se registra y se avisa en el admin.
			CCWOO_Logger::exception( $exception, 'init_components' );

			$this->startup_error = $exception->getMessage();

			add_action( 'admin_notices', array( $this, 'startup_error_notice' ) );
		}
	}

	/**
	 * Avisa en el administrador de que el plugin no ha podido arrancar del todo.
	 *
	 * @return void
	 */
	public function startup_error_notice() {
		if ( ! $this->startup_error || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s</p><p><code>%s</code></p></div>',
			esc_html__( 'Condiciones de contratación para WooCommerce:', 'condiciones-contratacion-woocommerce' ),
			esc_html__( 'el plugin no ha podido arrancar correctamente. Tienes el detalle en WooCommerce → Estado → Registros.', 'condiciones-contratacion-woocommerce' ),
			esc_html( $this->startup_error )
		);
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
		// En este punto WooCommerce ya ha incluido WC_Settings_Page.
		if ( ! class_exists( 'WC_Settings_Page' ) ) {
			CCWOO_Logger::error( 'No se ha podido registrar la pestaña de ajustes: falta la clase WC_Settings_Page.' );

			return $pages;
		}

		require_once CCWOO_PLUGIN_DIR . 'includes/class-ccwoo-settings-page.php';

		if ( ! class_exists( 'CCWOO_Settings_Page' ) ) {
			CCWOO_Logger::error( 'No se ha podido registrar la pestaña de ajustes: la clase CCWOO_Settings_Page no se ha definido.' );

			return $pages;
		}

		$pages[] = new CCWOO_Settings_Page();

		CCWOO_Logger::debug( 'Pestaña de ajustes registrada.' );

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
