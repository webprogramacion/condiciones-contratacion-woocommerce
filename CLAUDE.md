# Condiciones de contratación para WooCommerce

Plugin de WordPress/WooCommerce (slug: `condiciones-contratacion-woocommerce`) que añade casillas de aceptación de condiciones configurables al checkout. La planificación vive en `openspec/changes/`.

## Convenciones

- Seguir WordPress Coding Standards (WPCS); validar con `phpcs --standard=WordPress`.
- Todas las cadenas traducibles con textdomain `condiciones-contratacion-woocommerce`.
- Prefijo de clases y funciones: `CCWOO_` / `ccwoo_`.
- Compatibilidad declarada con HPOS y checkout por bloques.

## Depuración

Guía completa en [docs/depuracion.md](docs/depuracion.md). El plugin registra en el sistema de registros de WooCommerce vía `CCWOO_Logger` (origen `condiciones-contratacion`): errores y advertencias siempre, mensajes informativos solo con `WP_DEBUG` o el filtro `ccwoo_enable_logging`. Los ajustes incluyen una sección «Diagnóstico» con el estado del entorno.

Cuidado con las clases de WooCommerce que su autoloader no resuelve (`WC_Settings_Page` entre ellas): hay que cargar las clases que las extienden dentro del hook donde WooCommerce ya las ha incluido, nunca en `plugins_loaded`.

## Proceso de nueva versión

Cuando el usuario pida publicar una nueva versión (p. ej. "saca la versión 1.2.0" o "prepara una nueva versión"):

1. Decidir el número siguiendo SemVer si el usuario no lo indica (fix → patch, funcionalidad → minor, ruptura → major).
2. Actualizar la versión en **todos** estos sitios:
   - Cabecera `Version:` de `condiciones-contratacion-woocommerce.php`.
   - Constante de versión del plugin (p. ej. `CCWOO_VERSION`) en el mismo archivo.
   - `Stable tag:` de `readme.txt`.
3. Documentar los cambios en el changelog, en ambos archivos:
   - `CHANGELOG.md`: mover lo pendiente de `## [Unreleased]` a una nueva sección `## [X.Y.Z] - AAAA-MM-DD` (formato Keep a Changelog, en español).
   - `readme.txt`: añadir la misma entrada en la sección `== Changelog ==` con el formato de WordPress.org (`= X.Y.Z =`).
4. Hacer commit con el mensaje `Release X.Y.Z`.
5. Avisar al usuario de que al hacer **push a `main`** el workflow `.github/workflows/release.yml` creará automáticamente el tag `vX.Y.Z` y la release de GitHub con el zip `condiciones-contratacion-woocommerce-X.Y.Z.zip` adjunto, listo para subir a WordPress.org. No crear el tag ni la release a mano.

## Empaquetado

El contenido exacto del zip, qué se excluye y por qué, y el criterio sobre `vendor/` están en [docs/empaquetado.md](docs/empaquetado.md). Resumen:

- El zip de distribución se construye en CI con `rsync --exclude-from=.distignore`; el contenido queda dentro de una carpeta raíz `condiciones-contratacion-woocommerce/` (requisito de WordPress.org).
- `vendor/` **no** viaja en el zip: todas las dependencias de Composer son de desarrollo y el plugin no carga `vendor/autoload.php`. Si algún día se añade una dependencia de runtime, hay que revisar la sección correspondiente de `docs/empaquetado.md`.
- Cualquier archivo de desarrollo nuevo (tests, configs, tooling, documentación) debe añadirse a `.distignore` en el mismo commit para que no acabe en el zip.
- El workflow solo crea release si la versión de la cabecera del plugin no tiene ya un tag `vX.Y.Z`; un push sin cambio de versión no publica nada.
