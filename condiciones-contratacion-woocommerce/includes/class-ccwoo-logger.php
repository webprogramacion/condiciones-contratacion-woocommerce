<?php
/**
 * Registro de eventos para depuración.
 *
 * @package CondicionesContratacionWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Escribe en el registro de WooCommerce (WooCommerce → Estado → Registros).
 *
 * Los mensajes informativos solo se escriben cuando la depuración está activa
 * (`WP_DEBUG` o el filtro `ccwoo_enable_logging`). Los errores se registran
 * siempre, porque son justamente lo que hay que ver cuando algo falla.
 */
class CCWOO_Logger {

	/**
	 * Identificador del registro dentro de WooCommerce.
	 *
	 * @var string
	 */
	const SOURCE = 'condiciones-contratacion';

	/**
	 * Indica si el registro informativo está activo.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$enabled = defined( 'WP_DEBUG' ) && WP_DEBUG;

		/**
		 * Permite activar el registro del plugin sin activar WP_DEBUG.
		 *
		 * @param bool $enabled Si el registro informativo está activo.
		 */
		return (bool) apply_filters( 'ccwoo_enable_logging', $enabled );
	}

	/**
	 * Registra un mensaje informativo.
	 *
	 * @param string $message Mensaje.
	 * @param array  $context Datos adicionales.
	 *
	 * @return void
	 */
	public static function debug( $message, $context = array() ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		self::write( 'debug', $message, $context );
	}

	/**
	 * Registra una advertencia. Se escribe siempre.
	 *
	 * @param string $message Mensaje.
	 * @param array  $context Datos adicionales.
	 *
	 * @return void
	 */
	public static function warning( $message, $context = array() ) {
		self::write( 'warning', $message, $context );
	}

	/**
	 * Registra un error. Se escribe siempre.
	 *
	 * @param string $message Mensaje.
	 * @param array  $context Datos adicionales.
	 *
	 * @return void
	 */
	public static function error( $message, $context = array() ) {
		self::write( 'error', $message, $context );
	}

	/**
	 * Registra una excepción o error de PHP capturado.
	 *
	 * @param Throwable $exception Excepción capturada.
	 * @param string    $where     Punto del código donde se capturó.
	 *
	 * @return void
	 */
	public static function exception( $exception, $where = '' ) {
		self::write(
			'error',
			sprintf(
				'%s%s (%s:%d)',
				$where ? $where . ': ' : '',
				$exception->getMessage(),
				$exception->getFile(),
				$exception->getLine()
			),
			array( 'trace' => $exception->getTraceAsString() )
		);
	}

	/**
	 * Escribe la línea en el registro de WooCommerce o, si no está disponible,
	 * en el registro de errores de PHP.
	 *
	 * @param string $level   Nivel del mensaje.
	 * @param string $message Mensaje.
	 * @param array  $context Datos adicionales.
	 *
	 * @return void
	 */
	protected static function write( $level, $message, $context = array() ) {
		if ( ! empty( $context ) ) {
			$message .= ' ' . wp_json_encode( $context );
		}

		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();

			if ( $logger ) {
				$logger->log( $level, $message, array( 'source' => self::SOURCE ) );

				return;
			}
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Respaldo cuando WooCommerce no está disponible.
		error_log( sprintf( '[%s] [%s] %s', self::SOURCE, $level, $message ) );
	}
}
