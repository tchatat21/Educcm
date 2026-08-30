-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 20 fév. 2026 à 23:13
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_scolaire`
--

-- --------------------------------------------------------

--
-- Structure de la table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `niveau` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `classes`
--

INSERT INTO `classes` (`id`, `nom`, `niveau`) VALUES
(1, 'Seconde A', 'Seconde'),
(2, 'Première B', 'Première'),
(3, 'Tyrex', 'Seconde_II'),
(4, 'Super', 'Super'),
(5, 'Alpha', 'Seconde_I'),
(6, 'Nova', 'Novalis');

-- --------------------------------------------------------

--
-- Structure de la table `cours_supplementaires`
--

CREATE TABLE `cours_supplementaires` (
  `id` int(11) NOT NULL,
  `enseignant_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `date_heure` datetime NOT NULL,
  `duree_minutes` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `cours_supplementaires`
--

INSERT INTO `cours_supplementaires` (`id`, `enseignant_id`, `classe_id`, `matiere_id`, `date_heure`, `duree_minutes`) VALUES
(1, 13, 6, 4, '2026-02-19 10:15:00', 30);

-- --------------------------------------------------------

--
-- Structure de la table `emploi_du_temps`
--

CREATE TABLE `emploi_du_temps` (
  `id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL,
  `enseignant_id` int(11) NOT NULL,
  `jour_semaine` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche') NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `emploi_du_temps`
--

INSERT INTO `emploi_du_temps` (`id`, `classe_id`, `matiere_id`, `enseignant_id`, `jour_semaine`, `heure_debut`, `heure_fin`) VALUES
(1, 1, 1, 2, 'Lundi', '08:00:00', '09:00:00'),
(2, 1, 2, 3, 'Lundi', '09:00:00', '10:00:00'),
(3, 2, 2, 3, 'Mardi', '10:00:00', '11:00:00'),
(4, 5, 2, 13, 'Lundi', '12:00:00', '15:00:00'),
(5, 6, 4, 13, 'Lundi', '08:00:00', '12:00:00'),
(6, 6, 4, 13, 'Mardi', '08:30:00', '10:30:00'),
(7, 6, 4, 13, 'Mercredi', '09:00:00', '12:00:00'),
(8, 6, 4, 13, 'Jeudi', '10:00:00', '14:00:00'),
(10, 6, 4, 13, 'Vendredi', '11:00:00', '12:00:00'),
(11, 6, 1, 11, 'Lundi', '14:00:00', '15:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `enseignants_classes`
--

CREATE TABLE `enseignants_classes` (
  `id` int(11) NOT NULL,
  `enseignant_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `enseignants_classes`
--

INSERT INTO `enseignants_classes` (`id`, `enseignant_id`, `classe_id`, `matiere_id`) VALUES
(1, 2, 1, 1),
(2, 3, 1, 2),
(3, 3, 2, 2),
(4, 13, 6, 4),
(5, 13, 5, 4);

-- --------------------------------------------------------

--
-- Structure de la table `inscriptions`
--

CREATE TABLE `inscriptions` (
  `id` int(11) NOT NULL,
  `eleve_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `annee_scolaire` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `inscriptions`
--

INSERT INTO `inscriptions` (`id`, `eleve_id`, `classe_id`, `annee_scolaire`) VALUES
(1, 4, 1, '2023-2024'),
(2, 5, 2, '2023-2024'),
(8, 10, 6, '');

-- --------------------------------------------------------

--
-- Structure de la table `matieres`
--

CREATE TABLE `matieres` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `matieres`
--

INSERT INTO `matieres` (`id`, `nom`) VALUES
(7, 'Chimie'),
(6, 'EPS'),
(2, 'Français'),
(3, 'Histoire'),
(4, 'Informatique'),
(1, 'Mathématiques');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('email','sms','whatsapp') NOT NULL DEFAULT 'email',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `is_read` tinyint(1) DEFAULT 0,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `recipient_id`, `message`, `type`, `status`, `is_read`, `date_creation`) VALUES
(1, 9, 'Alerte Scolaire : Votre enfant eleve eleve a été marqué comme \'Retard\' lors du cours de Informatique ce 20/02/2026 à 15:11.', 'email', 'pending', 1, '2026-02-20 14:11:30'),
(2, 9, 'Bonjour,\n\nNous vous informons que votre enfant eleve eleve a été marqué comme \'Absent\' lors du cours de Informatique (Cours Supplémentaire) ce 20/02/2026 à 20:53.\n\nCordialement,\nL\'administration de Gestion Scolaire G', 'email', 'sent', 0, '2026-02-20 19:53:07'),
(3, 9, 'Bonjour, votre enfant eleve eleve a été marqué comme \'Absent\' en Informatique (Cours Supplémentaire) le 20/02/2026 à 21:00.', 'email', 'sent', 0, '2026-02-20 20:00:56'),
(4, 9, 'Bonjour, votre enfant <b>eleve eleve</b> a été marqué comme \'<b>Absent</b>\' en <b>Informatique</b> le 20/02/2026 à 22:41.', 'email', 'failed', 0, '2026-02-20 21:41:11'),
(5, 9, 'Bonjour, votre enfant <b>eleve eleve</b> a été marqué comme \'<b>Excusé</b>\' en <b>Informatique</b> le 20/02/2026 à 22:41.', 'email', 'failed', 0, '2026-02-20 21:41:18'),
(6, 9, 'Bonjour, votre enfant <b>eleve eleve</b> a été marqué comme \'<b>Absent</b>\' en <b>Informatique</b> le 20/02/2026 à 22:45.', 'email', 'failed', 0, '2026-02-20 21:45:00'),
(7, 9, 'Bonjour, votre enfant <b>eleve eleve</b> a été marqué comme \'<b>Absent</b>\' en <b>Informatique</b> le 20/02/2026 à 22:53.', 'email', 'sent', 0, '2026-02-20 21:53:02');

-- --------------------------------------------------------

--
-- Structure de la table `parents_eleves`
--

CREATE TABLE `parents_eleves` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `eleve_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `parents_eleves`
--

INSERT INTO `parents_eleves` (`id`, `parent_id`, `eleve_id`) VALUES
(1, 6, 4),
(2, 9, 10);

-- --------------------------------------------------------

--
-- Structure de la table `parents_enfants`
--

CREATE TABLE `parents_enfants` (
  `parent_id` int(11) NOT NULL,
  `enfant_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `parents_enfants`
--

INSERT INTO `parents_enfants` (`parent_id`, `enfant_id`) VALUES
(9, 4),
(9, 10);

-- --------------------------------------------------------

--
-- Structure de la table `presences`
--

CREATE TABLE `presences` (
  `id` int(11) NOT NULL,
  `eleve_id` int(11) NOT NULL,
  `emploi_du_temps_id` int(11) NOT NULL,
  `date_cours` date NOT NULL,
  `statut` enum('Présent','Absent','Retard','Excusé') NOT NULL,
  `enregistre_par` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `presences`
--

INSERT INTO `presences` (`id`, `eleve_id`, `emploi_du_temps_id`, `date_cours`, `statut`, `enregistre_par`) VALUES
(6, 10, 7, '2026-02-18', 'Absent', 13),
(16, 10, 10, '2026-02-20', 'Absent', 13);

-- --------------------------------------------------------

--
-- Structure de la table `presences_supplementaires`
--

CREATE TABLE `presences_supplementaires` (
  `id` int(11) NOT NULL,
  `cours_supp_id` int(11) NOT NULL,
  `eleve_id` int(11) NOT NULL,
  `statut` enum('Présent','Absent','Retard','Excusé') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `presences_supplementaires`
--

INSERT INTO `presences_supplementaires` (`id`, `cours_supp_id`, `eleve_id`, `statut`) VALUES
(2, 1, 10, 'Absent');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('administrateur','enseignant','eleve','parent') NOT NULL,
  `photo` varchar(255) DEFAULT 'default_avatar.png',
  `telephone` varchar(20) DEFAULT NULL,
  `qr_token` varchar(100) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `photo`, `telephone`, `qr_token`, `date_creation`) VALUES
(1, 'Admin', 'Principal', 'admin@ecole.com', '$2y$10$6YWaQoTHdKKuBJqvdPuRnOYTdlfHdaZLdkfNWy.jY3UZ2a19c01fG', 'administrateur', 'default_avatar.png', NULL, NULL, '2026-02-04 14:35:40'),
(2, 'Durand', 'Paul', 'p.durand@ecole.com', '$2y$10$wTdkxXtnplmF2ToMM4leIe5w.8ScmvVlD6E/nVLcTgwEaKxl2iCq.', 'enseignant', 'default_avatar.png', NULL, NULL, '2026-02-04 14:35:40'),
(3, 'Martin', 'Sophie', 's.martin@ecole.com', '$2y$10$bI.n.0m2MhquXg0aD8y.e.x0iJb2M4Yv3r/P8/C7.I.P0xZ1yG/2C', 'enseignant', 'default_avatar.png', NULL, NULL, '2026-02-04 14:35:40'),
(4, 'Dupont', 'Leo', 'leo.dupont@email.com', '$2y$10$3y/N.X7/mB/d/e/F8gHhJu.k6a/L.g1b.i9c.J4j.K2l.D6m.N0oO', 'eleve', 'default_avatar.png', NULL, '80252d8bfa6640d3d864cdcee2667899', '2026-02-04 14:35:40'),
(5, 'Petit', 'Mia', 'mia.petit@email.com', '$2y$10$O/P.Q.R1s/T2u/V3w/X4y.Z5A.B6C.D7E.F8G.H9I.J0K.L1M.N2o', 'eleve', 'default_avatar.png', NULL, NULL, '2026-02-04 14:35:40'),
(6, 'Dupont', 'Jean', 'j.dupont@email.com', '$2y$10$4z/A.B1C/D2E/F3G/H4I.J5K.L6M.N7O.P8Q.R9S.T0U.V1W.X2Y', 'parent', 'default_avatar.png', NULL, NULL, '2026-02-04 14:35:40'),
(7, 'aq', 'aq', 'aq@gmail.com', 'aq', 'administrateur', 'default_avatar.png', NULL, NULL, '2026-02-15 16:52:07'),
(8, 'Tyrex', 'ar', 'as@gmail.com', '$2y$10$49LQ2ZHTwad9lU6iDCbI8O2RuE4pCUu3gwIjvpzhWtV/HXwlMlMaC', 'enseignant', 'default_avatar.png', NULL, NULL, '2026-02-17 08:59:34'),
(9, 'Parent', 'Parent', 'parent@gmail.com', '$2y$10$EpGg5/uBQObVezNUp7sz3ONTU0tpiY/r.uizrC5NMmWf0wRKJeTda', 'parent', 'default_avatar.png', '689-12-89-53', NULL, '2026-02-17 09:02:38'),
(10, 'eleve', 'eleve', 'eleve@gmail.com', '$2y$10$aW7f45eRgq6JkL4lNF.lp.iG4DbTJWOIzmmSgAp.gDQbinyX3/ue2', 'eleve', '10_1771591815.png', '', NULL, '2026-02-17 09:03:07'),
(11, 'enseignant', 'enseignant', 'enseignant@gmail.com', '$2y$10$QURvU8gqkrjrXkf.0pubse7j8NJzyUHFfKZMFObsKJX8bioYZmy1K', 'enseignant', 'default_avatar.png', NULL, NULL, '2026-02-17 09:03:32'),
(12, 'alo', 'a', 'aaw@gmail.com', '$2y$10$QQM1BZomHLNBlXXd//zw9.G8s2UmddpvwRW/xK4ngYw7vcz.oXOkC', 'enseignant', 'default_avatar.png', NULL, NULL, '2026-02-17 18:00:48'),
(13, 'Junior', 'Jun', 'j@gmail.com', '$2y$10$KaMht.Njid.dOluCBPVpqePoiyJCfnagL6xE4Zfj/Mvb.cgbM27Qy', 'enseignant', 'default_avatar.png', NULL, NULL, '2026-02-17 18:39:06');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `cours_supplementaires`
--
ALTER TABLE `cours_supplementaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enseignant_id` (`enseignant_id`),
  ADD KEY `classe_id` (`classe_id`),
  ADD KEY `matiere_id` (`matiere_id`);

--
-- Index pour la table `emploi_du_temps`
--
ALTER TABLE `emploi_du_temps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `classe_id` (`classe_id`),
  ADD KEY `matiere_id` (`matiere_id`),
  ADD KEY `enseignant_id` (`enseignant_id`);

--
-- Index pour la table `enseignants_classes`
--
ALTER TABLE `enseignants_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enseignant_id` (`enseignant_id`),
  ADD KEY `classe_id` (`classe_id`),
  ADD KEY `matiere_id` (`matiere_id`);

--
-- Index pour la table `inscriptions`
--
ALTER TABLE `inscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eleve_id` (`eleve_id`),
  ADD KEY `classe_id` (`classe_id`);

--
-- Index pour la table `matieres`
--
ALTER TABLE `matieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Index pour la table `parents_eleves`
--
ALTER TABLE `parents_eleves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `eleve_id` (`eleve_id`);

--
-- Index pour la table `parents_enfants`
--
ALTER TABLE `parents_enfants`
  ADD PRIMARY KEY (`parent_id`,`enfant_id`),
  ADD KEY `enfant_id` (`enfant_id`);

--
-- Index pour la table `presences`
--
ALTER TABLE `presences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eleve_id` (`eleve_id`),
  ADD KEY `emploi_du_temps_id` (`emploi_du_temps_id`),
  ADD KEY `enregistre_par` (`enregistre_par`);

--
-- Index pour la table `presences_supplementaires`
--
ALTER TABLE `presences_supplementaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cours_supp_id` (`cours_supp_id`),
  ADD KEY `eleve_id` (`eleve_id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `qr_token` (`qr_token`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `cours_supplementaires`
--
ALTER TABLE `cours_supplementaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `emploi_du_temps`
--
ALTER TABLE `emploi_du_temps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `enseignants_classes`
--
ALTER TABLE `enseignants_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `inscriptions`
--
ALTER TABLE `inscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `matieres`
--
ALTER TABLE `matieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `parents_eleves`
--
ALTER TABLE `parents_eleves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `presences`
--
ALTER TABLE `presences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `presences_supplementaires`
--
ALTER TABLE `presences_supplementaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `cours_supplementaires`
--
ALTER TABLE `cours_supplementaires`
  ADD CONSTRAINT `cours_supplementaires_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cours_supplementaires_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cours_supplementaires_ibfk_3` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`);

--
-- Contraintes pour la table `emploi_du_temps`
--
ALTER TABLE `emploi_du_temps`
  ADD CONSTRAINT `emploi_du_temps_ibfk_1` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emploi_du_temps_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`),
  ADD CONSTRAINT `emploi_du_temps_ibfk_3` FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `enseignants_classes`
--
ALTER TABLE `enseignants_classes`
  ADD CONSTRAINT `enseignants_classes_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enseignants_classes_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enseignants_classes_ibfk_3` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`);

--
-- Contraintes pour la table `inscriptions`
--
ALTER TABLE `inscriptions`
  ADD CONSTRAINT `inscriptions_ibfk_1` FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscriptions_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `parents_eleves`
--
ALTER TABLE `parents_eleves`
  ADD CONSTRAINT `parents_eleves_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parents_eleves_ibfk_2` FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `parents_enfants`
--
ALTER TABLE `parents_enfants`
  ADD CONSTRAINT `parents_enfants_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parents_enfants_ibfk_2` FOREIGN KEY (`enfant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `presences`
--
ALTER TABLE `presences`
  ADD CONSTRAINT `presences_ibfk_1` FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presences_ibfk_2` FOREIGN KEY (`emploi_du_temps_id`) REFERENCES `emploi_du_temps` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presences_ibfk_3` FOREIGN KEY (`enregistre_par`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `presences_supplementaires`
--
ALTER TABLE `presences_supplementaires`
  ADD CONSTRAINT `presences_supplementaires_ibfk_1` FOREIGN KEY (`cours_supp_id`) REFERENCES `cours_supplementaires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presences_supplementaires_ibfk_2` FOREIGN KEY (`eleve_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
