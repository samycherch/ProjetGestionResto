<?php
session_start();

require_once "src/pdo/Database.php";
require_once "src/repo/ReservationRepository.php";
require_once "src/repo/CommandeRepository.php";

use gestion\pdo\Database;
use gestion\repo\ReservationRepository;
use gestion\repo\CommandeRepository;

$message = "";
$messageType = "";
$reservationSelectionnee = null;
$commandesDetail = [];
$montantTotal = 0;

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
                $message = "Cette réservation a déjà été encaissée le " . date('d/m/Y à H:i', strtotime($reservationSelectionnee['datpaie']));
                $messageType = "error";
                $reservationSelectionnee = null;
            } else {
                // Récupérer les commandes et calculer le montant
                $commandesDetail = CommandeRepository::obtenirCommandesReservation($numres);
                $montantTotal = CommandeRepository::calculerMontantTotal($numres);
                
                if (empty($commandesDetail)) {
                    $message = "Attention : Cette réservation n'a aucune commande";
                    $messageType = "error";
                }
            }
        } catch (Exception $e) {
            $message = "Erreur: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Traiter l'encaissement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'encaisser') {
    $pdo = null;
    try {
        $numres = (int)$_POST['numres'] ?? 0;
        $modpaie = htmlspecialchars($_POST['modpaie'] ?? '');
        $montcom = (float)$_POST['montcom'] ?? 0;

        if (!$numres) {
            throw new Exception("Numéro de réservation invalide");
        }

        if (empty($modpaie)) {
            throw new Exception("Veuillez sélectionner un mode de paiement");
        }

        if (!in_array($modpaie, ['Carte', 'Chèque', 'Espèces'])) {
            throw new Exception("Mode de paiement invalide");
        }

        if ($montcom < 0) {
            throw new Exception("Le montant ne peut pas être négatif");
        }

        // Obtenir la connexion PDO
        $pdo = Database::getConnection();

        // Vérifier que la réservation existe et n'est pas déjà encaissée
        $reservation = ReservationRepository::obtenirReservationParId($numres);
        if (!$reservation) {
            throw new Exception("Réservation #$numres introuvable");
        }

        if ($reservation['datpaie'] !== null) {
            throw new Exception("Cette réservation a déjà été encaissée");
        }

        // S'assurer qu'aucune transaction n'est en cours
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Démarrer une transaction
        $pdo->beginTransaction();

        // Enregistrer l'encaissement avec la date/heure actuelle
        $datpaie = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare(
            "UPDATE reservation SET datpaie = ?, modpaie = ?, montcom = ? WHERE numres = ?"
        );
        $stmt->execute([$datpaie, $modpaie, $montcom, $numres]);

        // Valider la transaction
        $pdo->commit();

        $message = "Réservation #$numres encaissée avec succès ! Montant : " . number_format($montcom, 2) . " € (" . $modpaie . ")";
        $messageType = "success";
        
        // Réinitialiser la sélection
        $reservationSelectionnee = null;
        $commandesDetail = [];
        $montantTotal = 0;

    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
        
        // Garder la réservation sélectionnée en cas d'erreur
        if ($numres) {
            $reservationSelectionnee = ReservationRepository::obtenirReservationParId($numres);
            $commandesDetail = CommandeRepository::obtenirCommandesReservation($numres);
            $montantTotal = CommandeRepository::calculerMontantTotal($numres);
        }
    }
}

// Récupérer les réservations non encore encaissées
$pdo = Database::getConnection();
$stmt = $pdo->query("SELECT * FROM reservation WHERE datpaie IS NULL ORDER BY datres");
$reservationsNonPayees = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Encaisser une Réservation</title>
  <link rel="stylesheet" href="ressources/css/style.css">
