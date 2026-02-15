<?php

namespace gestion\repo;

use gestion\pdo\Database;
use PDO;
use Exception;

class PlatRepository
{
    /**
     * Récupère tous les plats
     */
    public static function obtenirTousLesPlats(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM plat ORDER BY type, libelle");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un plat par son numéro
     */
    public static function obtenirPlatParId(int $numplat): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM plat WHERE numplat = ?");
        $stmt->execute([$numplat]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Récupère les plats disponibles (quantité servie > 0)
     */
    public static function obtenirPlatsDisponibles(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM plat WHERE qteservie > 0 ORDER BY type, libelle");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les plats par type
     */
    public static function obtenirPlatsParType(string $type): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM plat WHERE type = ? ORDER BY libelle");
        $stmt->execute([$type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Modifie le prix unitaire d'un plat
     *
     * @param int $numplat Numéro du plat
     * @param float $nouveauPrix Nouveau prix unitaire
     * @return bool True si la modification a réussi
     */
    public static function modifierPrixPlat(int $numplat, float $nouveauPrix): bool
    {
        $pdo = Database::getConnection();

        $plat = self::obtenirPlatParId($numplat);
        if (!$plat) {
            throw new Exception("Plat numéro $numplat inexistant");
        }

        if ($nouveauPrix < 0) {
            throw new Exception("Le prix ne peut pas être négatif");
        }

        $stmt = $pdo->prepare("UPDATE plat SET prixunit = ? WHERE numplat = ?");
        return $stmt->execute([$nouveauPrix, $numplat]);
    }

    /**
     * Modifie la quantité servie d'un plat
     *
     * @param int $numplat Numéro du plat
     * @param int $nouvelleQuantite Nouvelle quantité servie
     * @return bool True si la modification a réussi
     */
    public static function modifierQuantiteServie(int $numplat, int $nouvelleQuantite): bool
    {
        $pdo = Database::getConnection();

        $plat = self::obtenirPlatParId($numplat);
        if (!$plat) {
            throw new Exception("Plat numéro $numplat inexistant");
        }

        if ($nouvelleQuantite < 0) {
            throw new Exception("La quantité ne peut pas être négative");
        }

        $stmt = $pdo->prepare("UPDATE plat SET qteservie = ? WHERE numplat = ?");
        return $stmt->execute([$nouvelleQuantite, $numplat]);
    }

    /**
     * Décrémente la quantité servie d'un plat
     * Utilisé lors d'une commande
     *
     * @param int $numplat Numéro du plat
     * @param int $quantite Quantité à décrémenter
     * @return bool True si la décrémentation a réussi
     */
    public static function decrementerQuantiteServie(int $numplat, int $quantite): bool
    {
        $pdo = Database::getConnection();

        $plat = self::obtenirPlatParId($numplat);
        if (!$plat) {
            throw new Exception("Plat numéro $numplat inexistant");
        }

        if ($plat['qteservie'] < $quantite) {
            throw new Exception(
                "Quantité insuffisante pour le plat '{$plat['libelle']}'. " .
                "Disponible: {$plat['qteservie']}, Demandé: {$quantite}"
            );
        }

        $stmt = $pdo->prepare("UPDATE plat SET qteservie = qteservie - ? WHERE numplat = ?");
        return $stmt->execute([$quantite, $numplat]);
    }

    /**
     * Vérifie si un plat est disponible en quantité suffisante
     *
     * @param int $numplat Numéro du plat
     * @param int $quantite Quantité demandée
     * @return bool True si disponible
     */
    public static function estDisponible(int $numplat, int $quantite): bool
    {
        $plat = self::obtenirPlatParId($numplat);
        if (!$plat) {
            return false;
        }
        return $plat['qteservie'] >= $quantite;
    }

    /**
     * Récupère les types de plats distincts
     */
    public static function obtenirTypesPlats(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT DISTINCT type FROM plat ORDER BY type");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}