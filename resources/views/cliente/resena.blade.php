<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificar Servicio | CitasPro</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #111827;
            --card-bg: rgba(31, 41, 55, 0.6);
            --border-color: #374151;
            --primary: #6366F1;
            --primary-glow: rgba(99, 102, 241, 0.3);
            --text-color: #F9FAFB;
            --text-muted: #9CA3AF;
            --success: #10B981;
            --star-color: #F59E0B;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 480px;
            background-color: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            text-align: center;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            margin-bottom: 24px;
        }

        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 40px;
            margin-bottom: 12px;
            border: 2px solid var(--primary);
            box-shadow: 0 0 15px var(--primary-glow);
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 20px;
        }

        .servicio-tag {
            display: inline-block;
            background-color: rgba(99, 102, 241, 0.15);
            color: var(--primary);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        /* Sistema de Estrellas */
        .stars-container {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 24px 0;
            flex-direction: row-reverse; /* Permite hover hacia atrás con CSS puro */
        }

        .stars-container input {
            display: none;
        }

        .stars-container label {
            font-size: 38px;
            color: #374151;
            cursor: pointer;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .stars-container label:hover,
        .stars-container label:hover ~ label,
        .stars-container input:checked ~ label {
            color: var(--star-color);
            transform: scale(1.15);
        }

        .textarea-container {
            margin-bottom: 24px;
            text-align: left;
        }

        .textarea-label {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: block;
            font-weight: 500;
        }

        textarea {
            width: 100%;
            height: 120px;
            background-color: rgba(17, 24, 39, 0.8);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-color);
            padding: 12px;
            font-family: inherit;
            font-size: 15px;
            resize: none;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 8px var(--primary-glow);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s ease, box-shadow 0.2s ease, transform 0.1s ease;
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        .btn-submit:hover {
            background-color: #4F46E5;
            box-shadow: 0 6px 18px rgba(99, 102, 241, 0.5);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        /* Alertas */
        .alert {
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.15);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .success-box {
            text-align: center;
            animation: scaleIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes scaleIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .success-icon {
            font-size: 64px;
            color: var(--success);
            margin-bottom: 16px;
        }

        .success-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .success-desc {
            font-size: 15px;
            color: var(--text-muted);
            margin-bottom: 24px;
            line-height: 22px;
        }

        .btn-back {
            display: inline-block;
            text-decoration: none;
            color: var(--primary);
            font-weight: 600;
            font-size: 15px;
            transition: color 0.2s ease;
        }

        .btn-back:hover {
            color: #4F46E5;
        }
    </style>
</head>
<body>

    <div class="container">
        @if(session('success') || $yaResenado)
            <div class="success-box">
                <div class="success-icon">✓</div>
                <h2 class="success-title">¡Gracias por tu opinión!</h2>
                <p class="success-desc">
                    Tu calificación ya ha sido guardada. Nos ayuda muchísimo a seguir brindándote el mejor servicio.
                </p>
                <a href="{{ route('cliente.perfil', $cita->profesional_id) }}" class="btn-back">
                    Volver a {{ $cita->negocio->nombre }}
                </a>
            </div>
        @else
            <div class="header">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($cita->profesional->nombre) }}&background=6366F1&color=fff&size=100" class="avatar" alt="{{ $cita->profesional->nombre }}">
                <h1 class="title">¿Qué tal tu experiencia?</h1>
                <p class="subtitle">
                    Califica tu cita con <strong>{{ $cita->profesional->nombre }}</strong> en <strong>{{ $cita->negocio->nombre }}</strong>
                </p>
                <div class="servicio-tag">{{ $cita->servicio->nombre }}</div>
            </div>

            @if(session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('cliente.resena_submit', $cita->codigo_referencia) }}" method="POST">
                @csrf
                
                <!-- Estrellas (orden inverso en html para que funcione hover CSS) -->
                <div class="stars-container">
                    <input type="radio" name="calificacion" id="star-5" value="5" required>
                    <label for="star-5" title="Excelente">★</label>
                    
                    <input type="radio" name="calificacion" id="star-4" value="4">
                    <label for="star-4" title="Bueno">★</label>
                    
                    <input type="radio" name="calificacion" id="star-3" value="3">
                    <label for="star-3" title="Regular">★</label>
                    
                    <input type="radio" name="calificacion" id="star-2" value="2">
                    <label for="star-2" title="Malo">★</label>
                    
                    <input type="radio" name="calificacion" id="star-1" value="1">
                    <label for="star-1" title="Muy malo">★</label>
                </div>

                <div class="textarea-container">
                    <label for="comentario" class="textarea-label">Cuéntanos un poco más (opcional)</label>
                    <textarea name="comentario" id="comentario" placeholder="Escribe aquí tu opinión sobre el servicio..."></textarea>
                </div>

                <button type="submit" class="btn-submit">Enviar Calificación</button>
            </form>
        @endif
    </div>

</body>
</html>
