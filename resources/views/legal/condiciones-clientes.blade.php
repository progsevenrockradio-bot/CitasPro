@extends('legal.layout')

@section('title', 'Condiciones para Clientes')
@section('header_title', 'Condiciones para Clientes')

@section('content')
    <p>Las presentes condiciones regulan la relación entre los clientes finales (usuarios que reservan citas) y la plataforma CitasPro, así como las directrices que rigen el proceso de reserva online.</p>

    <h2>1. Relación Contractual y Rol de CitasPro</h2>
    <p>CitasPro actúa única y exclusivamente como **proveedor tecnológico intermediario**. Al reservar una cita con un profesional o negocio a través de citaspro.app, el cliente establece una relación contractual **directa** con dicho profesional/negocio. CitasPro no forma parte de dicha relación comercial, de salud o de servicio, ni es responsable de la calidad, tarifas, retrasos o cancelaciones del servicio contratado.</p>

    <h2>2. Datos Personales y Autorización</h2>
    <p>Al procesar una reserva online, el cliente autoriza expresamente que:</p>
    <ul>
        <li>Sus datos identificativos (nombre, apellidos, teléfono y correo electrónico) sean comunicados al profesional o negocio donde reserva, para posibilitar la correcta gestión de la cita.</li>
        <li>La plataforma CitasPro le remita alertas automatizadas relacionadas estrictamente con su reserva (confirmaciones, cambios de estado, recordatorios de hora y cancelaciones) mediante correo electrónico, SMS o WhatsApp.</li>
    </ul>

    <h2>3. Consentimiento para el Tratamiento de Datos de Salud (Ámbito Médico/Dental)</h2>
    <p>Si el negocio en el que está agendando pertenece a la categoría de salud (médicos, odontólogos, fisioterapeutas, psicólogos), el cliente autoriza explícitamente al profesional el tratamiento de sus datos clínicos e historia médica de acuerdo con el **Artículo 9.2.a del RGPD** y la **Ley 41/2002 de Autonomía del Paciente**.</p>
    <p>Dicho consentimiento se prestará de forma independiente mediante una casilla (checkbox) específica no premarcada en el formulario de reserva.</p>

    <h2>4. Menores de Edad</h2>
    <p>Los menores de 14 años no están autorizados a proporcionar datos personales a través de la plataforma sin el consentimiento previo e inequívoco de sus padres, tutores o representantes legales. Cualquier reserva para un menor debe ser procesada y autorizada por un adulto responsable.</p>

    <h2>5. Política de Cancelación y Reembolsos</h2>
    <p>Las condiciones de cancelación, plazos de preaviso y cobro de penalizaciones son establecidas de manera **autónoma** por cada negocio en la plataforma. Dichas políticas se muestran claramente antes de confirmar cada reserva. CitasPro no intermedia en la devolución de importes pagados online; cualquier reclamación financiera debe tramitarse directamente con el negocio donde se realizó el pago.</p>
@endsection
