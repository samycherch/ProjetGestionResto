<?php

namespace gestion\pdo;
use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static string $configFile =
        __DIR__ . "/../../ressources/conf/conf.ini";

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            if (empty(self::$configFile)) {
                throw new \Exception("Aucun fichier de configuration défini.");
            }

            $config = parse_ini_file(self::$configFile);

            if ($config === false) {
                throw new \Exception(
                    "Impossible de lire le fichier de configuration : " .
                        self::$configFile,
                );
            }

            // Construire le DSN à partir des valeurs du .ini
            $driver = $config["driver"] ?? "mysql";
            $host = $config["host"] ?? "localhost";
            $database = $config["database"] ?? "";
            $charset = $config["charset"] ?? "utf8";

            $dsn = "{$driver}:host={$host};dbname={$database};charset={$charset}";
            $username = $config["username"] ?? "";
            $password = $config["password"] ?? "";

            try {
                self::$instance = new PDO($dsn, $username, $password);
                self::$instance->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION,
                );
                self::$instance->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC,
                );
                // Désactiver l'autocommit pour laisser l'application contrôler les transactions
                self::$instance->setAttribute(
                    PDO::ATTR_AUTOCOMMIT,
                    false,
                );
            } catch (PDOException $e) {
                throw new \Exception(
                    "Erreur de connexion à la base : " . $e->getMessage(),
                );
            }
        }

        return self::$instance;
    }

    /**
     * Démarre une transaction
     */
    public static function beginTransaction(): void
    {
        try {
            self::getConnection()->beginTransaction();
        } catch (PDOException $e) {
            throw new \Exception(
                "Erreur au démarrage de la transaction : " . $e->getMessage(),
            );
        }
    }

    /**
     * Valide la transaction en cours
     */
    public static function commit(): void
    {
        try {
            self::getConnection()->commit();
        } catch (PDOException $e) {
            throw new \Exception(
                "Erreur lors de la validation de la transaction : " .
                    $e->getMessage(),
            );
        }
    }

    /**
     * Annule la transaction en cours
     */
    public static function rollback(): void
    {
        try {
            self::getConnection()->rollBack();
        } catch (PDOException $e) {
            throw new \Exception(
                "Erreur lors de l'annulation de la transaction : " .
                    $e->getMessage(),
            );
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
