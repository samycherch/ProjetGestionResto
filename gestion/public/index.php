<?php
session_start();

require_once __DIR__ . "/../vendor/autoload.php";

use gestion\pdo\Database;
use gestion\repo\ReservationRepository;
use gestion\repo\ServerRepository;

// Traiter la sélection du serveur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'selectServeur') {
    $_SESSION['numserv'] = (int)$_POST['numserv'];
    exit; 
}

// Traiter la réassignation d'un serveur pour une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reassignerServeur') {
    try {
        $numres = (int)$_POST['numres'];
        $newNumserv = (int)$_POST['newNumserv'];
        
        // Vérifier que le nouveau serveur existe et est actif
        $newServer = ServerRepository::obtenirServerParId($newNumserv);
        if (!$newServer) {
            throw new Exception("Serveur invalide");
        }
        
        Database::beginTransaction();
        ReservationRepository::mettreAJourReservation($numres, ['numserv' => $newNumserv]);
        Database::commit();
        
        $reassignmentSuccess = "Serveur réassigné avec succès";
    } catch (Exception $e) {
        Database::rollback();
        $reassignmentError = "Erreur: " . $e->getMessage();
    }
}

// Récupérer les réservations à venir
try {
    Database::beginTransaction();
    $reservationsAVenir = ReservationRepository::obtenirReservationsAVenir();
    $serveurs = ServerRepository::obtenirTousLesServeurs();
    Database::commit();
} catch (Exception $e) {
    Database::rollback();
    $reservationsAVenir = [];
    $serveurs = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resto - Gestion des Réservations</title>
    <link rel="stylesheet" href="ressources/css/style.css?v=2.2">
    <style>
        .serveur-selector {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 100;
        }

        .serveur-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid #333;
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }

        .serveur-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .serveur-button img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .serveur-button .placeholder {
            font-size: 28px;
        }

        .serveur-label {
            position: absolute;
            top: 70px;
            left: 10px;
            background: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: none;
        }

        .serveur-button:hover ~ .serveur-label {
            display: block;
        }

        .serveur-label:hover {
            display: block;
        }

        .serveur-menu {
            position: absolute;
            top: 70px;
            left: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 101;
            display: none;
            min-width: 200px;
        }

        .serveur-menu.active {
            display: block;
        }

        .serveur-menu-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            hover: background #f5f5f5;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .serveur-menu-item:last-child {
            border-bottom: none;
        }

        .serveur-menu-item:hover {
            background: #f5f5f5;
        }

        .serveur-menu-item img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .serveur-menu-item .placeholder {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .serveur-menu-gerer {
            padding: 10px 15px;
            background: #e9ecef;
            border-top: 1px solid #ddd;
            text-align: center;
        }

        .serveur-menu-gerer a {
            color: #0066cc;
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
        }

        .serveur-menu-gerer a:hover {
            text-decoration: underline;
        }

        .container {
            position: relative;
            padding-top: 40px;
        }
    </style>
</head>

<body>

<div class="serveur-selector">
    <button class="serveur-button" onclick="toggleServeurMenu()">
        <?php 
            $serveurActuel = $_SESSION['numserv'] ?? null;
            if ($serveurActuel && !empty($serveurs)):
                $serveur = array_filter($serveurs, fn($s) => $s['numserv'] == $serveurActuel)[0] ?? null;
                if ($serveur && $serveur['image']):
                    echo '<img src="ressources/images/serveurs/' . htmlspecialchars($serveur['image']) . '" alt="Serveur">';
                else:
                    echo '<span class="placeholder">👤</span>';
                endif;
            else:
                echo '<span class="placeholder">👤</span>';
            endif;
        ?>
    </button>
    <div class="serveur-label" id="serveurLabel">
        <?php 
            if ($serveurActuel && !empty($serveurs)):
                $serveur = array_filter($serveurs, fn($s) => $s['numserv'] == $serveurActuel)[0] ?? null;
                if ($serveur):
                    echo htmlspecialchars($serveur['prenom']);
                endif;
            else:
                echo 'Serveur';
            endif;
        ?>
    </div>

    <div class="serveur-menu" id="serveurMenu">
        <div style="padding: 10px 15px; font-weight: bold; border-bottom: 1px solid #ddd;">Sélectionner serveur:</div>
        <?php foreach ($serveurs as $serveur): ?>
            <div class="serveur-menu-item" onclick="selectServeur(<?php echo $serveur['numserv']; ?>)">
                <?php if ($serveur['image']): ?>
                    <img src="ressources/images/serveurs/<?php echo htmlspecialchars($serveur['image']); ?>" alt="Serveur">
                <?php else: ?>
                    <div class="placeholder">👤</div>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($serveur['prenom'] . ' ' . $serveur['nom']); ?></span>
            </div>
        <?php endforeach; ?>
        <div class="serveur-menu-gerer">
            <a href="./fonction/gererServeurs.php">+ Gérer les serveurs</a>
        </div>
    </div>
</div>

<div class="container">

    <h1>Gestion des Réservations - Restaurant</h1>

    <h2>Menu Principal</h2>

    <div class="menu-buttons">
        <button onclick="window.location.href='./fonction/reserver.php'">Réserver une Table</button>
        <button onclick="window.location.href='./fonction/commander.php'">Commander des Plats</button>
        <button onclick="window.location.href='./fonction/consulterPlats.php'">Consulter les Plats</button>
        <button onclick="window.location.href='./fonction/modifierPlat.php'">Modifier les Plats</button>
        <button onclick="window.location.href='./fonction/annuler.php'">Annuler une Réservation</button>
        <button onclick="window.location.href='./fonction/encaisser.php'">Encaisser une Réservation</button>
    </div>

    <!-- RÉSERVATIONS À VENIR -->
    <h2>Réservations à Venir</h2>

    <?php if (isset($reassignmentSuccess)): ?>
        <p style="color: green; font-weight: bold; padding: 10px; background: #e8f5e9; border-radius: 4px;">✓ <?php echo htmlspecialchars($reassignmentSuccess); ?></p>
    <?php endif; ?>
    
    <?php if (isset($reassignmentError)): ?>
        <p style="color: red; font-weight: bold; padding: 10px; background: #ffebee; border-radius: 4px;">✗ <?php echo htmlspecialchars($reassignmentError); ?></p>
    <?php endif; ?>

    <?php if (!empty($reservationsAVenir)): ?>
        <table>
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Table</th>
                    <th>Date/Heure</th>
                    <th>Serveur</th>
                    <th>Personnes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservationsAVenir as $res): ?>
                    <tr<?php echo (!$res['actif']) ? ' style="background-color: #fff3cd;"' : ''; ?>>
                        <td>#<?php echo $res['numres']; ?></td>
                        <td>Table <?php echo $res['numtab']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($res['datres'])); ?></td>
                        <td>
                            <?php echo htmlspecialchars(($res['prenom'] ?? '') . ' ' . ($res['nom'] ?? '')); ?>
                            <?php if (!$res['actif']): ?>
                                <span style="color: red; font-weight: bold;"> ⚠ Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $res['nbpers']; ?></td>
                        <td>
                            <?php if (!$res['actif']): ?>
                                <button onclick="openReassignModal(<?php echo $res['numres']; ?>, '<?php echo htmlspecialchars($res['prenom'] ?? ''); ?> <?php echo htmlspecialchars($res['nom'] ?? ''); ?>')">Réassigner</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="info-message">Aucune réservation à venir</p>
    <?php endif; ?>

