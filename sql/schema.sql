CREATE DATABASE IF NOT EXISTS guco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE guco;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('member','super_admin','secretariat','communications','newsletter_editor','chapter_coordinator') NOT NULL DEFAULT 'member',
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE chapters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    focus VARCHAR(150),
    contact_name VARCHAR(150),
    contact_email VARCHAR(190),
    coordinator_user_id INT UNSIGNED NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (coordinator_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE member_profiles (
    user_id INT UNSIGNED PRIMARY KEY,
    member_no INT UNSIGNED NULL UNIQUE,
    membership_id VARCHAR(40) NULL UNIQUE,
    category ENUM('founder','ordinary','honorary','life') NOT NULL DEFAULT 'ordinary',
    subscription_status ENUM('unpaid','active','arrears') NOT NULL DEFAULT 'unpaid',
    paid_until DATE NULL,
    approved_at DATETIME NULL,
    card_status ENUM('none','active','revoked') NOT NULL DEFAULT 'none',
    card_issued_at DATETIME NULL,
    card_revoked_at DATETIME NULL,
    card_revoke_reason VARCHAR(300),
    gender ENUM('female','male','other','undisclosed') NULL,
    date_of_birth DATE NULL,
    nationality VARCHAR(80),
    phone VARCHAR(40),
    address VARCHAR(200),
    location VARCHAR(150),
    country VARCHAR(80),
    college VARCHAR(150),
    programme VARCHAR(150),
    qualification_level VARCHAR(60),
    student_reg_no VARCHAR(60),
    graduation_year SMALLINT,
    eligibility_details TEXT,
    employment_status ENUM('employed','self_employed','unemployed','student','retired','other') NULL,
    employer VARCHAR(150),
    profession VARCHAR(150),
    interests VARCHAR(300),
    bio TEXT,
    photo VARCHAR(200),
    payment_reference VARCHAR(150),
    chapter_id INT UNSIGNED NULL,
    directory_consent TINYINT(1) NOT NULL DEFAULT 0,
    admin_notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE SET NULL
);

CREATE TABLE member_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    purpose ENUM('registration','renewal','other') NOT NULL DEFAULT 'registration',
    method VARCHAR(40),
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    reference VARCHAR(100),
    paid_on DATE NULL,
    proof_file VARCHAR(200),
    proof_original VARCHAR(200),
    status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(300),
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    INDEX (token_hash),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    category VARCHAR(60) NOT NULL DEFAULT 'GUCO Announcements',
    summary VARCHAR(300),
    body TEXT NOT NULL,
    image VARCHAR(200),
    status ENUM('draft','pending','published') NOT NULL DEFAULT 'draft',
    author_id INT UNSIGNED NULL,
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FULLTEXT KEY ft_news (title, summary, body),
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(60) NOT NULL DEFAULT 'Other',
    description TEXT,
    extra_details TEXT,
    flyer VARCHAR(200),
    location VARCHAR(200),
    online_link VARCHAR(300),
    audience VARCHAR(200),
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    show_countdown TINYINT(1) NOT NULL DEFAULT 1,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    registration_open TINYINT(1) NOT NULL DEFAULT 0,
    accessibility_info VARCHAR(300),
    contact VARCHAR(200),
    status ENUM('scheduled','postponed','cancelled') NOT NULL DEFAULT 'scheduled',
    is_public TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE event_registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_event_email (event_id, email),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE leaders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    title VARCHAR(150) NOT NULL,
    organ ENUM('patron','board','executive','disciplinary') NOT NULL DEFAULT 'executive',
    bio TEXT,
    role_description TEXT,
    photo VARCHAR(200),
    term VARCHAR(100),
    sort_order SMALLINT NOT NULL DEFAULT 100,
    is_public TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE programmes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    area VARCHAR(100),
    objective TEXT,
    date_location VARCHAR(200),
    target_group VARCHAR(200),
    reach VARCHAR(100),
    partners VARCHAR(300),
    outcomes TEXT,
    next_steps TEXT,
    is_public TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE awards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    eligibility TEXT,
    nomination_status ENUM('not_open','open','closed') NOT NULL DEFAULT 'not_open',
    deadline DATE NULL,
    required_documents TEXT,
    selection_process TEXT,
    past_recipients TEXT,
    contact VARCHAR(200),
    is_public TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE resources (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description VARCHAR(400),
    category VARCHAR(80) NOT NULL DEFAULT 'Governance',
    filename VARCHAR(200),
    original_name VARCHAR(200),
    file_size INT UNSIGNED,
    member_only TINYINT(1) NOT NULL DEFAULT 0,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    published_at DATE NOT NULL DEFAULT (CURRENT_DATE),
    FULLTEXT KEY ft_res (title, description)
);

CREATE TABLE gallery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    caption VARCHAR(200),
    filename VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40),
    category VARCHAR(60) NOT NULL DEFAULT 'General',
    subject VARCHAR(200),
    body TEXT NOT NULL,
    status ENUM('new','handled') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
    k VARCHAR(60) PRIMARY KEY,
    v TEXT
);

