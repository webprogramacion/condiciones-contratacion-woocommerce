=== Condiciones de contratación para WooCommerce ===
Contributors: damasovelazquez
Tags: woocommerce, checkout, condiciones, terminos, rgpd
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 8.2
WC tested up to: 9.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Añade al checkout de WooCommerce tantas casillas de aceptación como necesites, con texto propio, orden configurable y carácter obligatorio u opcional.

== Description ==

WooCommerce solo trae de serie una casilla genérica de «términos y condiciones». Muchas tiendas necesitan recoger varias aceptaciones diferenciadas: condiciones de contratación, política de privacidad, política de devoluciones, consentimiento para comunicaciones comerciales…

Este plugin añade una pestaña propia en **WooCommerce → Ajustes → Condiciones de contratación** desde la que puedes:

* Crear tantas casillas de aceptación como necesites.
* Escribir el texto de cada una, con enlaces a tus páginas legales.
* Marcar cada casilla como obligatoria u opcional.
* Ordenarlas arrastrándolas para decidir en qué orden las ve el cliente.
* Activar o desactivar casillas sin perder su configuración.
* Desactivar la casilla genérica de términos y condiciones de WooCommerce, si prefieres usar solo las tuyas.

Las casillas obligatorias se validan **en el servidor**, así que un pedido no puede completarse sin ellas aunque se manipule el navegador.

= Evidencia en cada pedido =

Al completarse un pedido, el plugin guarda en él qué condiciones se mostraron, cuáles aceptó el cliente y en qué fecha y hora, junto con el texto exacto que estaba vigente en ese momento. Si más adelante cambias el texto de una casilla, los pedidos antiguos conservan lo que el cliente aceptó realmente. Puedes consultarlo en la ficha del pedido, en el panel «Condiciones aceptadas».

= Compatibilidad =

* Checkout clásico (shortcode) y checkout por bloques.
* HPOS (almacenamiento de pedidos de alto rendimiento).
* No crea tablas propias: la configuración vive en dos opciones y las aceptaciones en los metadatos del pedido.

En el checkout por bloques las casillas se muestran en la zona del resumen del pedido, sin que tengas que editar la plantilla del checkout.

== Installation ==

1. Sube la carpeta del plugin a `/wp-content/plugins/` o instálalo desde Plugins → Añadir nuevo.
2. Activa el plugin desde el menú «Plugins» de WordPress (necesitas WooCommerce activo).
3. Ve a **WooCommerce → Ajustes → Condiciones de contratación** y añade tus casillas.

== Frequently Asked Questions ==

= ¿Funciona con el checkout por bloques? =

Sí. Las casillas se muestran también en el checkout por bloques y la validación se hace en el servidor a través del Store API, igual que en el checkout clásico.

= ¿Puedo poner enlaces en el texto de una casilla? =

Sí. Se admiten enlaces y las etiquetas `<strong>`, `<em>` y `<br>`. Cualquier otro HTML se elimina al guardar por seguridad.

= ¿Puedo quitar la casilla de «términos y condiciones» de WooCommerce? =

Sí, hay una opción en los ajustes del plugin para desactivarla. Ten en cuenta que si la desactivas y no configuras ninguna casilla obligatoria, el checkout no pedirá ninguna aceptación: el plugin te avisa en los ajustes si te ocurre eso.

= ¿Qué pasa con los pedidos anteriores a la instalación? =

No tienen aceptaciones registradas y su ficha lo indica sin errores. El plugin solo registra las aceptaciones de los pedidos realizados desde su activación.

= Algo no funciona, ¿dónde miro? =

En **WooCommerce → Ajustes → Condiciones de contratación** hay una sección «Diagnóstico» con las versiones del entorno y el recuento de casillas activas. El plugin además escribe en **WooCommerce → Estado → Registros**, en el archivo con origen `condiciones-contratacion`. Para obtener información detallada, activa `WP_DEBUG` o añade el filtro `add_filter( 'ccwoo_enable_logging', '__return_true' );`.

= ¿Se borran los datos al desinstalar? =

Al desinstalar se eliminan las opciones de configuración. Las aceptaciones guardadas en los pedidos se conservan intencionadamente, porque son la evidencia de lo que el cliente aceptó en cada compra.

== Screenshots ==

1. Pestaña de ajustes con la lista de casillas, su orden y su carácter obligatorio u opcional.
2. Casillas de aceptación en el checkout, antes del botón de realizar el pedido.
3. Panel «Condiciones aceptadas» en la ficha del pedido.

== Changelog ==

= 1.1.0 =
* Corregida la pantalla en blanco (error fatal `Class "CCWOO_Settings_Page" not found`) al abrir los ajustes de WooCommerce.
* Añadido el registro de eventos en WooCommerce → Estado → Registros, con origen `condiciones-contratacion`.
* Añadida la sección «Diagnóstico» en los ajustes con el estado del entorno y el recuento de casillas.
* Añadidos avisos por consola en el checkout por bloques cuando falta alguna API de WooCommerce Blocks o no hay casillas que pintar.
* Añadida la guía de depuración en `docs/depuracion.md`.
* Un fallo al inicializar los componentes ya no deja el sitio en blanco: se captura, se registra y se avisa en el administrador.

= 1.0.0 =
* Versión inicial.
* Pestaña de ajustes «Condiciones de contratación» dentro de WooCommerce.
* Casillas de aceptación ilimitadas con texto propio, orden configurable y marca de obligatoria u opcional.
* Opción para desactivar la aceptación genérica de condiciones de WooCommerce.
* Soporte del checkout clásico y del checkout por bloques, con validación en el servidor.
* Registro de las aceptaciones en el pedido y panel de consulta en el administrador, compatible con HPOS.

== Upgrade Notice ==

= 1.1.0 =
Corrige la pantalla en blanco al abrir los ajustes de WooCommerce y añade registro de eventos y diagnóstico. Actualización recomendada.

= 1.0.0 =
Primera versión del plugin.
