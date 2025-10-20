<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class PricingService
{
    /**
     * @return array<string, array{reference:string, description:string|null, prix:float}>
     */
    public function getTerrainPrices(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT reference, description, prix FROM prix WHERE categorie = 'terrain' ORDER BY reference"
        );
        $stmt->execute();
        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[$row['reference']] = [
                'reference' => $row['reference'],
                'description' => $row['description'],
                'prix' => (float) $row['prix'],
            ];
        }

        return $results;
    }

    /**
     * @return array<string, array{reference:string, description:string|null, prix:float}>
     */
    public function getServicePrices(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT reference, description, prix FROM prix WHERE categorie = 'service' ORDER BY reference"
        );
        $stmt->execute();
        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[$row['reference']] = [
                'reference' => $row['reference'],
                'description' => $row['description'],
                'prix' => (float) $row['prix'],
            ];
        }

        return $results;
    }

    public function getPrice(string $categorie, string $reference): ?float
    {
        $stmt = Database::connection()->prepare(
            'SELECT prix FROM prix WHERE categorie = :categorie AND reference = :reference LIMIT 1'
        );
        $stmt->execute([
            'categorie' => $categorie,
            'reference' => $reference,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float) $row['prix'] : null;
    }
}
