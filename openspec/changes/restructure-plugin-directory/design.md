## Context

Ver `proposal.md` — Why. Estado actual relevante:

- El código del plugin y los archivos de desarrollo comparten la raíz del repo; el zip se construye en CI con `rsync -a --exclude-from=.distignore` copiando la raíz dentro de `build/<slug>/` y comprimiendo eso.
- El workflow lee la versión de la cabecera del archivo principal en la raíz, extrae notas de `CHANGELOG.md` y solo publica si no existe el tag `vX.Y.Z`.
- Las rutas internas del plugin se resuelven en tiempo de ejecución con `plugin_dir_path( __FILE__ )` / `plugin_dir_url( __FILE__ )`, por lo que mover archivos dentro del repo no afecta al comportamiento en la tienda.
- `phpcs.xml.dist` analiza `<file>.</file>` con excludes; `composer.json` tiene un script `make-pot` que apunta a `.` y a `languages/` en la raíz.
- No hay tests automatizados; la verificación es phpcs + inspección del zip.

Restricción clave de WordPress.org y del instalador de WordPress: el zip debe contener una única carpeta raíz llamada exactamente `condiciones-contratacion-woocommerce/` con `condiciones-contratacion-woocommerce.php` dentro.

## Goals / Non-Goals

**Goals:**

- Que una implantación manual sea `cp -R condiciones-contratacion-woocommerce/ wp-content/plugins/` (o rsync equivalente), sin filtrar nada.
- Que el zip de la release de GitHub sea instalable tal cual en WordPress (admin o WordPress.org) sin repaquetizar.
- Que añadir archivos de desarrollo al repo deje de requerir mantenimiento de exclusiones.
- Preservar el historial de git de los archivos movidos (usar `git mv`).

**Non-Goals:**

- No se toca el contenido de ningún archivo PHP del plugin (ni cabeceras, ni constantes, ni lógica).
- No se cambia la lógica de versionado/publicación del workflow (detección de versión ya publicada, notas desde changelog, título de release).
- No se publica una versión nueva del plugin como parte de este cambio (el número de versión no varía; la release saldrá con la siguiente versión que se prepare).
- No se añade proceso de build (minificación, composer runtime, etc.).

## Decisions

### D1 — Nombre del subdirectorio: el slug (`condiciones-contratacion-woocommerce/`)

Alternativas: `src/`, `plugin/`, slug.

Se elige el slug porque elimina todo paso de renombrado: el directorio del repo ya es exactamente lo que WordPress espera encontrar en `wp-content/plugins/`, y `zip -r` sobre él produce directamente la estructura de carpeta raíz que exige WordPress.org. Con `src/` o `plugin/` el workflow tendría que copiar/renombrar a `build/<slug>/` (se mantiene el paso intermedio que queremos eliminar) y la copia manual a `plugins/` obligaría a renombrar.

Trade-off aceptado: la ruta `condiciones-contratacion-woocommerce/condiciones-contratacion-woocommerce.php` es redundante a la vista, pero es la convención exacta de WordPress (`plugins/<slug>/<slug>.php`).

### D2 — Empaquetado por lista blanca implícita; `.distignore` se elimina

El zip pasa a ser `zip -r <slug>-<version>.zip <slug>` desde la raíz del repo. Todo lo que esté dentro del directorio del plugin se distribuye; todo lo que esté fuera, no. Ya no hay lista de exclusiones que mantener, así que `.distignore` se borra (mantenerlo vacío o residual invitaría a confiar en él).

Consecuencia documentada: la regla de mantenimiento se invierte — «ningún archivo de desarrollo debe crearse dentro de `condiciones-contratacion-woocommerce/`». Es más fácil de cumplir que la lista negra porque el error se ve en el árbol del repo, y el paso del workflow que lista el contenido del zip (`unzip -l`) sirve de comprobación en cada release.

### D3 — `LICENSE`: copia en la raíz y en el directorio del plugin

