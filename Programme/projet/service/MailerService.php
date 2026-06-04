<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

class MailerService
{
    public static function sendMail($to, $subject, $body, $isHtml = true)
    {
        $mail = new PHPMailer(true);

        try {

            // CONFIG SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;

            $mail->Username = 'audiasmansoiliya@gmail.com';
            $mail->Password = 'jhiq yfyu zenv skjd';

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // EXPÉDITEUR
            $mail->setFrom('audiasmansoiliya@gmail.com', 'Gestion Memoires');

            // DESTINATAIRE
            $mail->addAddress($to);

            // CONTENU
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $body;

            return $mail->send();

        } catch (Exception $e) {
            return false;
        }
    }
}