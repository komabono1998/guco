-- 017: the Vice Chairperson of the Executive Committee, with photograph and biography.
-- Only fills the position while it is still empty, so later edits in the admin are never overwritten.

UPDATE leaders SET
  name = 'Counsel Prossy Akello',
  photo = 'assets/img/vicechair-akello.webp',
  bio = 'Counsel Prossy Akello is an Advocate of the High Court of Uganda and currently serves as an Attorney with Redeem International, where she is devoted to advancing justice, protecting human rights and championing the interests of vulnerable and marginalized communities.\n\nShe is an alumna of Gulu University and currently serves as the Vice Chairperson of the Gulu University Convocation, a role through which she is passionate about alumni engagement, institutional development and fostering meaningful partnerships for growth.\n\nHer vision is to see the Convocation mission of community transformation actualized through purposeful leadership, meaningful engagement and collective action.'
WHERE organ = 'executive' AND title = 'Vice Chairperson' AND (name IS NULL OR name = '');
