-- ============================================
-- BASE DE DONNÉES NATIORA RADIO 98.2
-- ============================================

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS natiora_radio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Utiliser la base de données
USE natiora_radio;

-- ============================================

-- TABLE USERS
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    bio TEXT,
    avatar VARCHAR(500),
    phone VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE EMISSIONS
CREATE TABLE IF NOT EXISTS emissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    day ENUM('lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche') NOT NULL,
    time TIME,
    title VARCHAR(255) NOT NULL,
    presenter VARCHAR(255),
    duration VARCHAR(50),
    level VARCHAR(50),
    category VARCHAR(100),
    src VARCHAR(500),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_day (day),
    INDEX idx_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE PLAYLISTS
CREATE TABLE IF NOT EXISTS playlists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    cover VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE PLAYLIST_SONGS (relation many-to-many)
CREATE TABLE IF NOT EXISTS playlist_songs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    playlist_id INT NOT NULL,
    song_title VARCHAR(255) NOT NULL,
    position INT,
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    INDEX idx_playlist (playlist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE USER_ACTIONS (historique des actions)
CREATE TABLE IF NOT EXISTS user_actions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERTION DES DONNÉES UTILISATEURS
-- ============================================

DELETE FROM users;
ALTER TABLE users AUTO_INCREMENT = 1;

INSERT INTO users (id, email, password, name, bio, avatar, phone, role, created_at) VALUES
(1, 'jean@example.com', '$2y$10$H4vIBQdVVYpzPFuKqUoEMeX1h5J0bVzS5O3LZ8aQ6sNmJpxW2YTUC', 'Jean Dupont', 'Passionné de musique et de radio', 'https://ui-avatars.com/api/?name=Jean+Dupont&background=667eea', '+261 32 123 45 67', 'user', '2026-02-18 10:00:00'),
(2, 'marie@example.com', '$2y$10$e1tJ2K3L4M5N6O7P8Q9R0S1T2U3V4W5X6Y7Z8A9B0C1D2E3F4G5', 'Marie Martin', 'Animatrice radio expérimentée', 'https://ui-avatars.com/api/?name=Marie+Martin&background=764ba2', '+261 34 567 89 01', 'user', '2026-02-17 14:30:00'),
(3, 'admin@natiora.com', '$2y$10$Czth12/4ppDRxZwIt5PfI.XwOh9aYa8qZwNl/IYgWGsJUxH2NqqLO', 'Admin Natiora', 'Administrateur système', 'https://ui-avatars.com/api/?name=Admin&background=f5576c', '', 'admin', '2026-02-18 08:00:00'),
(4, 'rabearisoafaniry3@gmail.com', '$2y$10$QJ7Sc6Bn4LTFzzS9SOWpougam9Zc7SV6ZCEzqElIBkOEiUFF70h72', 'faniry', '', 'https://ui-avatars.com/api/?name=faniry&background=random', '', 'user', '2026-02-19 06:12:37');

-- ============================================
-- MOTS DE PASSE DES UTILISATEURS (clair)
-- ============================================
-- jean@example.com : password123
-- marie@example.com : password456
-- admin@natiora.com : radio2026
-- rabearisoafaniry3@gmail.com : (hasché depuis le système)

-- ============================================
-- INSERTION DES DONNÉES D'ÉMISSIONS
-- ============================================

DELETE FROM emissions;
ALTER TABLE emissions AUTO_INCREMENT = 1;

INSERT INTO emissions (day, time, title, presenter, duration, level, category, src, description, created_at) VALUES
('lundi', '08:00:00', 'Concevoir une affiche', 'Tuto Boy', '45 min', 'Intermédiaire', 'Design Graphique', '/public/assets/videos/Tuto Boy  Comment concevoir une affiche sur Photoshop..mp4', 'Atelier complet sur la composition d\'affiche et typographie.', '2026-02-18 08:00:00'),
('lundi', '09:00:00', 'Montage Flyer Professionnel', 'Tuto Boy', '35 min', 'Débutant', 'Design Graphique', '/public/assets/videos/TUTO BOY COMMENT FAIRE UN MONTAGE PHOTO SUR PHOTOSHOP  [flyer].mp4', 'Techniques rapides et efficaces pour créer un flyer promotionnel captivant.', '2026-02-18 09:00:00'),
('mardi', '10:00:00', 'Dégradé sur texte Avancé', 'Tuto Boy', '28 min', 'Avancé', 'Effets Visuels', '/public/assets/videos/[Tuto Boy] Comment faire un dégradé sur un texte dans Photoshop..mp4', 'Effets de texte modernes, color grading et techniques de dégradé pour résultats époustouflants.', '2026-02-18 10:00:00'),
('mardi', '11:00:00', 'Effet de lumière Réaliste', 'Tuto Boy', '32 min', 'Intermédiaire', 'Retouche Photo', '/public/assets/videos/[Tuto boy] Comment faire un effet de lumière sur une image sur Photoshop.mp4', 'Astuces professionnelles pour simuler des éclairages réalistes, ombres et reflets lumineux.', '2026-02-18 11:00:00'),
('mercredi', '09:30:00', 'Concevoir une affiche - Rediffusion', 'Tuto Boy', '45 min', 'Intermédiaire', 'Design Graphique', '/public/assets/videos/Tuto Boy  Comment concevoir une affiche sur Photoshop..mp4', 'Rediffusion : composition, hiérarchie visuelle et principes de design graphique modernes.', '2026-02-18 09:30:00'),
('jeudi', '14:00:00', 'Montage Flyer - Spécial Événement', 'Tuto Boy', '40 min', 'Débutant', 'Design Graphique', '/public/assets/videos/TUTO BOY COMMENT FAIRE UN MONTAGE PHOTO SUR PHOTOSHOP  [flyer].mp4', 'Tutoriel pas-à-pas pour créer des flyers promotionnels pour vos événements.', '2026-02-18 14:00:00'),
('vendredi', '15:30:00', 'Effets de Lumière - Créatifs', 'Tuto Boy', '38 min', 'Intermédiaire', 'Retouche Photo', '/public/assets/videos/[Tuto boy] Comment faire un effet de lumière sur une image sur Photoshop.mp4', 'Effets créatifs avancés pour vos images, ajout de lumière naturelle et synthétique.', '2026-02-18 15:30:00'),
('samedi', '11:00:00', 'Best Of Design Avancé', 'Tuto Boy', '50 min', 'Avancé', 'Spécial', '/public/assets/videos/[Tuto Boy] Comment faire un dégradé sur un texte dans Photoshop..mp4', 'Compilation de techniques avancées pour utilisateurs confirmés.', '2026-02-18 11:00:00'),
('dimanche', '19:00:00', 'Best Of - Compilation Spéciale', 'Tuto Boy', '60 min', 'Tous niveaux', 'Spécial', '/public/assets/videos/Tuto Boy  Comment concevoir une affiche sur Photoshop..mp4', 'Compilation des meilleurs extraits et tutoriels de la semaine avec conseils bonus et Q&A.', '2026-02-18 19:00:00');

-- ============================================
-- INSERTION DES DONNÉES DE PLAYLISTS
-- ============================================

DELETE FROM playlist_songs;
DELETE FROM playlists;
ALTER TABLE playlists AUTO_INCREMENT = 1;
ALTER TABLE playlist_songs AUTO_INCREMENT = 1;

INSERT INTO playlists (title, description, cover, created_at) VALUES
('Best Of Design 2025', 'Les meilleurs tutoriels design de l\'année 2025', 'https://ui-avatars.com/api/?name=Design+2025&background=667eea&size=200', '2026-02-18 10:00:00'),
('Tutoriels Débutants', 'Parfait pour commencer avec Photoshop et design graphique', 'https://ui-avatars.com/api/?name=Debutant&background=f5576c&size=200', '2026-02-18 11:00:00'),
('Effets Visuels Avancés', 'Techniques avancées pour créer des effets époustouflants', 'https://ui-avatars.com/api/?name=Avance&background=764ba2&size=200', '2026-02-18 12:00:00'),
('Tutoriels Rediffusions', 'Émissions rediffusées et spéciales de la semaine', 'https://ui-avatars.com/api/?name=Rediffusion&background=ff6b6b&size=200', '2026-02-18 13:00:00');

-- ============================================
-- INSERTION DES CHANSONS DANS LES PLAYLISTS
-- ============================================

INSERT INTO playlist_songs (playlist_id, song_title, position) VALUES
(1, 'Concevoir une affiche', 1),
(1, 'Montage Flyer Professionnel', 2),
(1, 'Dégradé sur texte Avancé', 3),
(1, 'Best Of Design Avancé', 4),
(1, 'Best Of - Compilation Spéciale', 5),
(2, 'Montage Flyer Professionnel', 1),
(2, 'Concevoir une affiche', 2),
(2, 'Montage Flyer - Spécial Événement', 3),
(3, 'Dégradé sur texte Avancé', 1),
(3, 'Effet de lumière Réaliste', 2),
(3, 'Effets de Lumière - Créatifs', 3),
(3, 'Best Of Design Avancé', 4),
(4, 'Concevoir une affiche - Rediffusion', 1),
(4, 'Montage Flyer - Spécial Événement', 2),
(4, 'Montage Flyer Professionnel', 3);

-- ============================================
-- FIN DU SCRIPT SQL
-- ============================================
