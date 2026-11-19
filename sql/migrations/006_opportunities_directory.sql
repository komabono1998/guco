-- 006: opportunity categories (grants), study levels (a set: empty means any level), local or international scope, and a curated directory of
-- established scholarships, fellowships, grants and employers. Every entry links to its official website; no
-- deadlines are stored because they change each cycle, so visitors are sent to the official call.

ALTER TABLE jobs MODIFY kind ENUM('job','internship','scholarship','fellowship','grant','volunteer','resource') NOT NULL DEFAULT 'job';
ALTER TABLE jobs ADD COLUMN IF NOT EXISTS levels SET('undergraduate','masters','phd','professional') NOT NULL DEFAULT '' AFTER kind;
ALTER TABLE jobs ADD COLUMN IF NOT EXISTS scope ENUM('local','international') NOT NULL DEFAULT 'international' AFTER levels;
ALTER TABLE jobs ADD COLUMN IF NOT EXISTS verified_on DATE NULL AFTER deadline;

INSERT INTO jobs (title, kind, levels, scope, organisation, location, description, requirements, apply_url, allow_applications, member_only, is_public, verified_on)
SELECT s.* FROM (
  SELECT 'Chevening Scholarships' AS title, 'scholarship' AS kind, 'masters' AS levels, 'international' AS scope, 'UK Foreign, Commonwealth and Development Office (FCDO)' AS organisation, 'United Kingdom' AS location,
    'Fully funded one-year master''s degrees at UK universities for emerging leaders from around the world. Applications are made online on the official Chevening website.' AS description,
    'Check Who can apply and Eligible countries on the official site, including the work-experience requirement and your country''s status.' AS requirements,
    'https://www.chevening.org/scholarships/' AS apply_url, 0 AS allow_applications, 0 AS member_only, 1 AS is_public, '2026-11-19' AS verified_on
  UNION ALL SELECT 'Commonwealth Master''s and PhD Scholarships', 'scholarship', 'masters,phd', 'international', 'Commonwealth Scholarship Commission in the UK', 'United Kingdom',
    'Master''s and PhD scholarships for candidates from eligible Commonwealth countries, plus Shared Scholarships and Fellowships. Several award types are offered, each with its own call.',
    'Each award type lists its eligible countries and nominating process. Read the call for the award you want.',
    'https://cscuk.fcdo.gov.uk/scholarships/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'DAAD Scholarships for Germany', 'scholarship', 'masters,phd', 'international', 'German Academic Exchange Service (DAAD)', 'Germany',
    'The DAAD scholarship database lists funding for master''s study, doctoral research and research stays in Germany. Search it by your country, level and subject.',
    'Eligibility differs by programme. Use the database filters, then apply through the programme named there.',
    'https://www2.daad.de/deutschland/stipendium/datenbank/en/21148-scholarship-database/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Gates Cambridge Scholarships', 'scholarship', 'masters,phd', 'international', 'Gates Cambridge Trust', 'Cambridge, United Kingdom',
    'An international scholarship programme at the University of Cambridge for outstanding postgraduate students. Applicants apply to Cambridge and are considered for the scholarship.',
    'Read the Eligibility, Criteria and Timeline pages on the official site before you apply.',
    'https://www.gatescambridge.org/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Turkiye Scholarships (Turkiye Burslari)', 'scholarship', 'undergraduate,masters,phd', 'international', 'Government of Turkiye (YTB)', 'Turkiye',
    'Government scholarships for bachelor''s, master''s and doctoral study in Turkiye, with university placement and benefits such as a monthly stipend, accommodation, health insurance and flight tickets. Applications are free.',
    'Check the application calendar and the programme guide on the official site.',
    'https://www.turkiyeburslari.gov.tr/en', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Chinese Government Scholarship (CSC)', 'scholarship', 'undergraduate,masters,phd', 'international', 'China Scholarship Council', 'China',
    'Government scholarships for undergraduate, master''s and doctoral study at Chinese universities. Calls are usually announced early in the year.',
    'Read the current guide on the official portals for age limits, language requirements and the application route.',
    'https://www.campuschina.org/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'MEXT Japanese Government Scholarship', 'scholarship', 'undergraduate,masters,phd', 'international', 'Government of Japan (MEXT)', 'Japan',
    'Japanese government scholarships for international students at undergraduate, master''s and doctoral levels. Study in Japan publishes the official guide to the programme and how to apply.',
    'Read the official guidelines for your category and apply through the route they describe.',
    'https://www.studyinjapan.go.jp/en/planning/scholarships/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Erasmus Mundus Joint Masters Scholarships', 'scholarship', 'masters', 'international', 'European Commission (Erasmus+)', 'Europe',
    'Scholarships for international students on joint master''s degrees delivered by universities in several European countries. You apply directly to the joint programme you choose.',
    'Browse the official catalogue of programmes currently offering scholarships and read each programme''s call.',
    'https://www.eacea.ec.europa.eu/scholarships/emjmd-catalogue_en', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Australia Awards Africa Scholarships', 'scholarship', 'masters', 'international', 'Australian Government (DFAT)', 'Australia',
    'Master''s scholarships in Australia for citizens of eligible African countries, including Uganda. Awards are offered for a stated intake year.',
    'Applicants generally need a bachelor''s degree and must meet the requirements in the Policy Handbook on the official site.',
    'https://australiaawardsafrica.org/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Fulbright Foreign Student Program (Uganda)', 'scholarship', 'masters,phd', 'international', 'US Department of State, US Embassy Kampala', 'United States',
    'Funding for graduate study at US universities for Ugandan applicants. Applications for Uganda go through the US Embassy in Kampala and the Fulbright country page.',
    'Watch the Fulbright Uganda page and the US Embassy Uganda website for the next call and its requirements.',
    'https://us.fulbrightonline.org/countries/sub-saharan-africa/uganda/1575', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Mastercard Foundation Scholars Program at Makerere University', 'scholarship', 'undergraduate,masters', 'local', 'Mastercard Foundation and Makerere University', 'Kampala, Uganda',
    'Scholarships for undergraduate and master''s study at Makerere University for talented young Ugandans with financial need, including refugees and displaced youth. Applicants must also seek admission to the university.',
    'Read the call for your level on the official application portal. Applying for the scholarship does not replace the admission application.',
    'https://apply.mastercardfoundation.mak.ac.ug/scholarships', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Student loans for Ugandan students (HESFB)', 'scholarship', 'undergraduate', 'local', 'Higher Education Students Financing Board, Government of Uganda', 'Uganda',
    'The government scheme that provides loans and scholarships to Ugandan students in higher education. The scheme has been moving under the Ministry of Education and Sports, so confirm the current application route on the official sites.',
    'Check the current guidelines, eligible courses and application window on the official sites.',
    'https://hesfb.go.ug/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Gulu University scholarships and notices', 'scholarship', 'undergraduate,masters,phd', 'local', 'Gulu University', 'Gulu, Uganda',
    'Scholarship announcements for Gulu University students are published in the Scholarships category on the university website. Check the Notice Board too.',
    'Each announcement states its own requirements and closing date.',
    'https://gu.ac.ug/category/scholarships/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Mandela Washington Fellowship for Young African Leaders', 'fellowship', 'professional', 'international', 'US Department of State', 'United States',
    'A flagship US government leadership programme for young leaders from sub-Saharan Africa, including academic coursework, leadership training and networking in the United States.',
    'Applicants are young African leaders, generally aged 25 to 35, who live in sub-Saharan Africa. Read the current eligibility on the official site before you apply.',
    'https://www.mandelawashingtonfellowship.org/apply', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Africa Research Excellence Fund (AREF) fellowships', 'fellowship', 'phd,professional', 'international', 'Africa Research Excellence Fund', 'Africa and the United Kingdom',
    'Training, fellowships and grants that support African health researchers. Look at the current fellowship year and open calls on the official site.',
    'Eligibility and fellowship types change by call. Confirm them on the official site.',
    'https://africaresearchexcellencefund.org.uk/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Tony Elumelu Foundation Entrepreneurship Programme', 'grant', '', 'international', 'Tony Elumelu Foundation', 'Across Africa',
    'A programme for African entrepreneurs that combines training, mentoring and a $5,000 non-refundable seed grant for those who complete the stages. Applications are made online through TEFConnect.',
    'Applicants must be African, at least 18, living in Africa and building a business there, with an idea or a business no more than five years old. The application window has run from 1 January to 1 March.',
    'https://www.tonyelumelufoundation.org/tef-entrepreneurship-programme', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'UNDP GEF Small Grants Programme', 'grant', '', 'international', 'UNDP and the Global Environment Facility', 'Uganda and 120+ countries',
    'Small grants and technical support for community projects that protect the environment while improving livelihoods, in areas such as biodiversity, climate change and land degradation. Uganda is a participating country.',
    'Read the grant eligibility and How to apply pages, and contact the national coordinator for Uganda.',
    'https://sgp.undp.org/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Youth Livelihood Programme, UWEP and Emyooga (Uganda)', 'grant', '', 'local', 'Ministry of Gender, Labour and Social Development, Government of Uganda', 'Uganda',
    'Government programmes that finance livelihoods: the Youth Livelihood Programme for young people, the Uganda Women Entrepreneurship Programme for women, and Emyooga for enterprise groups through parish associations and constituency SACCOs.',
    'Each programme has its own guidelines. Start at your district or sub-county community development office and the Ministry''s website.',
    'https://mglsd.go.ug/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'United Nations Careers', 'job', 'professional', 'international', 'United Nations', 'Worldwide, including Uganda',
    'The official UN careers portal lists vacancies across the UN Secretariat and field offices, with roles for professionals and general staff.',
    'Create a profile and read each job opening for its requirements and closing date.',
    'https://careers.un.org/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'United Nations internships', 'internship', 'undergraduate,masters', 'international', 'United Nations', 'Worldwide, including Uganda',
    'Internship openings for students and recent graduates are advertised on the UN careers portal, in offices around the world.',
    'Each internship states its eligibility, duration and whether it is paid.',
    'https://careers.un.org/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'ReliefWeb Jobs', 'job', 'professional', 'international', 'ReliefWeb (UN OCHA)', 'Humanitarian and development roles, including Uganda',
    'The main job board for humanitarian and development organisations. Filter by country to see current vacancies in Uganda.',
    'Each vacancy lists its employer, requirements and how to apply.',
    'https://reliefweb.int/jobs', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'UNDP Careers', 'job', 'professional', 'international', 'United Nations Development Programme', 'Worldwide, including Uganda',
    'Vacancies, internships and fellowships at UNDP headquarters and country offices. Look for Uganda in the list of duty stations.',
    'Each opening states its requirements and closing date.',
    'https://www.undp.org/careers', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'BrighterMonday Uganda', 'job', '', 'local', 'BrighterMonday Uganda', 'Uganda',
    'Uganda''s popular job site, with vacancies across many industries that you can filter by experience level, plus job alerts and free skills courses.',
    'Create a free profile and apply to each employer through the site.',
    'https://www.brightermonday.co.ug/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Public service vacancies (Public Service Commission)', 'job', '', 'local', 'Uganda Public Service Commission', 'Uganda',
    'The Commission advertises vacancies in the Uganda public service. Check its website for current adverts and how to apply.',
    'Each advert states its qualifications, closing date and application route.',
    'https://www.psc.go.ug/', 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Gulu University jobs and careers', 'job', '', 'local', 'Gulu University', 'Gulu, Uganda',
    'Vacancies for academic and administrative staff are published in the Jobs and Careers section of the university website.',
    'Each advert states its requirements and closing date.',
    'https://gu.ac.ug/jobs-careers/', 0, 0, 1, '2026-11-19'
) s WHERE NOT EXISTS (SELECT 1 FROM jobs j WHERE j.title = s.title);
