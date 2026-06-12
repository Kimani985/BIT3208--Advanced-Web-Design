CREATE DATABASE IF NOT EXISTS campus_event_board
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE campus_event_board;

DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS rsvps;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(80) NOT NULL,
    location VARCHAR(160) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    capacity INT UNSIGNED NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_created_by
        FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE rsvps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    status ENUM('going', 'cancelled') NOT NULL DEFAULT 'going',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_event_rsvp (user_id, event_id),
    CONSTRAINT fk_rsvps_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_rsvps_event
        FOREIGN KEY (event_id)
        REFERENCES events(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rsvp_id INT UNSIGNED NOT NULL UNIQUE,
    checked_in_by INT UNSIGNED DEFAULT NULL,
    checked_in_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_rsvp
        FOREIGN KEY (rsvp_id)
        REFERENCES rsvps(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_attendance_checked_in_by
        FOREIGN KEY (checked_in_by)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_events_featured ON events (is_featured);
CREATE INDEX idx_events_date ON events (event_date);
CREATE INDEX idx_events_category ON events (category);
CREATE INDEX idx_rsvps_status ON rsvps (status);

INSERT INTO users (full_name, email, password, role)
VALUES
    (
        'Campus Admin',
        'admin@campus.test',
        '$2y$10$XqVqVz2CjGJp9brqJ1/33eWJ6EovfATd1wAJVuL7Y4FAWFrfYFbyW',
        'admin'
    );

INSERT INTO events (
    title,
    description,
    category,
    location,
    event_date,
    event_time,
    capacity,
    image,
    is_featured,
    created_by
)
VALUES
    (
        'Freshers Welcome Night',
        'A lively welcome event for new students featuring music, games, student clubs, and campus support teams.',
        'Social',
        'Main Auditorium',
        '2026-06-12',
        '18:00:00',
        250,
        NULL,
        1,
        1
    ),
    (
        'Tech Innovation Workshop',
        'A hands-on workshop where students learn practical prototyping, product thinking, and modern web development basics.',
        'Workshop',
        'Computer Lab 2',
        '2026-06-18',
        '10:00:00',
        60,
        NULL,
        1,
        1
    ),
    (
        'Interfaculty Football Finals',
        'Cheer for your faculty in the annual football final and enjoy food stalls, music, and community activities.',
        'Sports',
        'Campus Sports Ground',
        '2026-06-25',
        '15:30:00',
        500,
        NULL,
        0,
        1
    );