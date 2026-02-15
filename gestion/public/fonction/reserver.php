<?php
session_start();

require_once __DIR__ . "/../../vendor/autoload.php";

use gestion\pdo\Database;
use gestion\repo\TableRepository;
use gestion\repo\ReservationRepository;

$message = "";
$messageType = "";
$tablesDisponibles = [];

// Traiter la soumission du formulaire de réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserver') {
    try {
        $numtab = (int)$_POST['numtab'] ?? 0;
        $serveur = htmlspecialchars($_POST['serveur'] ?? '');
        $date = htmlspecialchars($_POST['date'] ?? '');
        $heure = htmlspecialchars($_POST['heure'] ?? '');
        $nbPersonnes = (int)$_POST['nbPersonnes'] ?? 0;

        if (!$numtab || !$serveur || !$date || !$heure || !$nbPersonnes) {
            throw new Exception("Veuillez remplir tous les champs");
        }

        $dateHeure = "$date $heure:00";

        Database::beginTransaction();
        $numReservation = ReservationRepository::creeReservation($numtab, $serveur, $dateHeure, $nbPersonnes);
        Database::commit();

        $message = "Réservation créée avec succès ! Numéro: #$numReservation";
        $messageType = "success";
    } catch (Exception $e) {
        Database::rollback();
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// Récupérer les tables disponibles si la date/heure est fournie
$dateDisponibilite = "";
$heureDisponibilite = "";
$nbPersonnesDisponibilite = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verifier') {
    $dateDisponibilite = htmlspecialchars($_POST['date'] ?? '');
    $heureDisponibilite = htmlspecialchars($_POST['heure'] ?? '');
    $nbPersonnesDisponibilite = (int)$_POST['nbPersonnes'] ?? 0;

    if ($dateDisponibilite && $heureDisponibilite && $nbPersonnesDisponibilite > 0) {
        try {
            Database::beginTransaction();
            $dateHeure = "$dateDisponibilite $heureDisponibilite:00";
            $tablesDisponibles = TableRepository::obtenirTablesDisponibles($dateHeure, $nbPersonnesDisponibilite);
            Database::commit();
        } catch (Exception $e) {
            Database::rollback();
            $message = "Erreur: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Réserver une Table</title>
  <link rel="stylesheet" href="../ressources/css/style.css?v=2.2">
</head>
<body>

<div class="container">

  <h1>Réserver une Table</h1>
  
  <p><a href="../index.php">← Retour à l'accueil</a></p>

  <?php if ($message): ?>
    <p class="message <?= $messageType ?>">
      <strong><?= htmlspecialchars($message); ?></strong>
    </p>
  <?php endif; ?>

  <!-- FORMULAIRE DE VÉRIFICATION -->
  <h2>Nouvelle Réservation</h2>
  <h3>Étape 1 : Vérifier la disponibilité</h3>

  <form method="POST" class="form-section">
    <input type="hidden" name="action" value="verifier">

    <label>Date de réservation :
      <input type="date" name="date" value="<?= $dateDisponibilite; ?>" required>
    </label>

    <label>Heure :
      <input type="time" name="heure" value="<?= $heureDisponibilite; ?>" required>
    </label>

    <label>Nombre de personnes :
      <input type="number" name="nbPersonnes" min="1" max="20" value="<?= $nbPersonnesDisponibilite; ?>" required>
    </label>

    <button type="submit" class="btn-primary">Voir les tables disponibles</button>
  </form>

  <!-- TABLES DISPONIBLES -->
  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'verifier'): ?>
    <hr>
    <h3>Étape 2 : Tables disponibles</h3>

    <?php if (!empty($tablesDisponibles)): ?>
      <table class="table">
        <thead>
          <tr>
            <th>Table</th>
            <th>Nombre de places</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($tablesDisponibles as $table): ?>
          <tr>
            <td>Table <?= $table['numtab']; ?></td>
            <td><?= $table['nbplace']; ?> places</td>
            <td>
              <form method="POST" class="inline-form">
                <input type="hidden" name="action" value="reserver">
                <input type="hidden" name="numtab" value="<?= $table['numtab']; ?>">
                <input type="hidden" name="date" value="<?= $dateDisponibilite; ?>">
                <input type="hidden" name="heure" value="<?= $heureDisponibilite; ?>">
                <input type="hidden" name="nbPersonnes" value="<?= $nbPersonnesDisponibilite; ?>">
                <input type="text" name="serveur" placeholder="Nom du serveur" required>
                <button type="submit" class="btn-success">Réserver</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p><strong>Aucune table disponible pour cette date/heure/nombre de personnes</strong></p>
    <?php endif; ?>
  <?php endif; ?>

  <hr>

  <h2>Comment ça fonctionne ?</h2>
  <ul>
    <li><strong>Étape 1 :</strong> Saisir la date, l'heure et le nombre de personnes</li>
    <li><strong>Étape 2 :</strong> Consulter les tables disponibles</li>
    <li><strong>Étape 3 :</strong> Saisir le nom du serveur et confirmer la réservation</li>
    <li>La disponibilité est vérifiée en temps réel (détection des chevauchements)</li>
    <li>L'opération est effectuée dans une <strong>transaction</strong> pour garantir la cohérence</li>
  </ul>

</div>

<footer>
  <p>&copy; <?= date('Y'); ?> Resto - Gestion des Réservations</p>
  <p class="footer-small">Développé par Samy Cherchari et Nathan Yvon</p>
</footer>

</body>
</html>
