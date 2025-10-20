<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    private PHPMailer $mailer;

    public function __construct(?PHPMailer $mailer = null)
    {
        $this->mailer = $mailer ?? new PHPMailer(true);
        $this->configure();
    }

    public function send(string $toEmail, string $toName, string $subject, string $body): bool
    {
        try {
            $this->mailer->clearAllRecipients();
            $this->mailer->setFrom(
                Config::get('MAIL_FROM', 'contact@example.com'),
                Config::get('MAIL_FROM_NAME', 'Foot Fields')
            );
            $this->mailer->addAddress($toEmail, $toName);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);
            $this->mailer->send();
            return true;
        } catch (MailException $exception) {
            $this->logMailFailure($subject, $body, $exception->getMessage());
        } catch (\Throwable $throwable) {
            $this->logMailFailure($subject, $body, $throwable->getMessage());
        }

        return false;
    }

    /**
     * @param array<string, mixed> $reservation
     */
    public function sendReservationConfirmation(array $reservation): void
    {
        $subject = 'Confirmation de réservation';
        $body = sprintf(
            '<p>Bonjour %s,</p>
             <p>Votre réservation du %s à %s pour le terrain %s est confirmée.</p>
             <p>Statut: %s</p>',
            htmlspecialchars((string) ($reservation['client_prenom'] ?? '')),
            htmlspecialchars((string) $reservation['date_reservation']),
            htmlspecialchars((string) $reservation['creneau_horaire']),
            htmlspecialchars((string) $reservation['terrain_nom']),
            htmlspecialchars((string) $reservation['statut'])
        );

        $this->send(
            (string) ($reservation['client_email'] ?? Config::get('MAIL_FROM')),
            trim((string) (($reservation['client_prenom'] ?? '') . ' ' . ($reservation['client_nom'] ?? ''))),
            $subject,
            $body
        );
    }

    public function sendVerificationCode(string $email, string $name, string $code): bool
    {
        $subject = 'Verification de votre compte';
        $body = sprintf(
            '<p>Bonjour %s,</p>
             <p>Voici votre code de verification :</p>
             <p style="font-size:20px;font-weight:bold;letter-spacing:4px;">%s</p>
             <p>Ce code expire dans 30 minutes.</p>',
            htmlspecialchars($name),
            htmlspecialchars($code)
        );

        return $this->send($email, $name, $subject, $body);
    }

    private function configure(): void
    {
        $env = Config::get('APP_ENV', 'production');
        $this->mailer->isSMTP();
        $this->mailer->Host = Config::get('SMTP_HOST', 'localhost');
        $this->mailer->Port = (int) Config::get('SMTP_PORT', 1025);
        $this->mailer->SMTPAuth = (bool) Config::get('SMTP_USER');
        if ($this->mailer->SMTPAuth) {
            $this->mailer->Username = Config::get('SMTP_USER', '');
            $this->mailer->Password = Config::get('SMTP_PASS', '');
        }
        $encryption = Config::get('SMTP_ENCRYPTION', 'tls');
        if (is_string($encryption) && $encryption !== '') {
            $this->mailer->SMTPSecure = $encryption;
        }
        $this->mailer->SMTPAutoTLS = $env !== 'local';
    }

    private function logMailFailure(string $subject, string $body, string $error): void
    {
        $logFile = Config::storagePath('logs/mail.log');
        $directory = \dirname($logFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $line = sprintf(
            "[%s] Subject: %s | Error: %s | Body: %s\n",
            date('c'),
            $subject,
            $error,
            strip_tags($body)
        );
        file_put_contents($logFile, $line, FILE_APPEND);
    }
}
