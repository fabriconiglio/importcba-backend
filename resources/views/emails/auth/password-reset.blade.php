<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña</title>
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
            background: #2563eb;
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
        .reset-button {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            margin: 20px 0;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .warning-box h4 {
            margin: 0 0 10px 0;
            color: #856404;
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
        .token-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-family: monospace;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $companyName }}</h1>
            <p>Recuperación de Contraseña</p>
        </div>

        <div class="content">
            <h2>Hola {{ $user->name }},</h2>
            
            <p>Has solicitado restablecer tu contraseña. Haz clic en el botón de abajo para crear una nueva contraseña:</p>

            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="reset-button">Restablecer Contraseña</a>
            </div>

            <p>Si el botón no funciona, puedes copiar y pegar el siguiente enlace en tu navegador:</p>
            
            <div class="token-info">
                {{ $resetUrl }}
            </div>

            <div class="warning-box">
                <h4>⚠️ Importante</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Este enlace expira el {{ $expiresAt }}</li>
                    <li>Si no solicitaste este cambio, puedes ignorar este email</li>
                    <li>Tu contraseña actual no cambiará hasta que completes el proceso</li>
                </ul>
            </div>

            <p>Si tienes problemas para acceder a tu cuenta, contacta con nuestro equipo de soporte:</p>
            <p><a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></p>

            <p>¡Gracias por usar {{ $companyName }}!</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $companyName }}. Todos los derechos reservados.</p>
            <p>Este email fue enviado a {{ $user->email }}</p>
            <div class="social-links">
                <a href="#">Facebook</a>
                <a href="#">Instagram</a>
                <a href="#">Twitter</a>
            </div>
        </div>
    </div>
</body>
</html> 