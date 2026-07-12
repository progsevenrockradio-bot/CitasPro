@component('mail::message')
@if($rol === 'paciente')
# Hola {{ $cita->cliente->nombre }},

Tu cita ha sido confirmada en **{{ $cita->negocio->nombre }}**. Aquí tienes los detalles:
@else
# Hola {{ $cita->negocio->nombre }},

Se ha registrado una nueva cita en tu negocio. Detalles:
@endif

@component('mail::table')
| Campo | Detalle |
| :--- | :--- |
| **Código de Referencia** | `{{ $cita->codigo_referencia }}` |
| **Servicio** | {{ $cita->servicio->nombre }} |
| **Profesional** | {{ $cita->profesional->nombre_completo }} |
| **Fecha** | {{ $cita->fecha->format('d/m/Y') }} |
| **Hora** | {{ substr($cita->hora_inicio, 0, 5) }} |
| **Cliente** | {{ $cita->cliente->nombre_completo }} ({{ $cita->cliente->telefono }}) |
@if($cita->notas_cliente)
| **Notas** | {{ $cita->notas_cliente }} |
@endif
@endcomponent

@if($rol === 'paciente')
Si necesitas realizar algún cambio o tienes dudas, por favor contáctanos al **{{ $cita->negocio->telefono }}**.

¡Te esperamos!
@else
Por favor, asegúrate de tener todo listo para atender al cliente en el horario agendado.
@endif

Gracias,<br>
El equipo de {{ $cita->negocio->nombre }}
@endcomponent
