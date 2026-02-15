<?php
session_start();

require_once __DIR__ . "/../../vendor/autoload.php";

use gestion\pdo\Database;
use gestion\repo\ServerRepository;
use PDO;

$message = '';
$messageType = '';
$serveurs = [];

// Récupérer tous les serveurs
try {
    Database::beginTransaction();
    $serveurs = ServerRepository::obtenirTousLesServeurs();
    Database::commit();
} catch (Exception $e) {
    Database::rollback();
    $message = "Erreur lors du chargement des serveurs: " . $e->getMessage();
    $messageType = 'error';
}

// Traitement de l'ajout de serveur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'ajouter') {
        try {
            $nom = $_POST['nom'] ?? '';
            $prenom = $_POST['prenom'] ?? '';
            $image = '';

            // Gestion de l'upload d'image
            if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                // Vérifier les erreurs d'upload
                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    $errors = [
                        UPLOAD_ERR_INI_SIZE => "Le fichier dépasse la taille max ini_set",
                        UPLOAD_ERR_FORM_SIZE => "Le fichier dépasse la taille max du formulaire",
                        UPLOAD_ERR_PARTIAL => "Le fichier n'a été téléchargé que partiellement",
                        UPLOAD_ERR_NO_FILE => "Aucun fichier n'a été téléchargé",
                        UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant",
                        UPLOAD_ERR_CANT_WRITE => "Impossible d'écrire le fichier",
                        UPLOAD_ERR_EXTENSION => "Une extension PHP a arrêté le téléchargement"
                    ];
                    throw new Exception("Erreur upload: " . ($errors[$_FILES['image']['error']] ?? "Erreur inconnue"));
                }

                // Construire le chemin de manière simple et efficace
                // __DIR__ = X:\var\www\gestionresto\gestion\public\fonction
                // On veut aller à: X:\var\www\gestionresto\gestion\public\ressources\images\serveurs\
                $publicDir = dirname(__DIR__); // X:\var\www\gestionresto\gestion\public
                $imageDir = $publicDir . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'serveurs' . DIRECTORY_SEPARATOR;
                
                // Vérifier que le dossier ressources existe
                $resourcesDir = $publicDir . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
                if (!is_dir($resourcesDir)) {
                    throw new Exception("Le dossier ressources/images n'existe pas: " . $resourcesDir);
                }

                // Créer le dossier serveurs s'il n'existe pas
                if (!is_dir($imageDir)) {
                    if (!@mkdir($imageDir, 0777, true)) {
                        throw new Exception("Impossible de créer le dossier: " . $imageDir);
                    }
                }

                $filename = basename($_FILES['image']['name']);
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                // Vérifier l'extension
                $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($extension, $allowedExt)) {
                    throw new Exception("Format d'image non autorisé. Utilisez JPG, PNG ou GIF.");
                }

                // Renommer avec un timestamp
                $image = 'serveur_' . time() . '.' . $extension;
                $targetPath = $imageDir . $image;

                // Vérifier que le dossier est accessible
                if (!is_writable($imageDir)) {
                    throw new Exception("Le dossier n'est pas accessible en écriture: " . $imageDir);
                }

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    throw new Exception("Impossible de déplacer le fichier uploadé vers: " . $targetPath);
                }
                
                // Vérifier que le fichier existe bien
                if (!file_exists($targetPath)) {
                    throw new Exception("Le fichier a été uploadé mais n'existe pas à: " . $targetPath);
                }
            }

            Database::beginTransaction();
            $numserv = ServerRepository::creeServer($nom, $prenom, $image);
            Database::commit();

            $message = "Serveur créé avec succès!";
            $messageType = 'success';

            // Recharger les serveurs
            $serveurs = ServerRepository::obtenirTousLesServeurs();
        } catch (Exception $e) {
            Database::rollback();
            $message = "Erreur: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    // Suppression de serveur (soft delete - désactivation)
    if ($_POST['action'] === 'supprimer') {
        try {
            $numserv = (int)$_POST['numserv'];
            
            Database::beginTransaction();
            ServerRepository::desactiverServer($numserv);
            Database::commit();

            $message = "Serveur désactivé avec succès! Il n'apparaîtra plus dans l'application.";
            $messageType = 'success';

            // Recharger les serveurs
            $serveurs = ServerRepository::obtenirTousLesServeurs();
        } catch (Exception $e) {
            Database::rollback();
            $message = "Erreur: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Serveurs</title>
    <link rel="stylesheet" href="../ressources/css/style.css?v=2.2">
    <style>
        .serveur-page {
            padding: 20px;
        }

        .serveur-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .serveur-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .serveur-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .serveur-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 10px;
            background: #f0f0f0;
        }

        .serveur-nom {
            font-weight: bold;
            font-size: 16px;
            margin: 10px 0;
        }

        .serveur-prenom {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .btn-supprimer {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-supprimer:hover {
            background-color: #c82333;
        }

        .formulaire-serveur {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            max-width: 500px;
        }

        .formulaire-serveur h3 {
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .form-group input[type="file"] {
            padding: 5px;
        }

        .btn-ajouter {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-ajouter:hover {
            background-color: #218838;
        }

        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .preview-image {
            max-width: 100px;
            margin-top: 10px;
            border-radius: 4px;
        }
    </style>
</head>

<body>

<div class="serveur-page">
    <h1>Gestion des Serveurs</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="formulaire-serveur">
        <h3>Ajouter un nouveau serveur</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="ajouter">

            <div class="form-group">
                <label for="prenom">Prénom *</label>
                <input type="text" id="prenom" name="prenom" required>
            </div>

            <div class="form-group">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" required>
            </div>

            <div class="form-group">
                <label for="image">Photo du serveur</label>
                <input type="file" id="image" name="image" accept="image/*">
                <small>JPG, PNG ou GIF acceptés</small>
                <img id="preview" class="preview-image" style="display: none;">
            </div>

            <button type="submit" class="btn-ajouter">✓ Ajouter le serveur</button>
        </form>
    </div>

    <h2>Serveurs actuels</h2>

    <?php if (!empty($serveurs)): ?>
        <div class="serveur-container">
            <?php foreach ($serveurs as $serveur): ?>
                <div class="serveur-card">
                    <?php if ($serveur['image'] && !empty(trim($serveur['image']))): ?>
                        <img src="../ressources/images/serveurs/<?php echo htmlspecialchars($serveur['image']); ?>"
                             alt="<?php echo htmlspecialchars($serveur['prenom']); ?>"
                             class="serveur-image"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: #ddd; margin: 0 auto 10px; display: none; align-items: center; justify-content: center;">
                            <span style="font-size: 30px;">👤</span>
                        </div>
                    <?php else: ?>
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: #ddd; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 30px;">👤</span>
                        </div>
                    <?php endif; ?>

                    <div class="serveur-prenom"><?php echo htmlspecialchars($serveur['prenom']); ?></div>
                    <div class="serveur-nom"><?php echo htmlspecialchars($serveur['nom']); ?></div>

                    <form method="POST" style="margin-top: 10px;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce serveur?');">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="numserv" value="<?php echo $serveur['numserv']; ?>">
                        <button type="submit" class="btn-supprimer">✕ Supprimer</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="info-message">Aucun serveur pour le moment. Créez-en un!</p>
    <?php endif; ?>

</div>

<script>
    // Aperçu de l'image
    document.getElementById('image').addEventListener('change', function(e) {
        const preview = document.getElementById('preview');
        const file = e.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });
</script>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Resto - Gestion des Réservations</p>
</footer>

</body>
</html>
