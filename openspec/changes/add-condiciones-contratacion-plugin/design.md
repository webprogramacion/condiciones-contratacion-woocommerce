# Design: Condiciones de contratación para WooCommerce

## Context

Repositorio vacío (solo commit inicial). Se construye desde cero un plugin destinado al repositorio oficial de WordPress.org, por lo que deben cumplirse las directrices del Plugin Handbook: WPCS, i18n completa, seguridad (nonces, sanitización, escapado, capability checks), `readme.txt` válido, sin librerías innecesarias. WooCommerce moderno tiene dos checkouts (clásico por shortcode y por bloques) y HPOS; un plugin nuevo debe soportar ambos y declararlo.

## Goals / Non-Goals

**Goals:**
- Plugin autocontenido, publicable en WordPress.org, con textdomain `condiciones-contratacion-woocommerce`.
- Pestaña de ajustes nativa de WooCommerce con gestión completa de casillas (CRUD + orden + obligatoria/opcional + texto).
- Soporte de checkout clásico y por bloques, validación en servidor, registro de aceptaciones compatible con HPOS.

**Non-Goals:**
- No se gestionan versiones/documentos legales de las condiciones (solo el texto de la casilla, que puede enlazar a páginas).
- No se aplican las casillas a otros flujos (registro de usuario, formularios de contacto, pedidos manuales del admin).
- No se crea tabla propia ni exportación de evidencias; los registros viven en el pedido.

## Decisions

1. **Estructura del plugin**: archivo principal `condiciones-contratacion-woocommerce.php` + clases en `includes/` con prefijo propio (`CCWOO_`), patrón singleton ligero en la clase principal y carga condicionada a que WooCommerce esté activo (aviso admin si no lo está). Se declara compatibilidad HPOS y con Cart/Checkout Blocks vía `FeaturesUtil::declare_compatibility()` en `before_woocommerce_init`. *Alternativa descartada*: estructura orientada a composer/autoloader PSR-4 — innecesaria para el tamaño del plugin y añade fricción en la revisión de WordPress.org.

2. **Pestaña de ajustes**: subclase de `WC_Settings_Page` registrada con el filtro `woocommerce_get_settings_pages` (id `condiciones_contratacion`). La opción global de desactivar la casilla genérica usa el Settings API de WooCommerce (checkbox estándar). *Alternativa descartada*: página propia en el menú de WordPress — el usuario pidió explícitamente la pestaña dentro de WooCommerce → Ajustes.

3. **Editor de casillas (repeater)**: el listado de casillas se renderiza como tabla propia dentro de la pestaña (hook `woocommerce_admin_field_*` personalizado), con filas añadibles/eliminables y reordenación por arrastre (jQuery UI Sortable, ya incluido en WordPress core) con fallback de campo numérico de orden. Guardado por POST estándar del formulario de ajustes de WooCommerce con nonce propio adicional. *Alternativa descartada*: pantalla React/REST — mayor coste y complica la revisión; el volumen de datos (pocas casillas) no lo justifica.

4. **Modelo de datos**: una única opción `ccwoo_checkboxes` (array serializado) con elementos `{ id (uuid), text, required (bool), enabled (bool), order (int) }`, más la opción `ccwoo_disable_wc_terms` (yes/no). Sanitización con `wp_kses` con lista blanca (`a[href|target|rel]`, `strong`, `em`, `br`). *Alternativa descartada*: CPT por casilla — sobredimensionado y complica el orden y la exportación de ajustes.

5. **Checkout clásico**: render en `woocommerce_review_order_before_submit`; validación servidor en `woocommerce_checkout_process` (añade `wc_add_notice( ..., 'error' )` por cada obligatoria sin marcar); guardado en `woocommerce_checkout_create_order` usando `$order->update_meta_data()` (compatible HPOS, evita `update_post_meta`).