CREATE TABLE audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    detail VARCHAR(400),
    ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE site_content (
    k VARCHAR(80) PRIMARY KEY,
    v MEDIUMTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(80) NOT NULL,
    url VARCHAR(300) NOT NULL,
    parent_id INT UNSIGNED NULL,
    sort_order SMALLINT NOT NULL DEFAULT 100,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    new_tab TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

CREATE TABLE pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    meta_description VARCHAR(300),
    body MEDIUMTEXT,
    image VARCHAR(200),
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE partners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    logo VARCHAR(200),
    url VARCHAR(300),
    sort_order SMALLINT NOT NULL DEFAULT 100,
    is_public TINYINT(1) NOT NULL DEFAULT 1
);

-- Contact details, fees and payment instructions stay blank/hidden until the
-- Secretariat confirms them (see the website content document).
INSERT INTO settings (k, v) VALUES
 ('contact_email', ''), ('contact_phone', ''), ('office_hours', ''),
 ('membership_contact', ''), ('chapter_contact', ''),
 ('social_facebook', ''), ('social_x', ''), ('social_linkedin', ''), ('social_youtube', ''),
 ('show_fees', '0'),
 ('fee_founder', '100000'), ('fee_ordinary', '20000'), ('fee_honorary', '200000'), ('fee_life', '400000'),
 ('payment_instructions', ''),
 ('applications_open', '1'),
 ('require_payment_proof', '1');

INSERT INTO awards (name, description, eligibility) VALUES
 ('Outstanding Achievement Awards', 'Recognition of outstanding achievements by Gulu University alumni.', 'To be confirmed by the Secretariat.'),
 ('Distinguished Service Award', 'For an alumnus who has made a significant contribution to the community or country.', 'To be confirmed by the Secretariat.'),
 ('Distinguished Service Senior Alumnus Award', 'For an alumnus over 40 years of age with remarkable achievements.', 'Alumnus over 40 years of age. Further criteria to be confirmed.'),
 ('Distinguished Service Youth Alumnus Award', 'For an alumnus under 40 years of age with significant achievements.', 'Alumnus under 40 years of age. Further criteria to be confirmed.');

-- Recurring events. Dates are left blank ("to be announced") until the Secretariat confirms them;
-- set the date, venue and flyer in Admin > Events to publish them and start the countdown.
INSERT INTO events (title, category, description, extra_details) VALUES
 ('GUCO Alumni & Stakeholders Dinner', 'Dinner', 'An evening for alumni and stakeholders of Gulu University: celebrating excellence, strengthening partnerships and investing in the future of the University.', 'Dress code, programme and attendance details will be added once confirmed.'),
 ('Annual General Meeting (AGM)', 'AGM', 'The Annual General Meeting of Gulu University Convocation. Under the Constitution the AGM is held not later than November each year, with written notice, the annual statement of accounts and the agenda sent at least 21 days before the meeting.', 'The official notice is published here and under Notices only after it is formally issued by the Association.'),
 ('GUCO League', 'Sports', 'The GUCO League brings alumni together through sport. Fixtures, teams and registration details will be announced.', 'Rules, categories and fixtures will be added once confirmed.');

