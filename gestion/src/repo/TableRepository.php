<?php

namespace gestion\repo;

use gestion\pdo\Database;
use PDO;
use Exception;

class TableRepository
{
    /**
     * Récupère toutes les tables
     */
    public static function obtenirToutesLesTables(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM tabl ORDER BY numtab");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une table par son numéro
     */
    public static function obtenirTableParId(int $numtab): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM tabl WHERE numtab = ?");
        $stmt->execute([$numtab]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupère les tables disponibles pour une date, heure et nombre de personnes donnés
     *
     * @param string $dateHeure Format: 'YYYY-MM-DD HH:MM:SS'
     * @param int $nbPersonnes Nombre de personnes
     * @param int $dureeMinutes Durée estimée de la réservation en minutes (défaut: 120)
     * @return array Liste des tables disponibles
     */
    public static function obtenirTablesDisponibles(
        string $dateHeure,
        int $nbPersonnes,
        int $dureeMinutes = 120
    ): array {
        $pdo = Database::getConnection();

        // Calculer l'heure de fin de la réservation
        $dateDebut = new \DateTime($dateHeure);
        $dateFin = clone $dateDebut;
        $dateFin->modify("+{$dureeMinutes} minutes");

        $dateDebutStr = $dateDebut->format('Y-m-d H:i:s');
        $dateFinStr = $dateFin->format('Y-m-d H:i:s');

        // Trouver les tables avec assez de places et qui ne sont pas occupées
        // une table est occupée si il y a une réservation qui se chevauche
        $sql = <<<SQL
            SELECT DISTINCT t.numtab, t.nbplace
            FROM tabl t
            WHERE t.nbplace >= ?
            AND t.numtab NOT IN (
                SELECT r.numtab
                FROM reservation r
                WHERE r.datres < ? AND ? < DATE_ADD(r.datres, INTERVAL ? MINUTE)
            )
            ORDER BY t.nbplace ASC
            SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nbPersonnes, $dateFinStr, $dateDebutStr, $dureeMinutes]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si une table est disponible à une date/heure donnée
     *
     * @param int $numtab Numéro de la table
     * @param string $dateHeure Format: 'YYYY-MM-DD HH:MM:SS'
     * @param int $dureeMinutes Durée estimée en minutes (défaut: 120)
     * @return bool True si la table est disponible
     */
    public static function tableEstDispo(
        int $numtab,
        string $dateHeure,
        int $dureeMinutes = 120
    ): bool {
        $pdo = Database::getConnection();

        $dateDebut = new \DateTime($dateHeure);
        $dateFin = clone $dateDebut;
        $dateFin->modify("+{$dureeMinutes} minutes");

        $dateDebutStr = $dateDebut->format('Y-m-d H:i:s');
        $dateFinStr = $dateFin->format('Y-m-d H:i:s');

        $sql = <<<SQL
            SELECT COUNT(*) as count
            FROM reservation
            WHERE numtab = ?
            AND datres < ? AND ? < DATE_ADD(datres, INTERVAL ? MINUTE)
            SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$numtab, $dateFinStr, $dateDebutStr, $dureeMinutes]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] == 0;
    }

    /**
     * Récupère les réservations d'une table à une date donnée
     *
     * @param int $numtab Numéro de la table
     * @param string $date Format: 'YYYY-MM-DD'
     * @return array Liste des réservations
     */
    public static function obtenirReservationsTableParDate(int $numtab, string $date): array
    {
        $pdo = Database::getConnection();

        $dateStart = $date . " 00:00:00";
        $dateEnd = $date . " 23:59:59";

        $stmt = $pdo->prepare(
            "SELECT * FROM reservation WHERE numtab = ? AND datres BETWEEN ? AND ? ORDER BY datres"
        );
        $stmt->execute([$numtab, $dateStart, $dateEnd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
