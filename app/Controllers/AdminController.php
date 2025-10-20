<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Services\AdminAnalytics;
use App\Services\ReservationService;
use PDO;

class AdminController
{
    private Auth $auth;
    private ReservationService $reservations;
    private AdminAnalytics $analytics;

    public function __construct(
        ?Auth $auth = null,
        ?ReservationService $reservationService = null,
        ?AdminAnalytics $analytics = null
    )
    {
        $this->auth = $auth ?? new Auth();
        $this->reservations = $reservationService ?? new ReservationService();
        $this->analytics = $analytics ?? new AdminAnalytics();
    }

    private function parseDate(string $value, \DateTimeImmutable $fallback): \DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date instanceof \DateTimeImmutable) {
            return $date;
        }

        return $fallback;
    }

    public function terrains(): string
    {
        $this->auth->requireAdmin();

        $terrains = $this->reservations->getTerrains(false);
        $flash = $_SESSION['admin_flash'] ?? null;
        unset($_SESSION['admin_flash']);

        return View::render('admin/terrains', [
            'terrains' => $terrains,
            'pageTitle' => 'Administration - Terrains',
            'activeAdminNav' => 'terrains',
            'flash' => $flash,
            'terrainSizes' => $this->reservations->getTerrainSizes(),
            'terrainTypes' => $this->reservations->getTerrainTypes(),
        ]);
    }

    public function updateTerrainAvailability(): void
    {
        $this->auth->requireAdmin();

        $terrainId = (int) ($_POST['terrain_id'] ?? 0);
        $disponible = isset($_POST['disponible']);

        if ($terrainId <= 0) {
            $_SESSION['admin_flash'] = [
                'status' => 'error',
                'message' => 'Terrain introuvable.',
            ];
            header('Location: /admin/terrains');
            exit;
        }

        $newImagePath = null;
        try {
            $newImagePath = $this->processTerrainImageUpload('terrain_image');
        } catch (\RuntimeException $runtimeException) {
            $_SESSION['admin_flash'] = [
                'status' => 'error',
                'message' => $runtimeException->getMessage(),
            ];
            header('Location: /admin/terrains');
            exit;
        }

        $connection = Database::connection();
        $connection->beginTransaction();
        $oldImagePath = null;

        try {
            $params = [
                'disponible' => $disponible ? 1 : 0,
                'id' => $terrainId,
            ];

            $sql = 'UPDATE terrain SET disponible = :disponible';
            if ($newImagePath !== null) {
                $sql .= ', image_path = :image_path';
                $params['image_path'] = $newImagePath;

                $select = $connection->prepare('SELECT image_path FROM terrain WHERE id = :id');
                $select->execute(['id' => $terrainId]);
                /** @var string|false $oldImage */
                $oldImage = $select->fetchColumn();
                $oldImagePath = $oldImage ? (string) $oldImage : null;
            }

            $statement = $connection->prepare($sql . ' WHERE id = :id');
            $statement->execute($params);
            $connection->commit();
        } catch (\Throwable $throwable) {
            $connection->rollBack();
            if ($newImagePath !== null) {
                $this->removeTerrainImage($newImagePath);
            }
            throw $throwable;
        }

        if ($newImagePath !== null && $oldImagePath !== null && $oldImagePath !== $newImagePath) {
            $this->removeTerrainImage($oldImagePath);
        }

        $_SESSION['admin_flash'] = [
            'status' => 'success',
            'message' => 'Terrain mis a jour.',
        ];

        header('Location: /admin/terrains');
        exit;
    }

    public function createTerrain(): void
    {
        $this->auth->requireAdmin();

        $nom = trim((string) ($_POST['nom'] ?? ''));
        $taille = (string) ($_POST['taille'] ?? '');
        $type = (string) ($_POST['type'] ?? '');
        $disponible = isset($_POST['disponible']);

        $errors = [];
        $formData = [
            'nom' => $nom,
            'taille' => $taille,
            'type' => $type,
            'disponible' => $disponible ? '1' : '0',
        ];

        if ($nom === '') {
            $errors[] = 'Le nom du terrain est obligatoire.';
        }

        if (!in_array($taille, $this->reservations->getTerrainSizes(), true)) {
            $errors[] = 'La taille choisie est invalide.';
        }

        if (!in_array($type, $this->reservations->getTerrainTypes(), true)) {
            $errors[] = 'Le type de terrain est invalide.';
        }

        $imagePath = null;
        try {
            $imagePath = $this->processTerrainImageUpload('terrain_image');
        } catch (\RuntimeException $runtimeException) {
            $errors[] = $runtimeException->getMessage();
        }

        if ($errors === []) {
            $checkStmt = Database::connection()->prepare(
                'SELECT COUNT(*) FROM terrain WHERE nom = :nom'
            );
            $checkStmt->execute(['nom' => $nom]);
            $exists = (int) ($checkStmt->fetchColumn() ?: 0) > 0;
            if ($exists) {
                $errors[] = 'Un terrain porte deja ce nom.';
            }
        }

        if ($errors !== []) {
            if ($imagePath !== null) {
                $this->removeTerrainImage($imagePath);
            }
            $_SESSION['admin_flash'] = [
                'status' => 'error',
                'message' => implode(' ', $errors),
                'data' => $formData,
            ];
            header('Location: /admin/terrains');
            exit;
        }

        try {
            $this->reservations->createTerrain($nom, $taille, $type, $disponible, $imagePath);
        } catch (\Throwable $throwable) {
            if ($imagePath !== null) {
                $this->removeTerrainImage($imagePath);
            }
            throw $throwable;
        }

        $_SESSION['admin_flash'] = [
            'status' => 'success',
            'message' => 'Nouveau terrain ajoute avec succes.',
        ];

        header('Location: /admin/terrains');
        exit;
    }

    public function users(): string
    {
        $this->auth->requireAdmin();

        $searchTerm = trim((string) ($_GET['q'] ?? ''));
        $sql = 'SELECT id, nom, prenom, email, telephone, role, avatar_path, created_at
                FROM utilisateur';
        $params = [];
        if ($searchTerm !== '') {
            $sql .= ' WHERE nom LIKE :term_nom OR prenom LIKE :term_prenom OR email LIKE :term_email';
            $wildcard = '%' . $searchTerm . '%';
            $params = [
                'term_nom' => $wildcard,
                'term_prenom' => $wildcard,
                'term_email' => $wildcard,
            ];
        }
        $sql .= ' ORDER BY created_at DESC';

        $connection = Database::connection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return View::render('admin/users', [
            'users' => $users,
            'pageTitle' => 'Administration - Utilisateurs',
            'activeAdminNav' => 'users',
            'searchTerm' => $searchTerm,
        ]);
    }

    public function dashboard(): string
    {
        $this->auth->requireAdmin();

        $snapshot = $this->analytics->getSnapshot();
        $revenueTrend = $this->analytics->getRevenueTrend(6);
        $topTerrains = $this->analytics->getTopTerrains(5);
        $upcomingReservations = array_slice(
            $this->reservations->getUpcomingReservations(new \DateTimeImmutable('now')),
            0,
            5
        );
        $utilizationStart = new \DateTimeImmutable('today');
        $utilizationEnd = $utilizationStart->modify('+30 days');
        $terrainUtilization = $this->analytics->getTerrainUtilization($utilizationStart, $utilizationEnd);

        return View::render('admin/dashboard', [
            'snapshot' => $snapshot,
            'revenueTrend' => $revenueTrend,
            'topTerrains' => $topTerrains,
            'upcomingReservations' => $upcomingReservations,
            'terrainUtilization' => $terrainUtilization,
            'utilizationRange' => [
                'start' => $utilizationStart->format('Y-m-d'),
                'end' => $utilizationEnd->format('Y-m-d'),
            ],
            'pageTitle' => 'Administration - Tableau de bord',
            'activeAdminNav' => 'dashboard',
        ]);
    }

    public function updateUserRole(): void
    {
        $this->auth->requireAdmin();

        $userId = (int) ($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? 'client';

        if (!in_array($role, ['client', 'admin'], true)) {
            header('Location: /admin/users');
            exit;
        }

        $stmt = Database::connection()->prepare(
            'UPDATE utilisateur SET role = :role WHERE id = :id'
        );
        $stmt->execute([
            'role' => $role,
            'id' => $userId,
        ]);

        if ($this->auth->id() === $userId) {
            $this->auth->refresh();
        }

        header('Location: /admin/users');
        exit;
    }

    public function disponibilites(): string
    {
        $this->auth->requireAdmin();

        $today = new \DateTimeImmutable('today');
        $defaultEnd = $today->modify('+14 days');

        $startDate = $this->parseDate((string) ($_GET['start'] ?? ''), $today);
        $endDate = $this->parseDate((string) ($_GET['end'] ?? ''), $defaultEnd);
        if ($endDate < $startDate) {
            $endDate = $startDate;
        }

        $terrainId = isset($_GET['terrain']) && (int) $_GET['terrain'] > 0 ? (int) $_GET['terrain'] : null;
        $statusParam = trim((string) ($_GET['status'] ?? ''));
        $status = $statusParam !== '' ? $statusParam : null;
        $focusDate = $this->parseDate((string) ($_GET['focus'] ?? ''), $startDate);

        $reservations = $this->reservations->getReservationsBetween($startDate, $endDate, $terrainId, $status);
        $terrains = $this->reservations->getTerrains(false);
        $statuses = $this->reservations->getReservationStatuses();
        $dailySchedule = $this->reservations->getDailySchedule($focusDate, $terrainId);

        $statusCounts = array_fill_keys($statuses, 0);
        $uniqueTerrains = [];
        $uniqueClients = [];
        foreach ($reservations as $reservation) {
            $statut = $reservation['statut'] ?? 'confirmee';
            if (isset($statusCounts[$statut])) {
                $statusCounts[$statut]++;
            }
            if (!empty($reservation['terrain_nom'])) {
                $uniqueTerrains[$reservation['terrain_nom']] = true;
            }
            if (!empty($reservation['utilisateur_id'])) {
                $uniqueClients[(int) $reservation['utilisateur_id']] = true;
            }
        }

        $nextReservation = $reservations[0] ?? null;

        return View::render('admin/dispo', [
            'reservations' => $reservations,
            'filters' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'terrain' => $terrainId !== null ? (string) $terrainId : '',
                'status' => $status ?? '',
                'focus' => $focusDate->format('Y-m-d'),
            ],
            'terrains' => $terrains,
            'statuses' => $statuses,
            'summary' => [
                'total' => count($reservations),
                'statusCounts' => $statusCounts,
                'uniqueTerrains' => count($uniqueTerrains),
                'uniqueClients' => count($uniqueClients),
            ],
            'nextReservation' => $nextReservation,
            'dailySchedule' => $dailySchedule,
            'pageTitle' => 'Administration - Reservations',
            'activeAdminNav' => 'reservations',
        ]);
    }

    public function exportReservations(): void
    {
        $this->auth->requireAdmin();

        $today = new \DateTimeImmutable('today');
        $defaultEnd = $today->modify('+14 days');

        $startDate = $this->parseDate((string) ($_GET['start'] ?? ''), $today);
        $endDate = $this->parseDate((string) ($_GET['end'] ?? ''), $defaultEnd);
        if ($endDate < $startDate) {
            $endDate = $startDate;
        }

        $terrainId = isset($_GET['terrain']) && (int) $_GET['terrain'] > 0 ? (int) $_GET['terrain'] : null;
        $statusParam = trim((string) ($_GET['status'] ?? ''));
        $status = $statusParam !== '' ? $statusParam : null;
        $focusDate = $this->parseDate((string) ($_GET['focus'] ?? ''), $startDate);

        $reservations = $this->reservations->getReservationsBetween($startDate, $endDate, $terrainId, $status);

        header('Content-Type: text/csv; charset=UTF-8');
        $fileName = sprintf(
            'reservations-%s_%s.csv',
            $startDate->format('Ymd'),
            $endDate->format('Ymd')
        );
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $output = fopen('php://output', 'wb');
        if ($output === false) {
            exit;
        }

        fputcsv($output, ['Date', 'Heure', 'Terrain', 'Client ID', 'Statut', 'Demandes']);
        foreach ($reservations as $reservation) {
            fputcsv($output, [
                $reservation['date_reservation'] ?? '',
                substr((string) ($reservation['creneau_horaire'] ?? ''), 0, 5),
                $reservation['terrain_nom'] ?? '',
                $reservation['utilisateur_id'] ?? '',
                $reservation['statut'] ?? '',
                $reservation['demande'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    private function processTerrainImageUpload(string $field): ?string
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            return null;
        }

        $file = $_FILES[$field];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Echec du telechargement de l image du terrain.');
        }

        $tmpPath = $file['tmp_name'] ?? '';
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new \RuntimeException('Fichier d image invalide.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath) ?: '';
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!array_key_exists($mime, $allowed)) {
            throw new \RuntimeException('Format d image non supporte (jpg, png, webp).');
        }

        $uploadsDir = dirname(__DIR__, 2) . '/public/uploads/terrains';
        if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
            throw new \RuntimeException('Impossible de creer le dossier des terrains.');
        }

        $extension = $allowed[$mime];
        $filename = 'terrain-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = $uploadsDir . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $destination)) {
            throw new \RuntimeException('Impossible d enregistrer l image du terrain.');
        }

        return 'uploads/terrains/' . $filename;
    }

    private function removeTerrainImage(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        $fullPath = dirname(__DIR__, 2) . '/public/' . ltrim($relativePath, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
