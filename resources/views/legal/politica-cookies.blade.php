@extends('legal.layout')

@section('title', 'Política de Cookies')
@section('header_title', 'Política de Cookies')

@section('content')
    <p>Este sitio web utiliza cookies propias y de terceros para recopilar información que ayuda a optimizar su visita y a mejorar la plataforma CitasPro, de acuerdo con el artículo 22 de la LSSI y la guía sobre el uso de cookies de la AEPD.</p>

    <h2>1. ¿Qué son las cookies?</h2>
    <p>Las cookies son pequeños archivos de texto que se descargan y almacenan en el navegador de su ordenador, smartphone o tableta al acceder a determinadas páginas web. Permiten que un sitio web, entre otras cosas, almacene y recupere información sobre los hábitos de navegación del usuario o de su equipo.</p>

    <h2>2. Tipos de Cookies Utilizadas</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Nombre</th>
                    <th>Finalidad</th>
                    <th>Duración</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Técnicas (Esenciales)</strong></td>
                    <td>XSRF-TOKEN<br>laravel_session<br>citaspro_session</td>
                    <td>Seguridad contra ataques CSRF, mantener activa la sesión del usuario profesional y cliente, recordar el estado del login.</td>
                    <td>Sesión / 2 horas</td>
                </tr>
                <tr>
                    <td><strong>Preferencias</strong></td>
                    <td>citaspro_theme<br>citaspro_lang</td>
                    <td>Recordar la preferencia de tema (claro/oscuro) e idioma del panel.</td>
                    <td>12 meses</td>
                </tr>
                <tr>
                    <td><strong>Analíticas (Terceros)</strong></td>
                    <td>_ga, _gid (Google Analytics)<br>_clck (Microsoft Clarity)</td>
                    <td>Medición anónima de las páginas más visitadas, tiempos de navegación e interacción técnica para mejorar las características de la app.</td>
                    <td>12 a 24 meses</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2>3. Gestión de Cookies y Consentimiento</h2>
    <p>Al entrar por primera vez en CitasPro se le presentará un banner de consentimiento que le permitirá configurar, aceptar o rechazar en bloque las cookies que requieran consentimiento legal (analíticas y de personalización).</p>
    <p>Las cookies esenciales técnicas no pueden ser deshabilitadas ya que son obligatorias para el correcto funcionamiento de las cuentas y la seguridad del sitio web.</p>

    <h2>4. Cómo Deshabilitar las Cookies desde su Navegador</h2>
    <p>Puede usted restringir, bloquear o borrar las cookies de CitasPro o de cualquier otra página web utilizando su propio navegador de internet:</p>
    <ul>
        <li><a href="https://support.google.com/chrome/answer/95647?hl=es" target="_blank">Google Chrome</a></li>
        <li><a href="https://support.mozilla.org/es/kb/habilitar-y-deshabilitar-cookies-sitios-web-rastrear-preferencias" target="_blank">Mozilla Firefox</a></li>
        <li><a href="https://support.apple.com/es-es/guide/safari/sfri11471/mac" target="_blank">Apple Safari</a></li>
        <li><a href="https://support.microsoft.com/es-es/microsoft-edge/eliminar-y-administrar-cookies-168dab11-0753-242d-fccf-183b6a226af8" target="_blank">Microsoft Edge</a></li>
    </ul>
@endsection
