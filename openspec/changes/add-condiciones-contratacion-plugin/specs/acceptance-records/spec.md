# Spec: acceptance-records

## ADDED Requirements

### Requirement: Registro de aceptaciones en el pedido
Al procesarse un pedido, el sistema SHALL guardar en los metadatos del pedido el estado de cada casilla configurada: identificador, texto mostrado en el momento de la compra, si era obligatoria u opcional, si fue aceptada y la fecha/hora de la aceptación. El guardado SHALL ser compatible con HPOS (High-Performance Order Storage).

#### Scenario: Pedido con aceptaciones
- **WHEN** un cliente completa un pedido marcando dos casillas obligatorias y dejando una opcional sin marcar
- **THEN** el pedido almacena las tres casillas con su texto vigente, su carácter y su estado (dos aceptadas, una no aceptada)

#### Scenario: Texto vigente como evidencia
- **WHEN** el administrador cambia el texto de una casilla después de que existan pedidos previos
- **THEN** los pedidos anteriores conservan el texto que el cliente vio y aceptó en su momento

### Requirement: Visualización de aceptaciones en la administración del pedido
La pantalla de edición de un pedido en el administrador SHALL mostrar un resumen de las condiciones aceptadas (y no aceptadas) por el cliente, con su texto y fecha.

#### Scenario: Ver aceptaciones de un pedido
- **WHEN** el administrador abre un pedido que tiene aceptaciones registradas
- **THEN** ve un panel o sección con cada condición, su texto, si era obligatoria y si fue aceptada, con la fecha/hora

#### Scenario: Pedido sin registros
- **WHEN** el administrador abre un pedido creado antes de instalar el plugin o sin casillas configuradas
- **THEN** la pantalla del pedido no muestra el panel o indica que no hay aceptaciones registradas, sin errores
