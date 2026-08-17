# Depuración: pantalla en blanco y otros fallos

Una pantalla en blanco en WordPress es **siempre un error fatal de PHP** que no se está mostrando. El error existe y está descrito en algún sitio; solo hay que hacerlo visible.

## Paso 1: ver el error real

Añade esto en `wp-config.php`, **antes** de la línea `/* That's all, stop editing! */`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );    // escribe en wp-content/debug.log
define( 'WP_DEBUG_DISPLAY', false ); // no lo muestra a los visitantes
@ini_set( 'display_errors', 0 );
```

Recarga la página que se queda en blanco y abre `wp-content/debug.log`. Las últimas líneas contienen el error, con archivo y número de línea. Un fatal tiene esta forma:

```
PHP Fatal error:  Uncaught Error: Class "CCWOO_Settings_Page" not found in .../class-ccwoo-plugin.php:120
```

Con `WP_DEBUG` activado también se enciende el registro de este plugin (ver paso 3).

Alternativas si no puedes editar `wp-config.php`:

- **Correo de recuperación**: desde WordPress 5.2, cuando un plugin provoca un fatal, WordPress envía un correo al administrador del sitio con el error exacto, el archivo y la línea, y un enlace al «modo de recuperación». Revisa el buzón de la dirección de administración.
- **Registro del servidor**: en el panel de tu hosting, busca el `error_log` de PHP del dominio. Contiene lo mismo que `debug.log`.

## Paso 2: confirmar que el culpable es este plugin

Si el sitio está inaccesible y no puedes entrar al escritorio para desactivar el plugin, renombra su carpeta por FTP o desde el gestor de archivos:

```
wp-content/plugins/condiciones-contratacion-woocommerce
→ wp-content/plugins/condiciones-contratacion-woocommerce-off
```

WordPress lo desactivará solo al no encontrarlo y volverás a tener acceso. Si con la carpeta renombrada el sitio funciona, el fallo es del plugin; si sigue en blanco, es de otro sitio.

## Paso 3: el registro del propio plugin

El plugin escribe en el sistema de registros de WooCommerce. Lo verás en:

**WooCommerce → Estado → Registros**, eligiendo el archivo cuyo origen es `condiciones-contratacion`.

Se registran siempre los errores y las advertencias. Los mensajes informativos (cuántas casillas se muestran, qué llega del checkout por bloques, cuántas aceptaciones se guardan) solo se escriben con la depuración activa, que se enciende de dos maneras:

- Con `WP_DEBUG` a `true`, como en el paso 1.
- O, sin tocar `WP_DEBUG`, añadiendo este filtro en el `functions.php` del tema hijo o en un plugin de utilidades:

```php
add_filter( 'ccwoo_enable_logging', '__return_true' );
```

Qué se registra en cada punto:

| Momento | Qué te dice |
|---|---|
| Arranque | Si WooCommerce falta o su versión es antigua |
| Arranque | Si un componente ha fallado al inicializarse (con el mensaje y la traza) |
| Ajustes | Si la pestaña se ha registrado, o por qué no |
| Guardado | Si el nonce falló y cuántas casillas se han guardado |
| Checkout clásico | Cuántas casillas se muestran, cuáles quedan sin aceptar, cuántas aceptaciones se guardan |
| Store API (bloques) | Qué datos llegan del navegador, cuántas obligatorias faltan |
| Bloques | Si la integración de JavaScript se ha registrado |

## Paso 4: el panel de diagnóstico

En **WooCommerce → Ajustes → Condiciones de contratación**, al final de la página, hay una sección **Diagnóstico** con: versión del plugin, de WooCommerce, de WordPress y de PHP; si HPOS está activo; si la página de pago usa el checkout por bloques; cuántas casillas hay configuradas, activas y obligatorias; si la casilla genérica de WooCommerce está desactivada; y si el registro está activo.

Es el primer sitio donde mirar cuando el plugin carga pero **no hace lo que esperas**. Por ejemplo: si «Casillas activas» es 0, no aparecerán en el checkout aunque las hayas creado (revisa la columna «Activa»); y si «Checkout por bloques» dice «no» pero tu página de pago sí usa bloques, es que la página de pago configurada en WooCommerce no es la que estás visitando.

## Paso 5: el checkout por bloques no muestra las casillas

Ese checkout se pinta con JavaScript, así que el registro de PHP no lo cuenta todo. Con la depuración activa (paso 3), abre la consola del navegador en la página de pago y busca mensajes que empiecen por `[condiciones-contratacion]`. Te dirán si falta alguna API de WooCommerce Blocks, si no hay casillas activas o cuántas se han inicializado.

Recuerda que la validación de verdad está en el servidor: aunque el JavaScript no llegue a pintar nada, un pedido con casillas obligatorias sin aceptar será rechazado por el Store API. Si ves un pedido rechazado con el mensaje «Debes aceptar las siguientes condiciones…» pero no ves las casillas, es exactamente este caso.

## Fallos ya conocidos y resueltos

- **`Fatal error: Class "CCWOO_Settings_Page" not found` (pantalla en blanco en los ajustes de WooCommerce).** La clase de la pestaña extiende `WC_Settings_Page`, que WooCommerce **no** carga con su autoloader: la incluye a mano justo antes de disparar `woocommerce_get_settings_pages`. Al cargar nuestra clase en `plugins_loaded`, `class_exists( 'WC_Settings_Page' )` era `false`, el archivo salía con `return` y la clase nunca se definía; después, el filtro intentaba instanciarla. Resuelto cargando el archivo dentro de `register_settings_page()`, con doble comprobación para que no pueda volver a provocar un fatal.

## Si necesitas ayuda con un fallo nuevo

Reúne estos cuatro datos, que son los que permiten localizar el problema sin adivinar:

1. Las últimas 20 líneas de `wp-content/debug.log` a partir del `PHP Fatal error`.
2. El contenido del panel **Diagnóstico** (o, si no puedes abrirlo, las versiones de WordPress, WooCommerce y PHP).
3. El registro `condiciones-contratacion` de WooCommerce → Estado → Registros.
4. Qué estabas haciendo exactamente: subir el plugin, activarlo, abrir los ajustes, guardar, o completar un pedido.
