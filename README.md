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

### 1. **Authentification**
    - Connexion du serveur à la base de données via login/mot de passe du SGBD
    - Gestion centralisée des transactions

### 2. **Gestion des Réservations**
   - Réserver une table disponible à une date et heure données
   - Annuler une réservation non consommée
   - Afficher le statut des tables et des réservations

### 3. **Gestion des Commandes**
   - Commander des plats disponibles pour une réservation donnée
   - Enregistrer la quantité et le prix unitaire de chaque plat
   - Tracker les quantités servies par jour

### 4. **Gestion des Plats**
   - Modifier le prix unitaire d'un plat
   - Modifier la quantité servie par jour
   - Consulter les plats disponibles et leurs stocks

### 5. **Gestion de l'Encaissement**
   - Calculer le montant total d'une réservation consommée
   - Enregistrer la date et l'heure d'encaissement
   - Enregistrer le mode de paiement
   - Mettre à jour le statut de la réservation

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
- `datres` (DATE) : Date et heure de la réservation
- `nbpers` (INT) : Nombre de personnes
- `datpaie` (DATE) : Date et heure du paiement
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
   - Éditer `ressources/conf/conf.ini`
   - Ajouter vos identifiants de base de données (username, password, host, dbname)

   ```ini
   [database]
   host = localhost
   dbname = gestion_restaurant
   username = votre_username
   password = votre_password
   ```

2. **Créer la base de données** :
   - Se connecter à PhpMyAdmin
   - Importer le fichier SQL fourni (ressources/repo/[fichier].sql)
   - Cela créera toutes les tables avec les contraintes d'intégrité référentielle
   - Les données de démonstration incluent :
     - 10 tables (numéros 10-19) avec 2 à 8 places
     - 16 plats disponibles (entrées, viandes, poisson, desserts, fromage)
     - 7 réservations avec des commandes variées

### Flux d'Utilisation Typique

```
1. Connexion du serveur (login/password)
   ↓
2. Afficher les tables disponibles
   ↓
3. Choisir une opération :
   - Réserver une table → Faire une commande → Encaisser
   - Annuler une réservation
   - Modifier les prix/quantités des plats
```

---

## Sécurité et Transactions

- **Utilisation de PDO** : Protection contre les injections SQL via requêtes préparées
- **Mode Transactionnel** : Chaque opération critique utilise BEGIN/COMMIT/ROLLBACK
- **Gestion des Erreurs** : Rollback automatique en cas d'erreur pour éviter l'incohérence des données
- **Concurrence** : Verrous optimiste/pessimiste pour éviter les conflits entre serveurs

---

## Points Clés d'Implémentation

### Intégrité Référentielle
- **FK sur RESERVATION** : référence TABL (table réservée)
- **FK sur COMMANDE** : références RESERVATION et PLAT (rattachement à la réservation et au plat)
- **Contraintes** : ON DELETE RESTRICT pour éviter les suppressions en cascade non autorisées
- **Clé composite dans COMMANDE** : (numres, numplat) pour éviter les doublons

---

**Développé par Nathan Yvon et Samy Cherchari DWM2**
