# Tasks: Condiciones de contratación para WooCommerce

## 1. Estructura base del plugin

- [x] 1.1 Crear el archivo principal `condiciones-contratacion-woocommerce.php` con cabecera completa (nombre, descripción, versión, autor, licencia GPLv2+, textdomain, `Requires at least`, `Requires PHP`, `WC requires at least`), constantes del plugin y guard `ABSPATH`
- [x] 1.2 Implementar la comprobación de WooCommerce activo con aviso en el admin si falta, y la carga del plugin en `plugins_loaded`
- [x] 1.3 Declarar compatibilidad con HPOS y Cart/Checkout Blocks vía `FeaturesUtil::declare_compatibility()` en `before_woocommerce_init`
- [x] 1.4 Crear la clase principal `CCWOO_Plugin` (singleton) en `includes/class-ccwoo-plugin.php` que instancia el resto de componentes, y cargar el textdomain
- [x] 1.5 Crear `uninstall.php` que elimine las opciones del plugin (conservando los metas de pedidos)

## 2. Modelo de datos y ajustes

- [x] 2.1 Implementar `CCWOO_Checkboxes` (acceso a la opción `ccwoo_checkboxes`): lectura ordenada, sanitización con `wp_kses` (lista blanca: `a[href|target|rel]`, `strong`, `em`, `br`), generación de ids y valores por defecto
- [x] 2.2 Implementar la pestaña de ajustes `CCWOO_Settings_Page` (subclase de `WC_Settings_Page`, id `condiciones_contratacion`, título "Condiciones de contratación") registrada con `woocommerce_get_settings_pages`
- [x] 2.3 Añadir el campo checkbox estándar para la opción `ccwoo_disable_wc_terms` ("Desactivar la aceptación genérica de condiciones de WooCommerce")
- [x] 2.4 Implementar el campo repetidor de casillas (hook `woocommerce_admin_field_ccwoo_checkboxes`): tabla con texto, obligatoria/opcional, añadir/eliminar filas y orden
- [x] 2.5 Implementar el guardado del repetidor (`woocommerce_settings_save_*`): verificación de nonce y `manage_woocommerce`, sanitización por fila y persistencia del orden
- [x] 2.6 Añadir JS/CSS de admin (jQuery UI Sortable para reordenar, plantilla de fila nueva) encolado solo en la pestaña del plugin
- [x] 2.7 Mostrar aviso en los ajustes cuando `ccwoo_disable_wc_terms` esté activo sin ninguna casilla obligatoria configurada

## 3. Checkout clásico

- [x] 3.1 Implementar `CCWOO_Checkout_Classic`: render de las casillas en `woocommerce_review_order_before_submit` en el orden configurado, con escapado (`wp_kses`) e indicación visual de requeridas
- [x] 3.2 Implementar la validación de servidor en `woocommerce_checkout_process` con `wc_add_notice` de error identificando cada casilla obligatoria sin marcar
- [x] 3.3 Ocultar la casilla genérica con el filtro `woocommerce_checkout_show_terms` cuando `ccwoo_disable_wc_terms` esté activo
- [x] 3.4 Añadir CSS mínimo del front para el listado de casillas, encolado solo en el checkout

## 4. Checkout por bloques

- [x] 4.1 Crear la integración `CCWOO_Blocks_Integration` (`IntegrationInterface`) que registre y encole el script del bloque con sus datos (casillas, textos, orden)
- [x] 4.2 Implementar el script JS que pinta las casillas en el checkout por bloques (área de términos) respetando orden y obligatoriedad, con build sin dependencias externas pesadas
- [x] 4.3 Implementar validación y persistencia de servidor para el Store API (`woocommerce_store_api_checkout_update_order_from_request`), rechazando el pedido si falta una obligatoria
- [x] 4.4 Suprimir el aviso/casilla genérica de términos del bloque cuando `ccwoo_disable_wc_terms` esté activo

## 5. Registro de aceptaciones

- [x] 5.1 Guardar en `woocommerce_checkout_create_order` (clásico) y en el flujo del Store API (bloques) el meta `_ccwoo_acceptances` con id, texto snapshot, required, accepted y timestamp usando `$order->update_meta_data()`
- [x] 5.2 Implementar el metabox de administración del pedido (registrado para `shop_order` y `woocommerce_page_wc-orders`) que muestre cada condición, su texto, carácter, estado y fecha; sin errores en pedidos sin registros

## 6. i18n, calidad y publicación

- [x] 6.1 Revisar que todas las cadenas usan funciones de traducción con el textdomain `condiciones-contratacion-woocommerce`; generar `languages/` con el `.pot` (44 cadenas, `wp i18n make-pot`). No se incluye `es_ES`: las cadenas fuente ya están en español, según la decisión 9 del diseño
- [x] 6.2 Crear `readme.txt` conforme al formato de WordPress.org (descripción, instalación, FAQ, changelog, `Stable tag`, `Tested up to`)
- [x] 6.3 Pasar `phpcs --standard=WordPress` sobre todo el plugin y corregir las violaciones (0 errores, 0 avisos; obligó a renombrar el prefijo a `CCWOO_`)
- [ ] 6.4 Prueba manual completa: CRUD y orden de casillas, validación en ambos checkouts, desactivación de la genérica, metabox del pedido con HPOS activado

  > Pendiente: requiere un WordPress con WooCommerce y base de datos, que no existe en este entorno. Verificado aquí en su lugar: `php -l` de los 9 archivos, PHPCS con el estándar WordPress sin hallazgos, sintaxis de ambos JS con `node --check`, 18 comprobaciones automáticas del modelo de datos (saneamiento, orden, ids, filtrado, opción corrupta) y simulación del empaquetado del zip (16 archivos).
