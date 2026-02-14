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

**Éditer le fichier** `ressources/conf/conf.ini` :

```ini
[database]
host = localhost
dbname = gestion_restaurant
username = votre_username
password = votre_password
charset = utf8mb4
```

**Remplacer :**
- `votre_username` : votre utilisateur MySQL
- `votre_password` : votre mot de passe MySQL

2. **Créer la base de données** :

**Via phpMyAdmin**
1. Se connecter à phpMyAdmin
2. Créer le nom de la base de donnèes dans le fichier .ini
3. Cliquer sur "Importer"
4. Sélectionner le fichier `ressources/repo/GestionResto.sql`
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
