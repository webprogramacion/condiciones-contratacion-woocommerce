# Spec: checkout-terms

## ADDED Requirements

### Requirement: Presentación de casillas en el checkout
El checkout SHALL mostrar todas las casillas configuradas, en el orden definido en los ajustes, antes del botón de realizar pedido. El texto de cada casilla SHALL renderizarse con el HTML seguro permitido. Las casillas obligatorias SHALL indicarse visualmente como requeridas.

#### Scenario: Checkout clásico (shortcode)
- **WHEN** un cliente llega al checkout clásico con tres casillas configuradas
- **THEN** ve las tres casillas en el orden configurado, antes del botón "Realizar el pedido", con las obligatorias marcadas como requeridas

#### Scenario: Checkout por bloques
- **WHEN** un cliente llega al checkout por bloques (Checkout Block) con casillas configuradas
- **THEN** ve las mismas casillas, en el mismo orden y con la misma indicación de obligatoriedad

#### Scenario: Sin casillas configuradas
- **WHEN** no hay ninguna casilla configurada
- **THEN** el checkout no muestra ningún elemento del plugin ni añade validaciones

### Requirement: Validación de casillas obligatorias
El sistema SHALL impedir que se procese el pedido si alguna casilla obligatoria no está marcada, mostrando un aviso de error que identifique la casilla pendiente. Las casillas opcionales SHALL poder dejarse sin marcar sin bloquear el pedido. La validación SHALL ejecutarse en el servidor, no solo en el navegador.

#### Scenario: Obligatoria sin marcar
- **WHEN** el cliente envía el pedido sin marcar una casilla obligatoria
- **THEN** el pedido no se procesa y se muestra un aviso de error indicando qué condición debe aceptar

#### Scenario: Todas las obligatorias marcadas
- **WHEN** el cliente marca todas las casillas obligatorias (con las opcionales en cualquier estado) y envía el pedido
- **THEN** el pedido se procesa con normalidad

#### Scenario: Manipulación del navegador
- **WHEN** un cliente elimina el atributo required o el propio campo desde las herramientas del navegador y envía el pedido sin aceptar una casilla obligatoria
- **THEN** la validación del servidor rechaza el pedido igualmente

### Requirement: Supresión de la casilla genérica de WooCommerce
Cuando la opción correspondiente esté activada en los ajustes, el checkout SHALL omitir la casilla genérica de términos y condiciones de WooCommerce y su validación asociada, tanto en el checkout clásico como en el de bloques.

#### Scenario: Genérica suprimida en checkout clásico
- **WHEN** la opción está activada y el cliente abre el checkout clásico
- **THEN** la casilla genérica de WooCommerce no aparece y el pedido puede completarse sin ella (cumpliendo las casillas propias del plugin)

#### Scenario: Genérica suprimida en checkout por bloques
- **WHEN** la opción está activada y el cliente abre el checkout por bloques
- **THEN** la casilla/aviso genérico de términos del bloque no se muestra
