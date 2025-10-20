<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Recaptcha;
use App\Core\View;
use App\Services\Mailer;
use DateInterval;
use DateTimeImmutable;
use PDOException;
use Throwable;

class AuthController
{
    private Auth $auth;
    private Mailer $mailer;

    public function __construct(?Auth $auth = null, ?Mailer $mailer = null)
    {
        $this->auth = $auth ?? new Auth();
        $this->mailer = $mailer ?? new Mailer();
    }

    public function showLogin(): string
    {
        if ($this->auth->check()) {
            header('Location: /');
            exit;
        }

        return $this->renderLogin();
    }

    public function login(): string
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $captchaValue = $_POST['g-recaptcha-response'] ?? '';
        $remember = isset($_POST['remember']) && ($_POST['remember'] === '1' || $_POST['remember'] === 'on');

        if ($email === '' || $password === '') {
            return $this->renderLogin('Veuillez saisir votre email et votre mot de passe.', null, false, $remember);
        }

        if ($this->isRecaptchaEnabled() && !Recaptcha::verify(
            $captchaValue,
            'login',
            0.5,
            $_SERVER['REMOTE_ADDR'] ?? null
        )) {
            return $this->renderLogin('Verification anti-robot invalide.', null, false, $remember);
        }

        $user = $this->auth->findUserByEmail($email);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return $this->renderLogin('Identifiants invalides.', null, false, $remember);
        }

        if (empty($user['email_verified_at'])) {
            $this->ensureVerificationCode((int) $user['id'], $user);
            $this->auth->setPendingVerification((int) $user['id'], $user['email']);
            return $this->renderLogin(
                'Votre email n\'est pas encore verifie. Un code vous a ete envoye.',
                'Un code de verification vient de vous etre renvoye.',
                true,
                $remember
            );
        }

        $this->auth->loginUser($user, $remember);
        header('Location: /');
        exit;
    }

    public function showRegister(): string
    {
        if ($this->auth->check()) {
            header('Location: /');
            exit;
        }

        return $this->renderRegister();
    }

    public function register(): string
    {
        $data = [
            'nom' => trim((string) ($_POST['nom'] ?? '')),
            'prenom' => trim((string) ($_POST['prenom'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'telephone' => trim((string) ($_POST['telephone'] ?? '')),
            'adresse' => trim((string) ($_POST['adresse'] ?? '')),
            'password' => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
        ];
        $captchaValue = $_POST['g-recaptcha-response'] ?? '';

        $error = $this->validateRegistration($data);
        if ($error !== null) {
            return $this->renderRegister($error);
        }

        if ($this->isRecaptchaEnabled() && !Recaptcha::verify(
            $captchaValue,
            'register',
            0.5,
            $_SERVER['REMOTE_ADDR'] ?? null
        )) {
            return $this->renderRegister('Verification anti-robot invalide.');
        }

        $code = $this->generateVerificationCode();
        $expires = (new DateTimeImmutable())->add(new DateInterval('PT30M'));

        $payload = $data;
        $payload['verification_code'] = $code;
        $payload['verification_expires_at'] = $expires->format('Y-m-d H:i:s');
        $payload['email_verified_at'] = null;

        try {
            $this->auth->register($payload);
        } catch (PDOException $exception) {
            if ((int) $exception->getCode() === 23000) {
                return $this->renderRegister('Cet email est deja utilise.');
            }

            return $this->renderRegister('Erreur lors de la creation du compte.');
        }

        $user = $this->auth->findUserByEmail($data['email']);
        if ($user) {
            $this->mailer->sendVerificationCode(
                $user['email'],
                trim((string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))),
                $code
            );
            $this->auth->setPendingVerification((int) $user['id'], $user['email']);
        }

        header('Location: /verify');
        exit;
    }

    public function showVerify(): string
    {
        $pending = $this->auth->pendingVerification();
        if (!$pending) {
            header('Location: /login');
            exit;
        }

        return $this->renderVerify(null, null, $pending['email']);
    }

    public function verify(): string
    {
        $pending = $this->auth->pendingVerification();
        if (!$pending) {
            header('Location: /login');
            exit;
        }

        $code = trim((string) ($_POST['code'] ?? ''));
        if ($code === '') {
            return $this->renderVerify('Veuillez saisir le code recu par email.', null, $pending['email']);
        }

        $user = $this->auth->findUserById((int) $pending['id']);
        if (!$user) {
            $this->auth->logout();
            header('Location: /register');
            exit;
        }

        if ((string) $user['verification_code'] !== $code) {
            return $this->renderVerify('Code invalide.', null, $pending['email']);
        }

        $expiresAt = isset($user['verification_expires_at']) ? new DateTimeImmutable((string) $user['verification_expires_at']) : null;
        if ($expiresAt !== null && $expiresAt < new DateTimeImmutable()) {
            return $this->renderVerify('Ce code est expire. Demandez-en un nouveau.', null, $pending['email']);
        }

        $this->auth->markEmailVerified((int) $user['id']);
        $fresh = $this->auth->findUserById((int) $user['id']);
        if ($fresh) {
            $this->auth->loginUser($fresh);
        }

        header('Location: /');
        exit;
    }

    public function resendVerification(): string
    {
        $pending = $this->auth->pendingVerification();
        if (!$pending) {
            header('Location: /login');
            exit;
        }

        $user = $this->auth->findUserById((int) $pending['id']);
        if (!$user) {
            header('Location: /register');
            exit;
        }

        $code = $this->generateVerificationCode();
        $expires = (new DateTimeImmutable())->add(new DateInterval('PT30M'));
        $this->auth->setVerificationCode((int) $user['id'], $code, $expires);
        $this->mailer->sendVerificationCode(
            $user['email'],
            trim((string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))),
            $code
        );

        return $this->renderVerify(null, 'Un nouveau code vous a ete envoye.', $user['email']);
    }

    public function logout(): void
    {
        $this->auth->logout();
        header('Location: /');
        exit;
    }

    /**
     * @param array<string, string> $data
     */
    private function validateRegistration(array $data): ?string
    {
        if ($data['nom'] === '' || $data['prenom'] === '') {
            return 'Merci de renseigner votre nom et prenom.';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Email invalide.';
        }

        if ($data['password'] === '' || strlen($data['password']) < 6) {
            return 'Le mot de passe doit contenir au moins 6 caracteres.';
        }

        if ($data['password'] !== $data['password_confirmation']) {
            return 'Les mots de passe ne correspondent pas.';
        }

        return null;
    }

    private function renderLogin(?string $error = null, ?string $status = null, bool $unverified = false, bool $remember = false): string
    {
        return View::render(
            'auth/login',
            [
                'error' => $error,
                'status' => $status,
                'unverified' => $unverified,
                 'remember' => $remember,
                'recaptchaSiteKey' => $this->recaptchaSiteKey(),
                'recaptchaEnabled' => $this->isRecaptchaEnabled(),
                'authMode' => 'login',
                'isAuthPage' => true,
                'pageTitle' => 'Connexion',
            ]
        );
    }

    private function renderRegister(?string $error = null): string
    {
        return View::render(
            'auth/register',
            [
                'error' => $error,
                'recaptchaSiteKey' => $this->recaptchaSiteKey(),
                'recaptchaEnabled' => $this->isRecaptchaEnabled(),
                'authMode' => 'register',
                'isAuthPage' => true,
                'pageTitle' => 'Inscription',
            ]
        );
    }

    private function renderVerify(?string $error = null, ?string $status = null, string $email = ''): string
    {
        return View::render(
            'auth/verify',
            [
                'error' => $error,
                'status' => $status,
                'email' => $email,
                'isAuthPage' => true,
                'authMode' => 'login',
                'pageTitle' => 'Verification email',
            ]
        );
    }

    private function recaptchaSiteKey(): ?string
    {
        $key = Config::get('RECAPTCHA_SITE_KEY');
        $key = is_string($key) ? trim($key) : '';
        return $key !== '' ? $key : null;
    }

    private function isRecaptchaEnabled(): bool
    {
        $siteKey = $this->recaptchaSiteKey();
        $secret = Config::get('RECAPTCHA_SECRET_KEY');
        return $siteKey !== null && is_string($secret) && trim($secret) !== '';
    }

    private function generateVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function ensureVerificationCode(int $userId, array $user): void
    {
        $needsNewCode = empty($user['verification_code']);
        $expires = isset($user['verification_expires_at']) && $user['verification_expires_at'] !== null
            ? new DateTimeImmutable((string) $user['verification_expires_at'])
            : null;

        if ($expires !== null && $expires < new DateTimeImmutable()) {
            $needsNewCode = true;
        }

        if ($needsNewCode) {
            $code = $this->generateVerificationCode();
            $expiresAt = (new DateTimeImmutable())->add(new DateInterval('PT30M'));
            $this->auth->setVerificationCode($userId, $code, $expiresAt);
            $user['verification_code'] = $code;
        } else {
            $code = (string) $user['verification_code'];
        }

        $this->mailer->sendVerificationCode(
            (string) $user['email'],
            trim((string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))),
            $code
        );
    }
}
