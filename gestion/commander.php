<?php
session_start();

require_once "src/pdo/Database.php";
require_once "src/repo/ReservationRepository.php";
require_once "src/repo/PlatRepository.php";
require_once "src/repo/CommandeRepository.php";

use gestion\pdo\Database;
use gestion\repo\ReservationRepository;
use gestion\repo\PlatRepository;
use gestion\repo\CommandeRepository;

$message = "";
$messageType = "";
$reservationSelectionnee = null;
$commandesExistantes = [];

// Traiter la sélection d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'selectionner') {
    $numres = (int)$_POST['numres'] ?? 0;
    if ($numres > 0) {
        try {
            $reservationSelectionnee = ReservationRepository::obtenirReservationParId($numres);
            if (!$reservationSelectionnee) {
                $message = "Réservation introuvable";
                $messageType = "error";
            } elseif ($reservationSelectionnee['datpaie'] !== null) {
                $message = "Cette réservation a déjà été encaissée";
                $messageType = "error";
                $reservationSelectionnee = null;
            } else {
                // Récupérer les commandes existantes
                $commandesExistantes = CommandeRepository::obtenirCommandesReservation($numres);
            }
        } catch (Exception $e) {
            $message = "Erreur: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Traiter l'ajout d'un plat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_plat') {
    try {
        $numres = (int)$_POST['numres'] ?? 0;
        $numplat = (int)$_POST['numplat'] ?? 0;
        $quantite = (int)$_POST['quantite'] ?? 0;

        if (!$numres || !$numplat || !$quantite) {
            throw new Exception("Veuillez remplir tous les champs");
        }

        // Utiliser une transaction pour garantir la cohérence
        Database::beginTransaction();
        CommandeRepository::ajouterPlatAReservation($numres, $numplat, $quantite);
        Database::commit();

        $message = "Plat ajouté avec succès !";
        $messageType = "success";

        // Recharger la réservation et ses commandes
        $reservationSelectionnee = ReservationRepository::obtenirReservationParId($numres);
        $commandesExistantes = CommandeRepository::obtenirCommandesReservation($numres);

    } catch (Exception $e) {
        Database::rollback();
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
        
        // Garder la réservation sélectionnée en cas d'erreur
        if ($numres) {
            $reservationSelectionnee = ReservationRepository::obtenirReservationParId($numres);
            $commandesExistantes = CommandeRepository::obtenirCommandesReservation($numres);
        }
    }
}

// Traiter la suppression d'un plat de la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer_plat') {
    try {
        $numres = (int)$_POST['numres'] ?? 0;
        $numplat = (int)$_POST['numplat'] ?? 0;

        Database::beginTransaction();
        CommandeRepository::supprimerPlatCommande($numres, $numplat);
        Database::commit();

        $message = "Plat supprimé de la commande !";
        $messageType = "success";

        // Recharger la réservation et ses commandes
        $reservationSelectionnee = ReservationRepository::obtenirReservationParId($numres);
        $commandesExistantes = CommandeRepository::obtenirCommandesReservation($numres);

    } catch (Exception $e) {
        Database::rollback();
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
        
        if ($numres) {
            $reservationSelectionnee = ReservationRepository::obtenirReservationParId($numres);
            $commandesExistantes = CommandeRepository::obtenirCommandesReservation($numres);
        }
    }
}

// Récupérer les réservations non encore encaissées
$pdo = Database::getConnection();
$stmt = $pdo->query("SELECT * FROM reservation WHERE datpaie IS NULL ORDER BY datres");
$reservationsNonPayees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les plats disponibles
$platsDisponibles = PlatRepository::obtenirPlatsDisponibles();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Commander des Plats</title>
  <link rel="stylesheet" href="ressources/css/style.css">
