<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\View;
use PDO;

class ProfileController
{
    private Auth $auth;

    public function __construct(?Auth $auth = null)
    {
        $this->auth = $auth ?? new Auth();
    }

    public function edit(): string
    {
        $this->auth->requireAuth();
        $user = $this->auth->user();

        return View::render('profile/edit', [
            'user' => $user,
            'pageTitle' => 'Mon profil',
        ]);
    }

    public function update(): void
    {
        $this->auth->requireAuth();
        $user = $this->auth->user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE utilisateur SET telephone = :telephone, adresse = :adresse, avatar_path = :avatar WHERE id = :id'
        );

        $avatarPath = $user['avatar_path'] ?? null;
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $uploaded = $this->handleAvatarUpload((int) $user['id'], $_FILES['avatar']);
            if ($uploaded !== null) {
                $avatarPath = $uploaded;
            }
        }

        $stmt->execute([
            'telephone' => trim((string) ($_POST['telephone'] ?? '')),
            'adresse' => trim((string) ($_POST['adresse'] ?? '')),
            'avatar' => $avatarPath,
            'id' => (int) $user['id'],
        ]);

        $this->auth->refresh();

        header('Location: /profile');
        exit;
    }

    /**
     * @param array<string, mixed> $file
     */
    private function handleAvatarUpload(int $userId, array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!$mime || !isset($allowed[$mime])) {
            return null;
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return null;
        }

        $extension = $allowed[$mime];
        $filename = sprintf('user_%d_%s.%s', $userId, bin2hex(random_bytes(4)), $extension);
        $destination = Config::basePath('public/uploads/avatars/' . $filename);

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return null;
        }

        return 'uploads/avatars/' . $filename;
    }
}
