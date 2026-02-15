<?php

namespace gestion\repo;

use gestion\pdo\Database;
use PDO;
use Exception;

class ReservationRepository
{
    /**
     * Crée une nouvelle réservation
     *
     * @param int $numtab Numéro de la table
     * @param string $serveur Nom du serveur
     * @param string $dateHeure Format: 'YYYY-MM-DD HH:MM:SS'
     * @param int $nbPersonnes Nombre de personnes
     * @return int Numéro de la réservation créée
     */
    public static function creeReservation(int $numtab,string $serveur,string $dateHeure,int $nbPersonnes): int {
        $pdo = Database::getConnection();

        try {
            // Vérifier que la table existe
            $table = TableRepository::obtenirTableParId($numtab);
            if (!$table) {
                throw new Exception("Table numéro $numtab inexistante");
            }

            // Vérifier que la table a assez de places
            if ($table['nbplace'] < $nbPersonnes) {
                throw new Exception(
                    "La table {$numtab} n'a que {$table['nbplace']} places pour $nbPersonnes personnes"
                );
            }

            // Vérifier la disponibilité de la table
            if (!TableRepository::tableEstDispo($numtab, $dateHeure)) {
                throw new Exception("La table $numtab n'est pas disponible à cette date/heure");
            }

            // Valider le format de la date
            $dateObj = \DateTime::createFromFormat('Y-m-d H:i:s', $dateHeure);
            if (!$dateObj) {
                throw new Exception(
                    "Format de date invalide. Utilisez: YYYY-MM-DD HH:MM:SS"
                );
            }

            // Insérer la réservation
            $stmt = $pdo->prepare(
                "INSERT INTO reservation (numtab, serveur, datres, nbpers) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$numtab, $serveur, $dateHeure, $nbPersonnes]);

            return (int) $pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la création de la réservation: " . $e->getMessage());
        }
    }

    /**
     * Récupère une réservation par son numéro
     */
    public static function obtenirReservationParId(int $numres): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM reservation WHERE numres = ?");
        $stmt->execute([$numres]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupère toutes les réservations
     */
    public static function obtenirToutesLesReservations(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM reservation ORDER BY datres DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les réservations d'un serveur
     */
    public static function obtenirReservationsParServeur(string $serveur): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM reservation WHERE serveur = ? ORDER BY datres DESC");
        $stmt->execute([$serveur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les réservations à une date donnée
     */
    public static function obtenirReservationsParDate(string $date): array
    {
        $pdo = Database::getConnection();

        $dateStart = $date . " 00:00:00";
        $dateEnd = $date . " 23:59:59";

        $stmt = $pdo->prepare(
            "SELECT * FROM reservation WHERE datres BETWEEN ? AND ? ORDER BY datres"
        );
        $stmt->execute([$dateStart, $dateEnd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les réservations à venir (futures)
     */
    public static function obtenirReservationsAVenir(): array
    {
        $pdo = Database::getConnection();
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare(
            "SELECT * FROM reservation WHERE datres > ? ORDER BY datres ASC"
        );
        $stmt->execute([$now]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Modifie une réservation
     *
     * @param int $numres Numéro de la réservation
     * @param array $data Données à modifier (numtab, serveur, datres, nbpers)
     * @return bool True si la modification a réussi
     */
    public static function mettreAJourReservation(int $numres, array $data): bool
    {
        $pdo = Database::getConnection();

        $reservation = self::obtenirReservationParId($numres);
        if (!$reservation) {
            throw new Exception("Réservation numéro $numres inexistante");
        }

        // Fusionner les données existantes avec les nouvelles
        $updated = array_merge($reservation, $data);

        // Si la table change, vérifier la disponibilité
        if (isset($data['numtab']) || isset($data['datres'])) {
            $numtab = $data['numtab'] ?? $reservation['numtab'];
            $dateHeure = $data['datres'] ?? $reservation['datres'];

            // Vérifier que la table existe
            $table = TableRepository::obtenirTableParId($numtab);
            if (!$table) {
                throw new Exception("Table numéro $numtab inexistante");
            }

            // Vérifier la disponibilité (en excluant la réservation actuelle)
            $nbPersonnes = $data['nbpers'] ?? $reservation['nbpers'];
            if ($table['nbplace'] < $nbPersonnes) {
                throw new Exception(
                    "La table {$numtab} n'a que {$table['nbplace']} places pour {$nbPersonnes} personnes"
                );
            }

            // Vérifier que la table est disponible à la nouvelle date/heure
            if (!self::tableDisponiblePourMaj($numtab, $dateHeure, $numres)) {
                throw new Exception("La table $numtab n'est pas disponible à cette date/heure");
            }
        }

        // Construire la requête UPDATE dynamiquement
        $setClause = [];
        $values = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['numtab', 'serveur', 'datres', 'nbpers', 'datpaie', 'modpaie', 'montcom'])) {
                $setClause[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (empty($setClause)) {
            return false;
        }

        $values[] = $numres;
        $sql = "UPDATE reservation SET " . implode(", ", $setClause) . " WHERE numres = ?";
        $stmt = $pdo->prepare($sql);

        try {
            return $stmt->execute($values);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la modification de la réservation: " . $e->getMessage());
        }
    }

    /**
     * Supprime une réservation
     */
    public static function supprimerReservation(int $numres): bool
    {
        $pdo = Database::getConnection();

        $reservation = self::obtenirReservationParId($numres);
        if (!$reservation) {
            throw new Exception("Réservation numéro $numres inexistante");
        }

        $stmt = $pdo->prepare("DELETE FROM reservation WHERE numres = ?");
        return $stmt->execute([$numres]);
    }

    /**
     * Vérifie si une table est disponible pour une mise à jour (exclut la réservation actuelle)
     *
     * @param int $numtab Numéro de la table
     * @param string $dateHeure Format: 'YYYY-MM-DD HH:MM:SS'
     * @param int $numresExclue Numéro de réservation à exclure de la vérification
     * @param int $dureeMinutes Durée estimée en minutes (défaut: 120)
     * @return bool True si la table est disponible
     */
    private static function tableDisponiblePourMaj(
        int $numtab,
        string $dateHeure,
        int $numresExclue,
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
            AND numres != ?
            AND datres < ? AND ? < DATE_ADD(datres, INTERVAL ? MINUTE)
            SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$numtab, $numresExclue, $dateFinStr, $dateDebutStr, $dureeMinutes]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] == 0;
    }

    /**
     * Valide une réservation (enregistre la date et le mode de paiement)
     *
     * @param int $numres Numéro de la réservation
     * @param string $modpaie Mode de paiement (Carte, Chèque, Espèces)
     * @param float|null $montcom Montant commandé
     * @return bool True si la validation a réussi
     */
    public static function validerReservation(
        int $numres,
        string $modpaie,
        ?float $montcom = null
    ): bool {
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        return self::mettreAJourReservation($numres, [
            'datpaie' => $now,
            'modpaie' => $modpaie,
            'montcom' => $montcom,
        ]);
    }

    public static function obtenirReservationsNonEncaisseesTransaction(): array
    {
        try {
            Database::beginTransaction();
            $reservations = self::obtenirReservationsNonEncaissees();
            Database::commit();
            return $reservations;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Récupère les réservations non encore encaissées (sans transaction - pour usage interne)
     */
    public static function obtenirReservationsNonEncaissees(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM reservation WHERE datpaie IS NULL ORDER BY datres");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
