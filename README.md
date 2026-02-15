# Gestion d'un Restaurant - Application PHP

## Description du Projet

Application web développée en PHP pour gérer les opérations d'un restaurant. Elle permet aux serveurs de gérer les réservations de tables, les commandes, et le suivi des plats disponibles via une base de données MySQL.

---

## Objectifs

### **L'application doit :**
- Permettre la connexion des serveurs à la base de données
- Gérer les transactions de manière sécurisée (mode transactionnel)
- Supporter l'accès concurrent de plusieurs serveurs
- Maintenir l'intégrité des données avec des contraintes référentielles

---

## Fonctionnalités Implémentées

---

## Architecture Technique

### Structure de la Base de Données

#### Table **TABL**
- `numtab` (INT, PK, AUTO_INCREMENT) : Identifiant unique de la table
- `nbplace` (INT) : Nombre maximum de places à cette table

#### Table **PLAT**
- `numplat` (INT, PK, AUTO_INCREMENT) : Identifiant unique du plat
- `libelle` (VARCHAR) : Nom du plat
- `type` (VARCHAR) : Type de plat (Entrée, Viande, Poisson, Dessert, Fromage, Plat)
- `prixunit` (DECIMAL) : Prix unitaire du plat
- `qteservie` (INT) : Quantité servie par jour

#### Table **RESERVATION**
- `numres` (INT, PK, AUTO_INCREMENT) : Identifiant unique de la réservation
- `numtab` (INT, FK) : Référence à la table réservée
- `serveur` (VARCHAR) : Nom du serveur responsable
- `datres` (DATETIME) : Date et heure de la réservation
- `nbpers` (INT) : Nombre de personnes
- `datpaie` (DATETIME) : Date et heure du paiement
- `modpaie` (VARCHAR) : Mode de paiement (Carte, Chèque, Espèces)
- `montcom` (DECIMAL) : Montant total de la commande

#### Table **COMMANDE**
- `numres` (INT, FK) : Référence à la réservation
- `numplat` (INT, FK) : Référence au plat
- `quantite` (INT) : Quantité commandée
- **Clé composite** : (numres, numplat)

### Modèles PHP

- **Tabl.php** : Gestion des tables du restaurant
- **Reservation.php** : Gestion des réservations
- **Commande.php** : Gestion des commandes
- **Plat.php** : Gestion des plats et des stocks

---

## Installation et Utilisation

### Prérequis

- PHP 7.4+
- MySQL 5.7+
- Accès à Apache/serveur web
- PDO MySQL activé

### Configuration Initiale

1. **Modifier le fichier de configuration** :

**Éditer le fichier** `gestion/conf/conf.ini` :

```ini
[database]
driver = "mysql"
host = "localhost"
database = "resto"
username = "votre_username"
password = "votre_password"
charset = "utf8"
collation = "utf8_unicode_ci"
```

**Remplacer :**
- `votre_username` : votre utilisateur MySQL
- `votre_password` : votre mot de passe MySQL

