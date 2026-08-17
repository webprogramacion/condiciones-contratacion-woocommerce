# Qué debe contener el zip que se sube a WordPress.org

Regla de oro: **el zip lleva solo lo que se ejecuta en la tienda del cliente**. Todo lo demás (herramientas, planificación, configuración de desarrollo) se queda fuera.

## Contenido exacto del zip

El archivo comprimido debe contener **una única carpeta raíz llamada igual que el slug del plugin** y, dentro, estos 17 archivos:

```
condiciones-contratacion-woocommerce/
├── condiciones-contratacion-woocommerce.php   ← archivo principal con la cabecera del plugin
├── uninstall.php                              ← limpieza de opciones al desinstalar
├── readme.txt                                 ← obligatorio para WordPress.org
├── LICENSE                                    ← GPLv2 (recomendable, no obligatorio)
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── checkout.css
│   └── js/
│       ├── admin.js
│       └── checkout-blocks.js
├── includes/
│   ├── class-ccwoo-plugin.php
│   ├── class-ccwoo-logger.php
│   ├── class-ccwoo-checkboxes.php
│   ├── class-ccwoo-settings-page.php
│   ├── class-ccwoo-checkout-classic.php
│   ├── class-ccwoo-checkout-blocks.php
│   ├── class-ccwoo-blocks-integration.php
│   └── class-ccwoo-order-acceptances.php
└── languages/
    └── condiciones-contratacion-woocommerce.pot
```

Si la carpeta raíz no se llama exactamente `condiciones-contratacion-woocommerce`, WordPress instalará el plugin en un directorio equivocado y el revisor de WordPress.org lo rechazará.

## ¿Hay que meter `vendor/`? No

**En este plugin, no.** Y no es una cuestión de preferencia, sino de qué hay dentro de `vendor/`:

- Todas las dependencias de Composer están en `require-dev`: PHP_CodeSniffer, WPCS, PHPCompatibility y el comando `i18n` de WP-CLI. Son herramientas que uso yo para revisar el código y generar el `.pot`; no las necesita el sitio del cliente.
- El único `require` de runtime en `composer.json` es `"php": ">=7.4"`, que no instala nada.
- El código del plugin **no carga en ningún momento** `vendor/autoload.php`. Puedes comprobarlo: no existe ninguna referencia a `autoload` en los archivos PHP.

Meter `vendor/` añadiría unos 10 MB de herramientas de desarrollo al zip, algunas con archivos de test y binarios, y el equipo de revisión de WordPress.org lo señalaría.

### Cuándo sí habría que incluirlo

Si algún día este plugin necesita una **librería en tiempo de ejecución** (por ejemplo un generador de PDF o un cliente de API), entonces sí:

1. Esa librería va en `require`, no en `require-dev`.
2. El zip debe incluir `vendor/`, generado con `composer install --no-dev --optimize-autoloader` (WordPress.org no ejecuta Composer al instalar: lo que no vaya en el zip, no existe).
3. Hay que quitar `vendor` de `.distignore` y añadir `require_once CCWOO_PLUGIN_DIR . 'vendor/autoload.php';` en el archivo principal.
4. Conviene renombrar el espacio de nombres de la librería (con PHP-Scoper o Mozart) para que no choque con otro plugin que incluya la misma librería en otra versión, que es una fuente clásica de fallos fatales en WordPress.

## Qué se queda fuera y por qué

Lo controla [`.distignore`](../.distignore):

| Fuera del zip | Motivo |
|---|---|
| `vendor/`, `composer.json`, `composer.lock` | Dependencias solo de desarrollo |
| `phpcs.xml.dist` | Configuración de linter |
| `.github/` | Workflow de release, no es código del plugin |
| `openspec/` | Planificación del cambio (propuesta, diseño, specs, tareas) |
| `docs/`, `CHANGELOG.md`, `CLAUDE.md` | Documentación interna del repositorio |
| `.claude/`, `.codex/`, `.agent/` | Configuración de herramientas locales |
| `.git/`, `.gitignore`, `.gitattributes`, `.distignore` | Metadatos del repositorio |
| `node_modules/`, `tests/`, `build/`, `*.zip` | Artefactos y dependencias generadas |

El changelog no viaja en el zip porque WordPress.org lee el suyo de la sección `== Changelog ==` de `readme.txt`. `CHANGELOG.md` es para quien lee el repositorio en GitHub; hay que mantener **los dos**.

Las capturas de pantalla que declara `readme.txt` **tampoco van en el zip**: en WordPress.org se suben al directorio `assets/` del SVN del plugin (`screenshot-1.png`, `screenshot-2.png`…), junto al banner y el icono. La carpeta `assets/` de este repositorio es otra cosa: el CSS y el JS que carga el plugin.

Solo se incluye el `.pot`. No hace falta añadir `.po` ni `.mo` de cada idioma: una vez publicado, las traducciones se gestionan en translate.wordpress.org y WordPress las descarga solo. Además, las cadenas fuente de este plugin ya están en español.

## Cómo se genera el zip

**Automáticamente (lo normal).** Al hacer push a `main`, el workflow [`.github/workflows/release.yml`](../.github/workflows/release.yml) lee la versión de la cabecera del plugin y, si esa versión aún no tiene release, construye el zip aplicando `.distignore` y lo adjunta a una release `vX.Y.Z`. Ese es el archivo que se sube a WordPress.org.

**A mano**, si quieres inspeccionarlo antes (PowerShell, desde la raíz del repositorio):

```powershell
$slug = 'condiciones-contratacion-woocommerce'
$version = '1.0.0'
Remove-Item -Recurse -Force build, "$slug-$version.zip" -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force "build\$slug" | Out-Null
Copy-Item condiciones-contratacion-woocommerce.php, uninstall.php, readme.txt, LICENSE "build\$slug"
Copy-Item -Recurse assets, includes, languages "build\$slug"
Compress-Archive -Path "build\$slug" -DestinationPath "$slug-$version.zip" -Force
```

Este comando parte de una lista blanca (copia lo que debe ir) mientras que el workflow parte de una lista negra (excluye `.distignore`). Ambos deben producir el mismo árbol; si difieren, es que se ha añadido un archivo nuevo y falta actualizar uno de los dos.

## Antes de subir: comprobaciones

1. La versión coincide en los tres sitios: cabecera `Version:` del archivo principal, constante `CCWOO_VERSION` y `Stable tag:` de `readme.txt`.
2. `readme.txt` tiene la entrada de la versión en `== Changelog ==`, y `CHANGELOG.md` también.
3. `Tested up to:` y `WC tested up to:` reflejan las versiones con las que realmente has probado.
4. `Contributors:` es tu usuario real de WordPress.org.
5. `php vendor\bin\phpcs` no devuelve hallazgos.
6. Si se han tocado cadenas traducibles, el `.pot` está regenerado:
   `php vendor\wp-cli\wp-cli\php\boot-fs.php i18n make-pot . languages\condiciones-contratacion-woocommerce.pot --domain=condiciones-contratacion-woocommerce --exclude=vendor,node_modules,build,openspec,.github,.claude,.codex,.agent`
7. Descomprime el zip y confirma que la carpeta raíz es `condiciones-contratacion-woocommerce`, que dentro está `readme.txt` y que **no** hay `vendor/`, `openspec/`, `.github/` ni `composer.json`.

## Mantenimiento

Cada vez que se añada un archivo o carpeta de desarrollo al repositorio (tests, configuración de herramientas, documentación, scripts de build), hay que añadirlo a `.distignore` en el mismo commit. Si no, acabará dentro del zip que se publica.
