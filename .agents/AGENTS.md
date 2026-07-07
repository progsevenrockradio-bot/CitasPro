# Reglas del Entorno CitasPro (Hostinger & Backend)

## Configuración de Negocio y Datos Fiscales
- **Datos Fiscales Dinámicos por País**: La tabla `paises` almacena la estructura de validación y los campos requeridos para cada país en su columna `fiscal_fields` (formato JSON). El sistema utiliza esta información de manera dinámica para generar reglas de validación en el backend (`StoreNegocioDatosFiscalesRequest`) y renderizar los campos adecuados en el frontend (`DatosFiscalesForm.vue`). Los datos ingresados por el usuario se guardan en la tabla relacionada `negocio_datos_fiscales`.
- **Teléfonos y Contactos Múltiples**: El modelo `Negocio` maneja un teléfono principal (`telefono`) y múltiples números adicionales (columna JSON `telefonos_adicionales`). Además, incluye la columna `verification_phone_index` para establecer de forma dinámica cuál de estos números recibirá los códigos SMS o WhatsApp de verificación.
- **Historias Clínicas Dinámicas**: Las clínicas médicas (`tipo_clinica = 'medical'`) o dentales (`tipo_clinica = 'dental'`) cargan sus plantillas de historia clínica desde `plantillas_historia_clinica` (`PlantillaHistoriaClinica`). Al agendar una cita pública, se verifica mediante `/api/public/{slug}/check-cliente` si el paciente requiere rellenar la historia clínica. De ser así, se le presenta el formulario dinámico (`ClinicalForm.vue`) y las respuestas se guardan en `entradas_historia_clinica`. Se pueden visualizar en el Panel de Administración del paciente en `FichaCliente.vue`.

## Infraestructura del Servidor (Hostinger)
- La ruta del repositorio git del proyecto es `/home/u531780502/domains/citaspro.app/CitasPro`.
- La ruta pública del servidor es `/home/u531780502/domains/citaspro.app/public_html`.
- No se puede compilar con `npm run build` en el servidor porque no tiene NodeJS instalado. Compila en local, sube a Git la carpeta `public/build` y en el servidor cópiala a la carpeta pública mediante:
  `cp -rf /home/u531780502/domains/citaspro.app/CitasPro/public/build /home/u531780502/domains/citaspro.app/public_html/`

## Autenticación y Selector de Países
- La base de datos tiene una tabla `paises` cargada con prefijos telefónicos.
- Todo flujo de login o registro por teléfono debe consumir el endpoint `/api/paises` para mostrar los códigos y banderas correspondientes en el frontend.

## Diseño UI / Frontend
- **Desplegables (Selects):** NUNCA usar etiquetas `<select>` nativas de HTML en el proyecto Vue. Se debe usar SIEMPRE el componente `CustomSelect.vue` (ubicado en `resources/js/Pages/Components/CustomSelect.vue`) para mantener la coherencia del diseño en modo oscuro. Si un formulario requiere un select, hay que importar e implementar este componente en su lugar.
- **Ventanas Emergentes (Modals):** NUNCA usar funciones nativas del navegador como `window.confirm()` o `window.alert()` para notificaciones o confirmaciones (por ejemplo, al eliminar una cita). Se debe usar SIEMPRE el componente `ConfirmModal.vue` (ubicado en `resources/js/Pages/Components/ConfirmModal.vue`) o componentes equivalentes de Vue para mantener la coherencia del diseño oscuro y evitar que las ventanas desentonen.
- **Evitar Bucles Reactivos Infinitos (Watchers):** NUNCA crear observadores (`watch`) bidireccionales cruzados en Vue 3 (por ejemplo, observar un prop de tipo objeto y también emitir cambios sobre el mismo objeto). Si es necesario sincronizar un objeto o array local con una propiedad (`prop`), se debe verificar SIEMPRE si el contenido real es diferente utilizando comparaciones como `JSON.stringify(newVal) !== JSON.stringify(localVal)` antes de realizar una nueva asignación, previniendo que la pestaña del navegador se congele.

## Impresión y Exportación a PDF (Citas, Fichas y Comprobantes)
- **Disponibilidad de Formato PDF e Impresión:** Todo módulo de la plataforma que contenga registros importantes para el usuario o el negocio (tales como citas, fichas clínicas/historias clínicas, consultas médicas y recibos o comprobantes de pago) debe incluir de manera obligatoria una opción para **imprimir directamente desde el navegador** o **descargar/enviar como archivo PDF**.
- **Generación y Diseño:** Las vistas de impresión y los PDFs generados deben contar con hojas de estilo CSS optimizadas para impresión (`@media print`) que oculten menús de navegación, barras laterales y botones innecesarios, dejando únicamente la información limpia y estructurada. Para la generación de PDFs en backend, se utilizarán librerías estándar compatibles con Laravel (como `barryvdh/laravel-dompdf`).
- **Envío de Comprobantes:** Siempre que el sistema envíe una confirmación o recibo por correo electrónico al paciente o negocio, se debe adjuntar o enlazar una versión en PDF del documento para asegurar la portabilidad y el archivo del registro.
