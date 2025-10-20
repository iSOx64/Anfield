<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ContactController;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\ProfileController;
use App\Controllers\ReservationController;
use App\Controllers\TournamentController;
use App\Core\Config;
use App\Core\Router;

$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoloadPath)) {
    require $autoloadPath;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

session_start();

Config::boot(dirname(__DIR__));

$appEnv = Config::get('APP_ENV', 'production');
error_reporting(E_ALL);
ini_set('display_errors', $appEnv === 'local' ? '1' : '0');

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/contact', [ContactController::class, 'show']);
$router->post('/contact', [ContactController::class, 'submit']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/verify', [AuthController::class, 'showVerify']);
$router->post('/verify', [AuthController::class, 'verify']);
$router->post('/verify/resend', [AuthController::class, 'resendVerification']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/reservation/create', [ReservationController::class, 'create']);
$router->post('/reservation', [ReservationController::class, 'store']);
$router->get('/reservation/my', [ReservationController::class, 'myReservations']);
$router->get('/reservation/{id}', [ReservationController::class, 'show']);
$router->get('/reservation/{id}/invoice', [ReservationController::class, 'downloadInvoice']);
$router->get('/reservation/{id}/edit', [ReservationController::class, 'edit']);
$router->post('/reservation/{id}/update', [ReservationController::class, 'update']);
$router->post('/reservation/{id}/cancel', [ReservationController::class, 'cancel']);

$router->get('/tournoi/create', [TournamentController::class, 'create']);
$router->post('/tournoi', [TournamentController::class, 'store']);
$router->get('/tournoi/{id}', [TournamentController::class, 'show']);
$router->get('/tournoi/{id}/planner', [TournamentController::class, 'planner']);
$router->post('/tournoi/{id}/team', [TournamentController::class, 'addTeam']);
$router->post('/tournoi/{id}/plan', [TournamentController::class, 'planMatches']);

$router->get('/profile', [ProfileController::class, 'edit']);
$router->post('/profile', [ProfileController::class, 'update']);

$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/terrains', [AdminController::class, 'terrains']);
$router->post('/admin/terrains', [AdminController::class, 'updateTerrainAvailability']);
$router->post('/admin/terrains/new', [AdminController::class, 'createTerrain']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->post('/admin/users/role', [AdminController::class, 'updateUserRole']);
$router->get('/admin/dispo', [AdminController::class, 'disponibilites']);
$router->get('/admin/dispo/export', [AdminController::class, 'exportReservations']);

$router->get('/ressources/faq', [PageController::class, 'faq']);
$router->get('/ressources/politique-confidentialite', [PageController::class, 'privacy']);
$router->get('/ressources/conditions-utilisation', [PageController::class, 'terms']);
$router->get('/mentions-legales', [PageController::class, 'legal']);
$router->get('/politique-cookies', [PageController::class, 'cookies']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

