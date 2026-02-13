<?php
session_start();

require_once "src/pdo/Database.php";

use gestion\pdo\Database;

$pdo = Database::getConnection();

$stmt2 = $pdo->query("SELECT * FROM plat");
$plat = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Resto</title>
  <link rel="stylesheet" href="ressources/css/style.css">
</head>
<body>
<h1>Liste des Plats</h1>
HTML;

// Boucle PHP normale
foreach ($plat as $p) {
    echo "<div>";
    echo "<h2>" . htmlspecialchars($p["libelle"]) . "</h2>";
    echo "<p>" . htmlspecialchars($p["type"]) . "</p>";
    echo "<strong>" . htmlspecialchars($p["prixunit"]) . " €</strong>";
    echo "</div>";
}

echo "</body></html>";
