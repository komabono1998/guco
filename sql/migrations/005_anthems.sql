-- 005: anthems (Gulu University, Uganda national anthem, East African Community anthem), managed in Admin > Anthems.

CREATE TABLE IF NOT EXISTS anthems (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    kicker VARCHAR(80) NOT NULL DEFAULT 'Anthem',
    body VARCHAR(160),
    language VARCHAR(80),
    description VARCHAR(600),
    credit VARCHAR(240),
    lyrics TEXT,
    audio VARCHAR(200),
    video_url VARCHAR(300),
    show_home TINYINT(1) NOT NULL DEFAULT 0,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT NOT NULL DEFAULT 100,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY k_anthem_list (is_public, sort_order)
);

INSERT INTO anthems (slug, title, kicker, body, language, description, lyrics, audio, video_url, show_home, sort_order)
SELECT 'gulu-university', 'The Gulu University Anthem', 'University anthem', 'Gulu University', 'English',
  'The anthem that unites every Gulu University student, graduate and friend.',
  COALESCE((SELECT v FROM site_content WHERE k = 'home.anthem_lyrics'), ''),
  NULLIF(COALESCE((SELECT v FROM site_content WHERE k = 'home.anthem_audio'), ''), ''),
  NULLIF(COALESCE((SELECT v FROM site_content WHERE k = 'home.anthem_video'), ''), ''),
  1, 10
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM anthems WHERE slug = 'gulu-university');

INSERT INTO anthems (slug, title, kicker, body, language, description, credit, lyrics, show_home, sort_order)
SELECT 'uganda-national-anthem', 'Uganda National Anthem', 'National anthem', 'Republic of Uganda', 'English',
  'Oh Uganda, May God Uphold Thee: the national anthem of the Republic of Uganda, sung at national and University occasions.',
  'Words and music by George Wilberforce Kakoma, adopted at independence in 1962.',
  'Oh Uganda! May God uphold thee,
We lay our future in thy hand.
United, free,
For liberty
Together we''ll always stand.

Oh Uganda! the land of freedom,
Our love and labour we give,
And with neighbours all
At our country''s call
In peace and friendship we''ll live.

Oh Uganda! the land that feeds us
By sun and fertile soil grown.
For our own dear land,
We shall always stand,
The Pearl of Africa''s Crown.',
  0, 20
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM anthems WHERE slug = 'uganda-national-anthem');

INSERT INTO anthems (slug, title, kicker, body, language, description, credit, lyrics, show_home, sort_order)
SELECT 'east-african-community-anthem', 'East African Community Anthem', 'Regional anthem', 'East African Community', 'Kiswahili and English',
  'The anthem of the East African Community, shared by its partner states. Add the official words and recording from the EAC Secretariat in Admin > Anthems.',
  'Official words and recording: East African Community Secretariat.', '', 0, 30
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM anthems WHERE slug = 'east-african-community-anthem');

INSERT INTO menu_items (label, url, parent_id, sort_order, is_visible, new_tab)
SELECT 'Our Anthems', 'anthems.php', p.id, 45, 1, 0 FROM menu_items p
WHERE p.parent_id IS NULL AND p.url = 'about.php' AND NOT EXISTS (SELECT 1 FROM menu_items x WHERE x.url = 'anthems.php');
