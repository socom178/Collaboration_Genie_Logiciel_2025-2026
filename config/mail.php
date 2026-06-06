<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

class MailService
{
    public static function sendMail($to, $subject, $body, $isHtml = true)
    {
        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;

            $mail->Username = 'audiasmansoiliya@gmail.com';
            $mail->Password = 'TON_APP_PASSWORD';

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('audiasmansoiliya@gmail.com', 'UATM GASA Système de Gestion Memoires');

            $mail->addAddress($to);

            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $body;

            return $mail->send();

        } catch (Exception $e) {
            return false;
        }
    }
}