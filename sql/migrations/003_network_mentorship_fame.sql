-- 003: Alumni Connect (connection requests), Mentorship Hub and Hall of Fame.

CREATE TABLE IF NOT EXISTS hall_of_fame (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    title VARCHAR(200),
    category VARCHAR(60) NOT NULL DEFAULT 'Distinguished professional',
    graduation_year SMALLINT UNSIGNED NULL,
    programme VARCHAR(200),
    citation TEXT,
    photo VARCHAR(200),
    inducted_year SMALLINT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT NOT NULL DEFAULT 100,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY k_fame (is_public, category, sort_order)
);

CREATE TABLE IF NOT EXISTS mentorship_profiles (
    user_id INT UNSIGNED PRIMARY KEY,
    role ENUM('mentor','mentee','both') NOT NULL,
    areas VARCHAR(300) NOT NULL,
    about VARCHAR(600),
    availability VARCHAR(200),
    status ENUM('open','paused','removed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY k_mentor_open (status, role)
);

CREATE TABLE IF NOT EXISTS mentorship_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mentee_id INT UNSIGNED NOT NULL,
    mentor_id INT UNSIGNED NOT NULL,
    message VARCHAR(800),
    status ENUM('pending','accepted','declined','ended') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    UNIQUE KEY u_pair (mentee_id, mentor_id),
    KEY k_mentor (mentor_id, status)
);

CREATE TABLE IF NOT EXISTS connections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id INT UNSIGNED NOT NULL,
    addressee_id INT UNSIGNED NOT NULL,
    message VARCHAR(400),
    status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    UNIQUE KEY u_conn (requester_id, addressee_id),
    KEY k_conn_to (addressee_id, status)
);

INSERT INTO menu_items (label, url, parent_id, sort_order, is_visible, new_tab)
SELECT 'Hall of Fame', 'hall-of-fame.php', NULL, 85, 1, 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url = 'hall-of-fame.php');
