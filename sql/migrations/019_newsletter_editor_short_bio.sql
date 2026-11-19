-- 019: shorten the Newsletter Editor's profile to the confirmed introduction.
-- Only replaces the longer text added by migration 018; a profile edited in the admin since then is left alone.

UPDATE leaders SET
  bio = 'Mr. Moses Komakech is an Information and Communication Technology (ICT) specialist, digital communications professional, graphic designer and youth development advocate from Northern Uganda. He holds a Bachelor of Science in Information and Communication Technology from Gulu University.'
WHERE organ = 'executive' AND title = 'Newsletter Editor' AND name = 'Mr. Moses Komakech' AND bio LIKE '%Areas of interest:%';
