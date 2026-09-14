-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: gestion_scolaire
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `gestion_scolaire`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `gestion_scolaire` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `gestion_scolaire`;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `niveau` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,'Seconde A','Seconde'),(2,'Première B','Première'),(3,'Tyrex','Seconde_II'),(4,'Super','Super'),(5,'Alpha','Seconde_I'),(6,'Nova','Novalis');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cours_supplementaires`
--

DROP TABLE IF EXISTS `cours_supplementaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cours_supplementaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enseignant_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `date_heure` datetime NOT NULL,
  `duree_minutes` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `enseignant_id` (`enseignant_id`),
  KEY `classe_id` (`classe_id`),
  KEY `matiere_id` (`matiere_id`),
  CONSTRAINT `cours_supplementaires_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cours_supplementaires_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cours_supplementaires_ibfk_3` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cours_supplementaires`
--

LOCK TABLES `cours_supplementaires` WRITE;
/*!40000 ALTER TABLE `cours_supplementaires` DISABLE KEYS */;
INSERT INTO `cours_supplementaires` VALUES (1,13,6,4,'2026-02-19 10:15:00',30);
/*!40000 ALTER TABLE `cours_supplementaires` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emploi_du_temps`
--

DROP TABLE IF EXISTS `emploi_du_temps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `emploi_du_temps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `classe_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `enseignant_id` int(11) NOT NULL,
  `jour_semaine` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche') NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `classe_id` (`classe_id`),
  KEY `matiere_id` (`matiere_id`),
  KEY `enseignant_id` (`enseignant_id`),
  CONSTRAINT `emploi_du_temps_ibfk_1` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emploi_du_temps_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`),
  CONSTRAINT `emploi_du_temps_ibfk_3` FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emploi_du_temps`
--

LOCK TABLES `emploi_du_temps` WRITE;
/*!40000 ALTER TABLE `emploi_du_temps` DISABLE KEYS */;
INSERT INTO `emploi_du_temps` VALUES (1,1,1,2,'Lundi','08:00:00','09:00:00'),(2,1,2,3,'Lundi','09:00:00','10:00:00'),(3,2,2,3,'Mardi','10:00:00','11:00:00'),(4,5,2,13,'Lundi','12:00:00','15:00:00'),(5,6,4,13,'Lundi','08:00:00','12:00:00'),(6,6,4,13,'Mardi','08:30:00','10:30:00'),(7,6,4,13,'Mercredi','09:00:00','12:00:00'),(8,6,4,13,'Jeudi','10:00:00','14:00:00'),(11,6,1,11,'Lundi','14:00:00','15:00:00'),(12,6,8,13,'Vendredi','10:00:00','12:00:00');
/*!40000 ALTER TABLE `emploi_du_temps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enseignants_classes`
--

DROP TABLE IF EXISTS `enseignants_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enseignants_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enseignant_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `enseignant_id` (`enseignant_id`),
  KEY `classe_id` (`classe_id`),
  KEY `matiere_id` (`matiere_id`),
  CONSTRAINT `enseignants_classes_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enseignants_classes_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enseignants_classes_ibfk_3` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enseignants_classes`
--

LOCK TABLES `enseignants_classes` WRITE;
/*!40000 ALTER TABLE `enseignants_classes` DISABLE KEYS */;
INSERT INTO `enseignants_classes` VALUES (1,2,1,1),(2,3,1,2),(3,3,2,2),(4,13,6,4),(5,13,5,4);
/*!40000 ALTER TABLE `enseignants_classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inscriptions`
--

DROP TABLE IF EXISTS `inscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `annee_scolaire` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `eleve_id` (`eleve_id`),
  KEY `classe_id` (`classe_id`),
  CONSTRAINT `inscriptions_ibfk_1` FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inscriptions`
--

LOCK TABLES `inscriptions` WRITE;
/*!40000 ALTER TABLE `inscriptions` DISABLE KEYS */;
INSERT INTO `inscriptions` VALUES (1,4,1,'2023-2024'),(2,5,2,'2023-2024'),(8,10,6,'');
/*!40000 ALTER TABLE `inscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matieres`
--

DROP TABLE IF EXISTS `matieres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `matieres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matieres`
--

