# Spec: terms-settings

## ADDED Requirements

### Requirement: Pestaña de ajustes en WooCommerce
El plugin SHALL registrar una pestaña propia llamada "Condiciones de contratación" dentro de WooCommerce → Ajustes, accesible solo para usuarios con la capacidad `manage_woocommerce`.

#### Scenario: Acceso a la pestaña
- **WHEN** un administrador de la tienda abre WooCommerce → Ajustes
- **THEN** ve una pestaña "Condiciones de contratación" y al pulsarla se muestra la pantalla de configuración del plugin

#### Scenario: Usuario sin permisos
- **WHEN** un usuario sin la capacidad `manage_woocommerce` intenta acceder a la URL de la pestaña
- **THEN** WordPress deniega el acceso y no se muestra ni se puede guardar la configuración

### Requirement: Gestión de casillas de aceptación
La pantalla de ajustes SHALL permitir añadir, editar y eliminar casillas de aceptación. Cada casilla SHALL tener: un texto personalizable (que admite HTML seguro limitado, incluidos enlaces) y una marca de obligatoria u opcional.

#### Scenario: Añadir una casilla
- **WHEN** el administrador pulsa "Añadir casilla", escribe el texto "Acepto las condiciones de contratación", la marca como obligatoria y guarda
- **THEN** la casilla queda persistida y aparece en el listado de ajustes con su texto y su marca de obligatoria

#### Scenario: Editar una casilla existente
- **WHEN** el administrador modifica el texto de una casilla o cambia su marca obligatoria/opcional y guarda
- **THEN** los cambios quedan persistidos y se reflejan en el listado y en el checkout

#### Scenario: Eliminar una casilla
- **WHEN** el administrador elimina una casilla y guarda
- **THEN** la casilla desaparece del listado y deja de mostrarse en el checkout

#### Scenario: Saneamiento del texto
- **WHEN** el administrador guarda un texto de casilla que contiene HTML no permitido (p. ej. `<script>`)
- **THEN** el sistema elimina las etiquetas no permitidas y persiste solo HTML seguro (enlaces, negrita, cursiva)

#### Scenario: Guardado sin nonce válido
- **WHEN** se envía el formulario de ajustes sin un nonce válido
- **THEN** el sistema rechaza la petición y no modifica la configuración

### Requirement: Ordenación de las casillas
La pantalla de ajustes SHALL permitir definir el orden de las casillas, y ese orden SHALL determinar el orden de presentación en el checkout.

#### Scenario: Reordenar casillas
- **WHEN** el administrador cambia el orden de dos casillas y guarda
- **THEN** el nuevo orden queda persistido y el checkout muestra las casillas en ese orden

### Requirement: Desactivación de la aceptación genérica de WooCommerce
Los ajustes SHALL incluir una opción para desactivar la casilla genérica de "términos y condiciones" de WooCommerce en el checkout. Por defecto la casilla genérica SHALL permanecer activa.

#### Scenario: Opción activada
- **WHEN** el administrador activa la opción "Desactivar la aceptación genérica de WooCommerce" y guarda
- **THEN** la casilla genérica de términos y condiciones de WooCommerce deja de mostrarse y de exigirse en el checkout

#### Scenario: Opción desactivada (por defecto)
- **WHEN** la opción está desactivada
- **THEN** la casilla genérica de WooCommerce se comporta como de serie, además de las casillas propias del plugin
