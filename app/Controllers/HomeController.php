<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\PricingService;
use App\Services\ReservationService;
use App\Services\TournamentService;

class HomeController
{
    private PricingService $pricing;
    private ReservationService $reservations;
    private TournamentService $tournaments;
    private Auth $auth;

    public function __construct(
        ?PricingService $pricingService = null,
        ?ReservationService $reservationService = null,
        ?TournamentService $tournamentService = null,
        ?Auth $auth = null
    ) {
        $this->pricing = $pricingService ?? new PricingService();
        $this->reservations = $reservationService ?? new ReservationService();
        $this->tournaments = $tournamentService ?? new TournamentService();
        $this->auth = $auth ?? new Auth();
    }

    public function index(): string
    {
        $terrainPrices = [];
        $servicePrices = [];
        $upcomingReservations = [];
        $latestTournaments = [];
        $featuredTerrains = [];
        $error = null;

        try {
            $terrainPrices = $this->pricing->getTerrainPrices();
            $servicePrices = $this->pricing->getServicePrices();
            $upcomingReservations = $this->reservations->getUpcomingReservations(new \DateTimeImmutable('now'));
            $latestTournaments = $this->tournaments->listTournaments();
            $featuredTerrains = array_slice($this->reservations->getTerrains(false), 0, 3);
        } catch (\Throwable $throwable) {
            $error = $throwable->getMessage();
        }

        return View::render('home', [
            'user' => $this->auth->user(),
            'terrainPrices' => $terrainPrices,
            'servicePrices' => $servicePrices,
            'upcomingReservations' => array_slice($upcomingReservations, 0, 5),
            'latestTournaments' => $latestTournaments,
            'featuredTerrains' => $featuredTerrains,
            'error' => $error,
        ]);
    }
}


