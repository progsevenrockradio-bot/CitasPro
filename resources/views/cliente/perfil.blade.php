<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva con {{ $profesional->nombre }} | {{ $profesional->negocio->nombre }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Propio -->
    <link rel="stylesheet" href="{{ asset('css/cliente.css') }}">
    
    <!-- PWA / Meta tags -->
    <meta name="theme-color" content="#121212">
    <meta name="description" content="Agenda tu cita con {{ $profesional->nombre_completo }}, especialista en {{ $profesional->especialidad }}.">

    <!-- Datos para JS -->
    <script>
        window.AppData = {
            profesional_id: {{ $profesional->id }},
            servicios: @json($profesional->servicios)
        };
    </script>
</head>
<body>

    <!-- Header / Cover -->
    <div class="cover-photo">
        <div class="cover-gradient"></div>
    </div>

    <!-- Info del Profesional -->
    <div class="profile-section">
        <div class="profile-avatar">
            <img src="{{ asset('img/avatar-default.png') }}" alt="{{ $profesional->nombre }}" id="avatar-img" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($profesional->nombre) }}&background=2C2C2C&color=fff&size=120'">
        </div>
        <h1 class="profile-name">{{ $profesional->nombre }} {{ $profesional->apellido }}</h1>
        <p class="profile-specialty">{{ $profesional->especialidad }}</p>
        
        <div class="business-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            {{ $profesional->negocio->nombre }}
        </div>
    </div>

    <!-- Portafolio -->
    <div class="portfolio-container">
        <h2 class="section-title">Trabajos Destacados</h2>
        
        @if($profesional->portafolios->isEmpty())
            <div class="empty-state">
                <p>Aún no hay fotos en el portafolio.</p>
            </div>
        @else
            <div class="masonry-grid">
                @foreach($profesional->portafolios as $item)
                    <div class="masonry-item">
                        @if($item->tipo === 'imagen')
                            <img src="{{ $item->url }}" alt="{{ $item->titulo ?? 'Portafolio' }}" loading="lazy">
                        @else
                            <video src="{{ $item->url }}" muted loop playsinline autoplay></video>
                        @endif
                        @if($item->titulo)
                            <div class="item-overlay">
                                <span>{{ $item->titulo }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Espaciador para el botón fijo -->
    <div class="bottom-spacer"></div>

    <!-- Botón Flotante -->
    <button class="fab-button" onclick="openBookingModal()">
        Agendar Cita
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
    </button>

    <!-- Modal de Reserva (Full Screen on Mobile, Centered on Desktop) -->
    <div id="booking-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <button class="btn-back" id="btn-modal-back" onclick="prevStep()" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </button>
                <h3 id="modal-title">Selecciona un Servicio</h3>
                <button class="btn-close" onclick="closeBookingModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            
            <div class="modal-body">
                <!-- Paso 1: Servicios -->
                <div id="step-1" class="booking-step active">
                    <div class="services-list" id="services-list-container">
                        @foreach($profesional->servicios as $servicio)
                            <div class="service-card" onclick="selectService({{ $servicio->id }}, '{{ addslashes($servicio->nombre) }}', {{ $servicio->precio }}, {{ $servicio->duracion_min }})">
                                <div class="service-info">
                                    <h4>{{ $servicio->nombre }}</h4>
                                    <span class="duration">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        {{ $servicio->duracion_min }} min
                                    </span>
                                </div>
                                <div class="service-price">
                                    ${{ number_format($servicio->precio, 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Paso 2: Fecha y Hora -->
                <div id="step-2" class="booking-step hidden">
                    <div class="date-selector">
                        <label>Elige una fecha:</label>
                        <input type="date" id="booking-date" min="{{ date('Y-m-d') }}" onchange="fetchAvailability(this.value)">
                    </div>
                    
                    <div id="slots-container" class="slots-container">
                        <div class="empty-state">Selecciona una fecha para ver los horarios disponibles.</div>
                    </div>
                </div>

                <!-- Paso 3: Datos del Cliente -->
                <div id="step-3" class="booking-step hidden">
                    <div class="form-group">
                        <label for="client-name">Nombre</label>
                        <input type="text" id="client-name" placeholder="Ej. Ana" required>
                    </div>
                    <div class="form-group">
                        <label for="client-lastname">Apellido</label>
                        <input type="text" id="client-lastname" placeholder="Ej. Gómez" required>
                    </div>
                    <div class="form-group">
                        <label for="client-phone">Teléfono (WhatsApp)</label>
                        <input type="tel" id="client-phone" placeholder="+34 600 000 000" required>
                        <small>Te enviaremos la confirmación a este número.</small>
                    </div>
                </div>

                <!-- Paso 4: Confirmación -->
                <div id="step-4" class="booking-step hidden">
                    <div class="summary-card">
                        <h4>Resumen de tu cita</h4>
                        <div class="summary-row">
                            <span>Servicio:</span>
                            <span id="summary-service">...</span>
                        </div>
                        <div class="summary-row">
                            <span>Fecha:</span>
                            <span id="summary-date">...</span>
                        </div>
                        <div class="summary-row">
                            <span>Hora:</span>
                            <span id="summary-time">...</span>
                        </div>
                        <div class="summary-row">
                            <span>Total a pagar:</span>
                            <span id="summary-price" class="highlight-price">...</span>
                        </div>
                    </div>
                </div>
                
                <!-- Pantalla de Éxito -->
                <div id="step-success" class="booking-step hidden success-step">
                    <div class="success-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <h3>¡Cita Confirmada!</h3>
                    <p>Hemos registrado tu reserva con éxito.</p>
                    <button class="btn-primary" onclick="closeBookingModal()">Volver al perfil</button>
                </div>
                
                <!-- Loading Overlay -->
                <div id="modal-loader" class="modal-loader hidden">
                    <div class="spinner"></div>
                </div>
            </div>
            
            <div class="modal-footer" id="modal-footer" style="display:none;">
                <button class="btn-primary" id="btn-next" onclick="nextStep()">Continuar</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/reserva.js') }}"></script>
</body>
</html>
