<?php
/**
 * Desinstalación del plugin.
 *
 * Elimina las opciones de configuración. Los metadatos de aceptación guardados
 * en los pedidos se conservan intencionadamente, porque son la evidencia de las
 * condiciones que aceptó el cliente en cada compra.
 *
 * @package CondicionesContratacionWooCommerce
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ccwoo_checkboxes' );
delete_option( 'ccwoo_disable_wc_terms' );

if ( is_multisite() ) {
	delete_site_option( 'ccwoo_checkboxes' );
	delete_site_option( 'ccwoo_disable_wc_terms' );
}