</head>
<body>
  <h1>Encaisser une Réservation</h1>
  
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
        <label>Choisir une réservation à encaisser :<br>
        <select name="numres" required>
          <option value="">-- Sélectionner --</option>
          <?php foreach ($reservationsNonPayees as $res): ?>
            <?php 
              $nbCommandes = CommandeRepository::compterPlatsCommandes($res['numres']);
              $montantRes = CommandeRepository::calculerMontantTotal($res['numres']);
            ?>
            <option value="<?php echo $res['numres']; ?>" 
                    <?php echo ($reservationSelectionnee && $reservationSelectionnee['numres'] == $res['numres']) ? 'selected' : ''; ?>>
              #<?php echo $res['numres']; ?> - 
              Table <?php echo $res['numtab']; ?> - 
              <?php echo date('d/m/Y H:i', strtotime($res['datres'])); ?> - 
              <?php echo htmlspecialchars($res['serveur']); ?> - 
              <?php echo $nbCommandes; ?> plat(s) - 
              <?php echo number_format($montantRes, 2); ?> €
            </option>
          <?php endforeach; ?>
        </select>
        </label>
      </p>
      
      <p>
        <button type="submit">Afficher le détail</button>
      </p>
    </form>
  <?php else: ?>
    <p><strong>Aucune réservation à encaisser</strong></p>
  <?php endif; ?>

  <?php if ($reservationSelectionnee): ?>
    <hr>

    <!-- INFORMATIONS DE LA RÉSERVATION -->
    <h2>Réservation #<?php echo $reservationSelectionnee['numres']; ?></h2>
    <ul>
      <li><strong>Table:</strong> <?php echo $reservationSelectionnee['numtab']; ?></li>
      <li><strong>Date/Heure:</strong> <?php echo date('d/m/Y à H:i', strtotime($reservationSelectionnee['datres'])); ?></li>
      <li><strong>Serveur:</strong> <?php echo htmlspecialchars($reservationSelectionnee['serveur']); ?></li>
      <li><strong>Nombre de personnes:</strong> <?php echo $reservationSelectionnee['nbpers']; ?></li>
    </ul>

    <hr>

    <!-- DÉTAIL DE LA COMMANDE -->
    <h2>Étape 2 : Détail de la commande</h2>
    
    <?php if (!empty($commandesDetail)): ?>
      <table border="1" cellpadding="5" cellspacing="0">
        <tr>
          <th>Plat</th>
          <th>Type</th>
          <th>Prix unitaire</th>
          <th>Quantité</th>
          <th>Total</th>
        </tr>
        <?php foreach ($commandesDetail as $cmd): ?>
          <tr>
            <td><?php echo htmlspecialchars($cmd['libelle']); ?></td>
            <td><?php echo htmlspecialchars($cmd['type']); ?></td>
            <td><?php echo number_format($cmd['prixunit'], 2); ?> €</td>
            <td><?php echo $cmd['quantite']; ?></td>
            <td><?php echo number_format($cmd['total_ligne'], 2); ?> €</td>
          </tr>
        <?php endforeach; ?>
        <tr>
          <td colspan="4" align="right"><strong>MONTANT TOTAL :</strong></td>
          <td><strong style="font-size: 18px;"><?php echo number_format($montantTotal, 2); ?> €</strong></td>
        </tr>
      </table>
    <?php else: ?>
      <p><strong>Aucune commande pour cette réservation</strong></p>
      <p><em>Le montant sera de 0.00 €</em></p>
    <?php endif; ?>

    <hr>

    <!-- FORMULAIRE D'ENCAISSEMENT -->
    <h2>Étape 3 : Confirmer l'encaissement</h2>
    
    <form method="POST">
      <input type="hidden" name="action" value="encaisser">
      <input type="hidden" name="numres" value="<?php echo $reservationSelectionnee['numres']; ?>">
      <input type="hidden" name="montcom" value="<?php echo $montantTotal; ?>">
      
      <p>
        <label><strong>Mode de paiement :</strong><br>
        <select name="modpaie" required>
          <option value="">-- Sélectionner --</option>
          <option value="Carte">Carte bancaire</option>
          <option value="Chèque">Chèque</option>
          <option value="Espèces">Espèces</option>
        </select>
        </label>
      </p>
      
      <p>
        <strong>Montant à encaisser :</strong> <?php echo number_format($montantTotal, 2); ?> €
      </p>
      
      <p>
        <button type="submit" style="font-size: 16px; padding: 10px 20px;">
          Confirmer l'encaissement
        </button>
      </p>
    </form>

  <?php endif; ?>

  <hr>

  <h2>Comment ça fonctionne ?</h2>
  <ul>
    <li>Sélectionnez une réservation <strong>non encore encaissée</strong></li>
    <li>Vérifiez le <strong>détail de la commande</strong> et le montant total</li>
    <li>Choisissez le <strong>mode de paiement</strong> (Carte, Chèque, Espèces)</li>
    <li>Confirmez l'encaissement : la réservation sera mise à jour avec :
      <ul>
        <li><strong>datpaie</strong> : Date et heure de l'encaissement</li>
        <li><strong>modpaie</strong> : Mode de paiement choisi</li>
        <li><strong>montcom</strong> : Montant total encaissé</li>
      </ul>
    </li>
    <li>L'opération est effectuée dans une <strong>transaction</strong> pour garantir la cohérence</li>
  </ul>

</body>
</html>