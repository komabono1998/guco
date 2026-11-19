-- 021: the Publicity Secretary of the Executive Committee, with photograph and biography.
-- Only fills the position while it is still empty, so later edits in the admin are never overwritten.

UPDATE leaders SET
  name = 'Counsel Moses Omara',
  photo = 'assets/img/publicity-secretary-omara.webp',
  bio = 'Counsel Moses Omara is a legal professional serving as the Legal Officer at Gulu University, where he contributes to legal affairs and institutional governance. With a professional interest in law, public service, and institutional development, he brings a legal perspective to matters concerning accountability, compliance, and organisational effectiveness.\n\nHe is also part of the leadership of the Gulu University Convocation (GUCO), having served as its inaugural Publicity Secretary following his swearing-in on 5th November 2024 at Gulu University Main Campus. In this capacity, he contributes to strengthening the Convocation’s public engagement, visibility, communication, and stakeholder relations.\n\nThrough his involvement in GUCO, Counsel Omara is committed to fostering stronger connections among Gulu University alumni, promoting institutional identity, and advancing initiatives that enhance the University’s reputation and engagement with its wider community.\n\nHis involvement reflects a commitment to professional excellence, responsible leadership, institutional development, and the continued growth of the Gulu University alumni community.'
WHERE organ = 'executive' AND title = 'Publicity Secretary' AND (name IS NULL OR name = '');
