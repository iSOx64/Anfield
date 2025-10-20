CREATE DATABASE IF NOT EXISTS foot_fields
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE foot_fields;

CREATE TABLE utilisateur (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(80) NOT NULL,
  prenom VARCHAR(80) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  telephone VARCHAR(30),
  adresse VARCHAR(255),
  password_hash VARCHAR(255) NOT NULL,
  avatar_path VARCHAR(255),
  verification_code VARCHAR(12),
  verification_expires_at DATETIME,
  email_verified_at DATETIME,
  remember_token VARCHAR(255),
  remember_expires_at DATETIME,
  role ENUM('client','admin') DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE terrain (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(80) NOT NULL,
  taille ENUM('mini','moyen','grand') NOT NULL,
  type ENUM('gazon_naturel','gazon_artificiel','dur') NOT NULL,
  image_path VARCHAR(255),
  disponible TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE reservation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  terrain_id INT NOT NULL,
  utilisateur_id INT NOT NULL,
  date_reservation DATE NOT NULL,
  creneau_horaire TIME NOT NULL,
  demande TEXT,
  type_evenement VARCHAR(60),
  niveau ENUM('loisir','intermediaire','competitif') DEFAULT 'loisir',
  participants INT DEFAULT NULL,
  ballon TINYINT(1) DEFAULT 0,
  arbitre TINYINT(1) DEFAULT 0,
  maillot TINYINT(1) DEFAULT 0,
  douche TINYINT(1) DEFAULT 0,
  coach TINYINT(1) DEFAULT 0,
  photographe TINYINT(1) DEFAULT 0,
  traiteur TINYINT(1) DEFAULT 0,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  statut ENUM('confirmee','annulee','terminee') DEFAULT 'confirmee',
  CONSTRAINT fk_res_terrain FOREIGN KEY (terrain_id) REFERENCES terrain(id),
  CONSTRAINT fk_res_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id),
  UNIQUE KEY uq_terrain_date_creneau (terrain_id, date_reservation, creneau_horaire)
) ENGINE=InnoDB;

CREATE TABLE prix (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categorie ENUM('terrain','service') NOT NULL,
  reference VARCHAR(80) NOT NULL,
  description VARCHAR(255),
  prix DECIMAL(10,2) NOT NULL,
  UNIQUE KEY uq_cat_ref (categorie, reference)
) ENGINE=InnoDB;

CREATE TABLE facture (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reservation_id INT NOT NULL,
  montant_terrain DECIMAL(10,2) NOT NULL,
  montant_service DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fact_res FOREIGN KEY (reservation_id) REFERENCES reservation(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tournoi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(120) NOT NULL,
  format ENUM('8','16') NOT NULL,
  organisateur_id INT NOT NULL,
  categorie VARCHAR(60),
  niveau VARCHAR(40),
  date_debut DATE,
  date_fin DATE,
  lieu VARCHAR(120),
  frais_inscription DECIMAL(10,2),
  recompense VARCHAR(160),
  description TEXT,
  contact_email VARCHAR(160),
  contact_phone VARCHAR(40),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organisateur_id) REFERENCES utilisateur(id)
) ENGINE=InnoDB;

CREATE TABLE equipe (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournoi_id INT NOT NULL,
  nom VARCHAR(120) NOT NULL,
  FOREIGN KEY (tournoi_id) REFERENCES tournoi(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE match_tournoi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournoi_id INT NOT NULL,
  round INT NOT NULL,
  equipe_a_id INT,
  equipe_b_id INT,
  terrain_id INT,
  date_match DATE,
  creneau_horaire TIME,
  reservation_id INT,
  FOREIGN KEY (tournoi_id) REFERENCES tournoi(id) ON DELETE CASCADE,
  FOREIGN KEY (terrain_id) REFERENCES terrain(id),
  FOREIGN KEY (reservation_id) REFERENCES reservation(id)
) ENGINE=InnoDB;

-- Seed administrator account (default password: Admin123!)
INSERT INTO utilisateur (
  nom,
  prenom,
  email,
  telephone,
  adresse,
  password_hash,
  avatar_path,
  verification_code,
  verification_expires_at,
  email_verified_at,
  remember_token,
  remember_expires_at,
  role
) VALUES (
  'Administrateur',
  'Principal',
  'admin@footfields.com',
  '+212600000000',
  'Casablanca',
  '$2y$12$kvu7ioaQatfafIHXh7KnH.aebL4V0QGIuDVquBphEG3Qbu7LC3Ame',
  NULL,
  NULL,
  NULL,
  NOW(),
  NULL,
  NULL,
  'admin'
);

INSERT INTO prix (categorie, reference, description, prix) VALUES
('terrain','mini','Location terrain mini (1h)', 350),
('terrain','moyen','Location terrain moyen (1h)', 450),
('terrain','grand','Location grand terrain (1h)', 600),
('service','ballon','Mise a disposition ballon', 25),
('service','arbitre','Arbitre officiel', 150),
('service','maillot','Kit maillots', 120),
('service','douche','Acces douche', 40),
('service','coach','Coach dedie', 320),
('service','photographe','Photographe terrain', 450),
('service','traiteur','Service traiteur', 900);
