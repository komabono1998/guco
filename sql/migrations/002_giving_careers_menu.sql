-- 002: receipt access token for donations, and public menu entries for the new modules.
ALTER TABLE donations ADD COLUMN IF NOT EXISTS access_token VARCHAR(40) NULL AFTER receipt_no;
UPDATE donations SET access_token = SUBSTRING(MD5(CONCAT(id, RAND(), UUID())), 1, 32) WHERE access_token IS NULL;
INSERT INTO menu_items (label, url, parent_id, sort_order, is_visible, new_tab)
SELECT 'Give', 'donate.php', NULL, 105, 1, 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url = 'donate.php');
INSERT INTO menu_items (label, url, parent_id, sort_order, is_visible, new_tab)
SELECT 'Careers', 'careers.php', NULL, 106, 1, 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url = 'careers.php');
