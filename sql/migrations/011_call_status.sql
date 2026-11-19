-- 011: show whether each opportunity is open, running all year, opening soon or closed, with a deadline and a note.
-- Only statuses that were seen in a source on 19 November 2026 are set; everything else stays "check the official page".

ALTER TABLE jobs ADD COLUMN IF NOT EXISTS call_status ENUM('open','rolling','upcoming','closed','unconfirmed') NOT NULL DEFAULT 'unconfirmed' AFTER deadline;

ALTER TABLE jobs ADD COLUMN IF NOT EXISTS call_note VARCHAR(255) NULL AFTER call_status;

ALTER TABLE jobs ADD COLUMN IF NOT EXISTS status_checked_on DATE NULL AFTER call_note;

-- Employers, job boards and internships post vacancies all year.
UPDATE jobs SET call_status = 'rolling', call_note = 'New vacancies are posted all year. Each vacancy has its own closing date.', status_checked_on = '2026-11-19' WHERE kind IN ('job', 'internship') AND call_status = 'unconfirmed';

-- Ongoing Ugandan government programmes and the development bank lend or fund throughout the year.
UPDATE jobs SET call_status = 'rolling', call_note = 'Ongoing programmes. Ask the district or ministry office about the current intake.', status_checked_on = '2026-11-19' WHERE title = 'Youth Livelihood Programme, UWEP and Emyooga (Uganda)' AND call_status = 'unconfirmed';

UPDATE jobs SET call_status = 'rolling', call_note = 'Loan applications are taken through the bank all year.', status_checked_on = '2026-11-19' WHERE title = 'Uganda Development Bank loans for SMEs, women and youth' AND call_status = 'unconfirmed';

-- Calls whose dates were reported on 19 November 2026. Third-party reports are marked as such in the note.
UPDATE jobs SET call_status = 'open', deadline = '2026-11-20', call_note = 'Closes on 20 November 2026.', status_checked_on = '2026-11-19' WHERE title = 'Mo Ibrahim Foundation Ibrahim Leadership Fellowship';

UPDATE jobs SET call_status = 'open', deadline = '2026-12-04', call_note = '2027-2028 call is open. The priority deadline was reported as 23 November 2026 and the final deadline as 4 December 2026.', status_checked_on = '2026-11-19' WHERE title = 'Obama Foundation Leaders: Africa';

UPDATE jobs SET call_status = 'open', deadline = '2026-12-08', call_note = '2027-28 round. The deadline depends on the course: 8 December 2026 or 6 January 2027. US citizens living in the US had 14 October 2026.', status_checked_on = '2026-11-19' WHERE title = 'Gates Cambridge Scholarships';

UPDATE jobs SET call_status = 'closed', call_note = 'The 2027/28 round closed on 6 October 2026 according to most sources (deadlines can differ by country). Rounds usually open in August.', status_checked_on = '2026-11-19' WHERE title = 'Chevening Scholarships';

UPDATE jobs SET call_status = 'closed', call_note = 'The 2027 round was reported to close on 13 October 2026 by third-party sites. Confirm the next call on the official page.', status_checked_on = '2026-11-19' WHERE title = 'Mandela Washington Fellowship for Young African Leaders';

UPDATE jobs SET call_status = 'upcoming', call_note = 'The 2027 intake is closed. The official page says applications for the 2028 intake open in February 2027.', status_checked_on = '2026-11-19' WHERE title = 'Australia Awards Africa Scholarships';

UPDATE jobs SET call_status = 'upcoming', call_note = 'The 2026 round ran from 1 January to 1 March 2026. The 2027 dates have not been confirmed, but the programme usually opens in January.', status_checked_on = '2026-11-19' WHERE title = 'Tony Elumelu Foundation Entrepreneurship Programme';

UPDATE jobs SET call_status = 'upcoming', call_note = 'The 2026 round closed on 20 February 2026. The 2027 dates are not confirmed yet. The official announcement usually appears around the end of the year.', status_checked_on = '2026-11-19' WHERE title = 'Turkiye Scholarships (Turkiye Burslari)';

UPDATE jobs SET call_note = 'Deadlines are set by each course. One reported example is 15 October 2026 for study starting in October 2027. Check the course you want.', status_checked_on = '2026-11-19' WHERE title = 'DAAD Scholarships for Germany';
