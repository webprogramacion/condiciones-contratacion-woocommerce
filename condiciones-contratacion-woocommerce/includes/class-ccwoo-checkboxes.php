<?php
/**
 * Repositorio de las casillas de aceptación configuradas.
 *
 * @package CondicionesContratacionWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lectura, saneamiento y persistencia de las casillas de aceptación.
 */
class CCWOO_Checkboxes {

	/**
	 * Opción donde se guardan las casillas.
	 *
	 * @var string
	 */
	const OPTION_CHECKBOXES = 'ccwoo_checkboxes';

	/**
	 * Opción que desactiva la casilla genérica de WooCommerce.
	 *
	 * @var string
	 */
	const OPTION_DISABLE_WC_TERMS = 'ccwoo_disable_wc_terms';

	/**
	 * Prefijo de los campos del checkout.
	 *
	 * @var string
	 */
	const FIELD_PREFIX = 'ccwoo_accept_';

	/**
	 * HTML permitido en el texto de las casillas.
	 *
	 * @return array
	 */
	public static function allowed_html() {
		return array(
			'a'      => array(
				'href'   => array(),
				'target' => array(),
				'rel'    => array(),
			),
			'strong' => array(),
			'em'     => array(),
			'br'     => array(),
		);
	}

	/**
	 * Devuelve todas las casillas guardadas, normalizadas y ordenadas.
	 *
	 * @return array Lista de casillas con las claves id, text, required, enabled y order.
	 */
	public static function get_all() {
		$stored = get_option( self::OPTION_CHECKBOXES, array() );

		if ( ! is_array( $stored ) ) {
			return array();
		}

		$items = array();

		foreach ( $stored as $index => $row ) {
			$item = self::normalize( $row, $index );

			if ( '' !== $item['text'] ) {
				$items[] = $item;
			}
		}

		usort( $items, array( __CLASS__, 'compare_order' ) );

		return array_values( $items );
	}

	/**
	 * Devuelve las casillas que deben mostrarse en el checkout.
	 *
	 * @return array
	 */
	public static function get_active() {
		$active = array();

		foreach ( self::get_all() as $item ) {
			if ( $item['enabled'] ) {
				$active[] = $item;
			}
		}

		/**
		 * Permite modificar las casillas que se muestran en el checkout.
		 *
		 * @param array $active Casillas activas, ya saneadas y ordenadas.
		 */
		$active = apply_filters( 'ccwoo_active_checkboxes', $active );

		return is_array( $active ) ? $active : array();
	}

	/**
	 * Indica si hay al menos una casilla activa y obligatoria.
	 *
	 * @return bool
	 */
	public static function has_required() {
		foreach ( self::get_active() as $item ) {
			if ( $item['required'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Sanea y guarda la lista completa de casillas.
	 *
	 * @param array $rows Filas sin procesar procedentes del formulario de ajustes.
	 *
	 * @return array Casillas guardadas.
	 */
	public static function save( $rows ) {
		$items = array();

		if ( is_array( $rows ) ) {
			$index = 0;

			foreach ( $rows as $row ) {
				$item = self::normalize( $row, $index );

				if ( '' === $item['text'] ) {
					continue;
				}

				$items[] = $item;
				++$index;
			}
		}

		usort( $items, array( __CLASS__, 'compare_order' ) );

		$items = array_values( $items );

		foreach ( $items as $position => $item ) {
			$items[ $position ]['order'] = $position;
		}

		update_option( self::OPTION_CHECKBOXES, $items );

		return $items;
	}

	/**
	 * Normaliza y sanea una fila de configuración.
	 *
	 * @param mixed $row   Fila sin procesar.
	 * @param int   $index Posición usada como orden por defecto.
	 *
	 * @return array
	 */
	public static function normalize( $row, $index = 0 ) {
		$row = is_array( $row ) ? $row : array();

		return array(
			'id'       => self::sanitize_id( isset( $row['id'] ) ? $row['id'] : '' ),
			'text'     => self::sanitize_text( isset( $row['text'] ) ? $row['text'] : '' ),
			'required' => self::to_bool( isset( $row['required'] ) ? $row['required'] : false ),
			'enabled'  => isset( $row['enabled'] ) ? self::to_bool( $row['enabled'] ) : true,
			'order'    => isset( $row['order'] ) && is_numeric( $row['order'] ) ? (int) $row['order'] : (int) $index,
		);
	}

	/**
	 * Sanea el texto de una casilla dejando solo el HTML permitido.
	 *
	 * @param mixed $text Texto sin procesar.
	 *
	 * @return string
	 */
	public static function sanitize_text( $text ) {
		if ( ! is_string( $text ) ) {
			return '';
		}

		$text = wp_kses( $text, self::allowed_html() );

		return trim( $text );
	}

	/**
	 * Sanea un identificador de casilla, generando uno nuevo si no es válido.
	 *
	 * @param mixed $id Identificador sin procesar.
	 *
	 * @return string
	 */
	public static function sanitize_id( $id ) {
		if ( is_string( $id ) ) {
			$id = strtolower( sanitize_key( $id ) );

			if ( preg_match( '/^[a-z0-9\-]{6,64}$/', $id ) ) {
				return $id;
			}
		}

		return self::generate_id();
	}

	/**
	 * Genera un identificador único para una casilla.
	 *
	 * @return string
	 */
	public static function generate_id() {
		return wp_generate_uuid4();
	}

	/**
	 * Devuelve el nombre del campo de formulario de una casilla.
	 *
	 * @param string $id Identificador de la casilla.
	 *
	 * @return string
	 */
	public static function field_name( $id ) {
		return self::FIELD_PREFIX . str_replace( '-', '_', $id );
	}

	/**
	 * Indica si la casilla genérica de WooCommerce debe desactivarse.
	 *
	 * @return bool
	 */
	public static function is_wc_terms_disabled() {
		return 'yes' === get_option( self::OPTION_DISABLE_WC_TERMS, 'no' );
	}

	/**
	 * Convierte un valor de formulario u opción en booleano.
	 *
	 * @param mixed $value Valor sin procesar.
	 *
	 * @return bool
	 */
	protected static function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		return in_array( $value, array( 1, '1', 'yes', 'true', 'on' ), true );
	}

	/**
	 * Compara dos casillas por su orden.
	 *
	 * @param array $a Primera casilla.
	 * @param array $b Segunda casilla.
	 *
	 * @return int
	 */
	protected static function compare_order( $a, $b ) {
		if ( $a['order'] === $b['order'] ) {
			return 0;
		}

		return ( $a['order'] < $b['order'] ) ? -1 : 1;
	}
}
