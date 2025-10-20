<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class AdminAnalytics
{
    /**
     * @return array<string, int|float>
     */
    public function getSnapshot(): array
    {
        $connection = Database::connection();

        $totalUsers = (int) ($connection->query('SELECT COUNT(*) FROM utilisateur')->fetchColumn() ?: 0);
        $adminUsers = (int) ($connection->query("SELECT COUNT(*) FROM utilisateur WHERE role = 'admin'")->fetchColumn() ?: 0);
        $activeTerrains = (int) ($connection->query('SELECT COUNT(*) FROM terrain WHERE disponible = 1')->fetchColumn() ?: 0);

        $today = new \DateTimeImmutable('today');
        $weekAhead = $today->modify('+7 days');

        $reservationStmt = $connection->prepare(
            'SELECT COUNT(*) FROM reservation WHERE date_reservation BETWEEN :start_date AND :end_date'
        );
        $reservationStmt->execute([
            'start_date' => $today->format('Y-m-d'),
            'end_date' => $weekAhead->format('Y-m-d'),
        ]);
        $reservationsWeek = (int) ($reservationStmt->fetchColumn() ?: 0);

        $reservationTodayStmt = $connection->prepare(
            'SELECT COUNT(*) FROM reservation WHERE date_reservation = :today'
        );
        $reservationTodayStmt->execute([
            'today' => $today->format('Y-m-d'),
        ]);
        $reservationsToday = (int) ($reservationTodayStmt->fetchColumn() ?: 0);

        $currentMonthStart = $today->modify('first day of this month');
        $nextMonthStart = $currentMonthStart->modify('+1 month');
        $previousMonthStart = $currentMonthStart->modify('-1 month');

        $revenueCurrent = $this->sumRevenueBetween($currentMonthStart, $nextMonthStart);
        $revenuePrevious = $this->sumRevenueBetween($previousMonthStart, $currentMonthStart);

        $revenueChange = 0.0;
        if ($revenuePrevious > 0.0) {
            $revenueChange = (($revenueCurrent - $revenuePrevious) / $revenuePrevious) * 100;
        } elseif ($revenueCurrent > 0.0) {
            $revenueChange = 100.0;
        }

        $newUsersStmt = $connection->prepare(
            'SELECT COUNT(*) FROM utilisateur WHERE created_at >= :month_start'
        );
        $newUsersStmt->execute([
            'month_start' => $currentMonthStart->format('Y-m-d 00:00:00'),
        ]);
        $newUsersMonth = (int) ($newUsersStmt->fetchColumn() ?: 0);

        return [
            'total_users' => $totalUsers,
            'admin_users' => $adminUsers,
            'active_terrains' => $activeTerrains,
            'reservations_week' => $reservationsWeek,
            'reservations_today' => $reservationsToday,
            'revenue_month' => $revenueCurrent,
            'revenue_change' => $revenueChange,
            'new_users_month' => $newUsersMonth,
        ];
    }

    /**
     * @return array<int, array{period:string,total:float}>
     */
    public function getRevenueTrend(int $months = 6): array
    {
        $months = max(1, min($months, 12));
        $connection = Database::connection();

        $startDate = (new \DateTimeImmutable('first day of this month'))->modify(sprintf('-%d months', $months - 1));

        $stmt = $connection->prepare(
            'SELECT DATE_FORMAT(created_at, "%Y-%m") AS period, SUM(total) AS total
             FROM facture
             WHERE created_at >= :start_date
             GROUP BY period
             ORDER BY period ASC'
        );
        $stmt->execute([
            'start_date' => $startDate->format('Y-m-d 00:00:00'),
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $trend = [];

        $period = $startDate;
        for ($i = 0; $i < $months; $i++) {
            $periodKey = $period->format('Y-m');
            $trend[$periodKey] = 0.0;
            $period = $period->modify('+1 month');
        }

        foreach ($rows as $row) {
            $periodKey = (string) ($row['period'] ?? '');
            if ($periodKey === '') {
                continue;
            }
            $trend[$periodKey] = (float) ($row['total'] ?? 0.0);
        }

        $result = [];
        foreach ($trend as $periodKey => $total) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $periodKey . '-01');
            $label = $date instanceof \DateTimeImmutable ? $date->format('M Y') : $periodKey;
            $result[] = [
                'period' => $label,
                'total' => $total,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{nom:string,reservations:int,disponible:bool}>
     */
    public function getTopTerrains(int $limit = 5): array
    {
        $limit = max(1, min($limit, 10));
        $connection = Database::connection();

        $startDate = (new \DateTimeImmutable('today'))->modify('-90 days')->format('Y-m-d');

        $sql = sprintf(
            'SELECT t.nom, t.disponible, COUNT(r.id) AS reservations
             FROM terrain t
             LEFT JOIN reservation r ON r.terrain_id = t.id AND r.date_reservation >= :start_date
             GROUP BY t.id, t.nom, t.disponible
             ORDER BY reservations DESC, t.nom ASC
             LIMIT %d',
            $limit
        );

        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'start_date' => $startDate,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'nom' => (string) ($row['nom'] ?? ''),
                'reservations' => (int) ($row['reservations'] ?? 0),
                'disponible' => (int) ($row['disponible'] ?? 0) === 1,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{id:int,nom:string,disponible:bool,reservations:int,share:float}>
     */
    public function getTerrainUtilization(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $connection = Database::connection();
        $stmt = $connection->prepare(
            'SELECT t.id, t.nom, t.disponible, COUNT(r.id) AS reservations
             FROM terrain t
             LEFT JOIN reservation r
               ON r.terrain_id = t.id
              AND r.date_reservation BETWEEN :start_date AND :end_date
             GROUP BY t.id, t.nom, t.disponible
             ORDER BY reservations DESC, t.nom ASC'
        );
        $stmt->execute([
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ]);

        $totalReservations = 0;
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $totalReservations += (int) ($row['reservations'] ?? 0);
        }

        $result = [];
        foreach ($rows as $row) {
            $reservations = (int) ($row['reservations'] ?? 0);
            $share = $totalReservations > 0
                ? round(($reservations / $totalReservations) * 100, 1)
                : 0.0;
            $result[] = [
                'id' => (int) ($row['id'] ?? 0),
                'nom' => (string) ($row['nom'] ?? ''),
                'disponible' => (int) ($row['disponible'] ?? 0) === 1,
                'reservations' => $reservations,
                'share' => $share,
            ];
        }

        return $result;
    }

    private function sumRevenueBetween(\DateTimeImmutable $startInclusive, \DateTimeImmutable $endExclusive): float
    {
        $connection = Database::connection();
        $stmt = $connection->prepare(
            'SELECT SUM(total) FROM facture WHERE created_at >= :start_date AND created_at < :end_date'
        );
        $stmt->execute([
            'start_date' => $startInclusive->format('Y-m-d 00:00:00'),
            'end_date' => $endExclusive->format('Y-m-d 00:00:00'),
        ]);

        return (float) ($stmt->fetchColumn() ?: 0.0);
    }
}
