/**
 * Casillas de aceptación en el checkout por bloques.
 *
 * Se pinta mediante el slot `ExperimentalOrderMeta`, de modo que aparece en el
 * checkout por bloques sin que el comercio tenga que editar la plantilla. La
 * validación real la hace el servidor en el Store API: aquí solo se bloquea el
 * envío para dar una respuesta inmediata al cliente.
 *
 * @package CondicionesContratacionWooCommerce
 */

( function () {
	'use strict';

	var wcNamespace = window.wc || {};
	var wpNamespace = window.wp || {};
	var blocksCheckout = wcNamespace.blocksCheckout;
	var element = wpNamespace.element;
	var plugins = wpNamespace.plugins;
	var data = wpNamespace.data;

	if ( ! blocksCheckout || ! blocksCheckout.ExperimentalOrderMeta || ! element || ! plugins || ! data ) {
		return;
	}

	var settings = {};

	if ( wcNamespace.wcSettings && 'function' === typeof wcNamespace.wcSettings.getSetting ) {
		settings = wcNamespace.wcSettings.getSetting( 'ccwoo-terms_data', {} ) || {};
	}

	var checkboxes = settings.checkboxes || [];

	if ( ! checkboxes.length ) {
		return;
	}

	var namespace = settings.namespace || 'ccwoo-terms';
	var strings = settings.i18n || {};
	var el = element.createElement;

	/**
	 * Clave con la que se registra el error de validación de una casilla.
	 *
	 * @param {string} id Identificador de la casilla.
	 * @return {string} Clave de validación.
	 */
	function validationKey( id ) {
		return 'ccwoo-terms-' + id;
	}

	/**
	 * Componente con las casillas de aceptación.
	 *
	 * @return {Object} Elemento de React.
	 */
	function TermsCheckboxes() {
		var state = element.useState( {} );
		var accepted = state[ 0 ];
		var setAccepted = state[ 1 ];

		var extensionApi = 'function' === typeof blocksCheckout.useCheckoutExtensionData
			? blocksCheckout.useCheckoutExtensionData()
			: null;

		var setterRef = element.useRef( null );
		setterRef.current = extensionApi && extensionApi.setExtensionData ? extensionApi.setExtensionData : null;

		element.useEffect(
			function () {
				var validation = data.dispatch( 'wc/store/validation' );

				checkboxes.forEach( function ( item ) {
					var value = !! accepted[ item.id ];

					if ( setterRef.current ) {
						setterRef.current( namespace, item.id, value );
					} else {
						var checkoutStore = data.dispatch( 'wc/store/checkout' );

						if ( checkoutStore && 'function' === typeof checkoutStore.__internalSetExtensionData ) {
							var payload = {};
							payload[ item.id ] = value;
							checkoutStore.__internalSetExtensionData( namespace, payload );
						}
					}

					if ( ! validation ) {
						return;
					}

					if ( item.required && ! value ) {
						var errors = {};
						errors[ validationKey( item.id ) ] = {
							message: item.message,
							hidden: true
						};
						validation.setValidationErrors( errors );
					} else if ( 'function' === typeof validation.clearValidationError ) {
						validation.clearValidationError( validationKey( item.id ) );
					}
				} );
			},
			[ accepted ]
		);

		element.useEffect(
			function () {
				return function () {
					var validation = data.dispatch( 'wc/store/validation' );

					if ( ! validation || 'function' !== typeof validation.clearValidationError ) {
						return;
					}

					checkboxes.forEach( function ( item ) {
						validation.clearValidationError( validationKey( item.id ) );
					} );
				};
			},
			[]
		);

		return el(
			'div',
			{ className: 'ccwoo-block-terms' },
			checkboxes.map( function ( item ) {
				var inputId = 'ccwoo-block-accept-' + item.id;

				return el(
					'div',
					{ className: 'ccwoo-block-terms__item', key: item.id },
					el(
						'label',
						{ className: 'ccwoo-block-terms__label', htmlFor: inputId },
						el( 'input', {
							type: 'checkbox',
							id: inputId,
							className: 'ccwoo-block-terms__input',
							checked: !! accepted[ item.id ],
							'aria-required': item.required ? 'true' : undefined,
							onChange: function ( event ) {
								var isChecked = event.target.checked;

								setAccepted( function ( previous ) {
									var next = Object.assign( {}, previous );
									next[ item.id ] = isChecked;

									return next;
								} );
							}
						} ),
						el( 'span', {
							className: 'ccwoo-block-terms__text',
							// El texto ya viene saneado con wp_kses desde el servidor.
							dangerouslySetInnerHTML: { __html: item.text }
						} ),
						item.required
							? el( 'span', { className: 'ccwoo-block-terms__required' }, strings.requiredMark || '*' )
							: null
					)
				);
			} )
		);
	}

	plugins.registerPlugin( 'ccwoo-terms', {
		render: function () {
			return el( blocksCheckout.ExperimentalOrderMeta, null, el( TermsCheckboxes, null ) );
		},
		scope: 'woocommerce-checkout'
	} );
} )();
