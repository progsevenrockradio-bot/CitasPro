# Reglas del Entorno CitasPro (Hostinger & Backend)

## Infraestructura del Servidor (Hostinger)
- La ruta del repositorio git del proyecto es `/home/u531780502/domains/citaspro.app/CitasPro`.
- La ruta p煤blica del servidor es `/home/u531780502/domains/citaspro.app/public_html`.
- No se puede compilar con `npm run build` en el servidor porque no tiene NodeJS instalado. Compila en local, sube a Git la carpeta `public/build` y en el servidor c贸piala a la carpeta p煤blica mediante:
  `cp -rf /home/u531780502/domains/citaspro.app/CitasPro/public/build /home/u531780502/domains/citaspro.app/public_html/`

## Autenticaci贸n y Selector de Pa铆ses
- La base de datos tiene una tabla `paises` cargada con prefijos telef贸nicos.
- Todo flujo de login o registro por tel茅fono debe consumir el endpoint `/api/paises` para mostrar los c贸digos y banderas correspondientes en el frontend.

## Dise駉 UI / Frontend
- **Desplegables (Selects):** NUNCA usar etiquetas <select> nativas de HTML en el proyecto Vue. Se debe usar SIEMPRE el componente CustomSelect.vue (ubicado en esources/js/Pages/Components/CustomSelect.vue) para mantener la coherencia del dise駉 en modo oscuro. Si un formulario requiere un select, hay que importar e implementar este componente en su lugar.
