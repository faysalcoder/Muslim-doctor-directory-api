-- ============================================================
-- NMP API Database Setup (Extended)
-- DB name: nomp
-- ============================================================

CREATE DATABASE IF NOT EXISTS nomp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nomp;

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
    academic_affiliation       VARCHAR(255)  DEFAULT NULL,
    medical_school_affiliation VARCHAR(255)  DEFAULT NULL,
    specialty                  VARCHAR(150)  DEFAULT NULL,
    subspecialty               VARCHAR(200)  DEFAULT NULL,
    graduation_degree          VARCHAR(100)  DEFAULT NULL,
    graduation_year            SMALLINT      DEFAULT NULL,
    location                   VARCHAR(255)  DEFAULT NULL,
    practice                   VARCHAR(255)  DEFAULT NULL,
    address                    TEXT          DEFAULT NULL,
    hospital_affiliations      TEXT          DEFAULT NULL,
    phone                      VARCHAR(50)   DEFAULT NULL,
    email                      VARCHAR(150)  DEFAULT NULL,
    password_hash              VARCHAR(255)  DEFAULT NULL,
    account_type               ENUM('listing','member') NOT NULL DEFAULT 'listing',
    profile_visibility         ENUM('public','private') NOT NULL DEFAULT 'public',
    bio                        LONGTEXT      NULL,
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

CREATE TABLE IF NOT EXISTS doctor_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id  INT           NOT NULL,
    image      VARCHAR(255)  NOT NULL,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctor_images_doctor
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS site_settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT         NULL,
    updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS forum_posts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id   INT NOT NULL,
    type        ENUM('image','case_report','question','discussion') NOT NULL DEFAULT 'question',
    title       VARCHAR(255) NOT NULL,
    content     LONGTEXT NOT NULL,
    image       VARCHAR(255) DEFAULT NULL,
    tags        VARCHAR(255) DEFAULT NULL,
    status      ENUM('published','pending','hidden','deleted') NOT NULL DEFAULT 'published',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_forum_posts_doctor_id (doctor_id),
    INDEX idx_forum_posts_type (type),
    INDEX idx_forum_posts_status (status),
    CONSTRAINT fk_forum_posts_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS forum_comments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL,
    doctor_id   INT NOT NULL,
    parent_id   INT DEFAULT NULL,
    comment     LONGTEXT NOT NULL,
    status      ENUM('published','hidden','deleted') NOT NULL DEFAULT 'published',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_forum_comments_post_id (post_id),
    INDEX idx_forum_comments_doctor_id (doctor_id),
    CONSTRAINT fk_forum_comments_post FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_forum_comments_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    CONSTRAINT fk_forum_comments_parent FOREIGN KEY (parent_id) REFERENCES forum_comments(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS job_posts (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id         INT NOT NULL,
    post_name         VARCHAR(255) NOT NULL,
    job_location      VARCHAR(255) NOT NULL,
    hospital_name     VARCHAR(255) NOT NULL,
    vacancy_available INT NOT NULL,
    description       LONGTEXT NOT NULL,
    status            ENUM('open','closed','hidden') NOT NULL DEFAULT 'open',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_job_posts_doctor_id (doctor_id),
    INDEX idx_job_posts_status (status),
    CONSTRAINT fk_job_posts_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS doctor_availability (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id          INT NOT NULL UNIQUE,
    degrees            VARCHAR(255) DEFAULT NULL,
    year_of_experience VARCHAR(50) DEFAULT NULL,
    specialty          VARCHAR(150) DEFAULT NULL,
    subspecialty       VARCHAR(150) DEFAULT NULL,
    location           VARCHAR(255) DEFAULT NULL,
    summary            TEXT NULL,
    status             ENUM('open','hidden') NOT NULL DEFAULT 'open',
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctor_availability_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

INSERT INTO admins (name, email, password, role, status)
VALUES (
    'System Admin',
    'admin@nomp.com',
    '$2y$12$MC7cWqE5/Qv32C52xI/dmORxHgaBipdcphT4I./vuEQOnQUssDrKy',
    'super_admin',
    'active'
) ON DUPLICATE KEY UPDATE id = id;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('site_name',   'Network of Muslim Physicians'),
    ('admin_email', 'admin@nomp.com')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
