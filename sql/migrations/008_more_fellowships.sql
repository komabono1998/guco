-- 008: more established fellowships and a youth volunteering programme, each linking to its official site.
-- Calls and deadlines change every cycle, so only dates stated on the official page are stored.

INSERT INTO jobs (title, kind, levels, scope, organisation, location, description, requirements, apply_url, deadline, allow_applications, member_only, is_public, verified_on)
SELECT s.* FROM (
  SELECT 'Obama Foundation Leaders: Africa' AS title, 'fellowship' AS kind, 'professional' AS levels, 'international' AS scope, 'Obama Foundation' AS organisation, 'Africa (virtual)' AS location,
    'A leadership programme of about eight months for emerging leaders working for positive change in communities, countries or the wider region across Africa. It is delivered mainly through virtual Obama Foundation programming.' AS description,
    'Applicants must live in Africa, be a citizen or permanent resident of an African country or have lived there for the last five years, be professionally proficient in English, be aged 24 to 45 during the programme, and have at least three years of experience in their issue area. Read the current call on the official page.' AS requirements,
    'https://www.obama.org/programs/leaders/africa/how-to-apply/' AS apply_url, NULL AS deadline, 0 AS allow_applications, 0 AS member_only, 1 AS is_public, '2026-11-19' AS verified_on
  UNION ALL SELECT 'beVisioneers: The Mercedes-Benz Fellowship', 'fellowship', '', 'international', 'The DO School Fellowships, supported by Mercedes-Benz', 'Global, open to young people in Africa',
    'A fellowship for young environmental innovators who want to start or grow projects that tackle environmental challenges. It offers training, expert support and resources. Applications are made in cohorts on the official beVisioneers website.',
    'The site lists applicants aged 18 to 28. Read the current cohort page for the age range, eligibility and closing date.',
    'https://bevisioneers.world/', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Mo Ibrahim Foundation Ibrahim Leadership Fellowship', 'fellowship', 'professional', 'international', 'Mo Ibrahim Foundation', 'Abidjan, Addis Ababa or Geneva',
    'A 12-month fellowship that places three future African leaders each year at the African Development Bank, the UN Economic Commission for Africa or the International Trade Centre, with a $100,000 stipend. Applications go through each host organisation.',
    'Applicants must be nationals of an African country, hold a master''s degree, have at least seven years of relevant experience, and be under 40 (or under 45 for women with children), plus any criteria set by the host.',
    'https://mo.ibrahim.foundation/fellowships', '2026-11-20', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Hubert H. Humphrey Fellowship (Uganda)', 'fellowship', 'professional', 'international', 'US Department of State, administered by the Institute of International Education', 'United States',
    'A Fulbright exchange that brings mid-career professionals to US universities for a year of non-degree graduate-level study and professional experience. Ugandan applicants apply through the US Embassy in Kampala.',
    'Calls in recent years asked for a bachelor''s degree, about five years of professional experience, leadership and public service, and English fluency. Confirm the current requirements with the US Embassy Uganda and the official site.',
    'https://www.humphreyfellowship.org/', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Rotary Peace Fellowships', 'fellowship', 'masters,professional', 'international', 'Rotary International', 'Universities worldwide',
    'Fully funded fellowships for people working in peace and development: a master''s degree at a Rotary Peace Center university, or a professional development certificate. Applications are usually made online in the first half of the year.',
    'Read the eligibility, selection and timeline on the official page, including work experience and the Rotary club endorsement rules that apply.',
    'https://www.rotary.org/en/our-programs/peace-fellowships', NULL, 0, 0, 1, NULL
  UNION ALL SELECT 'Echoing Green Fellowship', 'fellowship', '', 'international', 'Echoing Green', 'Global',
    'A fellowship for early-stage social entrepreneurs that combines a $100,000 stipend over 18 months with leadership development, expert support and a community of peers. Applicants first join the Echoing Green Talent Platform.',
    'There are no formal degree requirements. The programme looks for leaders with deep knowledge of the problem they work on, especially in overlooked places. Read the application guide on the official page.',
    'https://echoinggreen.org/fellowship/', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Acumen East Africa Fellowship', 'fellowship', 'professional', 'international', 'Acumen Academy', 'East Africa, including Uganda',
    'A six-month hybrid fellowship for leaders tackling poverty in East Africa. Fellows keep their jobs, take part in virtual and in-person sessions, and Acumen covers the programme costs, including lodging and food at the immersives.',
    'Uganda is an eligible country. Applicants are social entrepreneurs or senior decision-makers with at least five years of experience, proficient in English, who have not been an Acumen Fellow before. Check the page for the next cohort and its dates.',
    'https://fellowship.acumenacademy.org/east-africa', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Atlantic Fellows (equity programmes)', 'fellowship', 'professional', 'international', 'Atlantic Fellows', 'Cape Town, London, Washington DC and other cities',
    'A global community of leaders working to reduce inequity. Programmes include Health Equity in South Africa (based in Cape Town), Social and Economic Equity and Racial Equity (London), Health Equity Global, and others. Each programme runs its own call.',
    'Eligibility, funding and deadlines differ by programme. Choose the programme that fits your work and read its call on the official site.',
    'https://www.atlanticfellows.org/', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Ashoka Fellowship (Africa)', 'fellowship', '', 'international', 'Ashoka', 'Across Africa',
    'Ashoka elects social entrepreneurs with new ideas for solving social problems and supports them with a stipend, mentorship and a lifelong network. It has supported over 500 Fellows in Africa. Candidates are nominated by Ashoka''s network and self-nominations are also welcomed.',
    'The selection has several stages, from a screening call to a board decision. Read the Africa programme page and the nomination page for the current process.',
    'https://ashoka.org/program/africa-venture', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'AWARD Fellowships for African women scientists', 'fellowship', 'masters,phd', 'international', 'African Women in Agricultural Research and Development (AWARD)', 'Africa (hosted in Nairobi)',
    'A two-year career development fellowship for African women agricultural scientists, building leadership, mentoring and research skills. Awards are based on merit, leadership potential and the benefit of the research to smallholder farmers.',
    'Check the official site for the current round, the countries covered and the eligibility rules.',
    'https://awardfellowships.org/', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Yale World Fellows Program', 'fellowship', 'professional', 'international', 'Yale University', 'New Haven, United States',
    'A four-month residential fellowship at Yale for mid-career leaders from outside the United States, including a stipend, housing and health insurance. Applications are made on the programme''s online portal.',
    'Fellows are usually several years into their careers with a record of achievement. Read the eligibility and timeline on the official site.',
    'https://worldfellows.yale.edu/', NULL, 0, 0, 1, NULL
  UNION ALL SELECT 'Next Einstein Forum (NEF) Fellows', 'fellowship', 'phd,professional', 'international', 'Next Einstein Forum', 'Africa and worldwide',
    'A fellowship for young African scientists and technologists, including those in the diaspora, across all fields of science. Fellows are chosen by the NEF Scientific Programme Committee and join its community and events.',
    'Calls have asked for a CV and a short video. Look for the current call and its eligibility on the official site.',
    'https://nef.org/', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Mozilla Foundation fellowships', 'fellowship', 'professional', 'international', 'Mozilla Foundation', 'Global, including Africa',
    'Fellowships for people working to make technology serve the public, including the Tech + Society Fellowship and a Senior Fellowship. Open calls vary, so watch the programmes page.',
    'Each fellowship states its own eligibility, stipend and closing date.',
    'https://www.mozillafoundation.org/en/what-we-fund/programs/', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'African Union Youth Volunteer Corps (AU-YVC)', 'volunteer', 'undergraduate,professional', 'international', 'African Union Commission', 'African Union member states',
    'A continental programme that places young African professionals as volunteers in host organisations, with training and support covered by the African Union Commission and its partners. Calls open for each new cohort.',
    'Applicants are young Africans, and recent calls asked for some volunteer and work experience. Read the call on the African Union site.',
    'https://au.int/es/node/46210', NULL, 0, 0, 1, '2026-11-19'
) s WHERE NOT EXISTS (SELECT 1 FROM jobs j WHERE j.title = s.title);
