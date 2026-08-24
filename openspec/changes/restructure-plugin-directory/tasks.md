## 1. Mover el código del plugin al subdirectorio

- [x] 1.1 Crear `condiciones-contratacion-woocommerce/` y mover con `git mv` estos 7 elementos: `condiciones-contratacion-woocommerce.php`, `uninstall.php`, `readme.txt`, `assets/`, `includes/`, `languages/`, y copiar `LICENSE` dentro (la copia de la raíz se queda). Verificar con `git status` que los movimientos aparecen como renames y con `ls condiciones-contratacion-woocommerce/` que están los 7 elementos más `LICENSE`.
- [x] 1.2 Comprobar que la cabecera del plugin y las constantes no han cambiado: `head -30 condiciones-contratacion-woocommerce/condiciones-contratacion-woocommerce.php` muestra `Version: 1.1.0`, `Domain Path: /languages` y las `define()` intactas; `php -l` pasa en todos los `.php` del subdirectorio.

## 2. Ajustar el workflow de release

- [x] 2.1 En `.github/workflows/release.yml`, actualizar el paso «Leer versión» para leer `condiciones-contratacion-woocommerce/condiciones-contratacion-woocommerce.php`. Verificar reproduciendo el `sed` del paso en local contra la nueva ruta y comprobando que imprime `1.1.0`.
- [x] 2.2 En el paso «Construir el zip», sustituir mkdir/rsync por compresión directa del subdirectorio (`zip -rq "$ZIP" "$SLUG"` desde la raíz), conservando el `unzip -l` de inspección. Verificar reproduciendo el paso en local: el zip contiene una única carpeta raíz `condiciones-contratacion-woocommerce/` con los 17 archivos listados en `docs/empaquetado.md` (ahora 18 con `LICENSE`) y sin `openspec/`, `.github/`, `docs/` ni `composer.json`.
- [x] 2.3 Eliminar `.distignore` con `git rm`. Verificar que ninguna referencia queda en el workflow: `grep -rn distignore .github/` no devuelve nada.

## 3. Ajustar el tooling de desarrollo

- [x] 3.1 En `phpcs.xml.dist`, cambiar `<file>.</file>` por `<file>condiciones-contratacion-woocommerce</file>`. Verificar con `vendor/bin/phpcs` si está disponible; si no, validar que el XML está bien formado (`xmllint --noout` o `python3 -c "import xml.dom.minidom,..."`).
- [x] 3.2 En `composer.json`, actualizar el script `make-pot` para escanear `condiciones-contratacion-woocommerce` y escribir en `condiciones-contratacion-woocommerce/languages/condiciones-contratacion-woocommerce.pot`, retirando los `--exclude` que ya no aplican. Verificar que `composer.json` sigue siendo JSON válido (`python3 -m json.tool`).

## 4. Actualizar la documentación

- [x] 4.1 Reescribir las secciones afectadas de `docs/empaquetado.md`: nuevo criterio de lista blanca por directorio, desaparición de `.distignore` (tabla de exclusiones sustituida por la regla «nada de desarrollo dentro del directorio del plugin»), comando manual de zip actualizado, decisión sobre `LICENSE` duplicada, y árbol de contenido del zip con `LICENSE` incluido. Verificar que no queda ninguna mención a `.distignore` como archivo vigente: `grep -n distignore docs/empaquetado.md` solo en contexto histórico o ninguna.
- [x] 4.2 Actualizar `CLAUDE.md`: rutas del proceso de nueva versión (cabecera y constante ahora en `condiciones-contratacion-woocommerce/condiciones-contratacion-woocommerce.php`, `Stable tag` en `condiciones-contratacion-woocommerce/readme.txt`) y sustituir la regla de `.distignore` del apartado Empaquetado por la regla invertida. Verificar con `grep -n distignore CLAUDE.md` (sin resultados) y `grep -n "condiciones-contratacion-woocommerce/readme.txt" CLAUDE.md`.
- [x] 4.3 Revisar `docs/depuracion.md` y el resto de docs por rutas rotas a archivos movidos (`grep -rn "](\.\./\|](includes\|](assets" docs/ readme` y similares) y corregirlas. Verificar que todos los enlaces relativos de los `.md` del repo apuntan a archivos existentes.
- [x] 4.4 Añadir la entrada del cambio en `## [Unreleased]` de `CHANGELOG.md` (sección `### Changed`, en español). Verificar que la entrada existe y el formato Keep a Changelog se mantiene.

## 5. Verificación de conjunto

- [x] 5.1 Simular el workflow completo en local (lectura de versión + build del zip + extracción de notas) y descomprimir el zip en un directorio temporal comprobando: carpeta raíz exacta `condiciones-contratacion-woocommerce/`, `readme.txt` presente, `.pot` presente, ausencia de archivos de desarrollo.
- [x] 5.2 `git log --follow condiciones-contratacion-woocommerce/includes/class-ccwoo-plugin.php` muestra el historial anterior al movimiento (los renames conservan historial).
- [x] 5.3 Tras el push a `main`, revisar en GitHub Actions que el workflow acaba sin error y sin crear release (la versión 1.1.0 ya tiene tag), y que el paso de versión detecta `1.1.0` con la nueva ruta.
