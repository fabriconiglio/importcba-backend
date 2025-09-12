<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Bienvenido a {{ $companyName }}!</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 300;
        }
        .content {
            padding: 30px;
        }
        .welcome-message {
            text-align: center;
            margin: 30px 0;
        }
        .welcome-message h2 {
            color: #667eea;
            margin-bottom: 15px;
        }
        .features {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        .features h3 {
            color: #667eea;
            margin-top: 0;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .feature-list li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        .cta-buttons {
            text-align: center;
            margin: 30px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            margin: 10px;
        }
        .cta-button.secondary {
            background: #6c757d;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .social-links {
            margin: 15px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #667eea;
            text-decoration: none;
        }
        .help-section {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        .help-section h4 {
            color: #1976d2;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $companyName }}</h1>
            <p>¡Tu cuenta ha sido creada exitosamente!</p>
        </div>

        <div class="content">
            <div class="welcome-message">
                <h2>¡Hola {{ $user->name }}!</h2>
                <p>Nos complace darte la bienvenida a {{ $companyName }}. Tu cuenta ha sido creada exitosamente y ya puedes comenzar a disfrutar de todos nuestros servicios.</p>
            </div>

            <div class="features">
                <h3>🎉 ¿Qué puedes hacer ahora?</h3>
                <ul class="feature-list">
                    <li>Explorar nuestro catálogo de productos</li>
                    <li>Crear listas de deseos personalizadas</li>
                    <li>Recibir ofertas exclusivas por email</li>
                    <li>Realizar compras de forma segura</li>
                    <li>Seguir el estado de tus pedidos</li>
                    <li>Acceder a tu historial de compras</li>
                </ul>
            </div>

            <div class="cta-buttons">
                <a href="{{ $catalogUrl }}" class="cta-button">Explorar Productos</a>
                <a href="{{ $loginUrl }}" class="cta-button secondary">Iniciar Sesión</a>
            </div>

            <div class="help-section">
                <h4>💡 ¿Necesitas ayuda?</h4>
                <p>Si tienes alguna pregunta o necesitas asistencia, nuestro equipo de soporte está aquí para ayudarte:</p>
                <p><strong>Email:</strong> <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></p>
                <p><strong>Horario de atención:</strong> Lunes a Viernes de 9:00 AM a 6:00 PM</p>
            </div>

            <p style="text-align: center; margin-top: 30px;">
                ¡Gracias por unirte a {{ $companyName }}! Esperamos que disfrutes de tu experiencia de compra.
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $companyName }}. Todos los derechos reservados.</p>
            <p>Este email fue enviado a {{ $user->email }}</p>
            <div class="social-links">
                @if(isset($socialLinks['instagram']))
                <a href="{{ $socialLinks['instagram'] }}">Instagram</a>
                @endif
                @if(isset($socialLinks['twitter']))
                <a href="{{ $socialLinks['twitter'] }}">Twitter</a>
                @endif
            </div>
        </div>
    </div>
</body>
</html> 