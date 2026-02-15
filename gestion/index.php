<?php
session_start();

require_once "src/pdo/Database.php";
require_once "src/repo/TableRepository.php";
require_once "src/repo/ReservationRepository.php";

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
        $dateHeure = "$dateDisponibilite $heureDisponibilite:00";
        $tablesDisponibles = TableRepository::obtenirTablesDisponibles($dateHeure, $nbPersonnesDisponibilite);
    }
}

$pdo = Database::getConnection();
$stmt2 = $pdo->query("SELECT * FROM plat");
$plat = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les réservations à venir
$reservationsAVenir = ReservationRepository::obtenirReservationsAVenir();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Resto - Gestion des Réservations</title>
</head>
<body>
  <h1>Gestion des Réservations - Restaurant</h1>
  
  <p>
    <a href="commander.php">Commander des plats</a> | 
    <a href="annuler.php">Annuler une réservation</a>
  </p>

  <hr>

  <?php if ($message): ?>
    <p style="color: <?php echo $messageType === 'success' ? 'green' : 'red'; ?>;">
      <strong><?php echo $message; ?></strong>
    </p>
    <hr>
  <?php endif; ?>

  <!-- FORMULAIRE DE RÉSERVATION -->
  <h2>Nouvelle Réservation</h2>

  <h3>Étape 1 : Vérifier la disponibilité</h3>
  <form method="POST">
    <input type="hidden" name="action" value="verifier">
    
    <p>
      <label>Date de réservation :<br>
      <input type="date" name="date" value="<?php echo $dateDisponibilite; ?>" required></label>
    </p>
    
    <p>
      <label>Heure :<br>
      <input type="time" name="heure" value="<?php echo $heureDisponibilite; ?>" required></label>
    </p>
    
    <p>
      <label>Nombre de personnes :<br>
      <input type="number" name="nbPersonnes" min="1" max="20" value="<?php echo $nbPersonnesDisponibilite; ?>" required></label>
    </p>
    
    <p>
      <button type="submit">Voir les tables disponibles</button>
    </p>
  </form>

  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verifier'): ?>
    <hr>
    <h3>Tables disponibles :</h3>
    
    <?php if (!empty($tablesDisponibles)): ?>
      <table border="1" cellpadding="5" cellspacing="0">
        <tr>
          <th>Table</th>
          <th>Nombre de places</th>
          <th>Action</th>
        </tr>
        <?php foreach ($tablesDisponibles as $table): ?>
          <tr>
            <td>Table <?php echo $table['numtab']; ?></td>
            <td><?php echo $table['nbplace']; ?> places</td>
            <td>
              <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="reserver">
                <input type="hidden" name="numtab" value="<?php echo $table['numtab']; ?>">
                <input type="hidden" name="date" value="<?php echo $dateDisponibilite; ?>">
                <input type="hidden" name="heure" value="<?php echo $heureDisponibilite; ?>">
                <input type="hidden" name="nbPersonnes" value="<?php echo $nbPersonnesDisponibilite; ?>">
                <input type="text" name="serveur" placeholder="Nom du serveur" required>
                <button type="submit">Réserver</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p><strong>Aucune table disponible pour cette date/heure/nombre de personnes</strong></p>
    <?php endif; ?>
  <?php endif; ?>

  <hr>

  <!-- RÉSERVATIONS À VENIR -->
  <h2>Réservations à Venir</h2>
  
  <?php if (!empty($reservationsAVenir)): ?>
    <table border="1" cellpadding="5" cellspacing="0">
      <tr>
        <th>Numéro</th>
        <th>Table</th>
        <th>Date/Heure</th>
        <th>Serveur</th>
        <th>Personnes</th>
      </tr>
      <?php foreach ($reservationsAVenir as $res): ?>
        <tr>
          <td>#<?php echo $res['numres']; ?></td>
          <td>Table <?php echo $res['numtab']; ?></td>
          <td><?php echo date('d/m/Y H:i', strtotime($res['datres'])); ?></td>
          <td><?php echo htmlspecialchars($res['serveur']); ?></td>
          <td><?php echo $res['nbpers']; ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>Aucune réservation à venir</p>
  <?php endif; ?>

  <hr>

  <!-- LISTE DES PLATS -->
  <h2>Liste des Plats</h2>
  
  <table border="1" cellpadding="5" cellspacing="0">
    <tr>
      <th>Libellé</th>
      <th>Type</th>
      <th>Prix unitaire</th>
      <th>Quantité servie</th>
    </tr>
    <?php foreach ($plat as $p): ?>
      <tr>
        <td><?php echo htmlspecialchars($p["libelle"]); ?></td>
        <td><?php echo htmlspecialchars($p["type"]); ?></td>
        <td><?php echo htmlspecialchars($p["prixunit"]); ?> €</td>
        <td><?php echo htmlspecialchars($p["qteservie"]); ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

</body>
</html>