<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CitasPro | Sistema de Reservas para Negocios y Profesionales</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS Integrado Premium -->
    <style>
        :root {
            --bg: #0B0F19;
            --bg-card: rgba(17, 24, 39, 0.7);
            --border: rgba(55, 65, 81, 0.5);
            --primary: #6366F1;
            --primary-hover: #4F46E5;
            --primary-glow: rgba(99, 102, 241, 0.25);
            --text: #F9FAFB;
            --text-muted: #9CA3AF;
            --success: #10B981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.5;
            overflow-x: hidden;
        }

        /* Fondo Decorativo */
        .blur-bg {
            position: absolute;
            top: -10%;
            left: 20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(0,0,0,0) 70%);
            z-index: -1;
            filter: blur(80px);
            pointer-events: none;
        }

        header {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 10;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 24px;
            letter-spacing: -0.5px;
            color: var(--text);
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background-color: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 15px var(--primary-glow);
        }

        .logo-icon span {
            font-size: 18px;
            color: white;
        }

        .nav-links {
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .nav-link {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.2s ease;
        }

        .nav-link:hover {
            color: var(--text);
        }

        .btn-header {
            padding: 10px 20px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            color: var(--text);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .btn-header:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
        }

        /* Hero Section */
        .hero {
            max-width: 1200px;
            margin: 60px auto 80px auto;
            padding: 0 20px;
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .hero-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 30px;
            background-color: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            color: var(--primary);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            letter-spacing: 0.5px;
        }

        .hero-title {
            font-size: 56px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1.5px;
            margin-bottom: 24px;
            background: linear-gradient(135deg, #FFF 60%, #9CA3AF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 38px; }
        }

        .hero-subtitle {
            font-size: 18px;
            color: var(--text-muted);
            max-width: 640px;
            margin: 0 auto 36px auto;
            font-weight: 400;
        }

        .hero-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
        }

        .btn-primary {
            padding: 14px 28px;
            border-radius: 12px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 4px 20px var(--primary-glow);
            transition: background-color 0.2s ease, box-shadow 0.2s ease, transform 0.1s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            box-shadow: 0 6px 24px rgba(99, 102, 241, 0.4);
            transform: translateY(-1px);
        }

        .btn-secondary {
            padding: 14px 28px;
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            color: var(--text);
            text-decoration: none;
            font-weight: 700;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--text-muted);
        }

        /* Características */
        .features {
            max-width: 1200px;
            margin: 0 auto 100px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .feature-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 30px;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background-color: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 20px;
        }

        .feature-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .feature-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 22px;
        }

        /* Pricing Section */
        .pricing-section {
            max-width: 1200px;
            margin: 0 auto 100px auto;
            padding: 0 20px;
            text-align: center;
        }

        .pricing-header {
            margin-bottom: 48px;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            align-items: stretch;
        }

        .pricing-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 35px 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.3s ease, border-color 0.3s ease;
            position: relative;
        }

        .pricing-card-featured {
            border-color: var(--primary);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.15);
        }

        .pricing-card-featured::before {
            content: 'RECOMENDADO';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--primary);
            color: white;
            padding: 4px 14px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .plan-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .plan-price {
            font-size: 40px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 20px;
        }

        .plan-price span {
            font-size: 16px;
            font-weight: 500;
            color: var(--text-muted);
        }

        .plan-features {
            list-style: none;
            text-align: left;
            margin-bottom: 30px;
            flex-grow: 1;
        }

        .plan-features li {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .plan-features li::before {
            content: '✓';
            color: var(--success);
            font-weight: bold;
        }

        .btn-pricing {
            display: block;
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            transition: background-color 0.2s ease;
            text-align: center;
        }

        .btn-pricing-secondary {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-pricing-secondary:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .btn-pricing-primary {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 15px var(--primary-glow);
        }

        .btn-pricing-primary:hover {
            background-color: var(--primary-hover);
        }

        /* Negocios Compatibles */
        .business-section {
            max-width: 1200px;
            margin: 0 auto 100px auto;
            padding: 0 20px;
            text-align: center;
        }

        .business-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .business-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            text-align: left;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .business-card:hover {
            transform: scale(1.02);
            border-color: var(--primary);
        }

        .business-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .business-icon {
            font-size: 24px;
        }

        .business-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
        }

        .business-desc {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 20px;
        }

        /* Footer */
        footer {
            border-top: 1px solid var(--border);
            padding: 40px 20px;
            text-align: center;
            font-size: 14px;
            color: var(--text-muted);
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-logo {
            font-weight: 800;
            color: var(--text);
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <div class="blur-bg"></div>

    <header>
        <div class="logo-container">
            <div class="logo-icon">
                <span>C</span>
            </div>
            CitasPro
        </div>
        <div class="nav-links">
            <a href="#planes" class="nav-link">Planes</a>
            <a href="/panel" class="nav-link" style="color: var(--primary); font-weight: bold;">Acceso Médicos/Negocios</a>
            <a href="/api/reset-demo" class="nav-link" target="_blank">Reset Demo</a>
        </div>
    </header>

    <main>
        <!-- Hero -->
        <section class="hero">
            <div class="hero-badge">SaaS AUTOMATIZADO DE RESERVAS</div>
            <h1 class="hero-title">Tu negocio de citas<br>en piloto automático</h1>
            <p class="hero-subtitle">
                Gestiona tus profesionales, configura servicios y horarios, cobra de forma segura y automatiza recordatorios por WhatsApp y SMS para reducir inasistencias.
            </p>
            <div class="hero-actions">
                <a href="/panel" class="btn-primary" style="background-color: var(--text); color: var(--bg);">Entrar a mi Panel</a>
                <a href="#planes" class="btn-primary">Ver Planes</a>
                <a href="/api/reset-demo" target="_blank" class="btn-secondary">Reset Base de Datos</a>
            </div>
        </section>

        <!-- Características -->
        <section class="features">
            <div class="feature-card">
                <div class="feature-icon">⏱</div>
                <h3 class="feature-title">Agenda Online 24/7</h3>
                <p class="feature-desc">
                    Permite que tus clientes elijan fecha y hora directamente desde el portafolio público del profesional, consultando disponibilidad real.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3 class="feature-title">Recordatorios por WhatsApp</h3>
                <p class="feature-desc">
                    El sistema envía recordatorios automáticos 24h y 1h antes de la cita para asegurar la asistencia de los clientes sin que hagas nada.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💳</div>
                <h3 class="feature-title">Pagos con Tarjeta (Stripe)</h3>
                <p class="feature-desc">
                    Integra cobros en efectivo y tarjeta con Stripe Billing para que los clientes paguen al reservar el servicio de forma segura.
                </p>
            </div>
        </section>

        <!-- Negocios Compatibles -->
        <section class="business-section">
            <div class="pricing-header">
                <h2 style="font-size: 36px; font-weight: 800; margin-bottom: 12px;">Diseñado para tu negocio</h2>
                <p style="color: var(--text-muted); font-size: 16px;">CitasPro se adapta a una gran variedad de industrias y profesionales independientes.</p>
            </div>
            
            <div class="business-grid">
                <div class="business-card">
                    <div class="business-header">
                        <span class="business-icon">💇‍♀️</span>
                        <h4 class="business-title">Estética y Belleza</h4>
                    </div>
                    <p class="business-desc">Pelas, salones de manicura, barberías y spas. Muestra tu portafolio visual y gestiona los turnos de estilistas en tiempo real.</p>
                </div>
                
                <div class="business-card">
                    <div class="business-header">
                        <span class="business-icon">🐕</span>
                        <h4 class="business-title">Servicios para Mascotas</h4>
                    </div>
                    <p class="business-desc">Peluquerías caninas, veterinarios y paseadores. Agenda citas basadas en la duración del servicio y tipo de mascota.</p>
                </div>
                
                <div class="business-card">
                    <div class="business-header">
                        <span class="business-icon">🩺</span>
                        <h4 class="business-title">Salud y Bienestar</h4>
                    </div>
                    <p class="business-desc">Fisioterapeutas, psicólogos, nutricionistas y dentistas. Reduce el ausentismo con recordatorios y gestiona el historial.</p>
                </div>

                <div class="business-card">
                    <div class="business-header">
                        <span class="business-icon">💼</span>
                        <h4 class="business-title">Consultoría y Coaching</h4>
                    </div>
                    <p class="business-desc">Mentores, coaches, abogados y asesores financieros. Sincroniza con Google Calendar y automatiza los enlaces de videollamadas.</p>
                </div>

                <div class="business-card">
                    <div class="business-header">
                        <span class="business-icon">🎓</span>
                        <h4 class="business-title">Clases y Educación</h4>
                    </div>
                    <p class="business-desc">Profesores particulares, escuelas de conducción y entrenadores personales. Permite que tus estudiantes reserven clases individuales.</p>
                </div>

                <div class="business-card">
                    <div class="business-header">
                        <span class="business-icon">🍔</span>
                        <h4 class="business-title">Comida y Reservas</h4>
                    </div>
                    <p class="business-desc">Hamburgueserías, salas de eventos y catas. Controla el aforo y gestiona las reservas de mesas o turnos de comida sin fricción.</p>
                </div>
            </div>
        </section>

        <!-- Planes -->
        <section class="pricing-section" id="planes">
            <div class="pricing-header">
                <h2 style="font-size: 36px; font-weight: 800; margin-bottom: 12px;">Planes sencillos y transparentes</h2>
                <p style="color: var(--text-muted); font-size: 16px;">Comienza gratis y mejora según tu negocio crezca.</p>
            </div>
            
            <div class="pricing-grid">
                <!-- Free -->
                <div class="pricing-card">
                    <div>
                        <div class="plan-name">FREE</div>
                        <div class="plan-price">0 €<span>/mes</span></div>
                        <ul class="plan-features">
                            <li>1 Profesional</li>
                            <li>Hasta 12 citas al mes</li>
                            <li>Portafolio visual habilitado</li>
                            <li>Soporte de la comunidad</li>
                        </ul>
                    </div>
                    <a href="https://reservas.jmfn8n.top" class="btn-pricing btn-pricing-secondary">Empezar Gratis</a>
                </div>

                <!-- Basic -->
                <div class="pricing-card">
                    <div>
                        <div class="plan-name">BASIC</div>
                        <div class="plan-price">9 €<span>/mes</span></div>
                        <ul class="plan-features">
                            <li>1 Profesional (Single Pro)</li>
                            <li>Hasta 80 citas al mes</li>
                            <li>Portafolio de fotos públicas</li>
                            <li>WhatsApp / SMS de respaldo</li>
                        </ul>
                    </div>
                    <a href="https://reservas.jmfn8n.top" class="btn-pricing btn-pricing-secondary">Contratar Basic</a>
                </div>

                <!-- Pro -->
                <div class="pricing-card pricing-card-featured">
                    <div>
                        <div class="plan-name" style="color: var(--primary);">PRO</div>
                        <div class="plan-price">17 €<span>/mes</span></div>
                        <ul class="plan-features">
                            <li>Hasta 5 Profesionales</li>
                            <li>Citas ilimitadas</li>
                            <li>Pasarela Stripe Integrada</li>
                            <li>Recordatorios prioritarios</li>
                        </ul>
                    </div>
                    <a href="https://reservas.jmfn8n.top" class="btn-pricing btn-pricing-primary">Mejorar a Pro</a>
                </div>

                <!-- Enterprise -->
                <div class="pricing-card">
                    <div>
                        <div class="plan-name">ENTERPRISE</div>
                        <div class="plan-price">30 €<span>/mes</span></div>
                        <ul class="plan-features">
                            <li>Profesionales ilimitados</li>
                            <li>Citas ilimitadas</li>
                            <li>Soporte dedicado</li>
                            <li>Integración a medida</li>
                        </ul>
                    </div>
                    <a href="https://reservas.jmfn8n.top" class="btn-pricing btn-pricing-secondary">Contactar Ventas</a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-logo">CitasPro</div>
        <p>&copy; 2026 CitasPro SaaS. Todos los derechos reservados.</p>
    </footer>

</body>
</html>