-- Placeholder positions for the governing organs (Constitution Articles 10-16).
-- Edit them in Admin > Leadership to add names, photos and profiles.
INSERT INTO leaders (name, title, organ, role_description, sort_order, is_public) VALUES
 ('', 'Patron (Chancellor of Gulu University)', 'patron', 'The Constitution identifies the Chancellor of Gulu University as Patron of the Association.', 10, 1),
 ('', 'Board Chairperson', 'board', 'An elected prominent Gulu University alumnus who chairs the Board. The Board provides advisory services to the Executive Committee.', 10, 1),
 ('', 'Vice Chancellor, Gulu University', 'board', 'Member of the Board by virtue of office.', 20, 1),
 ('', 'Board Member', 'board', 'Nominated by and approved by the General Assembly.', 30, 1),
 ('', 'Board Member', 'board', 'Nominated by and approved by the General Assembly.', 40, 1),
 ('', 'Board Member (from outside Gulu University)', 'board', 'Nominated by and approved by the General Assembly; one of two members from outside Gulu University.', 50, 1),
 ('', 'Board Member (from outside Gulu University)', 'board', 'Nominated by and approved by the General Assembly; one of two members from outside Gulu University.', 60, 1),
 ('', 'Chairperson', 'executive', 'General supervision, representation, committee coordination, policy implementation, strategic planning, fundraising and alumni outreach.', 10, 1),
 ('', 'Vice Chairperson', 'executive', 'Performs the Chairperson''s duties in their absence and other assigned duties. The Chairperson and Vice Chairperson are of opposite gender.', 20, 1),
 ('', 'General Secretary (Academic Registrar, Gulu University)', 'executive', 'Meeting notices and agendas, minutes, attendance records, correspondence and Association records. Does not vote or stand for election.', 30, 1),
 ('', 'Treasurer', 'executive', 'Funds, accounts, membership dues, budgets and quarterly and annual financial reporting.', 40, 1),
 ('', 'Publicity Secretary', 'executive', 'Member mobilisation, information dissemination, social media and website management, and liaison with other university associations.', 50, 1),
 ('', 'Female Representative', 'executive', 'Encourages participation by female alumni and staff.', 60, 1),
 ('', 'Staff Representative', 'executive', 'Represents University staff and supports staff mobilisation.', 70, 1),
 ('', 'Newsletter Editor', 'executive', 'Provides timely information and publicises Association activities.', 80, 1),
 ('', 'Executive Member', 'executive', 'One other member elected at the General Assembly.', 90, 1),
 ('', 'Disciplinary Committee Member', 'disciplinary', 'Elected from active members at the AGM; responsible for the general disciplinary conduct of members.', 10, 1),
 ('', 'Disciplinary Committee Member', 'disciplinary', 'Elected from active members at the AGM; responsible for the general disciplinary conduct of members.', 20, 1),
 ('', 'Disciplinary Committee Member', 'disciplinary', 'Elected from active members at the AGM; responsible for the general disciplinary conduct of members.', 30, 1),
 ('', 'Disciplinary Committee Member', 'disciplinary', 'Elected from active members at the AGM; responsible for the general disciplinary conduct of members.', 40, 1),
 ('', 'Disciplinary Committee Member', 'disciplinary', 'Elected from active members at the AGM; responsible for the general disciplinary conduct of members.', 50, 1);

-- Branding defaults, homepage section switches and the default navigation menu.
INSERT IGNORE INTO settings (k, v) VALUES
 ('site_name', 'Gulu University Convocation'), ('site_tagline', 'Community Transformation Actualized'),
 ('color_primary', '#14532d'), ('color_accent', '#f2b807'), ('logo', ''),
 ('home_show_welcome', '1'), ('home_show_cards', '1'), ('home_show_news', '1'), ('home_show_events', '1'),
 ('home_show_gallery', '1'), ('home_show_awards', '1'), ('home_show_cta', '1');

INSERT INTO menu_items (id, label, url, parent_id, sort_order) VALUES
 (1, 'Home', 'index.php', NULL, 10),
 (2, 'About GUCO', 'about.php', NULL, 20),
 (3, 'Membership', 'membership.php', NULL, 30),
 (4, 'Leadership', 'leadership.php', NULL, 40),
 (5, 'Programmes', 'programmes.php', NULL, 50),
 (6, 'Chapters', 'chapters.php', NULL, 60),
 (7, 'Events & News', 'news.php', NULL, 70),
 (8, 'Awards', 'awards.php', NULL, 80),
 (9, 'Partnerships', 'partnerships.php', NULL, 90),
 (10, 'Resources', 'resources.php', NULL, 100),
 (11, 'Contact', 'contact.php', NULL, 110),
 (101, 'Overview', 'about.php#overview', 2, 10),
 (102, 'Vision and Mission', 'about.php#vision', 2, 20),
 (103, 'Objectives', 'about.php#objectives', 2, 30),
 (104, 'Constitution', 'resources.php?q=constitution', 2, 40),
 (111, 'Membership Categories', 'membership.php#categories', 3, 10),
 (112, 'Benefits', 'membership.php#benefits', 3, 20),
 (113, 'Join GUCO', 'register.php', 3, 30),
 (114, 'Member Portal', 'member/', 3, 40),
 (115, 'Renew Membership', 'member/payments.php#renew', 3, 50),
 (116, 'Verify a Card', 'verify.php', 3, 60),
 (117, 'FAQs', 'membership.php#faq', 3, 70),
 (121, 'General Assembly', 'leadership.php#assembly', 4, 10),
 (122, 'Board', 'leadership.php#board', 4, 20),
 (123, 'Executive Committee', 'leadership.php#executive', 4, 30),
 (124, 'Elections', 'leadership.php#elections', 4, 40),
 (131, 'News', 'news.php', 7, 10),
 (132, 'Events Calendar', 'events.php', 7, 20),
 (133, 'Notices', 'news.php?category=Notices', 7, 30),
 (134, 'Newsletter', 'news.php?category=Newsletter', 7, 40),
 (135, 'Gallery', 'gallery.php', 7, 50);
