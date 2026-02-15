<?php
session_start();

require_once "src/pdo/Database.php";
require_once "src/repo/ReservationRepository.php";
require_once "src/repo/CommandeRepository.php";
require_once "src/repo/PlatRepository.php";

use gestion\pdo\Database;
use gestion\repo\ReservationRepository;
use gestion\repo\CommandeRepository;
use gestion\repo\PlatRepository;

$message = "";
$messageType = "";

// Traiter l'annulation d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'annuler') {
    $pdo = null;
    try {
        $numres = (int)$_POST['numres'] ?? 0;

        if (!$numres) {
            throw new Exception("Numéro de réservation invalide");
        }

        // Obtenir la connexion PDO
        $pdo = Database::getConnection();

        // Vérifier que la réservation existe
        $reservation = ReservationRepository::obtenirReservationParId($numres);
        if (!$reservation) {
            throw new Exception("Réservation #$numres introuvable");
        }

        // Vérifier que la réservation n'est pas encore encaissée
        if ($reservation['datpaie'] !== null) {
            throw new Exception("Impossible d'annuler une réservation déjà encaissée");
        }

        // S'assurer qu'aucune transaction n'est en cours
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Démarrer une nouvelle transaction
        $pdo->beginTransaction();

        // Récupérer les commandes de cette réservation pour remettre les plats en stock
        $commandes = CommandeRepository::obtenirCommandesReservation($numres);
        
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
        $pdo->commit();

        $message = "Réservation #$numres annulée avec succès !";
        if (!empty($commandes)) {
            $message .= " Les plats commandés ont été remis en stock.";
        }
        $messageType = "success";

    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// Récupérer toutes les réservations non encaissées (non consommées)
$pdo = Database::getConnection();
$stmt = $pdo->query("SELECT * FROM reservation WHERE datpaie IS NULL ORDER BY datres");
$reservationsNonPayees = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Annuler une Réservation</title>
</head>
<body>
  <h1>Annuler une Réservation</h1>
  
  <p><a href="index.php">Retour à l'accueil</a></p>

  <hr>

  <?php if ($message): ?>
    <p style="color: <?php echo $messageType === 'success' ? 'green' : 'red'; ?>;">
      <strong><?php echo $message; ?></strong>
    </p>
    <hr>
  <?php endif; ?>

  <h2>Réservations non consommées</h2>
  
  <p><em>Seules les réservations non encore encaissées peuvent être annulées.</em></p>

  <?php if (!empty($reservationsNonPayees)): ?>
    <table border="1" cellpadding="5" cellspacing="0">
      <tr>
        <th>Numéro</th>
        <th>Table</th>
        <th>Date/Heure</th>
        <th>Serveur</th>
        <th>Personnes</th>
        <th>Commandes</th>
        <th>Action</th>
      </tr>
      <?php foreach ($reservationsNonPayees as $res): ?>
        <?php 
          // Vérifier si cette réservation a des commandes
          $nbCommandes = CommandeRepository::compterPlatsCommandes($res['numres']);
          $aDesCommandes = $nbCommandes > 0;
        ?>
        <tr>
          <td><strong>#<?php echo $res['numres']; ?></strong></td>
          <td>Table <?php echo $res['numtab']; ?></td>
          <td><?php echo date('d/m/Y H:i', strtotime($res['datres'])); ?></td>
          <td><?php echo htmlspecialchars($res['serveur']); ?></td>
          <td><?php echo $res['nbpers']; ?></td>
          <td>
            <?php if ($aDesCommandes): ?>
              <strong><?php echo $nbCommandes; ?> plat(s)</strong>
            <?php else: ?>
              <em>Aucune</em>
            <?php endif; ?>
          </td>
          <td>
            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ? <?php echo $aDesCommandes ? 'Les plats commandés seront remis en stock.' : ''; ?>');">
              <input type="hidden" name="action" value="annuler">
              <input type="hidden" name="numres" value="<?php echo $res['numres']; ?>">
              <button type="submit">Annuler</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p><strong>Aucune réservation non consommée</strong></p>
  <?php endif; ?>

  <hr>

  <h2>Comment ça fonctionne ?</h2>
  <ul>
    <li>Seules les réservations <strong>non encore encaissées</strong> peuvent être annulées</li>
    <li>Si la réservation a des commandes, les plats sont automatiquement <strong>remis en stock</strong></li>
    <li>La réservation et toutes ses commandes sont <strong>supprimées définitivement</strong></li>
    <li>L'opération est effectuée dans une <strong>transaction</strong> pour garantir la cohérence</li>
  </ul>

</body>
</html>