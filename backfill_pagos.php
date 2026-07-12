<?php
$citas = \App\Models\Cita::doesntHave('pago')->where('precio_total', '>', 0)->get();
foreach($citas as $cita) {
    \App\Models\Pago::create([
        'cita_id' => $cita->id,
        'cliente_id' => $cita->cliente_id,
        'negocio_id' => $cita->negocio_id,
        'monto' => $cita->precio_total,
        'monto_total' => $cita->precio_total,
        'metodo' => 'efectivo',
        'estado' => 'completado',
        'pagado_en' => now(),
        'moneda' => $cita->moneda ?? 'EUR'
    ]);
}
echo 'Pagos retroactivos generados: ' . $citas->count();
