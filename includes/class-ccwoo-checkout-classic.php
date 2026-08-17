<?php
/**
 * Integración con el checkout clásico (shortcode).
 *
 * @package CondicionesContratacionWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Muestra, valida y guarda las casillas en el checkout clásico.
 */
class CCWOO_Checkout_Classic {

	/**
	 * Registra los hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_review_order_before_submit', array( $this, 'render' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate' ) );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'record_acceptances' ), 10, 1 );
		add_filter( 'woocommerce_checkout_show_terms', array( $this, 'maybe_hide_wc_terms' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Imprime las casillas justo antes del botón de realizar el pedido.
	 *
	 * @return void
	 */
	public function render() {
		$items = CCWOO_Checkboxes::get_active();

		if ( empty( $items ) ) {
			CCWOO_Logger::debug( 'Checkout clásico: no hay casillas activas que mostrar.' );

			return;
		}

		CCWOO_Logger::debug( sprintf( 'Checkout clásico: se muestran %d casillas.', count( $items ) ) );

		echo '<div class="ccwoo-terms">';

		foreach ( $items as $item ) {
			$name     = CCWOO_Checkboxes::field_name( $item['id'] );
			$field_id = 'ccwoo-accept-' . sanitize_html_class( $item['id'] );
			$classes  = 'form-row ccwoo-terms__item' . ( $item['required'] ? ' validate-required' : '' );

			?>
			<p class="<?php echo esc_attr( $classes ); ?>">
				<label for="<?php echo esc_attr( $field_id ); ?>" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox ccwoo-terms__label">
					<input
						type="checkbox"
						class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox ccwoo-terms__checkbox"
						name="<?php echo esc_attr( $name ); ?>"
						id="<?php echo esc_attr( $field_id ); ?>"
						value="1"
						<?php checked( $this->is_submitted( $name ) ); ?>
						<?php
						if ( $item['required'] ) {
							echo ' required aria-required="true"';
						}
						?>
					/>
					<span class="ccwoo-terms__text"><?php echo wp_kses( $item['text'], CCWOO_Checkboxes::allowed_html() ); ?></span>
					<?php if ( $item['required'] ) : ?>
						<abbr class="required" title="<?php esc_attr_e( 'Obligatorio', 'condiciones-contratacion-woocommerce' ); ?>">*</abbr>
					<?php endif; ?>
				</label>
			</p>
			<?php
		}

		echo '</div>';
	}

	/**
	 * Valida en el servidor que se hayan aceptado todas las casillas obligatorias.
	 *
	 * @return void
	 */
	public function validate() {
		foreach ( CCWOO_Checkboxes::get_active() as $item ) {
			if ( ! $item['required'] ) {
				continue;
			}

			if ( ! $this->is_submitted( CCWOO_Checkboxes::field_name( $item['id'] ) ) ) {
				CCWOO_Logger::debug(
					sprintf( 'Checkout clásico: casilla obligatoria sin aceptar (%s).', $item['id'] )
				);

				wc_add_notice(
					sprintf(
						/* translators: %s: texto de la condición que el cliente debe aceptar. */
						esc_html__( 'Debes aceptar: %s', 'condiciones-contratacion-woocommerce' ),
						esc_html( wp_strip_all_tags( $item['text'] ) )
					),
					'error'
				);
			}
		}
	}

	/**
	 * Guarda las aceptaciones en el pedido.
	 *
	 * @param WC_Order $order Pedido en creación.
	 *
	 * @return void
	 */
	public function record_acceptances( $order ) {
		$accepted = array();

		foreach ( CCWOO_Checkboxes::get_active() as $item ) {
			if ( $this->is_submitted( CCWOO_Checkboxes::field_name( $item['id'] ) ) ) {
				$accepted[] = $item['id'];
			}
		}

		CCWOO_Logger::debug(
			sprintf( 'Checkout clásico: se registran %d aceptaciones en el pedido.', count( $accepted ) )
		);

		CCWOO_Order_Acceptances::record( $order, $accepted );
	}

	/**
	 * Oculta la casilla genérica de WooCommerce si así se ha configurado.
	 *
	 * @param bool $show Si WooCommerce debe mostrar su bloque de términos.
	 *
	 * @return bool
	 */
	public function maybe_hide_wc_terms( $show ) {
		if ( CCWOO_Checkboxes::is_wc_terms_disabled() ) {
			return false;
		}

		return $show;
	}

	/**
	 * Encola los estilos del checkout.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		if ( empty( CCWOO_Checkboxes::get_active() ) ) {
			return;
		}

		wp_enqueue_style(
			'ccwoo-checkout',
			CCWOO_PLUGIN_URL . 'assets/css/checkout.css',
			array(),
			CCWOO_VERSION
		);
	}

	/**
	 * Comprueba si un campo del checkout se ha enviado marcado.
	 *
	 * WooCommerce valida su propio nonce del checkout antes de disparar los hooks
	 * en los que se usa este método.
	 *
	 * @param string $name Nombre del campo.
	 *
	 * @return bool
	 */
	protected function is_submitted( $name ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- El nonce del checkout lo verifica WooCommerce.
		return ! empty( $_POST[ $name ] );
	}
}
