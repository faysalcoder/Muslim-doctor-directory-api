<?php
declare(strict_types=1);

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['table' => $table, 'column' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
    );
    $stmt->execute(['table' => $table]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function ensure_index(PDO $pdo, string $table, string $indexName, string $definition): void
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index_name'
    );
    $stmt->execute(['table' => $table, 'index_name' => $indexName]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$indexName` ($definition)");
    }
}

function ensure_schema(PDO $pdo): void
{
    // Base tables (safe if they already exist)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(120)  NOT NULL,
            email      VARCHAR(150)  NOT NULL UNIQUE,
            password   VARCHAR(255)  NOT NULL,
            role       VARCHAR(50)   NOT NULL DEFAULT 'super_admin',
            status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
            avatar     VARCHAR(255)  DEFAULT NULL,
            created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS doctors (
            id                         INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id                  VARCHAR(30)   NULL,
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Ensure all required doctor columns exist for older installs.
    ensure_column($pdo, 'doctors', 'doctor_id', 'VARCHAR(30) NULL UNIQUE AFTER id');
    ensure_column($pdo, 'doctors', 'title', 'VARCHAR(150) DEFAULT NULL AFTER name');
    ensure_column($pdo, 'doctors', 'academic_title', 'VARCHAR(200) DEFAULT NULL AFTER title');
    ensure_column($pdo, 'doctors', 'academic_affiliation', 'VARCHAR(255) DEFAULT NULL AFTER academic_title');
    ensure_column($pdo, 'doctors', 'medical_school_affiliation', 'VARCHAR(255) DEFAULT NULL AFTER academic_affiliation');
    ensure_column($pdo, 'doctors', 'specialty', 'VARCHAR(150) DEFAULT NULL AFTER medical_school_affiliation');
    ensure_column($pdo, 'doctors', 'subspecialty', 'VARCHAR(200) DEFAULT NULL AFTER specialty');
    ensure_column($pdo, 'doctors', 'graduation_degree', 'VARCHAR(100) DEFAULT NULL AFTER subspecialty');
    ensure_column($pdo, 'doctors', 'graduation_year', 'SMALLINT DEFAULT NULL AFTER graduation_degree');
    ensure_column($pdo, 'doctors', 'location', 'VARCHAR(255) DEFAULT NULL AFTER graduation_year');
    ensure_column($pdo, 'doctors', 'practice', 'VARCHAR(255) DEFAULT NULL AFTER location');
    ensure_column($pdo, 'doctors', 'address', 'TEXT NULL AFTER practice');
    ensure_column($pdo, 'doctors', 'hospital_affiliations', 'TEXT NULL AFTER address');
    ensure_column($pdo, 'doctors', 'phone', 'VARCHAR(50) DEFAULT NULL AFTER hospital_affiliations');
    ensure_column($pdo, 'doctors', 'email', 'VARCHAR(150) DEFAULT NULL AFTER phone');
    ensure_column($pdo, 'doctors', 'password_hash', 'VARCHAR(255) DEFAULT NULL AFTER email');
    ensure_column($pdo, 'doctors', 'account_type', "ENUM('listing','member') NOT NULL DEFAULT 'listing' AFTER password_hash");
    ensure_column($pdo, 'doctors', 'profile_visibility', "ENUM('public','private') NOT NULL DEFAULT 'public' AFTER account_type");
    ensure_column($pdo, 'doctors', 'bio', 'LONGTEXT NULL AFTER profile_visibility');
    ensure_column($pdo, 'doctors', 'education', 'TEXT NULL AFTER bio');
    ensure_column($pdo, 'doctors', 'experience', 'VARCHAR(255) DEFAULT NULL AFTER education');
    ensure_column($pdo, 'doctors', 'gender', 'VARCHAR(30) DEFAULT NULL AFTER experience');
    ensure_column($pdo, 'doctors', 'languages', 'VARCHAR(255) DEFAULT NULL AFTER gender');
    ensure_column($pdo, 'doctors', 'awards', 'TEXT NULL AFTER languages');
    ensure_column($pdo, 'doctors', 'accepting_patients', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER awards');
    ensure_column($pdo, 'doctors', 'profile_image', 'VARCHAR(255) DEFAULT NULL AFTER accepting_patients');
    ensure_column($pdo, 'doctors', 'status', "ENUM('verified','pending','inactive') NOT NULL DEFAULT 'pending' AFTER profile_image");
    ensure_column($pdo, 'doctors', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status');
    ensure_column($pdo, 'doctors', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');

    // Backfill doctor_id for pre-migration rows.
    $pdo->exec("UPDATE doctors SET doctor_id = CONCAT('MNP-', LPAD(id, 6, '0')) WHERE doctor_id IS NULL OR doctor_id = ''");

    // If doctor_id exists but lacks an index/unique constraint on older DBs, add a unique index carefully.
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index_name'
    );
    $stmt->execute(['table' => 'doctors', 'index_name' => 'uniq_doctors_doctor_id']);
    if ((int)$stmt->fetchColumn() === 0) {
        try {
            $pdo->exec('ALTER TABLE doctors ADD UNIQUE INDEX uniq_doctors_doctor_id (doctor_id)');
        } catch (Throwable $e) {
            // If duplicate data exists, keep going so the app can still run.
        }
    }

    // Core support tables for the new modules.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS doctor_images (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id  INT           NOT NULL,
            image      VARCHAR(255)  NOT NULL,
            created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_doctor_images_doctor
                FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            setting_key   VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT         NULL,
            updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Seed defaults if missing.
    $pdo->exec('INSERT IGNORE INTO admins (name, email, password, role, status) VALUES (\'System Admin\', \'admin@nomp.com\', \'$2y$12$MC7cWqE5/Qv32C52xI/dmORxHgaBipdcphT4I./vuEQOnQUssDrKy\', \'super_admin\', \'active\')');
    $pdo->exec("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('site_name', 'Network of Muslim Physicians'), ('admin_email', 'admin@nomp.com')");

    // Ensure upload directories exist.
    $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    foreach (['doctors', 'doctors/gallery', 'forum'] as $sub) {
        $dir = $baseDir . DIRECTORY_SEPARATOR . $sub;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
}
