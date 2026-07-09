@extends('legal.layout')

@section('title', 'Acuerdo de Encargo de Tratamiento (DPA)')
@section('header_title', 'Acuerdo de Tratamiento de Datos (DPA)')

@section('content')
    <p>Este Acuerdo de Tratamiento de Datos (en adelante, DPA) complementa los Términos y Condiciones Generales y es vinculante entre el Profesional/Negocio (el **Responsable del Tratamiento**) y CitasPro (el **Encargado del Tratamiento**), de conformidad con el **Artículo 28 del Reglamento General de Protección de Datos (RGPD)**.</p>

    <h2>1. Objeto del Encargo</h2>
    <p>El Responsable contrata los servicios SaaS de CitasPro, lo que implica el acceso y almacenamiento por parte del Encargado de datos de carácter personal de los clientes y pacientes del Responsable.</p>

    <h2>2. Obligaciones del Encargado del Tratamiento (CitasPro)</h2>
    <p>CitasPro se compromete a:</p>
    <ul>
        <li>Tratar los datos personales únicamente siguiendo las instrucciones documentadas del Responsable, salvo que esté obligado a ello por el Derecho de la Unión o de los Estados miembros aplicable.</li>
        <li>Garantizar que las personas autorizadas para tratar datos personales se hayan comprometido a respetar la confidencialidad.</li>
        <li>Implementar las medidas de seguridad técnicas y organizativas necesarias de conformidad con el Artículo 32 del RGPD.</li>
        <li>Asistir al Responsable para permitirle responder a las solicitudes de ejercicio de derechos ARSO-ACOL por parte de sus clientes.</li>
        <li>Notificar al Responsable sin dilación indebida (máximo 48 horas) cualquier brecha de seguridad de los datos personales de la que tenga conocimiento.</li>
    </ul>

    <h2>3. Subencargados Autorizados</h2>
    <p>El Responsable autoriza expresamente la contratación de los siguientes proveedores de infraestructura necesarios para la prestación del servicio:</p>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Servicio Prestado</th>
                    <th>Ubicación de Servidores</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Hostinger</strong></td>
                    <td>Alojamiento en la nube y base de datos</td>
                    <td>Unión Europea (Países Bajos)</td>
                </tr>
                <tr>
                    <td><strong>Stripe Inc.</strong></td>
                    <td>Procesamiento de pagos integrados</td>
                    <td>EE.UU. (Privacy Shield) / UE</td>
                </tr>
                <tr>
                    <td><strong>Twilio Inc.</strong></td>
                    <td>Pasarela de envío de SMS y WhatsApp API</td>
                    <td>EE.UU. / UE</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2>4. Medidas de Seguridad Aplicadas</h2>
    <p>El Encargado aplicará las siguientes medidas para asegurar la confidencialidad e integridad de la información:</p>
    <ul>
        <li>Cifrado en tránsito mediante protocolos seguros HTTPS (SSL/TLS).</li>
        <li>Hash criptográfico de contraseñas de acceso (Bcrypt).</li>
        <li>Copias de seguridad diarias automatizadas con retención segura.</li>
        <li>Aislamiento de bases de datos y control de accesos restringidos para el staff del soporte.</li>
    </ul>

    <h2>5. Destino de los Datos al Término del Servicio</h2>
    <p>Una vez finalizada la relación contractual o solicitada la cancelación de la cuenta, CitasPro procederá a la **eliminación definitiva** de todos los datos del Responsable y de sus clientes almacenados en los servidores de producción en un plazo máximo de 72 horas, salvo que la ley española exija la conservación de determinados datos (por ejemplo, facturas emitidas).</p>
@endsection
