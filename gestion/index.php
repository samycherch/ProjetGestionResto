<?php
session_start();

require_once "src/pdo/Database.php";
require_once "src/repo/TableRepository.php";
require_once "src/repo/ReservationRepository.php";

use gestion\pdo\Database;
use gestion\repo\TableRepository;
use gestion\repo\ReservationRepository;

$pdo = Database::getConnection();
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verifier') {
    $dateDisponibilite = htmlspecialchars($_POST['date'] ?? '');
    $heureDisponibilite = htmlspecialchars($_POST['heure'] ?? '');
    $nbPersonnesDisponibilite = (int)$_POST['nbPersonnes'] ?? 0;

    if ($dateDisponibilite && $heureDisponibilite && $nbPersonnesDisponibilite > 0) {
        $dateHeure = "$dateDisponibilite $heureDisponibilite:00";
        $tablesDisponibles = TableRepository::obtenirTablesDisponibles($dateHeure, $nbPersonnesDisponibilite);
    }
}

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
  <link rel="stylesheet" href="ressources/css/style.css">
  <style>
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .section { margin: 30px 0; padding: 20px; background: #f5f5f5; border-radius: 8px; }
    .form-group { margin: 15px 0; }
    label { display: block; margin-bottom: 5px; font-weight: bold; }
    input, select { padding: 8px; width: 100%; max-width: 300px; border: 1px solid #ddd; border-radius: 4px; }
    button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px 5px 5px 0; }
    button:hover { background: #0056b3; }
    .message { padding: 15px; margin: 10px 0; border-radius: 4px; }
    .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .table-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin: 15px 0; }
    .table-card { padding: 15px; background: white; border: 2px solid #ddd; border-radius: 6px; text-align: center; cursor: pointer; transition: all 0.3s; }
    .table-card:hover { border-color: #007bff; background: #e7f3ff; }
    .table-card.selected { border-color: #28a745; background: #d4edda; }
    .table-num { font-size: 24px; font-weight: bold; color: #007bff; }
    .table-places { color: #666; font-size: 14px; }
    .reservations-list { margin-top: 20px; }
    .reservation-item { padding: 10px; background: white; border-left: 4px solid #007bff; margin: 8px 0; border-radius: 4px; }
    .reservation-time { font-weight: bold; color: #007bff; }
    .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 768px) { .two-columns { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
<div class="container">
  <h1>Gestion des Réservations - Restaurant</h1>

  <?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>">
      <?php echo $message; ?>
    </div>
  <?php endif; ?>

  <div class="two-columns">
    <!-- FORMULAIRE DE RÉSERVATION -->
    <div class="section">
      <h2>Nouvelle Réservation</h2>

      <!-- Étape 1: Vérifier la disponibilité -->
      <form method="POST" style="margin-bottom: 20px;">
        <input type="hidden" name="action" value="verifier">
        <div class="form-group">
          <label for="date1">Date de réservation:</label>
          <input type="date" id="date1" name="date" value="<?php echo $dateDisponibilite; ?>" required>
        </div>
        <div class="form-group">
          <label for="heure1">Heure:</label>
          <input type="time" id="heure1" name="heure" value="<?php echo $heureDisponibilite; ?>" required>
        </div>
        <div class="form-group">
          <label for="nbPersonnes1">Nombre de personnes:</label>
          <input type="number" id="nbPersonnes1" name="nbPersonnes" min="1" max="20" value="<?php echo isset($nbPersonnesDisponibilite) ? $nbPersonnesDisponibilite : ''; ?>" required>
        </div>
        <button type="submit">Voir les tables disponibles</button>
      </form>

      <!-- Affichage des tables disponibles -->
      <?php if (!empty($tablesDisponibles)): ?>
        <h3>Tables disponibles:</h3>
        <div class="table-list">
          <?php foreach ($tablesDisponibles as $table): ?>
            <div class="table-card" onclick="selectTable(<?php echo $table['numtab']; ?>, <?php echo $table['nbplace']; ?>)">
              <div class="table-num">Table <?php echo $table['numtab']; ?></div>
              <div class="table-places"><?php echo $table['nbplace']; ?> places</div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif ($dateDisponibilite): ?>
        <p style="color: #dc3545;">Aucune table disponible pour cette date/heure/nombre de personnes</p>
      <?php endif; ?>

      <!-- Formulaire de confirmation de réservation -->
      <?php if (!empty($tablesDisponibles)): ?>
        <form method="POST" style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #ddd;">
          <input type="hidden" name="action" value="reserver">
          <div class="form-group">
            <label for="numtab">Table sélectionnée:</label>
            <input type="number" id="numtab" name="numtab" readonly style="background: #f0f0f0;">
          </div>
          <div class="form-group">
            <label for="serveur">Nom du serveur:</label>
            <input type="text" id="serveur" name="serveur" placeholder="Entrez votre nom" required>
          </div>
          <input type="hidden" name="date" value="<?php echo $dateDisponibilite; ?>">
          <input type="hidden" name="heure" value="<?php echo $heureDisponibilite; ?>">
          <input type="hidden" name="nbPersonnes" value="<?php echo $nbPersonnesDisponibilite; ?>">
          <button type="submit">Confirmer la réservation</button>
        </form>
      <?php endif; ?>
    </div>

    <!-- RÉSERVATIONS À VENIR -->
    <div class="section">
      <h2>Réservations à Venir</h2>
      <?php if (!empty($reservationsAVenir)): ?>
        <div class="reservations-list">
          <?php foreach ($reservationsAVenir as $res): ?>
            <div class="reservation-item">
              <div class="reservation-time">
                #<?php echo $res['numres']; ?> - Table <?php echo $res['numtab']; ?>
              </div>
              <div style="font-size: 14px; margin-top: 5px;">
                <?php echo date('d/m/Y H:i', strtotime($res['datres'])); ?><br>
                <p>Serveur: <?php echo htmlspecialchars($res['serveur']); ?></p>
                <p>Nombre personne: <?php echo $res['nbpers']; ?> personne<?php echo $res['nbpers'] > 1 ? 's' : ''; ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color: #666;">Aucune réservation à venir</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- LISTE DES PLATS -->
  <div class="section">
    <h2>Liste des Plats</h2>
    <div class="table-list">
      <?php foreach ($plat as $p): ?>
        <div class="table-card">
          <strong><?php echo htmlspecialchars($p["libelle"]); ?></strong>
          <div class="table-places"><?php echo htmlspecialchars($p["type"]); ?></div>
          <div style="margin-top: 10px; font-size: 18px; color: #28a745; font-weight: bold;">
            <?php echo htmlspecialchars($p["prixunit"]); ?> €
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
  function selectTable(numtab, nbplaces) {
    document.getElementById('numtab').value = numtab;
    
    const cards = document.querySelectorAll('.table-card');
    cards.forEach(card => card.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
  }
</script>

</body>
</html>
