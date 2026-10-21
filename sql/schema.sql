CREATE DATABASE IF NOT EXISTS guco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE guco;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('member','admin') NOT NULL DEFAULT 'member',
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE member_profiles (
    user_id INT UNSIGNED PRIMARY KEY,
    phone VARCHAR(40),
    faculty VARCHAR(150),
    course VARCHAR(150),
    graduation_year SMALLINT,
    occupation VARCHAR(150),
    location VARCHAR(150),
    bio TEXT,
    show_in_directory TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    body TEXT NOT NULL,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    location VARCHAR(200),
    starts_at DATETIME NOT NULL
);

CREATE TABLE gallery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    caption VARCHAR(200),
    filename VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE donation_pledges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    purpose VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(200),
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
