-- 004: simplify the public menu to seven main items. Related pages move into drop-downs
-- (About, Membership, Programmes, News & Events); Hall of Fame is merged into the Awards page.
-- Only the default pages are touched: custom items you added in Admin > Menu keep their place.

DELETE c FROM menu_items c JOIN menu_items p ON p.id = c.parent_id
  WHERE p.parent_id IS NULL AND p.url IN ('about.php', 'membership.php', 'leadership.php', 'programmes.php', 'news.php', 'chapters.php', 'awards.php', 'partnerships.php', 'resources.php');

DELETE FROM menu_items WHERE parent_id IS NULL AND url IN ('leadership.php', 'chapters.php', 'awards.php', 'hall-of-fame.php', 'partnerships.php', 'resources.php', 'careers.php');

UPDATE menu_items SET label = 'Home', sort_order = 10 WHERE parent_id IS NULL AND url = 'index.php';
UPDATE menu_items SET label = 'About', sort_order = 20 WHERE parent_id IS NULL AND url = 'about.php';
UPDATE menu_items SET label = 'Membership', sort_order = 30 WHERE parent_id IS NULL AND url = 'membership.php';
UPDATE menu_items SET label = 'Programmes', sort_order = 40 WHERE parent_id IS NULL AND url = 'programmes.php';
UPDATE menu_items SET label = 'News & Events', sort_order = 50 WHERE parent_id IS NULL AND url = 'news.php';
UPDATE menu_items SET label = 'Give', sort_order = 60 WHERE parent_id IS NULL AND url = 'donate.php';
UPDATE menu_items SET label = 'Contact', sort_order = 70 WHERE parent_id IS NULL AND url = 'contact.php';

INSERT INTO menu_items (label, url, parent_id, sort_order, is_visible, new_tab)
SELECT s.label, s.url, p.id, s.sort_order, 1, 0 FROM (
  SELECT 'about.php' AS parent, 'Who We Are' AS label, 'about.php#overview' AS url, 10 AS sort_order
  UNION ALL SELECT 'about.php', 'Vision, Mission and Values', 'about.php#vision', 20
  UNION ALL SELECT 'about.php', 'Leadership and Governance', 'leadership.php', 30
  UNION ALL SELECT 'about.php', 'Constitution and Documents', 'resources.php', 40
  UNION ALL SELECT 'about.php', 'Partnerships and Support', 'partnerships.php', 50
  UNION ALL SELECT 'membership.php', 'Categories and Benefits', 'membership.php#categories', 10
  UNION ALL SELECT 'membership.php', 'Join GUCO', 'register.php', 20
  UNION ALL SELECT 'membership.php', 'Alumni Chapters', 'chapters.php', 30
  UNION ALL SELECT 'membership.php', 'Careers and Opportunities', 'careers.php', 40
  UNION ALL SELECT 'membership.php', 'Verify a Card', 'verify.php', 50
  UNION ALL SELECT 'membership.php', 'Member Portal', 'member/', 60
  UNION ALL SELECT 'programmes.php', 'Programme Areas', 'programmes.php', 10
  UNION ALL SELECT 'programmes.php', 'Community Projects', 'projects.php', 20
  UNION ALL SELECT 'programmes.php', 'Awards and Hall of Fame', 'awards.php', 30
  UNION ALL SELECT 'programmes.php', 'Propose a Project', 'member/projects.php', 40
  UNION ALL SELECT 'news.php', 'News and Stories', 'news.php', 10
  UNION ALL SELECT 'news.php', 'Events Calendar', 'events.php', 20
  UNION ALL SELECT 'news.php', 'Newsletters', 'newsletters.php', 30
  UNION ALL SELECT 'news.php', 'Photo Gallery', 'gallery.php', 40
) s JOIN menu_items p ON p.parent_id IS NULL AND p.url = s.parent
WHERE NOT EXISTS (SELECT 1 FROM menu_items x WHERE x.parent_id = p.id AND x.url = s.url);