GitHub solo detecta la licencia del repositorio en la raíz; WordPress.org recomienda que la licencia viaje en el zip. Se mantiene `LICENSE` en la raíz (detección de GitHub) y se añade una copia dentro del directorio del plugin (distribución). Son 35 KB y cambia una vez por década; la duplicación es asumible y se anota en `docs/empaquetado.md`.

Alternativa descartada: symlink (los zips y checkouts en Windows los tratan mal) o copiarla en CI durante el build (reintroduce un paso de ensamblado que D2 elimina).

### D4 — El workflow lee versión y changelog desde sus nuevas rutas, sin más cambios

- Paso «Leer versión»: `FILE="condiciones-contratacion-woocommerce/condiciones-contratacion-woocommerce.php"`.
- Paso «Construir el zip»: sustituir mkdir/rsync/cd por `zip -rq "$ZIP" "$SLUG"` directo (más `unzip -l` como hasta ahora). Desaparece la exclusión de `build` porque ya no hay directorio intermedio.
- `CHANGELOG.md` sigue en la raíz: el paso de notas no cambia.

### D5 — Tooling apunta al subdirectorio

- `phpcs.xml.dist`: `<file>condiciones-contratacion-woocommerce</file>` y `<file>uninstall.php</file>`… — no: `uninstall.php` también se mueve; queda un único `<file>` con el directorio del plugin. Los `exclude-pattern` de `vendor/`, `node_modules/`, `build/` y `openspec/` se conservan por robustez aunque ya no haya PHP fuera del directorio analizado.
- `composer.json` → `make-pot`: `wp i18n make-pot condiciones-contratacion-woocommerce condiciones-contratacion-woocommerce/languages/<slug>.pot` (los `--exclude` de vendor/build dejan de ser necesarios al escanear solo el directorio del plugin).
- `.gitignore`: `/build/` deja de generarse en CI pero se mantiene la entrada (inofensiva y protege builds manuales antiguos); `*.zip` se conserva.

### D6 — Referencias del `.pot`

El `.pot` generado con rutas nuevas contendrá referencias `#: includes/...` relativas al directorio del plugin si se genera desde dentro, o `#: condiciones-contratacion-woocommerce/includes/...` si se genera desde la raíz. Se decide generarlo **desde la raíz apuntando al subdirectorio** (script de composer) y aceptar el prefijo en las referencias: son comentarios para traductores, no afectan a la carga de traducciones. No se regenera el `.pot` a mano en este cambio salvo que el tooling esté disponible; se anota en tareas como opcional.

## Risks / Trade-offs

- [El zip de una release antigua y el nuevo difieren en método de construcción] → El paso `unzip -l` del workflow permite comparar el árbol; la primera release tras el cambio se revisa a mano contra la lista de 17 archivos de `docs/empaquetado.md`.
- [Alguien crea un archivo de desarrollo dentro del directorio del plugin y acaba distribuido] → Regla invertida documentada en `CLAUDE.md` y `docs/empaquetado.md`; el listado del zip en el log del workflow lo delata.
- [Herramientas o editores con rutas cacheadas (IDE, sesiones previas) se rompen tras el `git mv`] → Cambio en un único commit, movimientos con `git mv` para conservar historial (`git log --follow` sigue funcionando).
- [`Domain Path: /languages` de la cabecera] → Es relativo al directorio del plugin instalado, no al repo; no requiere cambio. Verificar tras mover que la cabecera queda intacta.
- [El push de este cambio dispara el workflow] → Sin cambio de versión no se crea release (la lógica de tag existente lo impide); riesgo nulo, pero se verifica que el paso de lectura de versión no falle con la nueva ruta (fallaría silenciosamente con `skip=true` si la ruta fuera errónea — revisar el log del workflow tras el push).

## Migration Plan

1. `git mv` de los 7 elementos al subdirectorio + copia de `LICENSE`.
2. Actualizar workflow, phpcs, composer, docs, CLAUDE.md, CHANGELOG.md; borrar `.distignore`.
3. Un único commit. Tras el push, revisar en Actions que el workflow termina en «no se crea release» sin errores de ruta.
4. Rollback: revertir el commit (`git revert`) restaura la estructura anterior por completo; no hay estado externo.
