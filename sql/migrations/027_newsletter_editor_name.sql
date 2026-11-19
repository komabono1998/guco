-- 027: the Newsletter Editor is named Mr. Moses Komakech. This only matters on a site that already ran the
-- earlier version of migration 018 with the old name order; elsewhere it changes nothing.

UPDATE leaders SET
  name = 'Mr. Moses Komakech',
  bio = REPLACE(bio, 'Komakech Moses Santos', 'Mr. Moses Komakech')
WHERE organ = 'executive' AND title = 'Newsletter Editor' AND name = 'Komakech Moses Santos';
