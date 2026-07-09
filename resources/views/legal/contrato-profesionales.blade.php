@extends('legal.layout')

@section('title', 'Contrato de Prestación de Servicios')
@section('header_title', 'Contrato de Prestación de Servicios SaaS')

@section('content')
    <p>El presente documento constituye el Contrato de Prestación de Servicios SaaS que rige la relación entre CitasPro (el Titular) y los profesionales o negocios que se registran y contratan un plan en nuestra plataforma.</p>

    <h2>1. Objeto del Contrato</h2>
    <p>El objeto de este contrato es el arrendamiento y derecho de uso de la plataforma digital CitasPro en la modalidad SaaS (Software as a Service) para que el profesional/negocio gestione sus reservas, profesionales, cobros e historial de clientes.</p>

    <h2>2. Tabla de Planes y Precios</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Característica</th>
                    <th>Plan Free</th>
                    <th>Plan Basic</th>
                    <th>Plan Pro</th>
                    <th>Plan Enterprise</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Precio (Sin IVA)</strong></td>
                    <td>Gratuito</td>
                    <td>9,99€ / mes</td>
                    <td>19,99€ / mes</td>
                    <td>A convenir</td>
                </tr>
                <tr>
                    <td><strong>Profesionales/Staff</strong></td>
                    <td>1</td>
                    <td>Hasta 3</td>
                    <td>Hasta 10</td>
                    <td>Ilimitados</td>
                </tr>
                <tr>
                    <td><strong>Límite Citas/mes</strong></td>
                    <td>50 citas</td>
                    <td>200 citas</td>
                    <td>1.000 citas</td>
                    <td>Personalizado</td>
                </tr>
                <tr>
                    <td><strong>Agenda</strong></td>
                    <td>Básica</td>
                    <td>Avanzada + Recordatorios</td>
                    <td>Completa + Google Calendar</td>
                    <td>API Completa</td>
                </tr>
                <tr>
                    <td><strong>Cobros Online</strong></td>
                    <td>❌</td>
                    <td>Stripe / MercadoPago</td>
                    <td>Stripe / MercadoPago + Redsys</td>
                    <td>Todos + Personalizados</td>
                </tr>
                <tr>
                    <td><strong>Branding CitasPro</strong></td>
                    <td>Sí (Visible)</td>
                    <td>No (Marca blanca)</td>
                    <td>No (Marca blanca)</td>
                    <td>No (Personalizado)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2>3. Facturación, Plazos y Cancelación</h2>
    <ul>
        <li><strong>Planes de Pago (Basic y Pro):</strong> Se facturarán mensualmente por adelantado. En caso de planes anuales, se cobrará el año completo por adelantado.</li>
        <li><strong>Cancelación:</strong> El profesional puede cancelar su suscripción en cualquier momento desde su panel. La cancelación del plan de pago detendrá los siguientes cobros recurrentes. No se realizarán devoluciones de importes ya cobrados ni reembolsos parciales.</li>
        <li><strong>Impago:</strong> Si un cobro de suscripción mensual es rechazado por la pasarela de pago, el sistema otorgará un plazo de 7 días naturales de cortesía. Transcurrido dicho plazo, CitasPro suspenderá el acceso a las funciones del plan de pago.</li>
    </ul>

    <h2>4. Responsabilidad en la Gestión de Datos</h2>
    <p>El profesional/negocio asume toda la responsabilidad sobre los datos que recopila de sus clientes mediante el software (especialmente de las fichas clínicas e historias médicas). El profesional se obliga a recabar los consentimientos RGPD explícitos de sus pacientes antes de cargar datos de categoría especial (Art. 9 RGPD).</p>

    <h2>5. Acuerdo de Nivel de Servicio (SLA)</h2>
    <p>CitasPro se compromete a garantizar una disponibilidad mensual de la plataforma de al menos el <strong>99,5%</strong> para los planes Pro y Enterprise, excluyendo las interrupciones debidas a mantenimientos preventivos programados (que se realizarán preferentemente en horario nocturno y serán comunicados con antelación).</p>
@endsection
