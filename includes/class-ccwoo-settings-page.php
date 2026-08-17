<?php
/**
 * Pestaña de ajustes dentro de WooCommerce.
 *
 * @package CondicionesContratacionWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Settings_Page' ) ) {
	return;
}

/**
 * Pestaña «Condiciones de contratación» en WooCommerce → Ajustes.
 */
class CCWOO_Settings_Page extends WC_Settings_Page {

	/**
	 * Acción del nonce del repetidor de casillas.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'ccwoo_save_checkboxes';

	/**
	 * Nombre del campo del nonce.
	 *
	 * @var string
	 */
	const NONCE_NAME = 'ccwoo_checkboxes_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'condiciones_contratacion';
		$this->label = __( 'Condiciones de contratación', 'condiciones-contratacion-woocommerce' );

		parent::__construct();

		add_action( 'woocommerce_admin_field_ccwoo_checkboxes', array( $this, 'output_checkboxes_field' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Define los ajustes de la sección por defecto.
	 *
	 * @return array
	 */
	public function get_settings_for_default_section() {
		$settings = array(
			array(
				'title' => __( 'Casillas de aceptación', 'condiciones-contratacion-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Añade las casillas que el cliente verá en el checkout. Puedes reordenarlas arrastrándolas y decidir si cada una es obligatoria u opcional. En el texto se admiten enlaces y las etiquetas <code>&lt;strong&gt;</code>, <code>&lt;em&gt;</code> y <code>&lt;br&gt;</code>.', 'condiciones-contratacion-woocommerce' ),
				'id'    => 'ccwoo_checkboxes_options',
			),
			array(
				// Sin `id` a propósito: así WC_Admin_Settings::save_fields() no intenta
				// guardarlo como opción. El repetidor se guarda en save_checkboxes().
				'type'      => 'ccwoo_checkboxes',
				'is_option' => false,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'ccwoo_checkboxes_options',
			),
			array(
				'title' => __( 'Aceptación genérica de WooCommerce', 'condiciones-contratacion-woocommerce' ),
				'type'  => 'title',
				'id'    => 'ccwoo_general_options',
			),
			array(
				'title'    => __( 'Casilla de WooCommerce', 'condiciones-contratacion-woocommerce' ),
				'desc'     => __( 'Desactivar la aceptación genérica de condiciones de WooCommerce', 'condiciones-contratacion-woocommerce' ),
				'desc_tip' => __( 'Oculta la casilla de «términos y condiciones» que WooCommerce muestra de serie en el checkout, para que solo se usen las casillas configuradas en este plugin.', 'condiciones-contratacion-woocommerce' ),
				'id'       => CCWOO_Checkboxes::OPTION_DISABLE_WC_TERMS,
				'type'     => 'checkbox',
				'default'  => 'no',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'ccwoo_general_options',
			),
		);

		/**
		 * Permite modificar los ajustes de la pestaña del plugin.
		 *
		 * @param array $settings Definición de los ajustes.
		 */
		return apply_filters( 'ccwoo_settings', $settings );
	}

	/**
	 * Imprime el contenido de la pestaña.
	 *
	 * @return void
	 */
	public function output() {
		$this->output_configuration_warning();

		parent::output();
	}

	/**
	 * Avisa cuando se desactiva la casilla genérica sin tener casillas obligatorias propias.
	 *
	 * @return void
	 */
	protected function output_configuration_warning() {
		if ( ! CCWOO_Checkboxes::is_wc_terms_disabled() || CCWOO_Checkboxes::has_required() ) {
			return;
		}

		printf(
			'<div class="notice notice-warning inline"><p>%s</p></div>',
			esc_html__( 'Has desactivado la aceptación genérica de WooCommerce y no hay ninguna casilla obligatoria activa: el checkout no pedirá ninguna aceptación de condiciones.', 'condiciones-contratacion-woocommerce' )
		);
	}

	/**
	 * Imprime el repetidor de casillas de aceptación.
	 *
	 * @return void
	 */
	public function output_checkboxes_field() {
		$items = CCWOO_Checkboxes::get_all();

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php esc_html_e( 'Casillas', 'condiciones-contratacion-woocommerce' ); ?>
			</th>
			<td class="forminp forminp-ccwoo_checkboxes">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
				<table class="widefat ccwoo-checkboxes" id="ccwoo-checkboxes">
					<thead>
						<tr>
							<th class="ccwoo-col-handle"><span class="screen-reader-text"><?php esc_html_e( 'Orden', 'condiciones-contratacion-woocommerce' ); ?></span></th>
							<th class="ccwoo-col-order"><?php esc_html_e( 'Orden', 'condiciones-contratacion-woocommerce' ); ?></th>
							<th class="ccwoo-col-text"><?php esc_html_e( 'Texto de la casilla', 'condiciones-contratacion-woocommerce' ); ?></th>
							<th class="ccwoo-col-required"><?php esc_html_e( 'Aceptación', 'condiciones-contratacion-woocommerce' ); ?></th>
							<th class="ccwoo-col-enabled"><?php esc_html_e( 'Activa', 'condiciones-contratacion-woocommerce' ); ?></th>
							<th class="ccwoo-col-actions"><span class="screen-reader-text"><?php esc_html_e( 'Acciones', 'condiciones-contratacion-woocommerce' ); ?></span></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $index => $item ) : ?>
							<?php $this->output_row( $index, $item ); ?>
						<?php endforeach; ?>
						<tr class="ccwoo-no-rows <?php echo empty( $items ) ? '' : 'hidden'; ?>">
							<td colspan="6"><?php esc_html_e( 'Todavía no has añadido ninguna casilla.', 'condiciones-contratacion-woocommerce' ); ?></td>
						</tr>
					</tbody>
				</table>
				<p class="ccwoo-actions">
					<button type="button" class="button button-secondary" id="ccwoo-add-row"><?php esc_html_e( 'Añadir casilla', 'condiciones-contratacion-woocommerce' ); ?></button>
				</p>
				<script type="text/html" id="tmpl-ccwoo-checkbox-row">
					<?php
					// El identificador se deja vacío a propósito: se genera al guardar.
					$this->output_row(
						'__INDEX__',
						array(
							'id'       => '',
							'text'     => '',
							'required' => true,
							'enabled'  => true,
							'order'    => '',
						)
					);
					?>
				</script>
			</td>
		</tr>
		<?php
	}

	/**
	 * Imprime una fila del repetidor.
	 *
	 * @param int|string $index Índice de la fila en el formulario.
	 * @param array      $item  Datos de la casilla.
	 *
	 * @return void
	 */
	protected function output_row( $index, $item ) {
		$base = 'ccwoo_checkbox[' . $index . ']';

		?>
		<tr class="ccwoo-row">
			<td class="ccwoo-col-handle">
				<span class="ccwoo-sort-handle" aria-hidden="true"></span>
			</td>
			<td class="ccwoo-col-order">
				<input type="number" class="ccwoo-order small-text" name="<?php echo esc_attr( $base . '[order]' ); ?>" value="<?php echo esc_attr( $item['order'] ); ?>" step="1" min="0" />
				<input type="hidden" name="<?php echo esc_attr( $base . '[id]' ); ?>" value="<?php echo esc_attr( $item['id'] ); ?>" />
			</td>
			<td class="ccwoo-col-text">
				<textarea name="<?php echo esc_attr( $base . '[text]' ); ?>" rows="3" class="ccwoo-text widefat" placeholder="<?php esc_attr_e( 'Acepto las <a href="/condiciones">condiciones de contratación</a>', 'condiciones-contratacion-woocommerce' ); ?>"><?php echo esc_textarea( $item['text'] ); ?></textarea>
			</td>
			<td class="ccwoo-col-required">
				<select name="<?php echo esc_attr( $base . '[required]' ); ?>" class="ccwoo-required">
					<option value="1" <?php selected( $item['required'], true ); ?>><?php esc_html_e( 'Obligatoria', 'condiciones-contratacion-woocommerce' ); ?></option>
					<option value="0" <?php selected( $item['required'], false ); ?>><?php esc_html_e( 'Opcional', 'condiciones-contratacion-woocommerce' ); ?></option>
				</select>
			</td>
			<td class="ccwoo-col-enabled">
				<input type="hidden" name="<?php echo esc_attr( $base . '[enabled]' ); ?>" value="0" />
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $base . '[enabled]' ); ?>" value="1" <?php checked( $item['enabled'], true ); ?> />
					<span class="screen-reader-text"><?php esc_html_e( 'Mostrar esta casilla en el checkout', 'condiciones-contratacion-woocommerce' ); ?></span>
				</label>
			</td>
			<td class="ccwoo-col-actions">
				<button type="button" class="button-link ccwoo-remove-row">
					<?php esc_html_e( 'Eliminar', 'condiciones-contratacion-woocommerce' ); ?>
				</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Guarda los ajustes de la pestaña.
	 *
	 * @return void
	 */
	public function save() {
		$this->save_checkboxes();

		parent::save();
	}

	/**
	 * Guarda el repetidor de casillas.
	 *
	 * @return void
	 */
	protected function save_checkboxes() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			WC_Admin_Settings::add_error( __( 'No se han podido guardar las casillas de aceptación: la comprobación de seguridad ha fallado. Recarga la página e inténtalo de nuevo.', 'condiciones-contratacion-woocommerce' ) );

			return;
		}

		// Los valores se sanean fila a fila en CCWOO_Checkboxes::save().
		$rows = isset( $_POST['ccwoo_checkbox'] ) ? wp_unslash( $_POST['ccwoo_checkbox'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		CCWOO_Checkboxes::save( is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Encola los recursos de administración solo en esta pestaña.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! $this->is_current_tab() ) {
			return;
		}

		wp_enqueue_style(
			'ccwoo-admin',
			CCWOO_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			CCWOO_VERSION
		);

		wp_enqueue_script(
			'ccwoo-admin',
			CCWOO_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			CCWOO_VERSION,
			true
		);

		wp_localize_script(
			'ccwoo-admin',
			'ccwooAdmin',
			array(
				'confirmRemove' => __( '¿Seguro que quieres eliminar esta casilla?', 'condiciones-contratacion-woocommerce' ),
			)
		);
	}

	/**
	 * Comprueba si se está mostrando la pestaña del plugin.
	 *
	 * @return bool
	 */
	protected function is_current_tab() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo se lee la pestaña activa para encolar recursos.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		return $this->id === $tab;
	}
}
