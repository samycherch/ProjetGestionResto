<?php
session_start();

require_once __DIR__ . "/../../vendor/autoload.php";
use gestion\pdo\Database;
use gestion\repo\PlatRepository;

// Récupérer tous les plats dans une transaction
try {
    Database::beginTransaction();
    $plats = PlatRepository::obtenirTousLesPlats();
    Database::commit();
} catch (Exception $e) {
    Database::rollback();
    $plats = [];
    $message = "Erreur lors de la récupération des plats : " . $e->getMessage();
}

// Regrouper les plats par type pour un affichage organisé
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
  <title>Consulter les Plats</title>
  <link rel="stylesheet" href="../ressources/css/style.css?v=2.2">
</head>
<body>

<div class="container">

  <h1>Liste des Plats Disponibles</h1>
  
  <p><a href="../index.php">← Retour à l'accueil</a></p>

  <?php if (isset($message)): ?>
    <p class="message error">
      <strong><?= htmlspecialchars($message); ?></strong>
    </p>
  <?php endif; ?>

  <?php if (!empty($platsParType)): ?>
    <?php foreach ($platsParType as $type => $platsType): ?>
      
      <h2><?= htmlspecialchars($type); ?></h2>
      
      <table>
        <thead>
          <tr>
            <th>Numéro</th>
            <th>Libellé</th>
            <th>Prix unitaire</th>
            <th>Quantité disponible</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($platsType as $plat): ?>
          <?php 
            if ($plat['qteservie'] == 0) {
                $statut = "Rupture de stock";
                $classe = "badge-danger";
            } elseif ($plat['qteservie'] < 10) {
                $statut = "Stock faible";
                $classe = "badge-warning";
            } else {
                $statut = "Disponible";
                $classe = "badge-success";
            }
          ?>
          <tr>
            <td><?= $plat['numplat']; ?></td>
            <td><strong><?= htmlspecialchars($plat['libelle']); ?></strong></td>
            <td><?= number_format($plat['prixunit'], 2); ?> €</td>
            <td class="text-center">
              <strong><?= $plat['qteservie']; ?></strong>
            </td>
            <td>
              <span class="badge <?= $classe ?>">
                <?= $statut; ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

    <?php endforeach; ?>
  <?php else: ?>
    <p class="info-message"><strong>Aucun plat disponible</strong></p>
  <?php endif; ?>

  <h2>Légende</h2>
  <ul>
    <li><span class="badge badge-success">Disponible</span> : ≥ 10</li>
    <li><span class="badge badge-warning">Stock faible</span> : < 10</li>
    <li><span class="badge badge-danger">Rupture</span> : 0</li>
  </ul>

  <p>
    <a href="modifierPlat.php">Modifier les plats (prix et quantités)</a>
  </p>

</div>

<footer>
  <p>&copy; <?= date('Y'); ?> Resto - Gestion des Réservations</p>
  <p class="footer-small">Développé par Samy Cherchari et Nathan Yvon</p>
</footer>

</body>
</html>
