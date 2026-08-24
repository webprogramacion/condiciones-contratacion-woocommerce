# Qué debe contener el zip que se sube a WordPress.org

Regla de oro: **el zip lleva solo lo que se ejecuta en la tienda del cliente**. Todo lo demás (herramientas, planificación, configuración de desarrollo) se queda fuera.

## Cómo se garantiza: un directorio con lo distribuible

Todo lo que viaja en el zip vive dentro del directorio [`condiciones-contratacion-woocommerce/`](../condiciones-contratacion-woocommerce/) del repositorio, que se llama exactamente igual que el slug del plugin. Fuera de él solo hay herramientas, documentación, planificación y configuración de CI.

Esa separación hace innecesaria cualquier lista de exclusiones: el zip se construye comprimiendo ese directorio tal cual, así que **lo que está dentro se distribuye y lo que está fuera, no**. Como el directorio ya tiene el nombre que WordPress espera (`wp-content/plugins/<slug>/`), también sirve para implantar a mano: basta copiarlo o sincronizarlo al servidor sin filtrar nada.

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

Si la carpeta raíz no se llama exactamente `condiciones-contratacion-woocommerce`, WordPress instalará el plugin en un directorio equivocado y el revisor de WordPress.org lo rechazará. Por eso el directorio del repositorio ya se llama así: la carpeta raíz del zip es ese mismo directorio, sin renombrados por medio.

### `LICENSE` está duplicada a propósito

Hay dos copias idénticas: `LICENSE` en la raíz del repositorio y `condiciones-contratacion-woocommerce/LICENSE`. GitHub solo detecta la licencia del proyecto si está en la raíz, y el zip debe llevar la suya dentro. Se descartó resolverlo con un enlace simbólico (los zips y los checkouts en Windows los tratan mal) y con una copia hecha en CI durante el build (reintroduciría el paso de ensamblado que este esquema elimina). Son 35 KB que cambian una vez por década: si alguna vez se toca el archivo, hay que actualizar **las dos** copias.

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
3. El `vendor/` de runtime tiene que quedar **dentro** de `condiciones-contratacion-woocommerce/` para que entre en el zip, y hay que añadir `require_once CCWOO_PLUGIN_DIR . 'vendor/autoload.php';` en el archivo principal. Ojo: el `vendor/` de desarrollo que crea `composer install` en la raíz del repositorio es otro y debe seguir fuera.
4. Conviene renombrar el espacio de nombres de la librería (con PHP-Scoper o Mozart) para que no choque con otro plugin que incluya la misma librería en otra versión, que es una fuente clásica de fallos fatales en WordPress.

## Qué se queda fuera y por qué

Queda fuera todo lo que vive en la raíz del repositorio, es decir, todo lo que no está dentro de `condiciones-contratacion-woocommerce/`:

| Fuera del zip | Motivo |
|---|---|
| `vendor/`, `composer.json`, `composer.lock` | Dependencias solo de desarrollo |
| `phpcs.xml.dist` | Configuración de linter |
| `.github/` | Workflow de release, no es código del plugin |
| `openspec/` | Planificación del cambio (propuesta, diseño, specs, tareas) |
| `docs/`, `CHANGELOG.md`, `CLAUDE.md` | Documentación interna del repositorio |
| `.claude/`, `.codex/`, `.agent/` | Configuración de herramientas locales |
| `.git/`, `.gitignore`, `.gitattributes` | Metadatos del repositorio |
| `node_modules/`, `tests/`, `build/`, `*.zip` | Artefactos y dependencias generadas |
| `LICENSE` (la de la raíz) | Es la copia para GitHub; el zip lleva la del directorio del plugin |

Antes esta lista se mantenía a mano en un archivo `.distignore` que el workflow pasaba a `rsync`. Ese archivo ya no existe: cada entrada de la tabla queda fuera por estar en la raíz, no por figurar en una lista.

