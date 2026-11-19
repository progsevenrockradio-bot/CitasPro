<x-mail::message>
# ¡Hola, {{ $cita->cliente->nombre }}!

Te recordamos tu próxima cita en **{{ $cita->negocio->nombre }}**.

<x-mail::panel>
**Código de Referencia:** {{ $cita->codigo_referencia }}  
**Servicio:** {{ $cita->servicio->nombre }}  
**Profesional:** {{ $cita->profesional->nombre_completo }}  
**Fecha:** {{ $cita->fecha instanceof \Carbon\Carbon ? $cita->fecha->format('d/m/Y') : \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}  
**Hora:** {{ substr($cita->hora_inicio, 0, 5) }} ({{ $cita->negocio->getTimezoneEfectiva() }})  
**Total:** {{ number_format((float) $cita->precio_total, 2) }} {{ $cita->moneda ?? 'EUR' }}
</x-mail::panel>

@if($cita->negocio->direccion || $cita->negocio->ciudad)
**Dirección:**  
{{ trim(implode(', ', array_filter([$cita->negocio->direccion, $cita->negocio->ciudad]))) }}
@endif

Si necesitas reprogramar o cancelar tu cita, por favor contacta con el establecimiento lo antes posible.

Gracias por confiar en nosotros,<br>
**{{ $cita->negocio->nombre }}**
</x-mail::message>
