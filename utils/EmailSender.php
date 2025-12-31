<?php
/**
 * Clase para envío de emails
 * 
 * Configurar credenciales SMTP en config/config.php:
 * 
 * define('SMTP_HOST', 'smtp.gmail.com');
 * define('SMTP_PORT', 587);
 * define('SMTP_USER', 'tu-email@gmail.com');
 * define('SMTP_PASS', 'tu-contraseña-app');
 * define('SMTP_FROM_NAME', 'Test Vocacional');
 * define('SMTP_FROM_EMAIL', 'noreply@test-vocacional.com');
 */

class EmailSender
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromName;
    private $fromEmail;

    public function __construct()
    {
        $this->host = defined('SMTP_HOST') ? SMTP_HOST : '';
        $this->port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $this->username = defined('SMTP_USER') ? SMTP_USER : '';
        $this->password = defined('SMTP_PASS') ? SMTP_PASS : '';
        $this->fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Test Vocacional';
        $this->fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@test-vocacional.com';
    }

    /**
     * Validar si SMTP está configurado
     */
    public function isConfigured()
    {
        return !empty($this->host) && !empty($this->username) && !empty($this->password);
    }

    /**
     * Enviar email de recuperación de contraseña
     */
    public function sendPasswordResetEmail($toEmail, $userName, $resetLink)
    {
        // Si SMTP no está configurado, usar mail() de PHP
        if (!$this->isConfigured()) {
            return $this->sendWithPhpMail($toEmail, $userName, $resetLink);
        }

        return $this->sendWithSMTP($toEmail, $userName, $resetLink);
    }

    /**
     * Enviar usando PHPMailer (si está disponible)
     */
    private function sendWithSMTP($toEmail, $userName, $resetLink)
    {
        try {
            // Usar PHPMailer si está disponible vía Composer
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return $this->sendWithPHPMailer($toEmail, $userName, $resetLink);
            }

            // Fallback: usar fsockopen (menos recomendado)
            return $this->sendWithFsockopen($toEmail, $userName, $resetLink);
        } catch (Exception $e) {
            error_log("Error enviando email SMTP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar con PHPMailer
     */
    private function sendWithPHPMailer($toEmail, $userName, $resetLink)
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->username;
            $mail->Password = $this->password;
            $mail->SMTPSecure = 'tls';
            $mail->Port = $this->port;

            // Remitente y destinatario
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail, $userName);

            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = 'Recuperar tu contraseña - Test Vocacional';
            $mail->Body = $this->getEmailTemplate($userName, $resetLink);
            $mail->AltBody = strip_tags($this->getEmailTemplate($userName, $resetLink));

            // Enviar
            return $mail->send();
        } catch (Exception $e) {
            error_log("Error con PHPMailer: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar con mail() de PHP
     */
    private function sendWithPhpMail($toEmail, $userName, $resetLink)
    {
        $subject = 'Recuperar tu contraseña - Test Vocacional';
        $message = $this->getEmailTemplate($userName, $resetLink);

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $this->fromName . " <" . $this->fromEmail . ">\r\n";

        return mail($toEmail, $subject, $message, $headers);
    }

    /**
     * Plantilla de email HTML
     */
    private function getEmailTemplate($userName, $resetLink)
    {
        $appName = defined('APP_NAME') ? APP_NAME : 'Test Vocacional';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 28px;
        }
        .content {
            color: #555;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .content p {
            margin: 15px 0;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .reset-button {
            background-color: #3498db;
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        .reset-button:hover {
            background-color: #2980b9;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .token-info {
            background-color: #f9f9f9;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            font-size: 12px;
            color: #666;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🎯 $appName</h1>
        </div>

        <div class="content">
            <p>Hola <strong>$userName</strong>,</p>

            <p>Recibimos una solicitud para recuperar tu contraseña. Si no realizaste esta solicitud, puedes ignorar este email de forma segura.</p>

            <p>Haz clic en el botón siguiente para crear una nueva contraseña:</p>

            <div class="button-container">
                <a href="$resetLink" class="reset-button">Recuperar Contraseña</a>
            </div>

            <p>O copia y pega este enlace en tu navegador:</p>
            <div class="token-info">
                $resetLink
            </div>

            <div class="warning">
                <strong>Importante:</strong> Este enlace expira en 1 hora. Si no lo usas en ese tiempo, deberás solicitar otro.
            </div>

            <p>Si tienes problemas, contacta con el administrador del sistema.</p>
        </div>

        <div class="footer">
            <p>&copy; 2025 $appName. Instituto Tecnológico Superior Vida Nueva</p>
            <p>Este es un email automático, por favor no responder.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
?>
