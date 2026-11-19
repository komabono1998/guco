-- 026: photograph of the Staff Representative. Only set while no photograph has been added yet.

UPDATE leaders SET photo = 'assets/img/staff-representative-okello.webp'
WHERE organ = 'executive' AND title = 'Staff Representative' AND name = 'Mr. Okello Peter' AND (photo IS NULL OR photo = '');
