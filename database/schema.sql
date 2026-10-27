CREATE DATABASE IF NOT EXISTS fet_intranet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fet_intranet;

CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, identification VARCHAR(30) NOT NULL UNIQUE,
 full_name VARCHAR(150) NOT NULL, email VARCHAR(150) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','manager','leader','student','company') NOT NULL DEFAULT 'student', program VARCHAR(150) NULL,
 status ENUM('active','inactive') NOT NULL DEFAULT 'active', failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
 locked_until DATETIME NULL, last_login_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS opportunities (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, organization VARCHAR(180) NOT NULL,
 type ENUM('practica','empleo','formacion') NOT NULL, description TEXT NOT NULL, requirements TEXT NULL,
 location VARCHAR(150) NULL, modality ENUM('presencial','hibrida','remota') NOT NULL DEFAULT 'presencial',
 closes_on DATE NOT NULL, status ENUM('draft','review','published','closed') NOT NULL DEFAULT 'draft',
 verified_at DATETIME NULL, created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_opportunity_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS applications (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, opportunity_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
 motivation TEXT NOT NULL, status ENUM('submitted','review','accepted','rejected','withdrawn') NOT NULL DEFAULT 'submitted',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_application (opportunity_id,user_id),
 CONSTRAINT fk_application_opportunity FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
 CONSTRAINT fk_application_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projects (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(180) NOT NULL, summary TEXT NOT NULL,
 ods TINYINT UNSIGNED NOT NULL DEFAULT 9, leader_id INT UNSIGNED NOT NULL,
 status ENUM('planned','active','paused','completed') NOT NULL DEFAULT 'planned', starts_on DATE NULL, ends_on DATE NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_project_leader FOREIGN KEY (leader_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_members (
 project_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, responsibility VARCHAR(180) NULL,
 PRIMARY KEY (project_id,user_id),
 CONSTRAINT fk_member_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
 CONSTRAINT fk_member_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS milestones (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, title VARCHAR(180) NOT NULL,
 due_on DATE NOT NULL, status ENUM('pending','in_progress','completed','blocked') NOT NULL DEFAULT 'pending',
 evidence TEXT NULL, evidence_file VARCHAR(60) NULL, evidence_name VARCHAR(180) NULL,
 completed_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_milestone_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS partners (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(180) NOT NULL, sector VARCHAR(120) NULL,
 contact_name VARCHAR(150) NULL, contact_email VARCHAR(150) NULL,
 status ENUM('prospect','active','inactive') NOT NULL DEFAULT 'prospect', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS agreements (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, partner_id INT UNSIGNED NOT NULL, title VARCHAR(180) NOT NULL,
 objective TEXT NOT NULL, responsible_id INT UNSIGNED NOT NULL, starts_on DATE NULL, ends_on DATE NULL,
 status ENUM('proposed','active','expired','closed') NOT NULL DEFAULT 'proposed', evidence_reference VARCHAR(255) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_agreement_partner FOREIGN KEY (partner_id) REFERENCES partners(id),
 CONSTRAINT fk_agreement_responsible FOREIGN KEY (responsible_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activities (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, agreement_id INT UNSIGNED NOT NULL, title VARCHAR(180) NOT NULL,
 occurs_on DATE NOT NULL, status ENUM('planned','completed','cancelled') NOT NULL DEFAULT 'planned',
 result_summary TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_activity_agreement FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS minutes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, number VARCHAR(30) NOT NULL UNIQUE, title VARCHAR(180) NOT NULL,
 meeting_on DATE NOT NULL, modality VARCHAR(150) NULL, starts_at TIME NULL, ends_at TIME NULL,
 attendees TEXT NOT NULL, objectives TEXT NULL, summary TEXT NOT NULL, development TEXT NULL, conclusion TEXT NULL,
 work_model TEXT NULL, next_agenda TEXT NULL, observations TEXT NULL,
 status ENUM('draft','approved') NOT NULL DEFAULT 'draft', created_by INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_minutes_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS commitments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, minute_id INT UNSIGNED NOT NULL, description TEXT NOT NULL,
 responsible_id INT UNSIGNED NOT NULL, due_on DATE NOT NULL, expected_evidence VARCHAR(255) NULL,
 status ENUM('pending','in_progress','completed','overdue') NOT NULL DEFAULT 'pending',
 evidence TEXT NULL, evidence_file VARCHAR(60) NULL, evidence_name VARCHAR(180) NULL, completed_at DATETIME NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_commitment_minute FOREIGN KEY (minute_id) REFERENCES minutes(id) ON DELETE CASCADE,
 CONSTRAINT fk_commitment_responsible FOREIGN KEY (responsible_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS findings (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, module VARCHAR(80) NOT NULL,
 description TEXT NULL, priority ENUM('alta','media','baja') NOT NULL DEFAULT 'media',
 status ENUM('open','in_progress','fixed','verified') NOT NULL DEFAULT 'open',
 reported_by INT UNSIGNED NULL, assigned_to INT UNSIGNED NULL, resolution TEXT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_finding_reporter FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_finding_assignee FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, title VARCHAR(180) NOT NULL,
 body VARCHAR(255) NOT NULL, link VARCHAR(255) NULL, read_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NULL, action VARCHAR(80) NOT NULL,
 entity_type VARCHAR(80) NOT NULL, entity_id INT UNSIGNED NULL, details VARCHAR(255) NULL,
 ip_address VARCHAR(45) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_audit_created (created_at),
 CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS remember_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL,
 selector CHAR(18) NOT NULL UNIQUE, validator_hash CHAR(64) NOT NULL, expires_at DATETIME NOT NULL,
 CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS internships (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, application_id INT UNSIGNED NULL UNIQUE, student_id INT UNSIGNED NOT NULL,
 company_id INT UNSIGNED NULL, teacher_id INT UNSIGNED NULL, title VARCHAR(180) NOT NULL, organization VARCHAR(180) NOT NULL,
 starts_on DATE NULL, ends_on DATE NULL, status ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
 company_rating TINYINT UNSIGNED NULL, company_evaluation TEXT NULL, company_evaluated_at DATETIME NULL,
 final_result ENUM('aprobada','reprobada') NULL, final_justification TEXT NULL, final_by INT UNSIGNED NULL, final_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_internship_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL,
 CONSTRAINT fk_internship_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_internship_company FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_internship_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS internship_documents (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, internship_id INT UNSIGNED NOT NULL,
 doc_type ENUM('plan','parcial','final','evaluacion','certificado','otro') NOT NULL DEFAULT 'otro', title VARCHAR(180) NOT NULL,
 file VARCHAR(60) NOT NULL, file_name VARCHAR(180) NOT NULL,
 status ENUM('submitted','endorsed','approved','returned') NOT NULL DEFAULT 'submitted',
 grade DECIMAL(2,1) NULL, graded_by INT UNSIGNED NULL, graded_at DATETIME NULL,
 endorsed_at DATETIME NULL, approved_at DATETIME NULL, uploaded_by INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_idoc_internship FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE,
 CONSTRAINT fk_idoc_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS document_comments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, document_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
 decision VARCHAR(20) NULL, grade DECIMAL(2,1) NULL, body TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_dcomment_document FOREIGN KEY (document_id) REFERENCES internship_documents(id) ON DELETE CASCADE,
 CONSTRAINT fk_dcomment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
