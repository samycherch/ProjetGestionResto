<?php
session_start();
require_once __DIR__ . "/../../vendor/autoload.php";

use gestion\pdo\Database;
use gestion\repo\ReservationRepository;
use gestion\repo\CommandeRepository;
use gestion\repo\PlatRepository;

$message = "";
$messageType = "";

// Traiter l'annulation d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'annuler') {
    try {
        $numres = (int)$_POST['numres'] ?? 0;

        if (!$numres) {
            throw new Exception("Numéro de réservation invalide");
        }

        // Démarrer une transaction
        Database::beginTransaction();

        // Vérifier que la réservation existe
        $reservation = ReservationRepository::obtenirReservationParId($numres);
        if (!$reservation) {
            throw new Exception("Réservation #$numres introuvable");
        }

        // Vérifier que la réservation n'est pas encore encaissée
        if ($reservation['datpaie'] !== null) {
            throw new Exception("Impossible d'annuler une réservation déjà encaissée");
        }

        // Récupérer les commandes de cette réservation pour remettre les plats en stock
        $commandes = CommandeRepository::obtenirCommandesReservation($numres);
        
        $pdo = Database::getConnection();
        
        if (!empty($commandes)) {
            // Remettre les quantités en stock pour chaque plat commandé
            foreach ($commandes as $cmd) {
                $stmt = $pdo->prepare("UPDATE plat SET qteservie = qteservie + ? WHERE numplat = ?");
                $stmt->execute([$cmd['quantite'], $cmd['numplat']]);
            }

            // Supprimer toutes les commandes de cette réservation
            $stmt = $pdo->prepare("DELETE FROM commande WHERE numres = ?");
            $stmt->execute([$numres]);
        }

        // Supprimer la réservation
        $stmt = $pdo->prepare("DELETE FROM reservation WHERE numres = ?");
        $stmt->execute([$numres]);

        // Valider la transaction
        Database::commit();

        $message = "Réservation #$numres annulée avec succès !";
        if (!empty($commandes)) {
            $message .= " Les plats commandés ont été remis en stock.";
        }
        $messageType = "success";

    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        Database::rollback();
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// Récupérer toutes les réservations non encaissées (non consommées)
try {
    Database::beginTransaction();
    $pdo = Database::getConnection();
    $stmt = $pdo->query("SELECT r.*, s.prenom, s.nom, s.image FROM reservation r 
                         LEFT JOIN serveur s ON r.numserv = s.numserv 
                         WHERE r.datpaie IS NULL ORDER BY r.datres");
    $reservationsNonPayees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    Database::commit();
} catch (Exception $e) {
    Database::rollback();
    $reservationsNonPayees = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Annuler une Réservation</title>
  <link rel="stylesheet" href="../ressources/css/style.css?v=2.2">
</head>
<body>

<div class="container">

  <h1>Annuler une Réservation</h1>
  
  <p><a href="../index.php">← Retour à l'accueil</a></p>

  <?php if ($message): ?>
    <p class="message <?= $messageType ?>">
      <strong><?= htmlspecialchars($message); ?></strong>
    </p>
  <?php endif; ?>

  <h2>Réservations non consommées</h2>
  
  <p><em>Seules les réservations non encore encaissées peuvent être annulées.</em></p>

  <?php if (!empty($reservationsNonPayees)): ?>
    <table>
      <thead>
        <tr>
          <th>Numéro</th>
          <th>Table</th>
          <th>Date/Heure</th>
          <th>Serveur</th>
          <th>Personnes</th>
          <th>Commandes</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reservationsNonPayees as $res): ?>
          <?php 
            $nbCommandes = CommandeRepository::compterPlatsCommandes($res['numres']);
            $aDesCommandes = $nbCommandes > 0;
          ?>
          <tr>
            <td><strong>#<?= $res['numres']; ?></strong></td>
            <td>Table <?= $res['numtab']; ?></td>
            <td><?= date('d/m/Y H:i', strtotime($res['datres'])); ?></td>
            <td><?= htmlspecialchars(($res['prenom'] ?? '') . ' ' . ($res['nom'] ?? '')); ?></td>
            <td><?= $res['nbpers']; ?></td>
            <td>
              <?php if ($aDesCommandes): ?>
                <strong><?= $nbCommandes; ?> plat(s)</strong>
              <?php else: ?>
                <em>Aucune</em>
              <?php endif; ?>
            </td>
            <td>
              <form method="POST" class="inline-form"
                onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ? <?= $aDesCommandes ? 'Les plats commandés seront remis en stock.' : ''; ?>');">
                <input type="hidden" name="action" value="annuler">
                <input type="hidden" name="numres" value="<?= $res['numres']; ?>">
                <button type="submit" class="btn-danger">Annuler</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="info-message"><strong>Aucune réservation non consommée</strong></p>
  <?php endif; ?>

  <h2>Comment ça fonctionne ?</h2>
  <ul>
    <li>Seules les réservations <strong>non encore encaissées</strong> peuvent être annulées</li>
    <li>Si la réservation a des commandes, les plats sont automatiquement <strong>remis en stock</strong></li>
    <li>La réservation et toutes ses commandes sont <strong>supprimées définitivement</strong></li>
    <li>L'opération est effectuée dans une <strong>transaction</strong> pour garantir la cohérence</li>
  </ul>

</div>

<footer>
  <p>&copy; <?= date('Y'); ?> Resto - Gestion des Réservations</p>
  <p class="footer-small">Développé par Samy Cherchari et Nathan Yvon</p>
</footer>

</body>
</html>
