@extends('legal.layout')

@section('title', 'Política de Privacidad')
@section('header_title', 'Política de Privacidad')

@section('content')
    <p>La presente Política de Privacidad describe cómo CitasPro recopila, utiliza y protege los datos personales de los profesionales registrados y de los clientes que reservan citas a través de nuestra plataforma, de conformidad con el Reglamento General de Protección de Datos (RGPD) (UE) 2016/679 y la Ley Orgánica 3/2018 (LOPDGDD).</p>

    <h2>1. Responsable del Tratamiento</h2>
    <ul>
        <li><strong>Titular:</strong> JOSE FONT</li>
        <li><strong>NIF:</strong> Y8380813H</li>
        <li><strong>Domicilio:</strong> CALLE HERMANOS GONZALVEZ SELVA NRO 96, PISO 4 PUERTA IZQ., Elche (Alicante, España)</li>
        <li><strong>Email:</strong> jmsync.es@gmail.com</li>
    </ul>

    <h2>2. Datos Recogidos y Finalidades</h2>
    
    <h3>2.1 Datos de Profesionales / Negocios</h3>
    <p>Recopilamos: nombre y apellidos, NIF/CIF, dirección del negocio, teléfono, correo electrónico, datos de facturación y pasarela de pago configurada (ej. llaves API de Stripe/Redsys), fotos de portafolio y horarios de agenda.</p>
    <p><strong>Finalidad:</strong> Prestar el servicio SaaS contratado, facturación, soporte, mantenimiento y comunicaciones administrativas.</p>
    <p><strong>Base legal:</strong> Ejecución de un contrato (Art. 6.1.b del RGPD).</p>

    <h3>2.2 Datos de Clientes (Usuarios Finales)</h3>
    <p>Recopilamos: nombre, teléfono, correo electrónico, historial de reservas y, de forma especial, datos de salud o clínicos si el negocio donde reservan es de ámbito médico o dental.</p>
    <p><strong>Finalidad:</strong> Gestionar la reserva de la cita, enviar recordatorios automáticos (SMS, email, WhatsApp) e integrar la historia clínica digital requerida por el profesional.</p>
    <p><strong>Base legal:</strong> Ejecución de la reserva (Art. 6.1.b del RGPD) y consentimiento explícito en caso de datos de salud de categoría especial (Art. 9.2.a del RGPD).</p>

    <h2>3. Plazos de Conservación</h2>
    <ul>
        <li><strong>Datos del Profesional:</strong> Mientras dure la relación comercial y durante los 5 años posteriores en cumplimiento de obligaciones fiscales.</li>
        <li><strong>Datos de Clientes:</strong> Durante la vigencia de su relación con el negocio, y como máximo 1 año tras su última cita registrada (salvo que aplique normativa de historias clínicas).</li>
        <li><strong>Datos de Salud/Clínicos:</strong> Mínimo 5 años según la Ley 41/2002 de Autonomía del Paciente.</li>
    </ul>

    <h2>4. Cesión a Terceros</h2>
    <p>Los datos no se cederán a terceros, excepto a los proveedores indispensables para el funcionamiento del servicio (Subencargados de Tratamiento):</p>
    <ul>
        <li><strong>Alojamiento:</strong> Hostinger (Servidores ubicados en la Unión Europea).</li>
        <li><strong>Procesamiento de pagos:</strong> Stripe, Redsys y MercadoPago (según configuración).</li>
        <li><strong>Notificaciones:</strong> Twilio (WhatsApp/SMS) y servicios de correo SMTP.</li>
    </ul>

    <h2>5. Derechos del Usuario (ARSO + ACOL)</h2>
    <p>Puede ejercitar sus derechos de Acceso, Rectificación, Supresión (Derecho al olvido), Oposición, Limitación del tratamiento, Portabilidad de sus datos y a no ser objeto de decisiones individuales automatizadas.</p>
    <p>Para ejercitar estos derechos, envíe una solicitud firmada por correo electrónico a <strong>jmsync.es@gmail.com</strong> indicando como asunto "Derechos ARSO-ACOL" y adjuntando una fotocopia de su DNI o documento equivalente.</p>

    <div class="alert-box">
        <p><strong>Uso Médico / Dental:</strong> Si utilizas CitasPro para gestionar expedientes clínicos, CitasPro actúa exclusivamente como encargado del tratamiento. El profesional del negocio es el Responsable del Tratamiento primario y debe obtener el consentimiento y custodiar debidamente las historias de acuerdo con la legislación sanitaria.</p>
    </div>
@endsection
