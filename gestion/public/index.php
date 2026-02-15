<?php
session_start();

require_once __DIR__ . "/../vendor/autoload.php";

use gestion\pdo\Database;
use gestion\repo\ReservationRepository;

// Récupérer les réservations à venir
try {
    Database::beginTransaction();
    $reservationsAVenir = ReservationRepository::obtenirReservationsAVenir();
    Database::commit();
} catch (Exception $e) {
    Database::rollback();
    $reservationsAVenir = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resto - Gestion des Réservations</title>
    <link rel="stylesheet" href="ressources/css/style.css?v=2.2">
</head>

<body>

<div class="container">

    <h1>Gestion des Réservations - Restaurant</h1>

    <h2>Menu Principal</h2>

    <div class="menu-buttons">
        <button onclick="window.location.href='./fonction/reserver.php'">Réserver une Table</button>
        <button onclick="window.location.href='./fonction/commander.php'">Commander des Plats</button>
        <button onclick="window.location.href='./fonction/consulterPlats.php'">Consulter les Plats</button>
        <button onclick="window.location.href='./fonction/modifierPlat.php'">Modifier les Plats</button>
        <button onclick="window.location.href='./fonction/annuler.php'">Annuler une Réservation</button>
        <button onclick="window.location.href='./fonction/encaisser.php'">Encaisser une Réservation</button>
    </div>

    <!-- RÉSERVATIONS À VENIR -->
    <h2>Réservations à Venir</h2>

    <?php if (!empty($reservationsAVenir)): ?>
        <table>
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Table</th>
                    <th>Date/Heure</th>
                    <th>Serveur</th>
                    <th>Personnes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservationsAVenir as $res): ?>
                    <tr>
                        <td>#<?php echo $res['numres']; ?></td>
                        <td>Table <?php echo $res['numtab']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($res['datres'])); ?></td>
                        <td><?php echo htmlspecialchars($res['serveur']); ?></td>
                        <td><?php echo $res['nbpers']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="info-message">Aucune réservation à venir</p>
    <?php endif; ?>

</div>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Resto - Gestion des Réservations</p>
    <p class="footer-small">Développé par Samy Cherchari et Nathan Yvon</p>
</footer>

</body>
</html>
