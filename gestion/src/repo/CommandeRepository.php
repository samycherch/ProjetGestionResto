<?php

namespace gestion\repo;

use gestion\pdo\Database;
use PDO;
use Exception;

class CommandeRepository
{
    /**
     * Ajoute un plat à une réservation (crée une ligne de commande)
     *
     * @param int $numres Numéro de la réservation
     * @param int $numplat Numéro du plat
     * @param int $quantite Quantité commandée
     * @return bool True si l'ajout a réussi
     */
    public static function ajouterPlatAReservation(int $numres, int $numplat, int $quantite): bool
    {
        $pdo = Database::getConnection();

        // Vérifier que la réservation existe
        $reservation = ReservationRepository::obtenirReservationParId($numres);
        if (!$reservation) {
            throw new Exception("Réservation numéro $numres inexistante");
        }

        // Vérifier que la réservation n'est pas encore payée
        if ($reservation['datpaie'] !== null) {
            throw new Exception("Cette réservation a déjà été encaissée");
        }

        // Vérifier que le plat existe et est disponible
        $plat = PlatRepository::obtenirPlatParId($numplat);
        if (!$plat) {
            throw new Exception("Plat numéro $numplat inexistant");
        }

        if ($quantite <= 0) {
            throw new Exception("La quantité doit être positive");
        }

        // Vérifier la disponibilité
        if (!PlatRepository::estDisponible($numplat, $quantite)) {
            throw new Exception(
                "Quantité insuffisante pour le plat '{$plat['libelle']}'. " .
                "Disponible: {$plat['qteservie']}, Demandé: {$quantite}"
            );
        }

        // Vérifier si le plat est déjà commandé pour cette réservation
        $stmt = $pdo->prepare("SELECT quantite FROM commande WHERE numres = ? AND numplat = ?");
        $stmt->execute([$numres, $numplat]);
        $commandeExistante = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($commandeExistante) {
            // Mettre à jour la quantité existante
            $nouvelleQuantite = $commandeExistante['quantite'] + $quantite;
            
            // Vérifier que la nouvelle quantité est disponible
            if (!PlatRepository::estDisponible($numplat, $quantite)) {
                throw new Exception(
                    "Quantité insuffisante pour ajouter {$quantite} de '{$plat['libelle']}'"
                );
            }

            $stmt = $pdo->prepare("UPDATE commande SET quantite = ? WHERE numres = ? AND numplat = ?");
            $result = $stmt->execute([$nouvelleQuantite, $numres, $numplat]);
        } else {
            // Insérer une nouvelle commande
            $stmt = $pdo->prepare("INSERT INTO commande (numres, numplat, quantite) VALUES (?, ?, ?)");
            $result = $stmt->execute([$numres, $numplat, $quantite]);
        }

        // Décrémenter la quantité servie du plat
        if ($result) {
            PlatRepository::decrementerQuantiteServie($numplat, $quantite);
        }

        return $result;
    }

    /**
     * Récupère toutes les commandes d'une réservation avec les détails des plats
     *
     * @param int $numres Numéro de la réservation
     * @return array Liste des plats commandés avec leurs informations
     */
    public static function obtenirCommandesReservation(int $numres): array
    {
        $pdo = Database::getConnection();

        $sql = <<<SQL
            SELECT 
                c.numplat,
                c.quantite,
                p.libelle,
                p.type,
                p.prixunit,
                (c.quantite * p.prixunit) as total_ligne
            FROM commande c
            JOIN plat p ON c.numplat = p.numplat
            WHERE c.numres = ?
            ORDER BY p.type, p.libelle
        SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$numres]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcule le montant total d'une réservation
     *
     * @param int $numres Numéro de la réservation
     * @return float Montant total
     */
    public static function calculerMontantTotal(int $numres): float
    {
        $pdo = Database::getConnection();

        $sql = <<<SQL
            SELECT SUM(c.quantite * p.prixunit) as total
            FROM commande c
            JOIN plat p ON c.numplat = p.numplat
            WHERE c.numres = ?
        SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$numres]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (float) ($result['total'] ?? 0);
    }

    /**
     * Supprime un plat d'une commande (annule une ligne de commande)
     *
     * @param int $numres Numéro de la réservation
     * @param int $numplat Numéro du plat
     * @return bool True si la suppression a réussi
     */
    public static function supprimerPlatCommande(int $numres, int $numplat): bool
    {
        $pdo = Database::getConnection();

        // Récupérer la quantité avant suppression pour la remettre en stock
        $stmt = $pdo->prepare("SELECT quantite FROM commande WHERE numres = ? AND numplat = ?");
        $stmt->execute([$numres, $numplat]);
        $commande = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$commande) {
            throw new Exception("Cette commande n'existe pas");
        }

        // Remettre la quantité en stock
        $stmt = $pdo->prepare("UPDATE plat SET qteservie = qteservie + ? WHERE numplat = ?");
        $stmt->execute([$commande['quantite'], $numplat]);

        // Supprimer la ligne de commande
        $stmt = $pdo->prepare("DELETE FROM commande WHERE numres = ? AND numplat = ?");
        return $stmt->execute([$numres, $numplat]);
    }

    /**
     * Vérifie si une réservation a des commandes
     *
     * @param int $numres Numéro de la réservation
     * @return bool True si la réservation a des commandes
     */
    public static function reservationADesCommandes(int $numres): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM commande WHERE numres = ?");
        $stmt->execute([$numres]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Récupère le nombre de plats commandés pour une réservation
     *
     * @param int $numres Numéro de la réservation
     * @return int Nombre de plats différents commandés
     */
    public static function compterPlatsCommandes(int $numres): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM commande WHERE numres = ?");
        $stmt->execute([$numres]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }
}