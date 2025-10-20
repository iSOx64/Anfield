<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class TournamentService
{
    private const CATEGORIES = [
        'corporate' => 'Corporate',
        'association' => 'Association',
        'academie' => 'Academie',
        'jeunes' => 'Jeunes',
        'mixte' => 'Mixte',
    ];

    private const LEVELS = [
        'loisir' => 'Loisir',
        'intermediaire' => 'Intermediaire',
        'elite' => 'Elite',
    ];

    /**
     * @return array<string, string>
     */
    public function getCategories(): array
    {
        return self::CATEGORIES;
    }

    /**
     * @return array<string, string>
     */
    public function getLevels(): array
    {
        return self::LEVELS;
    }

    public function createTournament(array $data, int $organisateurId): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO tournoi (
                nom, format, organisateur_id, categorie, niveau, date_debut, date_fin,
                lieu, frais_inscription, recompense, description, contact_email, contact_phone
            ) VALUES (
                :nom, :format, :organisateur_id, :categorie, :niveau, :date_debut, :date_fin,
                :lieu, :frais_inscription, :recompense, :description, :contact_email, :contact_phone
            )'
        );
        $stmt->execute([
            'nom' => $data['nom'],
            'format' => $data['format'],
            'organisateur_id' => $organisateurId,
            'categorie' => $data['categorie'] ?? null,
            'niveau' => $data['niveau'] ?? null,
            'date_debut' => $data['date_debut'] ?? null,
            'date_fin' => $data['date_fin'] ?? null,
            'lieu' => $data['lieu'] ?? null,
            'frais_inscription' => $data['frais_inscription'] ?? null,
            'recompense' => $data['recompense'] ?? null,
            'description' => $data['description'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function getTournament(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.*, u.nom AS organisateur_nom, u.prenom AS organisateur_prenom
             FROM tournoi t
             INNER JOIN utilisateur u ON u.id = t.organisateur_id
             WHERE t.id = :id'
        );
        $stmt->execute(['id' => $id]);
        /** @var array<string, mixed>|false $tournoi */
        $tournoi = $stmt->fetch(PDO::FETCH_ASSOC);
        return $tournoi ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTournaments(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.*, COUNT(e.id) AS equipes
             FROM tournoi t
             LEFT JOIN equipe e ON e.tournoi_id = t.id
             GROUP BY t.id
             ORDER BY t.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTeams(int $tournoiId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM equipe WHERE tournoi_id = :id ORDER BY id'
        );
        $stmt->execute(['id' => $tournoiId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addTeam(int $tournoiId, string $teamName): bool
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO equipe (tournoi_id, nom) VALUES (:tournoi_id, :nom)'
        );

        return $stmt->execute([
            'tournoi_id' => $tournoiId,
            'nom' => $teamName,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMatches(int $tournoiId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.*, ta.nom AS equipe_a, tb.nom AS equipe_b, tr.nom AS terrain_nom
             FROM match_tournoi m
             LEFT JOIN equipe ta ON ta.id = m.equipe_a_id
             LEFT JOIN equipe tb ON tb.id = m.equipe_b_id
             LEFT JOIN terrain tr ON tr.id = m.terrain_id
             WHERE m.tournoi_id = :id
             ORDER BY m.round ASC, m.id ASC'
        );
        $stmt->execute(['id' => $tournoiId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Génère les matchs du premier tour et enregistre les créneaux proposés.
     *
     * @param array<string, mixed> $data
     */
    public function planMatches(int $tournoiId, array $data): void
    {
        $teams = $this->getTeams($tournoiId);
        if (count($teams) < 2) {
            throw new \RuntimeException('Le tournoi doit comporter au moins deux équipes.');
        }

        $format = (int) $data['format'];
        if (count($teams) !== $format) {
            throw new \RuntimeException("Il faut exactement {$format} équipes pour générer le bracket.");
        }

        $this->clearMatches($tournoiId);

        $pairs = $this->pairTeams($teams);
        foreach ($pairs as $index => $pair) {
            $stmt = Database::connection()->prepare(
                'INSERT INTO match_tournoi (tournoi_id, round, equipe_a_id, equipe_b_id, date_match, creneau_horaire, terrain_id)
                 VALUES (:tournoi_id, :round, :equipe_a, :equipe_b, :date_match, :creneau, :terrain)'
            );

            $stmt->execute([
                'tournoi_id' => $tournoiId,
                'round' => 1,
                'equipe_a' => $pair[0]['id'],
                'equipe_b' => $pair[1]['id'],
                'date_match' => $data['date_match'] ?? null,
                'creneau' => $data['creneau'] ?? null,
                'terrain' => $data['terrain_id'] ?? null,
            ]);
        }
    }

    private function clearMatches(int $tournoiId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM match_tournoi WHERE tournoi_id = :id');
        $stmt->execute(['id' => $tournoiId]);
    }

    /**
     * @param array<int, array<string, mixed>> $teams
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function pairTeams(array $teams): array
    {
        shuffle($teams);
        $pairs = [];
        for ($i = 0; $i < count($teams); $i += 2) {
            $pairs[] = [$teams[$i], $teams[$i + 1]];
        }

        return $pairs;
    }
}