</head>
<body>
  <h1>Commander des Plats</h1>
  
  <p><a href="index.php">Retour à l'accueil</a></p>

  <hr>

  <?php if ($message): ?>
    <p style="color: <?php echo $messageType === 'success' ? 'green' : 'red'; ?>;">
      <strong><?php echo $message; ?></strong>
    </p>
    <hr>
  <?php endif; ?>

  <!-- SÉLECTION DE LA RÉSERVATION -->
  <h2>Étape 1 : Sélectionner une réservation</h2>
  
  <?php if (!empty($reservationsNonPayees)): ?>
    <form method="POST">
      <input type="hidden" name="action" value="selectionner">
      
      <p>
        <label>Choisir une réservation :<br>
        <select name="numres" required>
          <option value="">-- Sélectionner --</option>
          <?php foreach ($reservationsNonPayees as $res): ?>
            <option value="<?php echo $res['numres']; ?>" 
                    <?php echo ($reservationSelectionnee && $reservationSelectionnee['numres'] == $res['numres']) ? 'selected' : ''; ?>>
              #<?php echo $res['numres']; ?> - 
              Table <?php echo $res['numtab']; ?> - 
              <?php echo date('d/m/Y H:i', strtotime($res['datres'])); ?> - 
              <?php echo htmlspecialchars($res['serveur']); ?> - 
              <?php echo $res['nbpers']; ?> pers.
            </option>
          <?php endforeach; ?>
        </select>
        </label>
      </p>
      
      <p>
        <button type="submit">Sélectionner cette réservation</button>
      </p>
    </form>
  <?php else: ?>
    <p><strong>Aucune réservation disponible (non encore encaissée)</strong></p>
  <?php endif; ?>

  <?php if ($reservationSelectionnee): ?>
    <hr>

    <!-- INFORMATIONS DE LA RÉSERVATION -->
    <h2>Réservation #<?php echo $reservationSelectionnee['numres']; ?></h2>
    <ul>
      <li><strong>Table:</strong> <?php echo $reservationSelectionnee['numtab']; ?></li>
      <li><strong>Date/Heure:</strong> <?php echo date('d/m/Y H:i', strtotime($reservationSelectionnee['datres'])); ?></li>
      <li><strong>Serveur:</strong> <?php echo htmlspecialchars($reservationSelectionnee['serveur']); ?></li>
      <li><strong>Nombre de personnes:</strong> <?php echo $reservationSelectionnee['nbpers']; ?></li>
    </ul>

    <hr>

    <!-- COMMANDES EXISTANTES -->
    <h2>Plats déjà commandés</h2>
    
    <?php if (!empty($commandesExistantes)): ?>
      <table border="1" cellpadding="5" cellspacing="0">
        <tr>
          <th>Plat</th>
          <th>Type</th>
          <th>Prix unitaire</th>
          <th>Quantité</th>
          <th>Total</th>
          <th>Action</th>
        </tr>
        <?php foreach ($commandesExistantes as $cmd): ?>
          <tr>
            <td><?php echo htmlspecialchars($cmd['libelle']); ?></td>
            <td><?php echo htmlspecialchars($cmd['type']); ?></td>
            <td><?php echo number_format($cmd['prixunit'], 2); ?> €</td>
            <td><?php echo $cmd['quantite']; ?></td>
            <td><strong><?php echo number_format($cmd['total_ligne'], 2); ?> €</strong></td>
            <td>
              <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce plat de la commande ?');">
                <input type="hidden" name="action" value="supprimer_plat">
                <input type="hidden" name="numres" value="<?php echo $reservationSelectionnee['numres']; ?>">
                <input type="hidden" name="numplat" value="<?php echo $cmd['numplat']; ?>">
                <button type="submit">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <tr>
          <td colspan="4" align="right"><strong>TOTAL:</strong></td>
          <td colspan="2"><strong><?php echo number_format(CommandeRepository::calculerMontantTotal($reservationSelectionnee['numres']), 2); ?> €</strong></td>
        </tr>
      </table>
    <?php else: ?>
      <p><em>Aucun plat commandé pour cette réservation</em></p>
    <?php endif; ?>

    <hr>

    <!-- AJOUTER UN PLAT -->
    <h2>Étape 2 : Ajouter un plat</h2>
    
    <?php if (!empty($platsDisponibles)): ?>
      <form method="POST">
        <input type="hidden" name="action" value="ajouter_plat">
        <input type="hidden" name="numres" value="<?php echo $reservationSelectionnee['numres']; ?>">
        
        <p>
          <label>Choisir un plat :<br>
          <select name="numplat" required>
            <option value="">-- Sélectionner --</option>
            <?php 
            $currentType = '';
            foreach ($platsDisponibles as $plat): 
              if ($currentType !== $plat['type']) {
                if ($currentType !== '') echo '</optgroup>';
                echo '<optgroup label="' . htmlspecialchars($plat['type']) . '">';
                $currentType = $plat['type'];
              }
            ?>
              <option value="<?php echo $plat['numplat']; ?>">
                <?php echo htmlspecialchars($plat['libelle']); ?> - 
                <?php echo number_format($plat['prixunit'], 2); ?> € 
                (Dispo: <?php echo $plat['qteservie']; ?>)
              </option>
            <?php endforeach; ?>
            <?php if ($currentType !== '') echo '</optgroup>'; ?>
          </select>
          </label>
        </p>
        
        <p>
          <label>Quantité :<br>
          <input type="number" name="quantite" min="1" value="1" required></label>
        </p>
        
        <p>
          <button type="submit">Ajouter à la commande</button>
        </p>
      </form>
    <?php else: ?>
      <p><strong>Aucun plat disponible actuellement</strong></p>
    <?php endif; ?>

  <?php endif; ?>

</body>
</html>