-- 010: more grants and funding for businesses, researchers, young people and community groups, each linking to its official site.
-- Calls and deadlines change every cycle, so no closing dates are stored. verified_on is set only where the official page was seen.

INSERT INTO jobs (title, kind, levels, scope, organisation, location, description, requirements, apply_url, deadline, allow_applications, member_only, is_public, verified_on)
SELECT s.* FROM (
  SELECT 'USADF grants for African-owned enterprises (Uganda)' AS title, 'grant' AS kind, '' AS levels, 'local' AS scope, 'US African Development Foundation' AS organisation, 'Uganda' AS location,
    'Grants for African-owned small businesses and cooperatives, announced through calls for applications, often with partners. Women- and youth-owned enterprises have received priority in past calls.' AS description,
    'Past Uganda calls were limited to privately owned, African-owned businesses registered and operating in Uganda. Application forms and open calls are posted on the official apply page.' AS requirements,
    'https://www.usadf.gov/apply/' AS apply_url, NULL AS deadline, 0 AS allow_applications, 0 AS member_only, 1 AS is_public, '2026-11-19' AS verified_on
  UNION ALL SELECT 'Uganda Development Bank loans for SMEs, women and youth', 'grant', '', 'local', 'Uganda Development Bank', 'Uganda',
    'The government-owned development bank offers affordable long-term loans, including the SME Kazi Loan, products for women and for youth, and equity investment in promising businesses. These are loans, not grants.',
    'Terms, interest rates and eligibility are set by the bank and change. Read the product pages and ask the bank before applying.',
    'https://www.udbl.co.ug/service/sme-kazi-loans/', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Grand Challenges Africa grants', 'grant', 'professional', 'international', 'Science for Africa Foundation, with Wellcome and partners', 'Africa',
    'Seed grants of up to US$100,000 and transition-to-scale grants of up to US$200,000 for African innovators working on health and development challenges, awarded in rounds. Past rounds were open to African organisations including non-profits, universities and research institutions.',
    'Each round has its own rules and guidelines. Open rounds and closing dates are listed on the official funding page.',
    'https://scienceforafrica.foundation/funding', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Cartier Women''s Initiative', 'grant', 'professional', 'international', 'Cartier Women''s Initiative', 'Global',
    'An annual programme for women-led and women-owned businesses with a strong social or environmental impact. Winners receive prize money, coaching and a global network.',
    'Applications are made in an annual cycle on the official site. Read the eligibility rules for the current edition.',
    'https://www.cartierwomensinitiative.com/', NULL, 0, 0, 1, '2026-11-19'
  UNION ALL SELECT 'Wellcome research funding', 'grant', 'phd,professional', 'international', 'Wellcome', 'Global, including Africa',
    'Funding for research in health, including career development awards for early- and mid-career researchers and discovery awards for bold ideas. Wellcome also co-funds African programmes.',
    'Each scheme has its own eligibility, host institution rules and closing dates. Read the funding pages before applying.',
    'https://wellcome.org/grant-funding', NULL, 0, 0, 1, NULL
  UNION ALL SELECT 'The Anzisha Prize for young African entrepreneurs', 'grant', 'undergraduate,professional', 'international', 'African Leadership Academy and the Mastercard Foundation', 'Africa',
    'A prize for young African entrepreneurs under a set age who are building a business, with funding, a fellowship and support from a network of founders.',
    'The age limit, business stage and dates are set for each call. Read the current call on the official site.',
    'https://anzishaprize.org/', NULL, 0, 0, 1, NULL
  UNION ALL SELECT 'Global Fund for Women grants', 'grant', '', 'international', 'Global Fund for Women', 'Global, including Africa',
    'Grants to women-led and gender justice groups for work on women''s rights and equality. The fund usually invites groups to apply when it opens a call.',
    'Check the official site to see whether applications are open and what it currently funds.',
    'https://www.globalfundforwomen.org/', NULL, 0, 0, 1, NULL
  UNION ALL SELECT 'Fund for Global Human Rights', 'grant', '', 'international', 'Fund for Global Human Rights', 'Global, including Africa',
    'Funding and support for local human rights groups in countries where defenders face risk. Support is given through its regional programmes.',
    'The fund does not run open calls for every region. Read the official page to see how it identifies and supports groups.',
    'https://globalhumanrights.org/', NULL, 0, 0, 1, NULL
) s WHERE NOT EXISTS (SELECT 1 FROM jobs j WHERE j.title = s.title);
