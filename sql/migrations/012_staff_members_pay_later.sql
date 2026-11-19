-- 012: members can join as alumni or as Gulu University staff, and can register now and pay later.

ALTER TABLE member_profiles ADD COLUMN IF NOT EXISTS member_type ENUM('alumni','staff') NOT NULL DEFAULT 'alumni' AFTER category;

ALTER TABLE member_profiles ADD COLUMN IF NOT EXISTS staff_joined_on DATE NULL AFTER student_reg_no;

ALTER TABLE member_profiles ADD COLUMN IF NOT EXISTS staff_position VARCHAR(150) NULL AFTER staff_joined_on;

ALTER TABLE member_profiles ADD COLUMN IF NOT EXISTS staff_department VARCHAR(150) NULL AFTER staff_position;

ALTER TABLE member_profiles ADD COLUMN IF NOT EXISTS staff_no VARCHAR(60) NULL AFTER staff_department;

ALTER TABLE member_profiles ADD COLUMN IF NOT EXISTS staff_terms ENUM('permanent','contract','part_time','visiting','other') NULL AFTER staff_no;

ALTER TABLE member_profiles ADD COLUMN IF NOT EXISTS staff_status ENUM('current','retired','former') NULL AFTER staff_terms;

ALTER TABLE member_profiles ADD COLUMN IF NOT EXISTS pay_later TINYINT(1) NOT NULL DEFAULT 0 AFTER payment_reference;

INSERT IGNORE INTO settings (k, v) VALUES ('allow_pay_later', '1');