LOCK TABLES `matieres` WRITE;
/*!40000 ALTER TABLE `matieres` DISABLE KEYS */;
INSERT INTO `matieres` VALUES (7,'Chimie'),(6,'EPS'),(2,'Français'),(8,'Geographie'),(3,'Histoire'),(4,'Informatique'),(1,'Mathématiques');
/*!40000 ALTER TABLE `matieres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('email','sms','whatsapp') NOT NULL DEFAULT 'email',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `is_read` tinyint(1) DEFAULT 0,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recipient_id` (`recipient_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,9,'Alerte Scolaire : Votre enfant eleve eleve a été marqué comme \'Retard\' lors du cours de Informatique ce 20/02/2026 à 15:11.','email','pending',1,'2026-02-20 14:11:30'),(2,9,'Bonjour,\n\nNous vous informons que votre enfant eleve eleve a été marqué comme \'Absent\' lors du cours de Informatique (Cours Supplémentaire) ce 20/02/2026 à 20:53.\n\nCordialement,\nL\'administration de Gestion Scolaire G','email','sent',1,'2026-02-20 19:53:07'),(3,9,'Bonjour, votre enfant eleve eleve a été marqué comme \'Absent\' en Informatique (Cours Supplémentaire) le 20/02/2026 à 21:00.','email','sent',1,'2026-02-20 20:00:56'),(4,9,'Bonjour, votre enfant <b>eleve eleve</b> a été marqué comme \'<b>Absent</b>\' en <b>Informatique</b> le 20/02/2026 à 22:41.','email','failed',1,'2026-02-20 21:41:11'),(5,9,'Bonjour, votre enfant <b>eleve eleve</b> a été marqué comme \'<b>Excusé</b>\' en <b>Informatique</b> le 20/02/2026 à 22:41.','email','failed',1,'2026-02-20 21:41:18'),(6,9,'Bonjour, votre enfant <b>eleve eleve</b> a été marqué comme \'<b>Absent</b>\' en <b>Informatique</b> le 20/02/2026 à 22:45.','email','failed',1,'2026-02-20 21:45:00'),(7,9,'Bonjour, votre enfant <b>eleve eleve</b> a été marqué comme \'<b>Absent</b>\' en <b>Informatique</b> le 20/02/2026 à 22:53.','email','sent',1,'2026-02-20 21:53:02'),(8,9,'Bonjour, votre enfant <b>eleve eleve</b> a été marqué comme \'<b>Absent</b>\' en <b>Informatique</b> le 26/03/2026 à 11:06.','email','sent',1,'2026-03-26 10:06:27');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parents_eleves`
--

DROP TABLE IF EXISTS `parents_eleves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parents_eleves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `eleve_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `eleve_id` (`eleve_id`),
  CONSTRAINT `parents_eleves_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parents_eleves_ibfk_2` FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parents_eleves`
--

LOCK TABLES `parents_eleves` WRITE;
/*!40000 ALTER TABLE `parents_eleves` DISABLE KEYS */;
INSERT INTO `parents_eleves` VALUES (1,6,4),(2,9,10);
/*!40000 ALTER TABLE `parents_eleves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parents_enfants`
--

DROP TABLE IF EXISTS `parents_enfants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parents_enfants` (
  `parent_id` int(11) NOT NULL,
  `enfant_id` int(11) NOT NULL,
  PRIMARY KEY (`parent_id`,`enfant_id`),
  KEY `enfant_id` (`enfant_id`),
  CONSTRAINT `parents_enfants_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parents_enfants_ibfk_2` FOREIGN KEY (`enfant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parents_enfants`
--

LOCK TABLES `parents_enfants` WRITE;
/*!40000 ALTER TABLE `parents_enfants` DISABLE KEYS */;
INSERT INTO `parents_enfants` VALUES (9,4),(9,10);
/*!40000 ALTER TABLE `parents_enfants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `presences`
--

DROP TABLE IF EXISTS `presences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `presences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eleve_id` int(11) NOT NULL,
  `emploi_du_temps_id` int(11) NOT NULL,
  `date_cours` date NOT NULL,
  `statut` enum('Présent','Absent','Retard','Excusé') NOT NULL,
  `enregistre_par` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `eleve_id` (`eleve_id`),
  KEY `emploi_du_temps_id` (`emploi_du_temps_id`),
  KEY `enregistre_par` (`enregistre_par`),
  CONSTRAINT `presences_ibfk_1` FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `presences_ibfk_2` FOREIGN KEY (`emploi_du_temps_id`) REFERENCES `emploi_du_temps` (`id`) ON DELETE CASCADE,
  CONSTRAINT `presences_ibfk_3` FOREIGN KEY (`enregistre_par`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `presences`
--

LOCK TABLES `presences` WRITE;
/*!40000 ALTER TABLE `presences` DISABLE KEYS */;
INSERT INTO `presences` VALUES (6,10,7,'2026-02-18','Absent',13),(17,10,8,'2026-03-26','Absent',13);
/*!40000 ALTER TABLE `presences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `presences_supplementaires`
--

DROP TABLE IF EXISTS `presences_supplementaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `presences_supplementaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cours_supp_id` int(11) NOT NULL,
  `eleve_id` int(11) NOT NULL,
  `statut` enum('Présent','Absent','Retard','Excusé') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cours_supp_id` (`cours_supp_id`),
  KEY `eleve_id` (`eleve_id`),
  CONSTRAINT `presences_supplementaires_ibfk_1` FOREIGN KEY (`cours_supp_id`) REFERENCES `cours_supplementaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `presences_supplementaires_ibfk_2` FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `presences_supplementaires`
--

LOCK TABLES `presences_supplementaires` WRITE;
/*!40000 ALTER TABLE `presences_supplementaires` DISABLE KEYS */;
INSERT INTO `presences_supplementaires` VALUES (2,1,10,'Absent');
/*!40000 ALTER TABLE `presences_supplementaires` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('school_stamp','','2026-03-11 21:16:12'),('stamp_right','2','2026-03-11 21:16:12'),('stamp_size','12','2026-03-11 21:16:12'),('stamp_top','-8','2026-03-11 21:16:12');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('administrateur','enseignant','eleve','parent') NOT NULL,
  `photo` varchar(255) DEFAULT 'default_avatar.png',
  `telephone` varchar(20) DEFAULT NULL,
  `qr_token` varchar(100) DEFAULT NULL,
  `justificatif` varchar(255) DEFAULT NULL,
  `enfants_noms` text DEFAULT NULL,
  `statut_compte` varchar(20) DEFAULT 'actif',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `qr_token` (`qr_token`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateurs`
--

LOCK TABLES `utilisateurs` WRITE;
/*!40000 ALTER TABLE `utilisateurs` DISABLE KEYS */;
INSERT INTO `utilisateurs` VALUES (1,'Admin','Principal','admin@ecole.com','.wK07mig0OTiL2prunA7et8XrOG6qpANT6zk3B3yG','administrateur','default_avatar.png',NULL,NULL,NULL,NULL,'actif','2026-02-04 14:35:40'),(2,'Durand','Paul','p.durand@ecole.com','','enseignant','default_avatar.png',NULL,NULL,NULL,NULL,'actif','2026-02-04 14:35:40'),(3,'Martin','Sophie','s.martin@ecole.com','$2y$10$bI.n.0m2MhquXg0aD8y.e.x0iJb2M4Yv3r/P8/C7.I.P0xZ1yG/2C','enseignant','default_avatar.png',NULL,NULL,NULL,NULL,'actif','2026-02-04 14:35:40'),(4,'Dupont','Leo','leo.dupont@email.com','.0NTWe/3rwdoy6tzW6V8qVqAEWIH9hPYOdvhG','eleve','default_avatar.png',NULL,'80252d8bfa6640d3d864cdcee2667899',NULL,NULL,'actif','2026-02-04 14:35:40'),(5,'Petit','Mia','mia.petit@email.com','$2y$10$O/P.Q.R1s/T2u/V3w/X4y.Z5A.B6C.D7E.F8G.H9I.J0K.L1M.N2o','eleve','default_avatar.png',NULL,NULL,NULL,NULL,'actif','2026-02-04 14:35:40'),(6,'Dupont','Jean','j.dupont@email.com','/oML7oSY8RFlvEOXG1cgRS2Fe','parent','default_avatar.png',NULL,NULL,NULL,NULL,'actif','2026-02-04 14:35:40'),(7,'aq','aq','aq@gmail.com','aq','administrateur','default_avatar.png',NULL,NULL,NULL,NULL,'actif','2026-02-15 16:52:07'),(8,'Tyrex','ar','as@gmail.com','$2y$10$49LQ2ZHTwad9lU6iDCbI8O2RuE4pCUu3gwIjvpzhWtV/HXwlMlMaC','enseignant','default_avatar.png',NULL,NULL,NULL,NULL,'actif','2026-02-17 08:59:34'),(9,'Parent','Parent','parent@gmail.com','$2y$10$EpGg5/uBQObVezNUp7sz3ONTU0tpiY/r.uizrC5NMmWf0wRKJeTda','parent','default_avatar.png','689-12-89-53',NULL,NULL,NULL,'actif','2026-02-17 09:02:38'),(10,'eleve','eleve','eleve@gmail.com','$2y$10$aW7f45eRgq6JkL4lNF.lp.iG4DbTJWOIzmmSgAp.gDQbinyX3/ue2','eleve','10_1771591815.png','',NULL,NULL,NULL,'actif','2026-02-17 09:03:07'),(11,'enseignant','enseignant','enseignant@gmail.com','$2y$10$QURvU8gqkrjrXkf.0pubse7j8NJzyUHFfKZMFObsKJX8bioYZmy1K','enseignant','default_avatar.png',NULL,NULL,NULL,NULL,'actif','2026-02-17 09:03:32'),(12,'alo','a','aaw@gmail.com','$2y$10$QQM1BZomHLNBlXXd//zw9.G8s2UmddpvwRW/xK4ngYw7vcz.oXOkC','enseignant','default_avatar.png',NULL,NULL,NULL,NULL,'actif','2026-02-17 18:00:48'),(13,'Junior','Jun','j@gmail.com','$2y$10$KaMht.Njid.dOluCBPVpqePoiyJCfnagL6xE4Zfj/Mvb.cgbM27Qy','enseignant','default_avatar.png',NULL,NULL,NULL,NULL,'actif','2026-02-17 18:39:06');
/*!40000 ALTER TABLE `utilisateurs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-08 20:13:23
