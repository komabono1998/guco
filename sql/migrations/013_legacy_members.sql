-- 013: preliminary register of existing GUCO members (for example the 2025 Voters' Register).
-- A member who already holds a GUCO number looks it up on claim.php, then completes registration,
-- renews their subscription and gets a portal account linked to that number. The data itself is imported
-- from a CSV file kept outside git (see tools/import_register.php); this migration only creates the tables.

CREATE TABLE IF NOT EXISTS legacy_members (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  guco_no VARCHAR(20) NOT NULL,
  title VARCHAR(30) NOT NULL DEFAULT '',
  full_name VARCHAR(200) NOT NULL,
  sex ENUM('F','M') NULL,
  designation ENUM('alumnus','staff','alumnus_staff') NOT NULL DEFAULT 'alumnus',
  status ENUM('unclaimed','claimed','disputed','withdrawn') NOT NULL DEFAULT 'unclaimed',
  note VARCHAR(300) NULL,
  source VARCHAR(80) NOT NULL DEFAULT 'Voters Register 2025',
  claimed_user_id INT UNSIGNED NULL,
  claimed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_legacy_person (guco_no, full_name),
  KEY idx_legacy_status (status),
  KEY idx_legacy_user (claimed_user_id),
  CONSTRAINT fk_legacy_user FOREIGN KEY (claimed_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE member_profiles ADD COLUMN IF NOT EXISTS legacy_guco_no VARCHAR(20) NULL AFTER membership_id;
