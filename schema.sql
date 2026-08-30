-- --------------------------------------------------------
-- Fichier SQL pour la création de la base de données "gestion_scolaire"
-- Version 2: Normalisation des matières et ajout des emplois du temps
-- --------------------------------------------------------

--
-- Base de données : `gestion_scolaire`
--
CREATE DATABASE IF NOT EXISTS `gestion_scolaire` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gestion_scolaire`;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--
CREATE TABLE `utilisateurs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `role` ENUM('administrateur', 'enseignant', 'eleve', 'parent') NOT NULL,
  `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `classes`
--
CREATE TABLE `classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(100) NOT NULL UNIQUE,
  `niveau` VARCHAR(50) -- Ex: "Seconde", "Première"
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `matieres` (Nouvelle table)
--
CREATE TABLE `matieres` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `inscriptions` (Lien élèves <-> classes)
--
CREATE TABLE `inscriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `eleve_id` INT NOT NULL,
  `classe_id` INT NOT NULL,
  `annee_scolaire` VARCHAR(20) NOT NULL, -- Ex: "2023-2024"
  FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `enseignants_classes` (Lien enseignants <-> classes/matières)
--
CREATE TABLE `enseignants_classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `enseignant_id` INT NOT NULL,
  `classe_id` INT NOT NULL,
  `matiere_id` INT NOT NULL, -- Modifié
  FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id`) ON DELETE RESTRICT -- Modifié
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `emploi_du_temps` (Nouvelle table)
--
CREATE TABLE `emploi_du_temps` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `classe_id` INT NOT NULL,
  `matiere_id` INT NOT NULL,
  `enseignant_id` INT NOT NULL,
  `jour_semaine` ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche') NOT NULL,
  `heure_debut` TIME NOT NULL,
  `heure_fin` TIME NOT NULL,
  FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `presences`
--
CREATE TABLE `presences` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `eleve_id` INT NOT NULL,
  `emploi_du_temps_id` INT NOT NULL, -- Référence au cours de l'emploi du temps
  `date_cours` DATE NOT NULL,
  `statut` ENUM('Présent', 'Absent', 'Retard', 'Excusé') NOT NULL,
  `enregistre_par` INT NOT NULL, -- ID de l'enseignant qui a fait l'appel
  FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`emploi_du_temps_id`) REFERENCES `emploi_du_temps`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`enregistre_par`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Structure de la table `cours_supplementaires`
--
CREATE TABLE `cours_supplementaires` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `enseignant_id` INT NOT NULL,
  `classe_id` INT NOT NULL,
  `matiere_id` INT NOT NULL, -- Modifié
  `date_heure` DATETIME NOT NULL,
  `duree_minutes` INT NOT NULL,
  FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id`) ON DELETE RESTRICT -- Modifié
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `presences_supplementaires`
--
CREATE TABLE `presences_supplementaires` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cours_supp_id` INT NOT NULL,
    `eleve_id` INT NOT NULL,
    `statut` ENUM('Présent', 'Absent', 'Retard', 'Excusé') NOT NULL,
    FOREIGN KEY (`cours_supp_id`) REFERENCES `cours_supplementaires`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `parents_eleves`
--
CREATE TABLE `parents_eleves` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NOT NULL,
    `eleve_id` INT NOT NULL,
    FOREIGN KEY (`parent_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Données d'exemple
-- Mots de passe d'origine: admin123, prof123, prof456, eleve123, eleve456, parent123
-- --------------------------------------------------------

-- Utilisateurs (IDs: 1=Admin, 2=P.Durand, 3=S.Martin, 4=L.Dupont, 5=M.Petit, 6=J.Dupont)
INSERT INTO `utilisateurs` (`nom`, `prenom`, `email`, `mot_de_passe`, `role`) VALUES
('Admin', 'Principal', 'admin@ecole.com', '$2y$10$99v/2D3vj/E.pBv3dJVd7uunpDdqyFXj5UjJ1jSg7C9D6bJkxIlGG', 'administrateur'),
('Durand', 'Paul', 'p.durand@ecole.com', '$2y$10$wTdkxXtnplmF2ToMM4leIe5w.8ScmvVlD6E/nVLcTgwEaKxl2iCq.', 'enseignant'),
('Martin', 'Sophie', 's.martin@ecole.com', '$2y$10$bI.n.0m2MhquXg0aD8y.e.x0iJb2M4Yv3r/P8/C7.I.P0xZ1yG/2C', 'enseignant'),
('Dupont', 'Leo', 'leo.dupont@email.com', '$2y$10$3y/N.X7/mB/d/e/F8gHhJu.k6a/L.g1b.i9c.J4j.K2l.D6m.N0oO', 'eleve'),
('Petit', 'Mia', 'mia.petit@email.com', '$2y$10$O/P.Q.R1s/T2u/V3w/X4y.Z5A.B6C.D7E.F8G.H9I.J0K.L1M.N2o', 'eleve'),
('Dupont', 'Jean', 'j.dupont@email.com', '$2y$10$4z/A.B1C/D2E/F3G/H4I.J5K.L6M.N7O.P8Q.R9S.T0U.V1W.X2Y', 'parent');

-- Classes (IDs: 1='Seconde A', 2='Première B')
INSERT INTO `classes` (`id`, `nom`, `niveau`) VALUES
(1, 'Seconde A', 'Seconde'),
(2, 'Première B', 'Première');

-- Matières (IDs: 1='Mathématiques', 2='Français')
INSERT INTO `matieres` (`id`, `nom`) VALUES
(1, 'Mathématiques'),
(2, 'Français');

-- Inscriptions
INSERT INTO `inscriptions` (`eleve_id`, `classe_id`, `annee_scolaire`) VALUES
(4, 1, '2023-2024'), -- Leo Dupont en Seconde A
(5, 2, '2023-2024'); -- Mia Petit en Première B

-- Lien Parent/Élève
INSERT INTO `parents_eleves` (`parent_id`, `eleve_id`) VALUES
(6, 4); -- Jean Dupont est le parent de Leo Dupont

-- Attribution des enseignants
INSERT INTO `enseignants_classes` (`enseignant_id`, `classe_id`, `matiere_id`) VALUES
(2, 1, 1), -- Paul Durand enseigne les Maths (1) en Seconde A (1)
(3, 1, 2), -- Sophie Martin enseigne le Français (2) en Seconde A (1)
(3, 2, 2); -- Sophie Martin enseigne le Français (2) en Première B (2)

-- Emploi du temps
INSERT INTO `emploi_du_temps` (`classe_id`, `matiere_id`, `enseignant_id`, `jour_semaine`, `heure_debut`, `heure_fin`) VALUES
(1, 1, 2, 'Lundi', '08:00:00', '09:00:00'),    -- Seconde A, Maths, P. Durand, Lundi 8h-9h
(1, 2, 3, 'Lundi', '09:00:00', '10:00:00'),    -- Seconde A, Français, S. Martin, Lundi 9h-10h
(2, 2, 3, 'Mardi', '10:00:00', '11:00:00');   -- Première B, Français, S. Martin, Mardi 10h-11h

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `recipient_id` INT NOT NULL, -- ID de l'utilisateur parent
    `message` TEXT NOT NULL,
    `type` ENUM('email', 'sms', 'whatsapp') NOT NULL DEFAULT 'email',
    `status` ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`recipient_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
