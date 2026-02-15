<?php

namespace gestion\pdo;
use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static string $configFile = __DIR__ . "/../../conf/conf.ini";

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            if (empty(self::$configFile)) {
                throw new \Exception("Aucun fichier de configuration défini.");
            }

            $config = parse_ini_file(self::$configFile);
            if ($config === false) {
                throw new \Exception("Impossible de lire le fichier de configuration : " . self::$configFile);
            }

            $driver = $config["driver"] ?? "mysql";
            $host = $config["host"] ?? "localhost";
            $database = $config["database"] ?? "";
            $charset = $config["charset"] ?? "utf8mb4";

            $dsn = "{$driver}:host={$host};dbname={$database};charset={$charset}";
            $username = $config["username"] ?? "";
            $password = $config["password"] ?? "";

            try {
                self::$instance = new PDO($dsn, $username, $password);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Pour donner le contrôle des transactions à l'application
                self::$instance->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
                
            } catch (PDOException $e) {
                throw new \Exception("Erreur de connexion à la base : " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Démarre une transaction
     */
    public static function beginTransaction(): void
    {
        $pdo = self::getConnection();
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }
    }

    /**
     * Valide la transaction en cours
     */
    public static function commit(): void
    {
        $pdo = self::getConnection();
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    }

    /**
     * Annule la transaction en cours
     */
    public static function rollback(): void
    {
        $pdo = self::getConnection();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    /**
     * Vérifie si une transaction est active
     */
    public static function inTransaction(): bool
    {
        return self::getConnection()->inTransaction();
    }
}