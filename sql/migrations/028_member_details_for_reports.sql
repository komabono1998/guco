-- 028: academic and subscription details for the register of existing members, so the admin can filter and export them.
-- Faculty is often suggested from the course; faculty_suggested = 1 marks a suggestion the Secretariat can confirm.

ALTER TABLE legacy_members ADD COLUMN IF NOT EXISTS course VARCHAR(200) NULL AFTER designation;

ALTER TABLE legacy_members ADD COLUMN IF NOT EXISTS faculty VARCHAR(150) NULL AFTER course;

ALTER TABLE legacy_members ADD COLUMN IF NOT EXISTS faculty_suggested TINYINT(1) NOT NULL DEFAULT 0 AFTER faculty;

ALTER TABLE legacy_members ADD COLUMN IF NOT EXISTS graduation_series SMALLINT NULL AFTER faculty_suggested;

ALTER TABLE legacy_members ADD COLUMN IF NOT EXISTS student_reg_no VARCHAR(60) NULL AFTER graduation_series;

ALTER TABLE legacy_members ADD COLUMN IF NOT EXISTS phone VARCHAR(40) NULL AFTER student_reg_no;

ALTER TABLE legacy_members ADD COLUMN IF NOT EXISTS subscribed_year SMALLINT NULL AFTER phone;

ALTER TABLE member_profiles ADD COLUMN IF NOT EXISTS graduation_series SMALLINT NULL AFTER graduation_year;
