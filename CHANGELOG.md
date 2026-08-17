# Changelog

Todos los cambios notables de este plugin se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y el versionado sigue [Semantic Versioning](https://semver.org/lang/es/).

## [Unreleased]

### Fixed

- Pantalla en blanco (error fatal `Class "CCWOO_Settings_Page" not found`) al abrir los ajustes de WooCommerce. La clase de la pestaña extiende `WC_Settings_Page`, que WooCommerce no resuelve con su autoloader, de modo que al cargarla en `plugins_loaded` nunca llegaba a definirse. Ahora se carga dentro de `register_settings_page()`, con doble comprobación para que no pueda provocar un fatal.

### Added

- Registro de eventos en el sistema de registros de WooCommerce (WooCommerce → Estado → Registros, origen `condiciones-contratacion`): errores y advertencias siempre, e información detallada con `WP_DEBUG` o el filtro `ccwoo_enable_logging`.
- Sección «Diagnóstico» en los ajustes con las versiones del entorno, si HPOS está activo, si la página de pago usa el checkout por bloques y el recuento de casillas configuradas, activas y obligatorias.
- Avisos por consola en el checkout por bloques cuando falta alguna API de WooCommerce Blocks o no hay casillas que pintar, para que deje de fallar en silencio.
- Guía de depuración en `docs/depuracion.md`.

### Changed

- Un fallo al inicializar los componentes ya no puede dejar el sitio en blanco: se captura, se registra y se muestra un aviso en el administrador.

## [1.0.0] - 2026-08-17

### Added

- Pestaña de ajustes «Condiciones de contratación» dentro de WooCommerce → Ajustes.
- Casillas de aceptación ilimitadas: texto personalizable con enlaces, orden por arrastre y marca de obligatoria u opcional.
- Interruptor por casilla para activarla o desactivarla sin perder su configuración.
- Opción para desactivar la casilla genérica de términos y condiciones de WooCommerce.
- Casillas en el checkout clásico (antes del botón de realizar el pedido) y en el checkout por bloques.
- Validación en el servidor de las casillas obligatorias en ambos checkouts, incluida la del Store API.
- Registro en el pedido de las condiciones mostradas, aceptadas y su fecha, guardando el texto vigente en el momento de la compra.
- Panel «Condiciones aceptadas» en la ficha del pedido, compatible con HPOS.
- Plantilla de traducción `languages/condiciones-contratacion-woocommerce.pot`.
