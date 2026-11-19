-- 009: more scholarships for undergraduate, master's and PhD study, each linking to its official site.
-- Calls and deadlines change every cycle, so no closing dates are stored; visitors are sent to the official page.
-- verified_on is set only where the official page was seen; otherwise it stays empty.

INSERT INTO jobs (title, kind, levels, scope, organisation, location, description, requirements, apply_url, deadline, allow_applications, member_only, is_public, verified_on)
SELECT s.* FROM (
  SELECT 'Rhodes Scholarship for East Africa' AS title, 'scholarship' AS kind, 'masters,phd' AS levels, 'international' AS scope, 'Rhodes Trust' AS organisation, 'University of Oxford, United Kingdom' AS location,
    'A fully funded postgraduate scholarship at the University of Oxford. The East Africa constituency covers Uganda, Tanzania, Rwanda, South Sudan and Burundi. Kenya has its own scholarship.' AS description,
    'Applicants must be citizens of one of the five countries and meet the academic and age rules for the current cycle. Read the eligibility checker and the closing date on the official page.' AS requirements,
    'https://www.rhodeshouse.ox.ac.uk/scholarships/apply/east-africa' AS apply_url, NULL AS deadline, 0 AS allow_applications, 0 AS member_only, 1 AS is_public, '2026-11-19' AS verified_on
  UNION ALL SELECT 'Mandela Rhodes Scholarship', 'scholarship', 'masters', 'international', 'Mandela Rhodes Foundation', 'South Africa',
    'A postgraduate scholarship for African citizens to study Honours or a master''s degree at partner universities in South Africa, combined with a leadership development programme.',
    'Open to citizens of African countries. Age limits, accepted degrees and the closing date are set each year on the official application page.',
    'https://www.mandelarhodes.org/scholarship/apply/', NULL, 0, 0, 1, NULL
  UNION ALL SELECT 'Schwarzman Scholars', 'scholarship', 'masters', 'international', 'Schwarzman Scholars', 'Tsinghua University, Beijing, China',
    'A fully funded one-year master''s in Global Affairs at Tsinghua University for future leaders. Applicants who do not hold a Chinese passport apply through the Schwarzman Scholars website.',
    'Applicants need a completed undergraduate degree, must meet the age limit, and must show English proficiency. Finalists are interviewed. Read the current rules and closing date on the official site.',
    'https://www.schwarzmanscholars.org/', NULL, 0, 0, 1, NULL
  UNION ALL SELECT 'Swedish Institute Scholarships for Global Professionals', 'scholarship', 'masters', 'international', 'Swedish Institute', 'Sweden',
    'Scholarships for master''s studies in Sweden for professionals from selected countries, covering tuition, living costs and a leadership network. Applicants must apply for an eligible master''s programme.',
    'Eligibility depends on citizenship, so check that Uganda is on the current country list. Applicants need work or leadership experience. Read the full criteria on the official page.',
    'https://si.se/en/apply/scholarships/swedish-institute-scholarships-for-global-professionals/', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Global Korea Scholarship (GKS)', 'scholarship', 'undergraduate,masters,phd', 'international', 'Government of the Republic of Korea (NIIED)', 'South Korea',
    'The Korean government scholarship for international students at undergraduate and graduate level. It usually covers tuition, a monthly allowance, airfare, health insurance and a year of Korean language training. Applications go through the Korean embassy or a partner university.',
    'Requirements and closing dates differ by track and year. Read the current announcement on the Study in Korea portal and ask the nearest Korean embassy.',
    'https://www.studyinkorea.go.kr/', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Stipendium Hungaricum (Hungary)', 'scholarship', 'undergraduate,masters,phd', 'international', 'Tempus Public Foundation, Hungary', 'Hungary',
    'The Hungarian government scholarship for bachelor, master, one-tier master and doctoral studies. It covers tuition and a monthly stipend. Ugandan applicants are nominated through the Ministry of Education and Sports before applying online.',
    'Watch the Ministry of Education and Sports notices for the nomination call, then apply on the official site. Read the current rules there.',
    'https://stipendiumhungaricum.hu/apply/', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Joint Japan/World Bank Graduate Scholarship Program', 'scholarship', 'masters', 'international', 'World Bank', 'Participating universities worldwide',
    'A master''s scholarship for professionals from World Bank member developing countries to study development-related subjects at participating universities outside their home country.',
    'Applicants need professional experience and admission to a participating master''s programme. Check that Uganda is on the current eligible country list on the official page.',
    'https://worldbank.org/en/programs/scholarships/jj-wbgsp', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'OWSD PhD Fellowships for Women in Science', 'scholarship', 'phd', 'international', 'Organization for Women in Science for the Developing World (OWSD)', 'Host institutions in developing countries',
    'PhD fellowships for women scientists from eligible developing countries, including in sub-Saharan Africa, to study science, technology, engineering or mathematics at a host institution in another developing country.',
    'Women only, in STEM fields. Fellows must intend to return home after the PhD. Calls open at set times, so read the current call on the official site.',
    'https://owsd.net/', NULL, 0, 0, 1, NULL
  UNION ALL SELECT 'Knight-Hennessy Scholars (Stanford)', 'scholarship', 'masters,phd', 'international', 'Stanford University', 'Stanford, United States',
    'A fully funded graduate scholarship for study in any Stanford graduate programme, together with a leadership programme for a community of scholars from around the world.',
    'Applicants need a first degree and must also apply to a Stanford graduate programme. Read the eligibility and the closing dates on the official site.',
    'https://knight-hennessy.stanford.edu/', NULL, 0, 0, 1, NULL
  UNION ALL SELECT 'Yenching Academy Scholarship (Peking University)', 'scholarship', 'masters', 'international', 'Peking University', 'Beijing, China',
    'A fully funded two-year master''s programme in China Studies at Peking University for graduates from around the world, taught in English.',
    'Applicants need an undergraduate degree and English proficiency. Read the application steps and the closing dates on the official site.',
    'https://yenchingacademy.pku.edu.cn/', NULL, 0, 0, 1, NULL
  UNION ALL SELECT 'Eiffel Excellence Scholarship (France)', 'scholarship', 'masters,phd', 'international', 'Campus France', 'France',
    'A French government scholarship for international students on master''s and PhD programmes in selected fields. French institutions nominate candidates, so applicants do not apply directly.',
    'Applicants must be nominated by a French higher education institution. Read the programme rules and the age limits on the official page.',
    'https://www.campusfrance.org/en/eiffel-scholarship-program-of-excellence', NULL, 0, 0, 1, NULL
  UNION ALL SELECT 'Mastercard Foundation Scholars Program', 'scholarship', 'undergraduate,masters', 'international', 'Mastercard Foundation', 'Partner universities in Africa and worldwide',
    'Scholarships for academically talented young people from Africa who face financial barriers, at partner universities. Each partner university runs its own selection, so apply through the university you are interested in.',
    'Eligibility, partner universities and deadlines differ by partner. Start from the Mastercard Foundation page and follow the link to each university.',
    'https://mastercardfdn.org/all/scholars/', NULL, 0, 0, 1, NULL
) s WHERE NOT EXISTS (SELECT 1 FROM jobs j WHERE j.title = s.title);
