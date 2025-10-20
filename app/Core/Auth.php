<?php

declare(strict_types=1);

namespace App\Core;

use DateInterval;
use DateTimeImmutable;
use PDO;

class Auth
{
    private const SESSION_KEY = 'auth_user';
    private const PENDING_KEY = 'pending_verification';
    private const REMEMBER_COOKIE = 'remember_me';
    private const REMEMBER_DURATION = 2592000; // 30 days

    public function check(): bool
    {
        if (isset($_SESSION[self::SESSION_KEY])) {
            return true;
        }

        return $this->loginFromRememberCookie();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        /** @var array<string, mixed>|null $user */
        $user = $_SESSION[self::SESSION_KEY] ?? null;
        return $user;
    }

    public function id(): ?int
    {
        $user = $this->user();
        return $user['id'] ?? null;
    }

    public function isAdmin(): bool
    {
        $user = $this->user();
        return ($user['role'] ?? '') === 'admin';
    }

    public function attempt(string $email, string $password): bool
    {
        $stmt = Database::connection()->prepare('SELECT * FROM utilisateur WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        $this->storeUser($user);
        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function register(array $data): bool
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO utilisateur (nom, prenom, email, telephone, adresse, password_hash, avatar_path, role, verification_code, verification_expires_at, email_verified_at, remember_token, remember_expires_at)
             VALUES (:nom, :prenom, :email, :telephone, :adresse, :password_hash, :avatar_path, :role, :verification_code, :verification_expires_at, :email_verified_at, :remember_token, :remember_expires_at)'
        );

        $hashedPassword = password_hash((string) $data['password'], PASSWORD_DEFAULT);

        return $stmt->execute([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'telephone' => $data['telephone'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'password_hash' => $hashedPassword,
            'avatar_path' => $data['avatar_path'] ?? null,
            'role' => $data['role'] ?? 'client',
            'verification_code' => $data['verification_code'] ?? null,
            'verification_expires_at' => $data['verification_expires_at'] ?? null,
            'email_verified_at' => $data['email_verified_at'] ?? null,
            'remember_token' => null,
            'remember_expires_at' => null,
        ]);
    }

    public function logout(): void
    {
        $userId = $this->id();
        unset($_SESSION[self::SESSION_KEY]);
        $this->clearRememberToken($userId);
    }

    public function refresh(): void
    {
        $userId = $this->id();
        if ($userId === null) {
            return;
        }
        $stmt = Database::connection()->prepare('SELECT * FROM utilisateur WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $this->storeUser($user);
        }
    }

    public function requireAuth(): void
    {
        if ($this->check()) {
            return;
        }

        header('Location: /login');
        exit;
    }

    public function requireAdmin(): void
    {
        if ($this->isAdmin()) {
            return;
        }

        http_response_code(403);
        echo View::render('errors/403.php', [], null);
        exit;
    }

    /**
     * @param array<string, mixed> $user
     */
    private function storeUser(array $user): void
    {
        unset($user['password_hash']);
        $_SESSION[self::SESSION_KEY] = $user;
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM utilisateur WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function loginUser(array $user, bool $remember = false): void
    {
        $this->storeUser($user);
        unset($_SESSION[self::PENDING_KEY]);

        if ($remember) {
            $this->createRememberToken((int) $user['id']);
        } else {
            $this->clearRememberToken((int) $user['id']);
        }
    }

    public function findUserById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM utilisateur WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function setPendingVerification(int $userId, string $email): void
    {
        $_SESSION[self::PENDING_KEY] = [
            'id' => $userId,
            'email' => $email,
        ];
    }

    public function pendingVerification(): ?array
    {
        return $_SESSION[self::PENDING_KEY] ?? null;
    }

    public function setVerificationCode(int $userId, string $code, \DateTimeInterface $expiresAt): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE utilisateur SET verification_code = :code, verification_expires_at = :expires WHERE id = :id'
        );
        $stmt->execute([
            'code' => $code,
            'expires' => $expiresAt->format('Y-m-d H:i:s'),
            'id' => $userId,
        ]);
    }

    public function markEmailVerified(int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE utilisateur
             SET email_verified_at = :verified_at, verification_code = NULL, verification_expires_at = NULL
             WHERE id = :id'
        );
        $stmt->execute([
            'verified_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'id' => $userId,
        ]);
        $this->refresh();
        unset($_SESSION[self::PENDING_KEY]);
    }

    private function createRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = (new DateTimeImmutable())->add(new DateInterval('PT' . self::REMEMBER_DURATION . 'S'));

        $stmt = Database::connection()->prepare(
            'UPDATE utilisateur SET remember_token = :token, remember_expires_at = :expires WHERE id = :id'
        );
        $stmt->execute([
            'token' => $hash,
            'expires' => $expiresAt->format('Y-m-d H:i:s'),
            'id' => $userId,
        ]);

        $cookieValue = $userId . '|' . $token;
        $this->setCookie(self::REMEMBER_COOKIE, $cookieValue, $expiresAt->getTimestamp());
        $_COOKIE[self::REMEMBER_COOKIE] = $cookieValue;
    }

    private function clearRememberToken(?int $userId = null): void
    {
        if ($userId !== null) {
            $stmt = Database::connection()->prepare(
                'UPDATE utilisateur SET remember_token = NULL, remember_expires_at = NULL WHERE id = :id'
            );
            $stmt->execute(['id' => $userId]);
        }

        $this->forgetRememberCookie();
    }

    private function loginFromRememberCookie(): bool
    {
        if (empty($_COOKIE[self::REMEMBER_COOKIE])) {
            return false;
        }

        $parts = explode('|', $_COOKIE[self::REMEMBER_COOKIE], 2);
        if (count($parts) !== 2) {
            $this->forgetRememberCookie();
            return false;
        }

        [$idPart, $token] = $parts;
        if (!ctype_digit($idPart) || $token === '') {
            $this->forgetRememberCookie();
            return false;
        }

        $user = $this->findUserById((int) $idPart);
        if (!$user || empty($user['remember_token'])) {
            $this->forgetRememberCookie();
            return false;
        }

        $hash = (string) $user['remember_token'];
        if (!hash_equals($hash, hash('sha256', $token))) {
            $this->forgetRememberCookie();
            return false;
        }

        if (!empty($user['remember_expires_at'])) {
            $expiresAt = new DateTimeImmutable((string) $user['remember_expires_at']);
            if ($expiresAt < new DateTimeImmutable()) {
                $this->clearRememberToken((int) $user['id']);
                return false;
            }
        }

        $this->storeUser($user);
        return true;
    }

    private function setCookie(string $name, string $value, int $expires): void
    {
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        setcookie($name, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function forgetRememberCookie(): void
    {
        if (!isset($_COOKIE[self::REMEMBER_COOKIE])) {
            return;
        }

        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::REMEMBER_COOKIE]);
    }
}
