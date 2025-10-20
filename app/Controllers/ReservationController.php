<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\PricingService;
use App\Services\ReservationService;
use App\Services\InvoicePdfService;
use PDOException;
use Throwable;

class ReservationController
{
    private ReservationService $reservations;
    private PricingService $pricing;
    private Auth $auth;

    public function __construct(
        ?ReservationService $reservationService = null,
        ?PricingService $pricingService = null,
        ?Auth $auth = null
    ) {
        $this->reservations = $reservationService ?? new ReservationService();
        $this->pricing = $pricingService ?? new PricingService();
        $this->auth = $auth ?? new Auth();
    }

    public function create(): string
    {
        $this->auth->requireAuth();

        return View::render('reservation/create', [
            'terrains' => $this->reservations->getTerrains(),
            'terrainPrices' => $this->pricing->getTerrainPrices(),
            'servicePrices' => $this->pricing->getServicePrices(),
            'eventTypes' => $this->reservations->getEventTypes(),
            'skillLevels' => $this->reservations->getSkillLevels(),
            'addonServices' => $this->reservations->getAddonServices(),
            'error' => null,
            'old' => [],
            'pageTitle' => 'Nouvelle reservation',
        ]);
    }

    public function store(): string
    {
        $this->auth->requireAuth();
        $userId = $this->auth->id();

        try {
            $reservationId = $this->reservations->createReservation($_POST, $userId ?? 0);
        } catch (Throwable $exception) {
            $message = ($exception instanceof PDOException && $exception->getCode() === '23000')
                ? 'Ce creneau est deja reserve.'
                : 'Erreur lors de la creation de la reservation.';

            return View::render('reservation/create', [
                'terrains' => $this->reservations->getTerrains(),
                'terrainPrices' => $this->pricing->getTerrainPrices(),
                'servicePrices' => $this->pricing->getServicePrices(),
                'eventTypes' => $this->reservations->getEventTypes(),
                'skillLevels' => $this->reservations->getSkillLevels(),
                'addonServices' => $this->reservations->getAddonServices(),
                'error' => $message,
                'old' => $_POST,
                'pageTitle' => 'Nouvelle reservation',
            ]);
        }

        header("Location: /reservation/{$reservationId}");
        exit;
    }

    public function myReservations(): string
    {
        $this->auth->requireAuth();
        $reservations = $this->reservations->getReservationsForUser($this->auth->id() ?? 0);

        return View::render('reservation/my_list', [
            'reservations' => $reservations,
            'pageTitle' => 'Mes reservations',
        ]);
    }

    public function show(int $id): string
    {
        $this->auth->requireAuth();
        $reservation = $this->reservations->getReservation($id);
        if (!$reservation) {
            http_response_code(404);
            return View::render('errors/404', ['path' => '/reservation/' . $id], null);
        }

        $userId = $this->auth->id();
        if (!$this->auth->isAdmin() && (int) $reservation['utilisateur_id'] !== $userId) {
            http_response_code(403);
            return View::render('errors/403', [], null);
        }

        return View::render('reservation/show', [
            'reservation' => $reservation,
            'facture' => [
                'terrain' => (float) ($reservation['montant_terrain'] ?? 0),
                'service' => (float) ($reservation['montant_service'] ?? 0),
                'total' => (float) ($reservation['facture_total'] ?? 0),
            ],
            'pageTitle' => 'Reservation #' . $id,
        ]);
    }

    public function edit(int $id): string
    {
        $this->auth->requireAuth();
        $reservation = $this->reservations->getReservation($id);
        if (!$reservation) {
            http_response_code(404);
            return View::render('errors/404', ['path' => '/reservation/' . $id], null);
        }

        $userId = $this->auth->id();
        if (!$this->auth->isAdmin() && (int) $reservation['utilisateur_id'] !== $userId) {
            http_response_code(403);
            return View::render('errors/403', [], null);
        }

        return View::render('reservation/edit', [
            'reservation' => $reservation,
            'terrains' => $this->reservations->getTerrains(false),
            'terrainPrices' => $this->pricing->getTerrainPrices(),
            'servicePrices' => $this->pricing->getServicePrices(),
            'eventTypes' => $this->reservations->getEventTypes(),
            'skillLevels' => $this->reservations->getSkillLevels(),
            'addonServices' => $this->reservations->getAddonServices(),
            'error' => null,
            'pageTitle' => 'Modifier la reservation',
        ]);
    }

    public function update(int $id): string
    {
        $this->auth->requireAuth();
        $userId = $this->auth->id() ?? 0;

        try {
            $updated = $this->reservations->updateReservation($id, $_POST, $userId, $this->auth->isAdmin());
        } catch (Throwable $exception) {
            $message = ($exception instanceof PDOException && $exception->getCode() === '23000')
                ? 'Le creneau selectionne est deja reserve.'
                : 'Impossible de mettre a jour cette reservation.';

            return View::render('reservation/edit', [
                'reservation' => $this->reservations->getReservation($id),
                'terrains' => $this->reservations->getTerrains(false),
                'terrainPrices' => $this->pricing->getTerrainPrices(),
                'servicePrices' => $this->pricing->getServicePrices(),
                'eventTypes' => $this->reservations->getEventTypes(),
                'skillLevels' => $this->reservations->getSkillLevels(),
                'addonServices' => $this->reservations->getAddonServices(),
                'error' => $message,
                'pageTitle' => 'Modifier la reservation',
            ]);
        }

        if (!$updated) {
            return View::render('reservation/edit', [
                'reservation' => $this->reservations->getReservation($id),
                'terrains' => $this->reservations->getTerrains(false),
                'terrainPrices' => $this->pricing->getTerrainPrices(),
                'servicePrices' => $this->pricing->getServicePrices(),
                'eventTypes' => $this->reservations->getEventTypes(),
                'skillLevels' => $this->reservations->getSkillLevels(),
                'addonServices' => $this->reservations->getAddonServices(),
                'error' => 'Impossible de mettre a jour cette reservation.',
                'pageTitle' => 'Modifier la reservation',
            ]);
        }

        header("Location: /reservation/{$id}");
        exit;
    }

    public function cancel(int $id): void
    {
        $this->auth->requireAuth();
        $this->reservations->cancelReservation($id, $this->auth->id() ?? 0, $this->auth->isAdmin());
        header('Location: /reservation/my');
        exit;
    }

    public function downloadInvoice(int $id): void
    {
        $this->auth->requireAuth();
        $reservation = $this->reservations->getReservation($id);
        if (!$reservation) {
            http_response_code(404);
            echo View::render('errors/404', ['path' => '/reservation/' . $id], null);
            return;
        }

        $userId = $this->auth->id();
        if (!$this->auth->isAdmin() && (int) $reservation['utilisateur_id'] !== $userId) {
            http_response_code(403);
            echo View::render('errors/403', [], null);
            return;
        }

        $terrainPrices = $this->pricing->getTerrainPrices();
        $servicePrices = $this->pricing->getServicePrices();

        $invoice = new InvoicePdfService();
        $invoice->stream($reservation, [
            'terrain' => $terrainPrices,
            'services' => $servicePrices,
        ]);
        exit;
    }
}