6. **Checkout por bloques**: integración vía `IntegrationInterface` de WooCommerce Blocks con un script JS (sin build, `wp.element.createElement`) que pinta las casillas en el slot `ExperimentalOrderMeta`, marca los errores en el store `wc/store/validation` y envía el estado como datos de extensión (`useCheckoutExtensionData`, con respaldo sobre el store `wc/store/checkout`). En el servidor, los datos se declaran con `woocommerce_store_api_register_endpoint_data` y se validan y persisten en `woocommerce_store_api_checkout_update_order_from_request` (lanza `RouteException` si falta una obligatoria). La validación de servidor es la fuente de verdad en ambos checkouts. *Alternativa descartada*: `registerCheckoutBlock` sobre el bloque de términos — obligaría al comercio a insertar el bloque a mano en la plantilla del checkout. *Alternativa descartada*: la Additional Checkout Fields API — es la vía oficial y sin JS, pero escapa el HTML de las etiquetas y no permitiría enlazar las páginas legales desde el texto de la casilla. *Alternativa descartada*: soportar solo el checkout clásico — los sites nuevos usan bloques por defecto; sería un plugin incompleto para el repositorio.

7. **Desactivar la casilla genérica**: en checkout clásico, filtro `woocommerce_checkout_show_terms` → `false` (esto también suprime el campo `terms-field`, con lo que WooCommerce deja de validar su casilla); en bloques, filtro `render_block` que elimina la salida del bloque `woocommerce/checkout-terms-block`. No se toca la página de términos configurada en WooCommerce.

8. **Registro de aceptaciones**: meta del pedido `_ccwoo_acceptances` (array con id, texto snapshot, required, accepted, timestamp). Visualización con metabox en la pantalla de pedido registrado para ambas pantallas (legacy `shop_order` y HPOS `woocommerce_page_wc-orders`).

9. **i18n**: todo el texto con funciones de traducción y textdomain del slug, y `languages/*.pot` generado con `wp i18n make-pot`. Las cadenas fuente van **en español**, no en inglés: el requisito del usuario es que la pestaña se llame literalmente «Condiciones de contratación», y con fuente en inglés ese literal dependería de que el sitio cargara la traducción `es_ES`. El plugin apunta al mercado español (las condiciones de contratación son una figura de la LSSI-CE), por lo que no hace falta un `es_ES.po` adicional; queda pendiente, como mejora futura, aportar una traducción `en_US`. *Alternativa descartada*: fuente en inglés + `es_ES` incluido (práctica habitual en WordPress.org) — rompía el requisito literal del nombre de la pestaña.

10. **Prefijo del código**: `CCWOO_` / `ccwoo_` para clases, funciones, opciones, hooks y handles. WPCS rechaza prefijos de menos de cuatro caracteres (`WordPress.NamingConventions.PrefixAllGlobals.ShortPrefixPassed`) por riesgo de colisión, de modo que el `CCW_` inicial no era admisible para un plugin publicado.

## Risks / Trade-offs

- [El checkout por bloques cambia de API entre versiones de WooCommerce] → Fijar versión mínima de WooCommerce (8.x+) y probar contra la versión estable actual; encapsular la integración de bloques en una clase aislada para que un fallo no afecte al checkout clásico.
- [HTML en el texto de las casillas puede introducir XSS] → `wp_kses` con lista blanca estricta al guardar y al imprimir.
- [Temas o plugins que reordenan hooks del checkout clásico pueden desplazar las casillas] → usar el hook estándar `woocommerce_review_order_before_submit` y documentar un filtro para cambiar de hook/prioridad.
- [Desactivar la casilla genérica sin configurar casillas propias deja el checkout sin aceptación legal] → mostrar un aviso en los ajustes cuando se active la opción sin que exista al menos una casilla obligatoria.
- [Snapshot del texto en cada pedido aumenta el tamaño del meta] → aceptable: pocas casillas y texto corto; es requisito de evidencia legal.

## Migration Plan

Plugin nuevo: sin migraciones. En la activación se crean las opciones por defecto (sin casillas, opción genérica desactivada = "no"). La desinstalación (`uninstall.php`) elimina las opciones; los metas de pedidos se conservan como evidencia.

## Open Questions

- Ninguna bloqueante. (Si el usuario quisiera en el futuro aplicar casillas por rol/producto o exportar evidencias, sería un cambio nuevo.)
