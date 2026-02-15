<?php

namespace gestion\repo;

use gestion\pdo\Database;
use PDO;
use Exception;

class ServerRepository
{
    /**
     * Obtient tous les serveurs actifs
     *
     * @return array Liste des serveurs actifs
     */
    public static function obtenirTousLesServeurs(): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT numserv, nom, prenom, image, actif FROM serveur WHERE actif = 1 ORDER BY prenom, nom";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtient un serveur par son ID
     *
     * @param int $numserv Numéro du serveur
     * @return array|false Données du serveur ou false
     */
    public static function obtenirServerParId(int $numserv)
    {
        $pdo = Database::getConnection();
        $sql = "SELECT numserv, nom, prenom, image, actif FROM serveur WHERE numserv = :numserv AND actif = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':numserv' => $numserv]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouveau serveur
     *
     * @param string $nom Nom du serveur
     * @param string $prenom Prénom du serveur
     * @param string $image Nom du fichier image
     * @return int Numéro du serveur créé
     */
    public static function creeServer(string $nom, string $prenom, string $image): int
    {
        if (empty(trim($nom)) || empty(trim($prenom))) {
            throw new Exception("Le nom et prénom du serveur sont obligatoires");
        }

        $pdo = Database::getConnection();
        $sql = "INSERT INTO serveur (nom, prenom, image, actif) VALUES (:nom, :prenom, :image, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nom' => trim($nom),
            ':prenom' => trim($prenom),
            ':image' => $image,
        ]);
        
        return (int)$pdo->lastInsertId();
    }

    /**
     * Met à jour un serveur
     *
     * @param int $numserv Numéro du serveur
     * @param string $nom Nom du serveur
     * @param string $prenom Prénom du serveur
     * @param string $image Nom du fichier image
     * @return bool Succès de la mise à jour
     */
    public static function metsAJourServer(int $numserv, string $nom, string $prenom, string $image): bool
    {
        if (empty(trim($nom)) || empty(trim($prenom))) {
            throw new Exception("Le nom et prénom du serveur sont obligatoires");
        }

        $pdo = Database::getConnection();
        $sql = "UPDATE serveur SET nom = :nom, prenom = :prenom, image = :image WHERE numserv = :numserv";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':numserv' => $numserv,
            ':nom' => trim($nom),
            ':prenom' => trim($prenom),
            ':image' => $image
        ]);
    }

    /**
     * Désactive un serveur (soft delete)
     *
     * @param int $numserv Numéro du serveur
     * @return bool Succès de la désactivation
     */
    public static function desactiverServer(int $numserv): bool
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE serveur SET actif = 0 WHERE numserv = :numserv";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':numserv' => $numserv]);
    }

    /**
     * Supprime un serveur (hard delete - vraie suppression)
     * [Gardée pour compatibilité mais ne devrait pas être utilisée]
     *
     * @param int $numserv Numéro du serveur
     * @return bool Succès de la suppression
     */
    public static function suppServer(int $numserv): bool
    {
        $pdo = Database::getConnection();
        $sql = "DELETE FROM serveur WHERE numserv = :numserv";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':numserv' => $numserv]);
    }

    /**
     * Obtient le nom complet d'un serveur
     *
     * @param int $numserv Numéro du serveur
     * @return string Nom complet (Prénom NOM)
     */
    public static function obtenirNomComplet(int $numserv): string
    {
        $server = self::obtenirServerParId($numserv);
        if ($server) {
            return ucfirst($server['prenom']) . ' ' . strtoupper($server['nom']);
        }
        return "Inconnu";
    }
}
