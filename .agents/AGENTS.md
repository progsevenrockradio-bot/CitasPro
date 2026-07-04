# Reglas del Entorno CitasPro (Hostinger & Backend)

## Infraestructura del Servidor (Hostinger)
- La ruta del repositorio git del proyecto es `/home/u531780502/domains/citaspro.app/CitasPro`.
- La ruta pública del servidor es `/home/u531780502/domains/citaspro.app/public_html`.
- No se puede compilar con `npm run build` en el servidor porque no tiene NodeJS instalado. Compila en local, sube a Git la carpeta `public/build` y en el servidor cópiala a la carpeta pública mediante:
  `cp -rf /home/u531780502/domains/citaspro.app/CitasPro/public/build /home/u531780502/domains/citaspro.app/public_html/`

## Autenticación y Selector de Países
- La base de datos tiene una tabla `paises` cargada con prefijos telefónicos.
- Todo flujo de login o registro por teléfono debe consumir el endpoint `/api/paises` para mostrar los códigos y banderas correspondientes en el frontend.
