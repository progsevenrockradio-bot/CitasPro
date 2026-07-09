<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - CitasPro</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS Básico Premium -->
    <style>
        :root {
            --color-bg: #09090b;
            --color-bg-card: #18181b;
            --color-primary: #3b82f6;
            --color-primary-hover: #2563eb;
            --color-text: #f4f4f5;
            --color-text-muted: #a1a1aa;
            --color-border: #27272a;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 20px;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: var(--color-text);
            text-decoration: none;
            background: linear-gradient(to right, var(--color-primary), #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-back {
            color: var(--color-text-muted);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }

        .btn-back:hover {
            color: var(--color-text);
        }

        h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #ffffff;
        }

        .last-update {
            color: var(--color-text-muted);
            font-size: 14px;
            margin-bottom: 30px;
        }

        .content {
            background-color: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        h2 {
            font-size: 22px;
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 15px;
            color: #ffffff;
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 8px;
        }

        h3 {
            font-size: 18px;
            font-weight: 600;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #f4f4f5;
        }

        p, ul, ol {
            color: var(--color-text-muted);
            margin-bottom: 20px;
        }

        li {
            margin-bottom: 8px;
        }

        a {
            color: var(--color-primary);
            text-decoration: none;
            transition: color 0.2s;
        }

        a:hover {
            color: var(--color-primary-hover);
            text-decoration: underline;
        }

        .table-responsive {
            overflow-x: auto;
            margin-bottom: 20px;
            border: 1px solid var(--color-border);
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--color-border);
        }

        th {
            background-color: rgba(255, 255, 255, 0.02);
            color: #ffffff;
            font-weight: 600;
        }

        td {
            color: var(--color-text-muted);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .alert-box {
            background-color: rgba(59, 130, 246, 0.05);
            border-left: 4px solid var(--color-primary);
            padding: 16px;
            border-radius: 4px 12px 12px 4px;
            margin-bottom: 20px;
        }

        .alert-box p {
            margin: 0;
            color: var(--color-text);
        }

        .footer {
            margin-top: 60px;
            text-align: center;
            color: var(--color-text-muted);
            font-size: 14px;
            border-top: 1px solid var(--color-border);
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="/" class="logo">CitasPro</a>
            <a href="javascript:history.back()" class="btn-back">&larr; Volver</a>
        </div>

        <h1>@yield('header_title')</h1>
        <div class="last-update">Última actualización: 9 de Julio de 2026</div>

        <div class="content">
            @yield('content')
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} CitasPro - Todos los derechos reservados.
        </div>
    </div>
</body>
</html>
