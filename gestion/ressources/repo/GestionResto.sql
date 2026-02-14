SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ---------------------------------------------------------
-- Table : tabl
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `tabl`;
CREATE TABLE `tabl` (
  `numtab` int(11) NOT NULL AUTO_INCREMENT,
  `nbplace` int(2),
  PRIMARY KEY (`numtab`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tabl` (numtab, nbplace) VALUES
(10,4), (11,6), (12,8), (13,4), (14,6),
(15,4), (16,4), (17,6), (18,2), (19,4);

-- ---------------------------------------------------------
-- Table : plat
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `plat`;
CREATE TABLE `plat` (
  `numplat` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(40),
  `type` varchar(15),
  `prixunit` decimal(6,2) DEFAULT NULL,
  `qteservie` int(2),
  PRIMARY KEY (`numplat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `plat` (numplat, libelle, type, prixunit, qteservie) VALUES
(1,'Assiette de crudités','Entrée',9.00,25),
(2,'Tarte de saison','Dessert',6.50,25),
(3,'Sorbet mirabelle','Dessert',5.50,35),
(4,'Filet de boeuf','Viande',19.00,62),
(5,'Salade verte','Entrée',4.00,15),
(6,'Chèvre chaud','Entrée',8.50,21),
(7,'Pâté lorrain','Entrée',7.50,25),
(8,'Saumon fumé','Entrée',12.00,30),
(9,'Entrecôte printanière','Viande',18.50,58),
(10,'Gratin dauphinois','Plat',12.00,42),
(11,'Brochet à l\'oseille','Poisson',17.00,68),
(12,'Gigot d\'agneau','Viande',16.00,56),
(13,'Crème caramel','Dessert',5.00,15),
(14,'Munster au cumin','Fromage',6.00,18),
(15,'Filet de sole au beurre','Poisson',21.00,70),
(16,'Foie gras de Lorraine','Entrée',15.00,61);

-- ---------------------------------------------------------
-- Table : reservation
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `reservation`;
CREATE TABLE `reservation` (
  `numres` int(11) NOT NULL AUTO_INCREMENT,
  `numtab` int(11),
  `serveur` varchar(35),
  `datres` datetime, -- Correction : DATETIME pour garder l'heure
  `nbpers` int(2),
  `datpaie` datetime, -- Correction : DATETIME pour garder l'heure
  `modpaie` varchar(15),
  `montcom` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`numres`),
  CONSTRAINT `fk_res_tab` FOREIGN KEY (`numtab`) REFERENCES `tabl` (`numtab`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `reservation` VALUES
(100,10,'Paul','2021-09-10 19:00:00',2,'2021-09-10 20:50:00','Carte',null),
(101,11,'Albert','2021-09-10 20:00:00',4,'2021-09-10 21:20:00','Chèque',null),
(102,17,'Xavier','2021-09-10 18:00:00',2,'2021-09-10 20:55:00','Carte',null),
(103,12,'Beatrice','2021-09-10 19:00:00',2,'2021-09-10 21:10:00','Espèces',null),
(104,18,'Lola','2021-09-10 19:00:00',1,'2021-09-10 21:00:00','Chèque',null),
(105,10,'Lola1','2021-09-10 19:00:00',2,'2021-09-10 20:45:00','Carte',null),
(106,14,'Paul1','2021-10-11 19:00:00',2,'2021-10-11 22:45:00','Carte',null);

-- ---------------------------------------------------------
-- Table : commande
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `commande`;
CREATE TABLE `commande` (
  `numres` int(11) NOT NULL,
  `numplat` int(11) NOT NULL,
  `quantite` int(2),
  PRIMARY KEY (`numres`, `numplat`),
  CONSTRAINT `fk_cmd_res` FOREIGN KEY (`numres`) REFERENCES `reservation` (`numres`),
  CONSTRAINT `fk_cmd_plat` FOREIGN KEY (`numplat`) REFERENCES `plat` (`numplat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `commande` VALUES
(100,4,2),(100,5,2),(100,13,1),(100,3,1),
(101,7,2),(101,16,2),(101,12,2),(101,15,2),(101,2,2),(101,3,2),
(102,1,2),(102,10,2),(102,14,2),(102,2,1),(102,3,1),
(103,9,2),(103,14,2),(103,2,1),(103,3,1),
(104,7,1),(104,11,1),(104,14,1),(104,3,1),
(105,3,2),
(106,3,2);

SET foreign_key_checks = 1;
