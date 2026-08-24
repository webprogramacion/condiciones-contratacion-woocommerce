/**
 * Repetidor de casillas de aceptación en los ajustes de WooCommerce.
 *
 * @package CondicionesContratacionWooCommerce
 */

/* global jQuery, ccwooAdmin */
( function ( $ ) {
	'use strict';

	var $table = $( '#ccwoo-checkboxes' );

	if ( ! $table.length ) {
		return;
	}

	var $body = $table.find( 'tbody' );
	var template = $( '#tmpl-ccwoo-checkbox-row' ).html() || '';
	var nextIndex = $body.find( '.ccwoo-row' ).length;

	/**
	 * Renumera el campo de orden para que coincida con la posición de las filas.
	 */
	function refreshOrder() {
		$body.find( '.ccwoo-row' ).each( function ( position ) {
			$( this ).find( '.ccwoo-order' ).val( position );
		} );

		$body.find( '.ccwoo-no-rows' ).toggleClass( 'hidden', $body.find( '.ccwoo-row' ).length > 0 );
	}

	$( '#ccwoo-add-row' ).on( 'click', function ( event ) {
		event.preventDefault();

		if ( ! template ) {
			return;
		}

		var markup = template.replace( /__INDEX__/g, String( nextIndex ) );

		nextIndex++;

		$body.find( '.ccwoo-no-rows' ).before( markup );
		refreshOrder();
		$body.find( '.ccwoo-row' ).last().find( '.ccwoo-text' ).trigger( 'focus' );
	} );

	$body.on( 'click', '.ccwoo-remove-row', function ( event ) {
		event.preventDefault();

		var message = 'undefined' !== typeof ccwooAdmin ? ccwooAdmin.confirmRemove : '';

		if ( message && ! window.confirm( message ) ) {
			return;
		}

		$( this ).closest( '.ccwoo-row' ).remove();
		refreshOrder();
	} );

	if ( $.fn.sortable ) {
		$body.sortable( {
			items: '.ccwoo-row',
			handle: '.ccwoo-sort-handle',
			axis: 'y',
			cursor: 'move',
			helper: function ( event, element ) {
				// Fija el ancho de las celdas para que la fila arrastrada no se descuadre.
				element.children().each( function () {
					$( this ).width( $( this ).width() );
				} );

				return element;
			},
			update: refreshOrder
		} );
	}
} )( jQuery );
