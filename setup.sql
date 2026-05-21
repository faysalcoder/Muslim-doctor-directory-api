-- ============================================================
-- NMP API Database Setup
-- DB name: nomp
-- Admin login: admin@nomp.com / admin123
-- ============================================================

CREATE DATABASE IF NOT EXISTS nomp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nomp;

-- ── XAMPP users: run this as root in phpMyAdmin (Query tab) ───────────────
-- No extra MySQL user needed if you use the default root account.
-- If you created a custom MySQL user 'admin', grant it access:
--   GRANT ALL PRIVILEGES ON nomp.* TO 'admin'@'localhost' IDENTIFIED BY 'admin123';
--   FLUSH PRIVILEGES;
-- ─────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120)  NOT NULL,
    email      VARCHAR(150)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       VARCHAR(50)   NOT NULL DEFAULT 'super_admin',
    status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    avatar     VARCHAR(255)  DEFAULT NULL,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS doctors (
    id                         INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id                  VARCHAR(30)   NOT NULL UNIQUE,
    name                       VARCHAR(150)  NOT NULL,
    title                      VARCHAR(150)  DEFAULT NULL,
    academic_title             VARCHAR(200)  DEFAULT NULL,
    medical_school_affiliation VARCHAR(255)  DEFAULT NULL,
    specialty                  VARCHAR(150)  NOT NULL,
    subspecialty               VARCHAR(200)  DEFAULT NULL,
    graduation_degree          VARCHAR(100)  DEFAULT NULL,
    graduation_year            SMALLINT      DEFAULT NULL,
    location                   VARCHAR(255)  DEFAULT NULL,
    practice                   VARCHAR(255)  DEFAULT NULL,
    address                    TEXT          DEFAULT NULL,
    hospital_affiliations      TEXT          DEFAULT NULL,
    phone                      VARCHAR(50)   DEFAULT NULL,
    email                      VARCHAR(150)  DEFAULT NULL,
    bio                        LONGTEXT      DEFAULT NULL,
    education                  TEXT          DEFAULT NULL,
    experience                 VARCHAR(255)  DEFAULT NULL,
    gender                     VARCHAR(30)   DEFAULT NULL,
    languages                  VARCHAR(255)  DEFAULT NULL,
    awards                     TEXT          DEFAULT NULL,
    accepting_patients         TINYINT(1)    NOT NULL DEFAULT 0,
    profile_image              VARCHAR(255)  DEFAULT NULL,
    status                     ENUM('verified','pending','inactive') NOT NULL DEFAULT 'pending',
    created_at                 TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at                 TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Run this if the doctors table already exists (adds new columns safely)
ALTER TABLE doctors
    ADD COLUMN IF NOT EXISTS academic_title             VARCHAR(200) DEFAULT NULL AFTER title,
    ADD COLUMN IF NOT EXISTS medical_school_affiliation VARCHAR(255) DEFAULT NULL AFTER academic_title,
    ADD COLUMN IF NOT EXISTS subspecialty               VARCHAR(200) DEFAULT NULL AFTER specialty,
    ADD COLUMN IF NOT EXISTS graduation_degree          VARCHAR(100) DEFAULT NULL AFTER subspecialty,
    ADD COLUMN IF NOT EXISTS graduation_year            SMALLINT     DEFAULT NULL AFTER graduation_degree,
    ADD COLUMN IF NOT EXISTS hospital_affiliations      TEXT         DEFAULT NULL AFTER address,
    ADD COLUMN IF NOT EXISTS awards                     TEXT         DEFAULT NULL AFTER languages;

CREATE TABLE IF NOT EXISTS doctor_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id  INT           NOT NULL,
    image      VARCHAR(255)  NOT NULL,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctor_images_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Default admin account — password: admin123
INSERT INTO admins (name, email, password, role, status)
VALUES (
    'System Admin',
    'admin@nomp.com',
    '$2y$12$MC7cWqE5/Qv32C52xI/dmORxHgaBipdcphT4I./vuEQOnQUssDrKy',
    'super_admin',
    'active'
) ON DUPLICATE KEY UPDATE id = id;

-- Make existing doctors visible on public frontend (status='verified' required)
UPDATE doctors SET status = 'verified' WHERE status = 'pending';

-- ── Site Settings table (required for Settings page) ──────────────────────
CREATE TABLE IF NOT EXISTS site_settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT         DEFAULT NULL,
    updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default settings
INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('site_name',   'Network of Muslim Physicians'),
    ('admin_email', 'admin@nomp.com')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- ── Ensure all extended doctor columns exist (safe to re-run) ─────────────
ALTER TABLE doctors
    ADD COLUMN IF NOT EXISTS academic_title             VARCHAR(200) DEFAULT NULL AFTER title,
    ADD COLUMN IF NOT EXISTS medical_school_affiliation VARCHAR(255) DEFAULT NULL AFTER academic_title,
    ADD COLUMN IF NOT EXISTS subspecialty               VARCHAR(200) DEFAULT NULL AFTER specialty,
    ADD COLUMN IF NOT EXISTS graduation_degree          VARCHAR(100) DEFAULT NULL AFTER subspecialty,
    ADD COLUMN IF NOT EXISTS graduation_year            SMALLINT     DEFAULT NULL AFTER graduation_degree,
    ADD COLUMN IF NOT EXISTS hospital_affiliations      TEXT         DEFAULT NULL AFTER address,
    ADD COLUMN IF NOT EXISTS awards                     TEXT         DEFAULT NULL AFTER languages;