2. **Lancer Apache2 et MariaDb ou utiliser la méthode XAMPP** :
   - Apache2/MariaDb :
  
   Il vous faut maintenant lancer Apache2 et MariaDB pour que le site soit fonctionnel:

   Dans un premier temps, dans Apache2 il vous faut créer le site :
   Si vous utiliser WSL :
   - Il faut créer le fichier de configuration du site dans `/etc/apache2/sites-available/` avec le nom `gestionresto.conf` si vous voulez que l'adresse que je vous donne fonctionne :
      - Voici un exemple :
        ```conf
        <VirtualHost *:80>
         ServerName gestionresto.localhost
         DocumentRoot /var/www/gestionresto/gestion/public
         <Directory "/var/www/gestionresto/gestion/public">
         AllowOverride None
         </Directory>
         </VirtualHost>
         ```
   -Maintenant il faut activer le site via la commande (utiliser sudo si elle ne marche pas):
   ```bash
   a2ensite gestionresto.conf
   systemctl reload apache2
   ```
   - Après cela, veuillez créer le dossier `gestionresto` dans `/var/www/` et y mettre le dossier `gestion` de l'archive .zip (et le Readme.md si vous voulez).
   - Pour lancer MariaDB utiliser la commande suivante :
   ```bash
   service mariadb start 
   ```
   - Xampp :
   1.  Localisez votre dossier d'installation XAMPP (généralement `C:\xampp`).
   2.  Ouvrez le dossier `htdocs` (`C:\xampp\htdocs`).
   3.  Créez un dossier nommé `gestionresto`.
   4.  Copiez l'intégralité du contenu de votre projet (le dossier `gestion` et ses sous-dossiers) à l'intérieur de `C:\xampp\htdocs\gestionresto\`.

      *Chemin final attendu : `C:\xampp\htdocs\gestionresto\gestion\public\index.php`*

   5.  Lancez le **XAMPP Control Panel**.
   6.  Cliquez sur **Start** en face de **Apache**.
   7.  Cliquez sur **Start** en face de **MySQL**.
   8.  Attendez que les modules passent au vert.

3. **Créer la base de données** :

**Via phpMyAdmin**
1. Se connecter à phpMyAdmin
2. Créer le nom de la base de donnèes dans le fichier .ini
3. Cliquer sur "Importer"
4. Sélectionner le fichier `gestion/public/ressources/BDDConf/GestionResto.sql`
4. Cliquer sur "Exécuter"
   - Les données de démonstration incluent :
      - 10 tables (numéros 10-19) avec 2 à 8 places.
      - 16 plats disponibles (entrées, viandes, poisson, desserts, fromage).
      - 7 réservations avec des commandes variées.

## Améliorations Techniques de la Base de Données

### 1. DATETIME au lieu de DATE
**Pourquoi :** Pour sauvegarder l'heure exacte des réservations (12:30, 19:00...) et éviter les conflits sur la même table le même jour.

### 2. Clés Étrangères (Foreign Keys)
**Pourquoi :** Pour empêcher les erreurs d'intégrité (ex: commander un plat qui n'existe pas, supprimer une table avec des réservations actives).

### 3. Encodage utf8mb4
**Pourquoi :** Pour que les accents français s'affichent correctement (é, à, è, ç) au lieu de caractères bizarres.

### 4. DECIMAL au lieu de FLOAT
**Pourquoi :** Pour des calculs de prix précis au centime près, sans erreurs d'arrondi (8.50 × 3 = 25.50 exactement).

### 5. Mode Transactionnel
**Pourquoi :** Pour garantir que les opérations complexes (réserver + commander) se font atomiquement. Soit tout est validé, soit tout est annulé.

---

## Utilisation de l'Application

### Accès au site

- **Avec WSL** : `http://gestionresto.localhost`
- **Avec XAMPP** : `http://localhost/gestionresto/gestion/public`

### Fonctionnalités disponibles

1. **Réserver une Table** - Vérifier les tables disponibles et créer une réservation
2. **Commander des Plats** - Ajouter/supprimer des plats à une réservation
3. **Consulter les Plats** - Voir tous les plats et leur disponibilité
4. **Modifier les Plats** - Ajuster les prix et quantités disponibles
5. **Encaisser** - Finaliser le paiement d'une réservation
6. **Annuler** - Annuler une réservation et remettre les plats en stock
   


---

## Architecture

### Structure des fichiers

```
gestion/
├── public/              # Point d'entrée (HTML/formulaires)
│   ├── index.php        # Page d'accueil
│   ├── fonction/        # Pages métier
│   └── ressources/      # CSS, SQL
├── src/
│   ├── pdo/             # Database
│   └── repo/            # Classes Table
├── conf/
│   └── conf.ini         # Configuration BDD
└── vendor/              # Composer (autoload)
```

### Flux transactionnel

```
Utilisateur → formulaire → contrôleur → Repository → Database
                                ↓
                         BEGIN TRANSACTION
                         ↓
                      Exécute requêtes
                         ↓
                    COMMIT ou ROLLBACK
```

---

## Notes

- **Développeurs** : Samy Cherchari et Nathan Yvon
- **Dernière mise à jour** : 15/02/2026
- **Standards** : PSR-4 (Composer autoload), PDO, SQL standard