</div>

<div id="reassignModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 400px; width: 90%;">
        <h3>Réassigner le Serveur</h3>
        <p id="reassignMessage"></p>
        <form method="POST" style="margin-top: 20px;">
            <input type="hidden" name="action" value="reassignerServeur">
            <input type="hidden" name="numres" id="reassignNumres">
            <label for="newNumserv">Sélectionner un serveur actif:</label>
            <select name="newNumserv" id="newNumserv" required style="width: 100%; padding: 8px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">-- Choisir un serveur --</option>
                <?php foreach ($serveurs as $serveur): ?>
                    <option value="<?php echo $serveur['numserv']; ?>">
                        <?php echo htmlspecialchars($serveur['prenom'] . ' ' . $serveur['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div style="margin-top: 20px; text-align: right;">
                <button type="button" onclick="closeReassignModal()" style="background: #ccc; color: black; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">Annuler</button>
                <button type="submit" style="background: #4CAF50; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">Réassigner</button>
            </div>
        </form>
    </div>
</div>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Resto - Gestion des Réservations</p>
    <p class="footer-small">Développé par Samy Cherchari et Nathan Yvon</p>
</footer>

<script>
    function openReassignModal(numres, serverName) {
        document.getElementById('reassignNumres').value = numres;
        document.getElementById('reassignMessage').textContent = 'Réassigner la réservation #' + numres + ' (ancien serveur: ' + serverName + ')';
        document.getElementById('reassignModal').style.display = 'flex';
    }

    function closeReassignModal() {
        document.getElementById('reassignModal').style.display = 'none';
    }

    document.addEventListener('click', function(event) {
        const modal = document.getElementById('reassignModal');
        if (event.target === modal) {
            closeReassignModal();
        }
    });

    function toggleServeurMenu() {
        const menu = document.getElementById('serveurMenu');
        menu.classList.toggle('active');
    }

    function selectServeur(numserv) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=selectServeur&numserv=' + numserv
        }).then(() => {
            location.reload();
        });
    }

    document.addEventListener('click', function(event) {
        const serveurSelector = document.querySelector('.serveur-selector');
        if (!serveurSelector.contains(event.target)) {
            document.getElementById('serveurMenu').classList.remove('active');
        }
    });
</script>

</body>
</html>
