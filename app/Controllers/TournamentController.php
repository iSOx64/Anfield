<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\ReservationService;
use App\Services\TournamentService;
use PDOException;

class TournamentController
{
    private TournamentService $tournaments;
    private ReservationService $reservations;
    private Auth $auth;

    public function __construct(
        ?TournamentService $tournamentService = null,
        ?ReservationService $reservationService = null,
        ?Auth $auth = null
    ) {
        $this->tournaments = $tournamentService ?? new TournamentService();
        $this->reservations = $reservationService ?? new ReservationService();
        $this->auth = $auth ?? new Auth();
    }

    public function create(): string
    {
        $this->auth->requireAuth();

        return View::render('tournoi/create', [
            'error' => null,
            'categories' => $this->tournaments->getCategories(),
            'levels' => $this->tournaments->getLevels(),
            'old' => [],
        ]);
    }

    public function store(): string
    {
        $this->auth->requireAuth();

        $raw = [
            'nom' => trim((string) ($_POST['nom'] ?? '')),
            'format' => (string) ($_POST['format'] ?? '8'),
            'categorie' => (string) ($_POST['categorie'] ?? ''),
            'niveau' => (string) ($_POST['niveau'] ?? ''),
            'date_debut' => trim((string) ($_POST['date_debut'] ?? '')),
            'date_fin' => trim((string) ($_POST['date_fin'] ?? '')),
            'lieu' => trim((string) ($_POST['lieu'] ?? '')),
            'frais_inscription' => trim((string) ($_POST['frais_inscription'] ?? '')),
            'recompense' => trim((string) ($_POST['recompense'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'contact_email' => trim((string) ($_POST['contact_email'] ?? '')),
            'contact_phone' => trim((string) ($_POST['contact_phone'] ?? '')),
        ];

        $categories = $this->tournaments->getCategories();
        $levels = $this->tournaments->getLevels();
        $errors = [];

        if ($raw['nom'] === '') {
            $errors[] = 'Le nom du tournoi est obligatoire.';
        }

        if (!in_array($raw['format'], ['8', '16'], true)) {
            $errors[] = 'Le format doit etre 8 ou 16 equipes.';
        }

        $categorie = $raw['categorie'] !== '' && array_key_exists($raw['categorie'], $categories) ? $raw['categorie'] : null;
        $niveau = $raw['niveau'] !== '' && array_key_exists($raw['niveau'], $levels) ? $raw['niveau'] : null;

        $dateDebut = null;
        if ($raw['date_debut'] !== '') {
            $dateDebutObj = \DateTimeImmutable::createFromFormat('Y-m-d', $raw['date_debut']);
            if ($dateDebutObj instanceof \DateTimeImmutable) {
                $dateDebut = $dateDebutObj->format('Y-m-d');
            } else {
                $errors[] = 'La date de debut est invalide.';
            }
        }

        $dateFin = null;
        if ($raw['date_fin'] !== '') {
            $dateFinObj = \DateTimeImmutable::createFromFormat('Y-m-d', $raw['date_fin']);
            if ($dateFinObj instanceof \DateTimeImmutable) {
                $dateFin = $dateFinObj->format('Y-m-d');
            } else {
                $errors[] = 'La date de fin est invalide.';
            }
        }

        if ($dateDebut !== null && $dateFin !== null && $dateFin < $dateDebut) {
            $errors[] = 'La date de fin doit etre posterieure a la date de debut.';
        }

        $fraisInscription = null;
        if ($raw['frais_inscription'] !== '') {
            $normalized = str_replace(',', '.', $raw['frais_inscription']);
            if (!is_numeric($normalized) || (float) $normalized < 0) {
                $errors[] = 'Le montant des frais est invalide.';
            } else {
                $fraisInscription = round((float) $normalized, 2);
            }
        }

        $contactEmail = null;
        if ($raw['contact_email'] !== '') {
            if (!filter_var($raw['contact_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'L adresse e-mail de contact est invalide.';
            } else {
                $contactEmail = $raw['contact_email'];
            }
        }

        if ($errors !== []) {
            return View::render('tournoi/create', [
                'error' => implode(' ', $errors),
                'categories' => $categories,
                'levels' => $levels,
                'old' => $raw,
            ]);
        }

        $data = [
            'nom' => $raw['nom'],
            'format' => $raw['format'],
            'categorie' => $categorie,
            'niveau' => $niveau,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'lieu' => $raw['lieu'] !== '' ? $raw['lieu'] : null,
            'frais_inscription' => $fraisInscription,
            'recompense' => $raw['recompense'] !== '' ? $raw['recompense'] : null,
            'description' => $raw['description'] !== '' ? $raw['description'] : null,
            'contact_email' => $contactEmail,
            'contact_phone' => $raw['contact_phone'] !== '' ? $raw['contact_phone'] : null,
        ];

        try {
            $tournoiId = $this->tournaments->createTournament($data, $this->auth->id() ?? 0);
        } catch (PDOException) {
            return View::render('tournoi/create', [
                'error' => 'Impossible de creer ce tournoi pour le moment.',
                'categories' => $categories,
                'levels' => $levels,
                'old' => $raw,
            ]);
        }

        header("Location: /tournoi/{$tournoiId}");
        exit;
    }

    public function show(int $id): string
    {
        $this->auth->requireAuth();

        $tournoi = $this->tournaments->getTournament($id);
        if (!$tournoi) {
            http_response_code(404);
            return View::render('errors/404', ['path' => '/tournoi/' . $id], null);
        }

        $teams = $this->tournaments->getTeams($id);
        $matches = $this->tournaments->getMatches($id);

        return View::render('tournoi/show', [
            'tournoi' => $tournoi,
            'teams' => $teams,
            'matches' => $matches,
            'isOrganisateur' => $this->isOrganisateur($tournoi),
        ]);
    }

    public function planner(int $id): string
    {
        $this->auth->requireAuth();
        $tournoi = $this->tournaments->getTournament($id);
        if (!$tournoi) {
            http_response_code(404);
            return View::render('errors/404', ['path' => '/tournoi/' . $id], null);
        }

        if (!$this->isOrganisateur($tournoi) && !$this->auth->isAdmin()) {
            http_response_code(403);
            return View::render('errors/403', [], null);
        }

        return View::render('tournoi/planner', [
            'tournoi' => $tournoi,
            'teams' => $this->tournaments->getTeams($id),
            'terrains' => $this->reservations->getTerrains(false),
            'error' => null,
        ]);
    }

    public function addTeam(int $id): string
    {
        $this->auth->requireAuth();
        $tournoi = $this->tournaments->getTournament($id);
        if (!$tournoi) {
            http_response_code(404);
            return View::render('errors/404', ['path' => '/tournoi/' . $id], null);
        }

        if (!$this->isOrganisateur($tournoi) && !$this->auth->isAdmin()) {
            http_response_code(403);
            return View::render('errors/403', [], null);
        }

        $teamName = trim((string) ($_POST['team_name'] ?? ''));
        if ($teamName === '') {
            return View::render('tournoi/show', [
                'tournoi' => $tournoi,
                'teams' => $this->tournaments->getTeams($id),
                'matches' => $this->tournaments->getMatches($id),
                'isOrganisateur' => $this->isOrganisateur($tournoi),
                'error' => 'Le nom de l equipe est requis.',
            ]);
        }

        $this->tournaments->addTeam($id, $teamName);

        header("Location: /tournoi/{$id}");
        exit;
    }

    public function planMatches(int $id): string
    {
        $this->auth->requireAuth();
        $tournoi = $this->tournaments->getTournament($id);
        if (!$tournoi) {
            http_response_code(404);
            return View::render('errors/404', ['path' => '/tournoi/' . $id], null);
        }

        if (!$this->isOrganisateur($tournoi) && !$this->auth->isAdmin()) {
            http_response_code(403);
            return View::render('errors/403', [], null);
        }

        $data = [
            'format' => $tournoi['format'],
            'date_match' => $_POST['date_match'] ?? null,
            'creneau' => $_POST['creneau'] ?? null,
            'terrain_id' => $_POST['terrain_id'] ?? null,
        ];

        try {
            $this->tournaments->planMatches($id, $data);
        } catch (\Throwable $throwable) {
            return View::render('tournoi/planner', [
                'tournoi' => $tournoi,
                'teams' => $this->tournaments->getTeams($id),
                'terrains' => $this->reservations->getTerrains(false),
                'error' => $throwable->getMessage(),
            ]);
        }

        header("Location: /tournoi/{$id}");
        exit;
    }

    /**
     * @param array<string, mixed> $tournoi
     */
    private function isOrganisateur(array $tournoi): bool
    {
        return (int) ($tournoi['organisateur_id'] ?? 0) === ($this->auth->id() ?? 0);
    }
}
