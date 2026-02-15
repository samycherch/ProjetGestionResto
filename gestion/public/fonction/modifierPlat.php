<?php
session_start();

require_once __DIR__ . "/../../vendor/autoload.php";

use gestion\pdo\Database;
use gestion\repo\PlatRepository;

$message = "";
$messageType = "";

// Traiter la modification du prix d'un plat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier_prix') {
    try {
        $numplat = (int)$_POST['numplat'] ?? 0;
        $nouveauPrix = (float)$_POST['nouveau_prix'] ?? 0;

        if (!$numplat) {
            throw new Exception("Numéro de plat invalide");
        }

        if ($nouveauPrix < 0) {
            throw new Exception("Le prix ne peut pas être négatif");
        }

        Database::beginTransaction();

        // Récupérer le plat pour afficher son nom
        $plat = PlatRepository::obtenirPlatParId($numplat);
        if (!$plat) {
            throw new Exception("Plat introuvable");
        }

        // Modifier le prix
        PlatRepository::modifierPrixPlat($numplat, $nouveauPrix);

        // Valider la transaction
        Database::commit();

        $message = "Prix du plat '{$plat['libelle']}' modifié avec succès : " . number_format($nouveauPrix, 2) . " €";
        $messageType = "success";

    } catch (Exception $e) {
        Database::rollback();
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// Traiter la modification de la quantité servie d'un plat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier_quantite') {
    try {
        $numplat = (int)$_POST['numplat'] ?? 0;
        $nouvelleQuantite = (int)$_POST['nouvelle_quantite'] ?? 0;

        if (!$numplat) {
            throw new Exception("Numéro de plat invalide");
        }

        if ($nouvelleQuantite < 0) {
            throw new Exception("La quantité ne peut pas être négative");
        }

        Database::beginTransaction();

        // Récupérer le plat pour afficher son nom
        $plat = PlatRepository::obtenirPlatParId($numplat);
        if (!$plat) {
            throw new Exception("Plat introuvable");
        }

        // Modifier la quantité
        PlatRepository::modifierQuantiteServie($numplat, $nouvelleQuantite);

        // Valider la transaction
        Database::commit();

        $message = "Quantité servie du plat '{$plat['libelle']}' modifiée avec succès : {$nouvelleQuantite}";
        $messageType = "success";

    } catch (Exception $e) {
        Database::rollback();
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

try {
    Database::beginTransaction();
    $plats = PlatRepository::obtenirTousLesPlats();
    Database::commit();
} catch (Exception $e) {
    Database::rollback();
    $plats = [];
}

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
  <link rel="stylesheet" href="../ressources/css/style.css?v=2.2">
</head>
<body>

<div class="container">

  <h1>Modifier les Plats</h1>
  
  <p><a href="../index.php">← Retour à l'accueil</a></p>

  <?php if ($message): ?>
    <p class="message <?= $messageType ?>">
      <strong><?= htmlspecialchars($message); ?></strong>
    </p>
  <?php endif; ?>

  <h2>Liste des Plats</h2>
  
  <p><em>Vous pouvez modifier le prix unitaire et/ou la quantité servie de chaque plat.</em></p>

  <?php foreach ($platsParType as $type => $platsType): ?>
    <h3><?= htmlspecialchars($type); ?></h3>
    
    <table>
      <thead>
        <tr>
          <th>Numéro</th>
          <th>Libellé</th>
          <th>Prix unitaire</th>
          <th>Modifier Prix</th>
          <th>Quantité servie</th>
          <th>Modifier Quantité</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($platsType as $plat): ?>
        <?php 
          if ($plat['qteservie'] == 0) {
              $classe = "badge-danger";
          } elseif ($plat['qteservie'] < 10) {
              $classe = "badge-warning";
          } else {
              $classe = "badge-success";
          }
        ?>
        <tr>
          <td><?= $plat['numplat']; ?></td>
          <td><strong><?= htmlspecialchars($plat['libelle']); ?></strong></td>

          <!-- Prix actuel -->
          <td><?= number_format($plat['prixunit'], 2); ?> €</td>

          <!-- Modifier prix -->
          <td>
            <form method="POST" class="inline-form">
              <input type="hidden" name="action" value="modifier_prix">
              <input type="hidden" name="numplat" value="<?= $plat['numplat']; ?>">
              <input type="number"
                     name="nouveau_prix"
                     step="0.01"
                     min="0"
                     value="<?= $plat['prixunit']; ?>"
                     class="input-small"
                     required>
              <button type="submit">Modifier</button>
            </form>
          </td>

          <!-- Quantité actuelle -->
          <td>
            <span class="badge <?= $classe ?>">
              <?= $plat['qteservie']; ?>
            </span>
          </td>

          <!-- Modifier quantité -->
          <td>
            <form method="POST" class="inline-form">
              <input type="hidden" name="action" value="modifier_quantite">
              <input type="hidden" name="numplat" value="<?= $plat['numplat']; ?>">
              <input type="number"
                     name="nouvelle_quantite"
                     min="0"
                     value="<?= $plat['qteservie']; ?>"
                     class="input-small"
                     required>
              <button type="submit">Modifier</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

  <?php endforeach; ?>

  <h2>Légende</h2>
  <ul>
    <li><span class="badge badge-success">Disponible</span> : ≥ 10</li>
    <li><span class="badge badge-warning">Stock faible</span> : < 10</li>
    <li><span class="badge badge-danger">Rupture</span> : 0</li>
  </ul>

  <h2>Comment ça fonctionne ?</h2>
  <ul>
    <li>Chaque modification est effectuée dans une <strong>transaction</strong></li>
    <li>Modifiez le <strong>prix unitaire</strong> en euros (ex: 12.50)</li>
    <li>Modifiez la <strong>quantité servie</strong></li>
    <li>Les plats avec quantité = 0 n'apparaissent plus à la commande</li>
  </ul>

</div>

<footer>
  <p>&copy; <?= date('Y'); ?> Resto - Gestion des Réservations</p>
  <p class="footer-small">Développé par Samy Cherchari et Nathan Yvon</p>
</footer>

</body>
</html>
