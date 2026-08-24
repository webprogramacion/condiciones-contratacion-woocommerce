## Why

Hoy el código del plugin (archivo principal, `includes/`, `assets/`, `languages/`, `readme.txt`, `uninstall.php`) vive mezclado en la raíz del repositorio con todo lo que NO se distribuye (tooling, docs, planificación, CI). Eso complica las implantaciones: no se puede copiar/sincronizar un directorio tal cual a `wp-content/plugins/`, y la lista de exclusiones de `.distignore` tiene que mantenerse al día a mano con cada archivo de desarrollo nuevo.

## What Changes

- Mover todo lo que viaja en el zip de distribución a un subdirectorio llamado exactamente como el slug: `condiciones-contratacion-woocommerce/`. Con ello, una implantación manual es copiar esa carpeta a `wp-content/plugins/` y listo (WordPress espera `plugins/<slug>/<slug>.php`).
- Ajustar el workflow `.github/workflows/release.yml` para que:
  - lea la versión desde `condiciones-contratacion-woocommerce/condiciones-contratacion-woocommerce.php`;
  - construya el zip comprimiendo directamente ese subdirectorio, de modo que el zip descargado de la release de GitHub conserve la carpeta raíz `condiciones-contratacion-woocommerce/` y pueda subirse a WordPress.org (o instalarse desde el admin de WordPress) sin retoques.
- Sustituir el enfoque de lista negra (`.distignore` + rsync) por el de lista blanca implícita (se empaqueta el directorio del plugin): `.distignore` deja de ser necesario y se elimina.
- Actualizar las rutas en el tooling y la documentación: `phpcs.xml.dist` (analizar solo el directorio del plugin), script `make-pot` de `composer.json`, `docs/empaquetado.md`, `docs/depuracion.md` si aplica, `CLAUDE.md` (rutas del proceso de release) y `CHANGELOG.md`.
- **BREAKING** (solo para el repositorio, no para los usuarios del plugin): cambian las rutas de todos los archivos del plugin dentro del repo. El zip resultante y el comportamiento en tienda son idénticos.

## Capabilities

### New Capabilities

Ninguna. (Este cambio es de estructura del repositorio y empaquetado; no altera el comportamiento del plugin. `skip_specs: true` en `.openspec.yaml`.)

### Modified Capabilities

Ninguna.

## Impact

- **Movidos** a `condiciones-contratacion-woocommerce/`: `condiciones-contratacion-woocommerce.php`, `uninstall.php`, `readme.txt`, `LICENSE` (se mantiene una copia en la raíz para que GitHub detecte la licencia), `assets/`, `includes/`, `languages/`.
- **Modificados**: `.github/workflows/release.yml`, `phpcs.xml.dist`, `composer.json` (script `make-pot`), `docs/empaquetado.md`, `CLAUDE.md`, `CHANGELOG.md`, `.gitignore` si hace falta.
- **Eliminado**: `.distignore`.
- **Sin cambios funcionales**: ningún archivo PHP cambia de contenido (las rutas internas del plugin usan `plugin_dir_path( __FILE__ )`, que es relativo al archivo principal y sigue funcionando igual).
- El workflow de release conserva su lógica actual (solo publica si la versión no tiene ya tag `vX.Y.Z`).
