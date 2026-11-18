-- 001: RBAC roles, donations and campaigns, careers, notifications, event tickets and capacity.
-- Idempotent: safe to run more than once. Applied by `php tools/migrate.php`.

ALTER TABLE users MODIFY role ENUM('member','super_admin','system_admin','secretariat','executive','membership_officer','finance_officer','communications','newsletter_editor','events_officer','career_officer','chapter_coordinator') NOT NULL DEFAULT 'member';

CREATE TABLE IF NOT EXISTS campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(60) NOT NULL DEFAULT 'General',
    summary VARCHAR(400),
    description TEXT,
    goal_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
    starts_on DATE NULL,
    ends_on DATE NULL,
    image VARCHAR(200),
    status ENUM('draft','active','closed') NOT NULL DEFAULT 'draft',
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY k_campaign_status (status, is_public)
);

CREATE TABLE IF NOT EXISTS donations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    donor_name VARCHAR(150) NOT NULL,
    donor_email VARCHAR(190) NOT NULL,
    donor_phone VARCHAR(40),
    amount BIGINT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'UGX',
    method ENUM('mtn_momo','airtel_money','bank','cash','card') NOT NULL,
    reference VARCHAR(120) NOT NULL,
    category VARCHAR(60) NOT NULL DEFAULT 'General',
    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
    recurring ENUM('none','monthly','quarterly','yearly') NOT NULL DEFAULT 'none',
    message VARCHAR(400),
    status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    receipt_no VARCHAR(30) NULL UNIQUE,
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    reject_reason VARCHAR(250),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY k_don_campaign (campaign_id, status),
    KEY k_don_user (user_id),
    KEY k_don_status (status, created_at),
    UNIQUE KEY u_don_ref (method, reference)
);

CREATE TABLE IF NOT EXISTS jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    kind ENUM('job','internship','scholarship','fellowship','volunteer','resource') NOT NULL DEFAULT 'job',
    organisation VARCHAR(200) NOT NULL,
    location VARCHAR(200),
    description TEXT,
    requirements TEXT,
    apply_url VARCHAR(400),
    apply_email VARCHAR(190),
    allow_applications TINYINT(1) NOT NULL DEFAULT 1,
    deadline DATE NULL,
    member_only TINYINT(1) NOT NULL DEFAULT 0,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    posted_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY k_jobs_list (is_public, kind, deadline)
);

CREATE TABLE IF NOT EXISTS job_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    cover_note TEXT,
    status ENUM('submitted','shortlisted','rejected','successful') NOT NULL DEFAULT 'submitted',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY u_app (job_id, user_id),
    KEY k_app_user (user_id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    body VARCHAR(500),
    link VARCHAR(300),
    read_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY k_notif_user (user_id, read_at, created_at)
);

ALTER TABLE events ADD COLUMN IF NOT EXISTS capacity INT UNSIGNED NULL AFTER registration_open;
ALTER TABLE event_registrations ADD COLUMN IF NOT EXISTS ticket_code VARCHAR(24) NULL AFTER phone;
ALTER TABLE event_registrations ADD COLUMN IF NOT EXISTS checked_in_at DATETIME NULL AFTER ticket_code;
ALTER TABLE event_registrations ADD COLUMN IF NOT EXISTS checked_in_by INT UNSIGNED NULL AFTER checked_in_at;
UPDATE event_registrations SET ticket_code = UPPER(SUBSTRING(MD5(CONCAT(id, RAND(), UUID())), 1, 12)) WHERE ticket_code IS NULL;
ALTER TABLE event_registrations ADD UNIQUE KEY IF NOT EXISTS u_ticket (ticket_code);
