# Propuesta: Plugin "Condiciones de contratación para WooCommerce"

## Why

Las tiendas WooCommerce solo ofrecen de serie una única casilla genérica de "términos y condiciones" en el checkout. Muchos comercios necesitan recoger varias aceptaciones diferenciadas (condiciones de contratación, política de privacidad, comunicaciones comerciales, etc.), cada una con su propio texto, su carácter obligatorio u opcional y un orden concreto — algo que WooCommerce no permite configurar. Este plugin cubre esa necesidad y se prepara para su publicación en el repositorio oficial de WordPress.org.

## What Changes

- Se crea un plugin nuevo e independiente llamado **Condiciones de contratación para WooCommerce** (slug: `condiciones-contratacion-woocommerce`), siguiendo las directrices de desarrollo de plugins de WordPress.org (Plugin Handbook, WPCS, i18n, seguridad).
- Nueva pestaña de ajustes **"Condiciones de contratación"** dentro de WooCommerce → Ajustes, con:
  - Gestión de casillas de aceptación: añadir, editar, eliminar y **reordenar**.
  - Por cada casilla: texto personalizable (con HTML básico/enlaces) y marca de **obligatoria u opcional**.
  - Opción global para **desactivar la casilla genérica de términos y condiciones de WooCommerce**.
- Las casillas configuradas se muestran en el checkout (clásico con shortcode y checkout por bloques) en el orden definido.
- Validación en el envío del pedido: si una casilla obligatoria no está marcada, el pedido no se procesa y se muestra un aviso.
- Registro de las aceptaciones en los metadatos del pedido como evidencia (qué casillas se aceptaron y con qué texto), visible en la ficha del pedido en el administrador.

## Capabilities

### New Capabilities

- `terms-settings`: Pestaña de ajustes en WooCommerce para gestionar las casillas de aceptación (CRUD, orden, obligatoria/opcional, texto personalizado) y la opción de desactivar la aceptación genérica de WooCommerce.
- `checkout-terms`: Presentación de las casillas configuradas en el checkout (clásico y por bloques) y validación de las obligatorias antes de procesar el pedido.
- `acceptance-records`: Guardado de las aceptaciones del cliente en el pedido y su visualización en la administración del pedido.

### Modified Capabilities

_Ninguna (no existen specs previas; es un plugin nuevo)._

## Impact

- **Código nuevo**: todo el plugin (estructura, clases, ajustes, front de checkout, i18n, readme.txt para WordPress.org).
- **Dependencias**: requiere WooCommerce activo (comprobación en el arranque con aviso si falta). Declarar compatibilidad con HPOS (High-Performance Order Storage) y con el checkout por bloques.
- **Integraciones con WooCommerce**: filtro `woocommerce_get_settings_pages` (pestaña de ajustes), hooks del checkout clásico (`woocommerce_review_order_before_submit`, `woocommerce_checkout_process`, `woocommerce_checkout_update_order_meta`), Store API / Checkout Blocks para el checkout por bloques, y filtro sobre la casilla genérica de términos.
- **Datos**: una opción en `wp_options` con la configuración de las casillas; metadatos por pedido con las aceptaciones. Sin tablas propias.
- **Sistemas externos**: ninguno.