El changelog no viaja en el zip porque WordPress.org lee el suyo de la sección `== Changelog ==` de `readme.txt`. `CHANGELOG.md` es para quien lee el repositorio en GitHub; hay que mantener **los dos**.

Las capturas de pantalla que declara `readme.txt` **tampoco van en el zip**: en WordPress.org se suben al directorio `assets/` del SVN del plugin (`screenshot-1.png`, `screenshot-2.png`…), junto al banner y el icono. La carpeta `assets/` de este repositorio es otra cosa: el CSS y el JS que carga el plugin.

Solo se incluye el `.pot`. No hace falta añadir `.po` ni `.mo` de cada idioma: una vez publicado, las traducciones se gestionan en translate.wordpress.org y WordPress las descarga solo. Además, las cadenas fuente de este plugin ya están en español.

## Cómo se genera el zip

**Automáticamente (lo normal).** Al hacer push a `main`, el workflow [`.github/workflows/release.yml`](../.github/workflows/release.yml) lee la versión de la cabecera del plugin y, si esa versión aún no tiene release, comprime el directorio `condiciones-contratacion-woocommerce/` y adjunta el zip a una release `vX.Y.Z`. Ese archivo se puede descargar de la release e instalar tal cual: sirve igual para subirlo a WordPress.org que para instalarlo desde **Plugins → Añadir nuevo → Subir plugin** en el administrador de WordPress. El propio workflow imprime el contenido del zip con `unzip -l`, que es la comprobación de que no se ha colado nada.

**A mano**, si quieres inspeccionarlo antes. Desde la raíz del repositorio, en PowerShell:

```powershell
$slug = 'condiciones-contratacion-woocommerce'
$version = '1.1.0'
Remove-Item -Force "$slug-$version.zip" -ErrorAction SilentlyContinue
Compress-Archive -Path $slug -DestinationPath "$slug-$version.zip"
```

O en bash, exactamente lo que hace el workflow:

```bash
slug=condiciones-contratacion-woocommerce
version=1.1.0
rm -f "$slug-$version.zip"
zip -rq "$slug-$version.zip" "$slug"
unzip -l "$slug-$version.zip"
```

Ya no hay dos criterios que mantener sincronizados: tanto el workflow como el comando manual comprimen el mismo directorio, así que producen el mismo árbol por construcción.

## Antes de subir: comprobaciones

1. La versión coincide en los tres sitios: cabecera `Version:` de `condiciones-contratacion-woocommerce/condiciones-contratacion-woocommerce.php`, constante `CCWOO_VERSION` en ese mismo archivo y `Stable tag:` de `condiciones-contratacion-woocommerce/readme.txt`.
2. `readme.txt` tiene la entrada de la versión en `== Changelog ==`, y `CHANGELOG.md` también.
3. `Tested up to:` y `WC tested up to:` reflejan las versiones con las que realmente has probado.
4. `Contributors:` es tu usuario real de WordPress.org.
5. `composer lint` (o `php vendor\bin\phpcs`) no devuelve hallazgos.
6. Si se han tocado cadenas traducibles, el `.pot` está regenerado con `composer make-pot`, que equivale a:
   `wp i18n make-pot condiciones-contratacion-woocommerce condiciones-contratacion-woocommerce\languages\condiciones-contratacion-woocommerce.pot`
7. Descomprime el zip y confirma que la carpeta raíz es `condiciones-contratacion-woocommerce`, que dentro está `readme.txt` y que **no** hay `vendor/`, `openspec/`, `.github/` ni `composer.json`.

## Mantenimiento

La regla es una sola: **nada de desarrollo se crea dentro de `condiciones-contratacion-woocommerce/`**. Tests, configuración de herramientas, documentación y scripts de build van en la raíz del repositorio o en sus propios directorios, y con eso quedan fuera del zip sin tocar ninguna lista.

Es más difícil de incumplir que el esquema anterior, porque el error se ve en el árbol del repositorio en lugar de esconderse en un archivo de exclusiones desactualizado. Y si aun así se cuela algo, el `unzip -l` del log del workflow lo enseña en cada release.
