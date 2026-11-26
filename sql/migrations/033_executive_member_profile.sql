-- 033: the Executive Member elected at the General Assembly, with photograph and biography.
-- Only fills the position while it is still empty, so later edits in the admin are never overwritten.

UPDATE leaders SET
  name = 'Mr. Komakech Patrick',
  photo = 'assets/img/executive-member-komakech-patrick.webp',
  bio = 'Komakech Patrick is a passionate and committed individual with a strong interest in youth and children empowerment. He believes that young people and children are important agents of positive change and deserve opportunities, guidance, support, and an enabling environment to realize their full potential.

He is passionate about promoting the rights and well-being of children, supporting youth development, and creating opportunities for young people to actively participate in community development. His interests include youth mentorship, leadership development, education, life skills development, child protection, and community engagement.

Patrick believes in empowering young people with the knowledge, skills, confidence, and opportunities they need to become responsible, productive, and self-reliant members of society.

Through his passion and commitment, he hopes to contribute to building a society where every child is protected, every young person is empowered, and everyone has an opportunity to achieve their dreams.'
WHERE organ = 'executive' AND title = 'Executive Member' AND (name IS NULL OR name = '');
