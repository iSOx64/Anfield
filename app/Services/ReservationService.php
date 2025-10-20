<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class ReservationService
{
    private const TERRAIN_SIZES = ['mini', 'moyen', 'grand'];
    private const TERRAIN_TYPES = ['gazon_naturel', 'gazon_artificiel', 'dur'];
    private const RESERVATION_STATUSES = ['confirmee', 'annulee', 'terminee'];
    private const EVENT_TYPES = [
        'match_amical' => 'Match amical',
        'entrainement' => 'Entrainement dirige',
        'tournoi_corporate' => 'Tournoi corporate',
        'anniversaire' => 'Anniversaire sportif',
        'stage' => 'Stage intensif',
    ];
    private const SKILL_LEVELS = [
        'loisir' => 'Loisir',
        'intermediaire' => 'Intermediaire',
        'competitif' => 'Competitif',
    ];
    private const ADDON_SERVICE_COLUMNS = ['ballon', 'arbitre', 'maillot', 'douche', 'coach', 'photographe', 'traiteur'];

    private PricingService $pricing;
    private Mailer $mailer;

    public function __construct(?PricingService $pricingService = null, ?Mailer $mailer = null)
    {
        $this->pricing = $pricingService ?? new PricingService();
        $this->mailer = $mailer ?? new Mailer();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTerrains(bool $onlyDisponibles = true): array
    {
        $sql = 'SELECT * FROM terrain';
        if ($onlyDisponibles) {
            $sql .= ' WHERE disponible = 1';
        }
        $sql .= ' ORDER BY nom';

        $stmt = Database::connection()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, string>
     */
    public function getTerrainSizes(): array
    {
        return self::TERRAIN_SIZES;
    }

    /**
     * @return array<int, string>
     */
    public function getTerrainTypes(): array
    {
        return self::TERRAIN_TYPES;
    }

    public function createTerrain(string $nom, string $taille, string $type, bool $disponible = true, ?string $imagePath = null): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO terrain (nom, taille, type, image_path, disponible) VALUES (:nom, :taille, :type, :image_path, :disponible)'
        );

        $stmt->execute([
            'nom' => $nom,
            'taille' => $taille,
            'type' => $type,
            'image_path' => $imagePath,
            'disponible' => $disponible ? 1 : 0,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @return array<int, string>
     */
    public function getReservationStatuses(): array
    {
        return self::RESERVATION_STATUSES;
    }

    /**
     * @return array<string, string>
     */
    public function getEventTypes(): array
    {
        return self::EVENT_TYPES;
    }

    /**
     * @return array<string, string>
     */
    public function getSkillLevels(): array
    {
        return self::SKILL_LEVELS;
    }

    /**
     * @return array<int, string>
     */
    public function getAddonServices(): array
    {
        return self::ADDON_SERVICE_COLUMNS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getReservationsBetween(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $terrainId = null,
        ?string $statut = null
    ): array {
        $sql = 'SELECT r.*, t.nom AS terrain_nom
                FROM reservation r
                INNER JOIN terrain t ON t.id = r.terrain_id
                WHERE r.date_reservation BETWEEN :start AND :end';
        $params = [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ];

        if ($terrainId !== null) {
            $sql .= ' AND r.terrain_id = :terrain_id';
            $params['terrain_id'] = $terrainId;
        }

        if ($statut !== null && in_array($statut, self::RESERVATION_STATUSES, true)) {
            $sql .= ' AND r.statut = :statut';
            $params['statut'] = $statut;
        }

        $sql .= ' ORDER BY r.date_reservation ASC, r.creneau_horaire ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array{date:string,terrains:array<int,array<string,mixed>>,slots:array<int,array{time:string,terrains:array<int,array<string,mixed>>}>}
     */
    public function getDailySchedule(\DateTimeInterface $date, ?int $terrainId = null): array
    {
        $day = $date->format('Y-m-d');
        $reservations = $this->getReservationsBetween($date, $date, $terrainId, null);

        $terrainList = [];
        if ($terrainId !== null) {
            $single = $this->getTerrain($terrainId);
            if ($single !== null) {
                $terrainList = [$single];
            }
        } else {
            $terrainList = $this->getTerrains(false);
        }

        $reservationMap = [];
        foreach ($reservations as $reservation) {
            $terrainKey = (int) ($reservation['terrain_id'] ?? 0);
            $time = substr((string) ($reservation['creneau_horaire'] ?? ''), 0, 2);
            if ($terrainKey > 0 && $time !== '') {
                if (!isset($reservationMap[$terrainKey])) {
                    $reservationMap[$terrainKey] = [];
                }
                $reservationMap[$terrainKey][$time] = $reservation;
            }
        }

        $slots = [];
        for ($hour = 6; $hour <= 23; $hour++) {
            $hourKey = sprintf('%02d', $hour);
            $slot = [
                'time' => $hourKey . ':00',
                'terrains' => [],
            ];
            foreach ($terrainList as $terrain) {
                $terrainKey = (int) ($terrain['id'] ?? 0);
                $reservation = $reservationMap[$terrainKey][$hourKey] ?? null;
                $slot['terrains'][$terrainKey] = [
                    'reserved' => $reservation !== null,
                    'reservation' => $reservation,
                ];
            }
            $slots[] = $slot;
        }

        return [
            'date' => $day,
            'terrains' => $terrainList,
            'slots' => $slots,
        ];
    }

    private function sanitizeEventType(?string $type): string
    {
        $type = $type !== null ? strtolower(trim($type)) : '';
        return array_key_exists($type, self::EVENT_TYPES) ? $type : array_key_first(self::EVENT_TYPES);
    }

    private function sanitizeSkillLevel(?string $niveau): string
    {
        $niveau = $niveau !== null ? strtolower(trim($niveau)) : '';
        return array_key_exists($niveau, self::SKILL_LEVELS) ? $niveau : array_key_first(self::SKILL_LEVELS);
    }

    private function normalizeParticipants(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $participants = (int) $value;
        if ($participants <= 0) {
            return null;
        }

        return max(2, min(40, $participants));
    }

    public function getTerrain(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM terrain WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        /** @var array<string, mixed>|false $terrain */
        $terrain = $stmt->fetch(PDO::FETCH_ASSOC);
        return $terrain ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getReservationsForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*, t.nom AS terrain_nom, t.taille AS terrain_taille, t.image_path AS terrain_image_path
             FROM reservation r
             INNER JOIN terrain t ON t.id = r.terrain_id
             WHERE r.utilisateur_id = :user
             ORDER BY r.date_reservation DESC, r.creneau_horaire DESC'
        );
        $stmt->execute(['user' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReservation(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*, t.nom AS terrain_nom, t.taille AS terrain_taille, t.image_path AS terrain_image_path,
                    u.nom AS client_nom, u.prenom AS client_prenom, u.email AS client_email,
                    f.montant_terrain, f.montant_service, f.total AS facture_total
             FROM reservation r
             INNER JOIN terrain t ON t.id = r.terrain_id
             INNER JOIN utilisateur u ON u.id = r.utilisateur_id
             LEFT JOIN facture f ON f.reservation_id = r.id
             WHERE r.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        /** @var array<string, mixed>|false $reservation */
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        return $reservation ?: null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createReservation(array $data, int $userId): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        $reservationId = 0;

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO reservation (terrain_id, utilisateur_id, date_reservation, creneau_horaire, demande,
                 type_evenement, niveau, participants, ballon, arbitre, maillot, douche, coach, photographe, traiteur, statut)
                 VALUES (:terrain_id, :utilisateur_id, :date_reservation, :creneau_horaire, :demande,
                 :type_evenement, :niveau, :participants, :ballon, :arbitre, :maillot, :douche, :coach, :photographe, :traiteur, :statut)'
            );

            $typeEvenement = $this->sanitizeEventType($data['type_evenement'] ?? null);
            $niveau = $this->sanitizeSkillLevel($data['niveau'] ?? null);
            $participants = $this->normalizeParticipants($data['participants'] ?? null);

            $payload = [
                'terrain_id' => (int) $data['terrain_id'],
                'utilisateur_id' => $userId,
                'date_reservation' => $data['date_reservation'],
                'creneau_horaire' => $data['creneau_horaire'],
                'demande' => $data['demande'] ?? null,
                'type_evenement' => $typeEvenement,
                'niveau' => $niveau,
                'participants' => $participants,
                'statut' => 'confirmee',
            ];

            foreach (self::ADDON_SERVICE_COLUMNS as $service) {
                $payload[$service] = $this->toFlag($data[$service] ?? false);
            }

            $stmt->execute($payload);
            $reservationId = (int) $pdo->lastInsertId();

            $totals = $this->calculateTotals($reservationId);
            $this->storeInvoice($reservationId, $totals['terrain'], $totals['service'], $totals['total']);

            $pdo->commit();
        } catch (\Throwable $throwable) {
            $pdo->rollBack();
            throw $throwable;
        }

        $reservation = $this->getReservation($reservationId);
        if ($reservation) {
            $this->mailer->sendReservationConfirmation($reservation);
        }

        return $reservationId;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateReservation(int $id, array $data, int $userId, bool $isAdmin = false): bool
    {
        $reservation = $this->getReservation($id);
        if (!$reservation) {
            return false;
        }

        if (!$isAdmin && (int) $reservation['utilisateur_id'] !== $userId) {
            return false;
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'UPDATE reservation SET terrain_id = :terrain_id, date_reservation = :date_reservation,
                 creneau_horaire = :creneau_horaire, demande = :demande,
                 type_evenement = :type_evenement, niveau = :niveau, participants = :participants,
                 ballon = :ballon, arbitre = :arbitre, maillot = :maillot, douche = :douche,
                 coach = :coach, photographe = :photographe, traiteur = :traiteur
                 WHERE id = :id'
            );

            $typeEvenement = $this->sanitizeEventType($data['type_evenement'] ?? ($reservation['type_evenement'] ?? null));
            $niveau = $this->sanitizeSkillLevel($data['niveau'] ?? ($reservation['niveau'] ?? null));
            $participants = $this->normalizeParticipants($data['participants'] ?? ($reservation['participants'] ?? null));

            $payload = [
                'terrain_id' => (int) $data['terrain_id'],
                'date_reservation' => $data['date_reservation'],
                'creneau_horaire' => $data['creneau_horaire'],
                'demande' => $data['demande'] ?? null,
                'type_evenement' => $typeEvenement,
                'niveau' => $niveau,
                'participants' => $participants,
                'id' => $id,
            ];

            foreach (self::ADDON_SERVICE_COLUMNS as $service) {
                $payload[$service] = $this->toFlag($data[$service] ?? $reservation[$service] ?? false);
            }

            $stmt->execute($payload);

            $totals = $this->calculateTotals($id);
            $this->storeInvoice($id, $totals['terrain'], $totals['service'], $totals['total'], true);

            $pdo->commit();
        } catch (\Throwable $throwable) {
            $pdo->rollBack();
            throw $throwable;
        }

        return true;
    }

    public function cancelReservation(int $id, int $userId, bool $isAdmin = false): bool
    {
        $reservation = $this->getReservation($id);
        if (!$reservation) {
            return false;
        }

        if (!$isAdmin && (int) $reservation['utilisateur_id'] !== $userId) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            "UPDATE reservation SET statut = 'annulee' WHERE id = :id"
        );

        return $stmt->execute(['id' => $id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingReservations(\DateTimeInterface $from, ?int $terrainId = null): array
    {
        $sql = 'SELECT r.*, t.nom AS terrain_nom
                FROM reservation r
                INNER JOIN terrain t ON t.id = r.terrain_id
                WHERE r.date_reservation >= :date';
        $params = ['date' => $from->format('Y-m-d')];

        if ($terrainId !== null) {
            $sql .= ' AND r.terrain_id = :terrainId';
            $params['terrainId'] = $terrainId;
        }

        $sql .= ' ORDER BY r.date_reservation ASC, r.creneau_horaire ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, float>
     */
    private function calculateTotals(int $reservationId): array
    {
        $reservation = $this->getReservation($reservationId);
        if (!$reservation) {
            return ['terrain' => 0.0, 'service' => 0.0, 'total' => 0.0];
        }

        $terrainPrice = $this->pricing->getPrice('terrain', (string) $reservation['terrain_taille']) ?? 0.0;

        $serviceTotal = 0.0;
        foreach (self::ADDON_SERVICE_COLUMNS as $service) {
            if ((int) $reservation[$service] === 1) {
                $servicePrice = $this->pricing->getPrice('service', $service) ?? 0.0;
                $serviceTotal += $servicePrice;
            }
        }

        $total = $terrainPrice + $serviceTotal;

        return [
            'terrain' => $terrainPrice,
            'service' => $serviceTotal,
            'total' => $total,
        ];
    }

    private function storeInvoice(
        int $reservationId,
        float $montantTerrain,
        float $montantService,
        float $total,
        bool $update = false
    ): void {
        $pdo = Database::connection();
        if ($update) {
            $updateStmt = $pdo->prepare(
                'UPDATE facture
                 SET montant_terrain = :montant_terrain, montant_service = :montant_service, total = :total
                 WHERE reservation_id = :reservation_id'
            );
            $updateStmt->execute([
                'reservation_id' => $reservationId,
                'montant_terrain' => $montantTerrain,
                'montant_service' => $montantService,
                'total' => $total,
            ]);
            if ($updateStmt->rowCount() > 0) {
                return;
            }
        }

        $insertStmt = $pdo->prepare(
            'INSERT INTO facture (reservation_id, montant_terrain, montant_service, total)
             VALUES (:reservation_id, :montant_terrain, :montant_service, :total)'
        );

        $insertStmt->execute([
            'reservation_id' => $reservationId,
            'montant_terrain' => $montantTerrain,
            'montant_service' => $montantService,
            'total' => $total,
        ]);
    }

    private function toFlag(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (int) ((int) $value === 1);
        }

        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $bool ? 1 : 0;
    }
}
