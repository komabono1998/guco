-- 016: the Vice Chancellor of Gulu University, a Board member by virtue of office, with photograph and biography.
-- Only fills the position while it is still empty, so later edits in the admin are never overwritten.

UPDATE leaders SET
  name = 'Prof. George Ladaah Openjuru',
  photo = 'assets/img/vc-openjuru.webp',
  term = 'Vice Chancellor since January 2018',
  bio = 'Professor George Ladaah Openjuru is a Ugandan professor of Education and an academic administrator. He has been the Vice Chancellor of Gulu University in Northern Uganda since January 2018, succeeding Professor Jack Nyeko Pen-Mogi, the founding Vice Chancellor. As Vice Chancellor, Professor Openjuru has been instrumental in promoting the university’s engagement with local communities and spearheading initiatives to foster sustainable development in the region.\n\nBefore he was appointed Vice Chancellor, Professor Openjuru served as the Deputy Vice Chancellor for Academic Affairs at Gulu University from 2015 to 2017. He played a key role in developing the university’s strategic plans for 2015/2020 and 2021-2025.\n\nHe was also the first Dean of the School of Distance and Lifelong Learning (SoDLL) at Makerere University. He held various positions, including Head of Department, Lecturer and Researcher, in the Department of Community Education and Extra-Mural Studies (CEEMS) at Makerere University’s Institute of Adult and Continuing Education (IACE), which was subsequently renamed the School of Distance and Lifelong Learning (SoDLL) within the College of Education and External Studies at Makerere University.'
WHERE organ = 'board' AND title LIKE 'Vice Chancellor%' AND (name IS NULL OR name = '');
