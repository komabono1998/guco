-- 032: removes the swearing-in date from the Publicity Secretary's profile. Safe to run more than once.

UPDATE leaders SET bio = REPLACE(bio, ', having been sworn into office on 5th November 2024 at Gulu University Main Campus', '')
WHERE organ = 'executive' AND title = 'Publicity Secretary' AND name = 'Counsel Moses Omara';
