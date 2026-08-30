-- Supprime la table si elle existe pour éviter les erreurs
DROP TABLE IF EXISTS `parents_enfants`;

-- Crée la table avec une collation compatible
CREATE TABLE `parents_enfants` (
  `parent_id` int NOT NULL,
  `enfant_id` int NOT NULL,
  PRIMARY KEY (`parent_id`,`enfant_id`),
  KEY `enfant_id` (`enfant_id`),
  CONSTRAINT `parents_enfants_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parents_enfants_ibfk_2` FOREIGN KEY (`enfant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
