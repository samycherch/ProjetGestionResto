<?php
session_start();

require_once "src/pdo/Database.php";
require_once "src/repo/PlatRepository.php";

use gestion\pdo\Database;
use gestion\repo\PlatRepository;

$message = "";
$messageType = "";

// Traiter la modification du prix d'un plat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier_prix') {
    $pdo = null;
    try {
        $numplat = (int)$_POST['numplat'] ?? 0;
        $nouveauPrix = (float)$_POST['nouveau_prix'] ?? 0;

        if (!$numplat) {
            throw new Exception("Numéro de plat invalide");
        }

        if ($nouveauPrix < 0) {
            throw new Exception("Le prix ne peut pas être négatif");
        }

        // Obtenir la connexion PDO
        $pdo = Database::getConnection();

        // S'assurer qu'aucune transaction n'est en cours
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Démarrer une transaction
        $pdo->beginTransaction();

        // Récupérer le plat pour afficher son nom
        $plat = PlatRepository::obtenirPlatParId($numplat);
        if (!$plat) {
            throw new Exception("Plat introuvable");
        }

        // Modifier le prix
        PlatRepository::modifierPrixPlat($numplat, $nouveauPrix);

        // Valider la transaction
        $pdo->commit();

        $message = "Prix du plat '{$plat['libelle']}' modifié avec succès : " . number_format($nouveauPrix, 2) . " €";
        $messageType = "success";

    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// Traiter la modification de la quantité servie d'un plat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier_quantite') {
    $pdo = null;
    try {
        $numplat = (int)$_POST['numplat'] ?? 0;
        $nouvelleQuantite = (int)$_POST['nouvelle_quantite'] ?? 0;

        if (!$numplat) {
            throw new Exception("Numéro de plat invalide");
        }

        if ($nouvelleQuantite < 0) {
            throw new Exception("La quantité ne peut pas être négative");
        }

        // Obtenir la connexion PDO
        $pdo = Database::getConnection();

        // S'assurer qu'aucune transaction n'est en cours
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Démarrer une transaction
        $pdo->beginTransaction();

        // Récupérer le plat pour afficher son nom
        $plat = PlatRepository::obtenirPlatParId($numplat);
        if (!$plat) {
            throw new Exception("Plat introuvable");
        }

        // Modifier la quantité
        PlatRepository::modifierQuantiteServie($numplat, $nouvelleQuantite);

        // Valider la transaction
        $pdo->commit();

        $message = "Quantité servie du plat '{$plat['libelle']}' modifiée avec succès : {$nouvelleQuantite}";
        $messageType = "success";

    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// Récupérer tous les plats
$plats = PlatRepository::obtenirTousLesPlats();

// Regrouper les plats par type
$platsParType = [];
foreach ($plats as $plat) {
    $type = $plat['type'];
    if (!isset($platsParType[$type])) {
        $platsParType[$type] = [];
    }
    $platsParType[$type][] = $plat;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modifier les Plats</title>
  <link rel="stylesheet" href="ressources/css/style.css">
</head>
<body>
  <h1>Modifier les Plats</h1>
  
  <p><a href="index.php">Retour à l'accueil</a></p>

  <hr>

  <?php if ($message): ?>
    <p style="color: <?php echo $messageType === 'success' ? 'green' : 'red'; ?>;">
      <strong><?php echo $message; ?></strong>
    </p>
    <hr>
  <?php endif; ?>

  <h2>Liste des Plats</h2>
  
  <p><em>Vous pouvez modifier le prix unitaire et/ou la quantité servie de chaque plat.</em></p>

  <?php foreach ($platsParType as $type => $platsType): ?>
    <h3><?php echo htmlspecialchars($type); ?></h3>
    
    <table border="1" cellpadding="5" cellspacing="0">
      <tr>
        <th>Numéro</th>
        <th>Libellé</th>
        <th>Prix unitaire</th>
        <th>Modifier Prix</th>
        <th>Quantité servie</th>
        <th>Modifier Quantité</th>
      </tr>
      <?php foreach ($platsType as $plat): ?>
        <tr>
          <td><?php echo $plat['numplat']; ?></td>
          <td><strong><?php echo htmlspecialchars($plat['libelle']); ?></strong></td>
          
          <!-- Prix actuel -->
          <td><?php echo number_format($plat['prixunit'], 2); ?> €</td>
          
          <!-- Formulaire modification prix -->
          <td>
            <form method="POST" style="display: inline;">
              <input type="hidden" name="action" value="modifier_prix">
              <input type="hidden" name="numplat" value="<?php echo $plat['numplat']; ?>">
              <input type="number" name="nouveau_prix" step="0.01" min="0" 
                     value="<?php echo $plat['prixunit']; ?>" 
                     style="width: 80px;" required>
              <button type="submit">Modifier</button>
            </form>
          </td>
          
          <!-- Quantité actuelle -->
          <td>
            <?php 
              $couleur = $plat['qteservie'] == 0 ? 'red' : ($plat['qteservie'] < 10 ? 'orange' : 'green');
            ?>
            <span style="color: <?php echo $couleur; ?>; font-weight: bold;">
              <?php echo $plat['qteservie']; ?>
            </span>
          </td>
          
          <!-- Formulaire modification quantité -->
          <td>
            <form method="POST" style="display: inline;">
              <input type="hidden" name="action" value="modifier_quantite">
              <input type="hidden" name="numplat" value="<?php echo $plat['numplat']; ?>">
              <input type="number" name="nouvelle_quantite" min="0" 
                     value="<?php echo $plat['qteservie']; ?>" 
                     style="width: 80px;" required>
              <button type="submit">Modifier</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    <br>
  <?php endforeach; ?>

  <hr>

  <h2>Légende</h2>
  <ul>
    <li><span style="color: green; font-weight: bold;">Vert</span> : Quantité suffisante (≥ 10)</li>
    <li><span style="color: orange; font-weight: bold;">Orange</span> : Quantité faible (< 10)</li>
    <li><span style="color: red; font-weight: bold;">Rouge</span> : Rupture de stock (0)</li>
  </ul>

  <hr>

  <h2>Comment ça fonctionne ?</h2>
  <ul>
    <li>Chaque modification est effectuée dans une <strong>transaction</strong></li>
    <li>Modifiez le <strong>prix unitaire</strong> en euros (ex: 12.50)</li>
    <li>Modifiez la <strong>quantité servie</strong> (nombre de plats disponibles pour le jour)</li>
    <li>Les plats avec quantité = 0 n'apparaissent plus dans la liste des plats disponibles à la commande</li>
  </ul>

</body>
</html>