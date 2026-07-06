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
