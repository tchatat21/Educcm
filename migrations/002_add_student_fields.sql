-- Migration : Ajout des champs pour la carte scolaire et le QR Code
ALTER TABLE `utilisateurs` 
ADD COLUMN `photo` VARCHAR(255) DEFAULT 'default_avatar.png' AFTER `role`,
ADD COLUMN `telephone` VARCHAR(20) DEFAULT NULL AFTER `photo`,
ADD COLUMN `qr_token` VARCHAR(100) UNIQUE DEFAULT NULL AFTER `telephone`;
