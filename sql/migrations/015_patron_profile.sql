-- 015: the Patron of GUCO, the Chancellor of Gulu University, with his photograph and biography.
-- Only fills the Patron position while it is still empty, so later edits in the admin are never overwritten.

UPDATE leaders SET
  name = 'Dr. Ruhakana Rugunda',
  photo = 'assets/img/patron-rugunda.webp',
  bio = 'Ruhakana Rugunda (born 7 November 1947) is a Ugandan physician and politician who was Prime Minister of Uganda from 2014 to 2021. He is the Chancellor of Gulu University.\n\nHe held a long series of cabinet posts under President Yoweri Museveni beginning in 1986. He served as Uganda''s Minister of Foreign Affairs from 1994 to 1996 and Minister of Internal Affairs from 2003 to 2009. He was then Uganda''s Permanent Representative to the United Nations from 2009 to 2011, and Minister of Health from 2013 to 2014.\n\nHe was appointed Prime Minister on 18 September 2014 and served until 21 June 2021, replacing Amama Mbabazi.\n\nHarvard University published a case study on his role in leading the Juba peace talks, entitled Giving Peace a Chance: The 2006-2008 Negotiations to End the Conflict in Northern Uganda.'
WHERE organ = 'patron' AND title LIKE 'Patron%' AND (name IS NULL OR name = '');
