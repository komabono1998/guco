<?php
/**
 * Editable website content. Every block has a default (the text shipped with the site);
 * edits made in Admin > Site content are stored in site_content and override the default.
 *
 * Block types: text (one line), rich (paragraphs, ## headings, - bullets, **bold**, [link](url)),
 * list (one item per line; "A | B" pairs where noted) and image.
 */

function content_registry(): array
{
    static $r = null;
    if ($r !== null) {
        return $r;
    }
    $r = [
        'home' => ['label' => 'Home page', 'url' => 'index.php', 'blocks' => [
            'home.hero_tagline' => ['Hero tagline', 'text', 'Community Transformation Actualized'],
            'home.hero_title' => ['Hero headline', 'text', 'Together for Alumni Excellence and Community Transformation'],
            'home.hero_text' => ['Hero supporting text', 'rich', 'Gulu University Convocation (GUCO) brings together Gulu University alumni, eligible university community members and partners to strengthen alumni networks, support the University\'s development and contribute knowledge, talent and resources to the wider community.'],
            'home.hero_btn1' => ['Primary button label', 'text', 'JOIN GUCO'],
            'home.hero_btn2' => ['Secondary button label', 'text', 'EXPLORE OUR WORK'],
            'home.hero_image' => ['Hero image (replaces the logo in the hero)', 'image', ''],
            'home.welcome_title' => ['Welcome heading', 'text', 'Welcome'],
            'home.welcome_text' => ['Welcome message', 'rich', 'Welcome to the official website of Gulu University Convocation. GUCO is the umbrella association established to mobilise, coordinate and inspire alumni and the wider University community to contribute to intellectual development, university advancement and community service. This platform connects alumni with one another and with opportunities to participate in the life and development of Gulu University.'],
            'home.stats' => ['Hero statistics (shown under the headline)', 'list', "2023 | Constitution in force\n4 | Membership categories\n9 | Executive Committee offices\n4 | Governing organs", 'One per line: Value | Label. Use only figures you can stand behind.'],
            'home.who_title' => ['Who we are: heading', 'text', 'Who We Are'],
            'home.who_text' => ['Who we are: introduction', 'rich', 'Gulu University Convocation (GUCO) is the alumni body of Gulu University, bringing graduates and the wider University community together to support the University and the communities it serves.'],
            'home.programmes_title' => ['Programmes section heading', 'text', 'Our Programme Areas'],
            'home.help_title' => ['"How you can help" heading', 'text', 'How You Can Get Involved'],
            'home.cards' => ['"How you can help" cards', 'list', "Join GUCO | Apply for Founder, Ordinary, Honorary or Life membership and get your membership card. | register.php\nRenew membership | Keep your card valid by submitting your annual subscription. | member/payments.php#renew\nJoin a chapter | Connect with alumni by region, college or profession. | chapters.php\nPartner or support | Collaborate with GUCO on approved initiatives and events. | partnerships.php", 'One card per line: Title | Description | page link (e.g. about.php).'],
            'home.news_heading' => ['News section heading', 'text', 'News and stories'],
            'home.events_heading' => ['Events section heading', 'text', 'Upcoming events'],
            'home.cta_title' => ['Closing call to action: heading', 'text', 'Your connection with Gulu University continues after graduation'],
            'home.cta_text' => ['Closing call to action: text', 'rich', 'Join the Convocation, reconnect with fellow alumni and contribute to a stronger University and more empowered communities.'],
        ]],
        'about' => ['label' => 'About GUCO', 'url' => 'about.php', 'blocks' => [
            'about.overview' => ['Overview', 'rich', 'Gulu University Convocation (GUCO) is the umbrella association for alumni of Gulu University and other eligible members recognised under its Constitution. The Constitution describes GUCO as a platform for mobilisation, coordination and inspiration, bringing alumni and the University closer together in pursuit of intellectual development, institutional advancement and service to the community.'],
            'about.image' => ['Overview photo (leave empty to use the default event photo)', 'image', ''],
            'about.vision' => ['Vision', 'rich', 'To be an umbrella association for the alumni of Gulu University, mobilising, coordinating and inspiring alumni to participate in the development of enhanced intellectualism.'],
            'about.mission' => ['Mission', 'rich', 'To build a vibrant association that safeguards the best interests of its members and gives them a platform to use their talents and resources to support Gulu University in achieving international distinction in quality teaching, research and service to the community.'],
            'about.values' => ['Core values', 'list', "Integrity\nGender responsiveness\nProfessionalism\nTeamwork\nExcellence\nInclusiveness", 'One value per line.'],
            'about.objectives' => ['Objectives', 'list', "Develop alumni programmes that promote effective networks among members.\nPromote the academic, physical, moral and spiritual growth of members of Gulu University.\nSupport the University's socio-cultural and co-curricular activities.\nInvolve alumni in matters relating to University governance, administration and academic interests.\nPromote and support the formation of local and international alumni chapters.\nEnhance Gulu University's involvement in community service.\nInitiate, organise and coordinate fundraising activities for Gulu University.\nSupport University programmes and awards, including outstanding achievement and distinguished service recognition.\nAdvance the interests and welfare of members.\nBuild relationships with alumni associations at other institutions locally and internationally.\nProject a positive image of Gulu University, coordinate chapter activities and create local and international partnerships.", 'One objective per line.'],
            'about.mandate' => ['Our mandate', 'rich', 'GUCO\'s objectives include strengthening alumni relationships, supporting University development, promoting community service, mobilising resources and creating opportunities for alumni participation. The Association is non-political under Article 4 of the Constitution.'],
            'about.constitution' => ['Constitution box', 'rich', 'The Constitution is GUCO\'s foundational governance document. It sets out the Association\'s objectives, membership requirements, member rights and obligations, leadership structures, elections, meetings, financial controls and amendment procedures.'],
        ]],
        'membership' => ['label' => 'Membership', 'url' => 'membership.php', 'blocks' => [
            'membership.intro' => ['Introduction', 'rich', 'Membership connects eligible alumni and other qualifying members with a shared platform for networking, participation in Association activities and support for Gulu University. The Constitution provides for Founder, Ordinary, Honorary and Life Membership.'],
            'membership.eligibility' => ['Eligibility and registration', 'rich', 'Article 5 states that membership is open to eligible alumni above 18 years, subject to the Constitution\'s requirements. A person is deemed a member upon registration and full payment of the applicable membership and subscription fees for the relevant category.'],
            'membership.rights' => ['Rights of members', 'list', "Attend Annual General Meetings.\nVote in general elections where the member meets the Constitution's active-member requirements.\nStand for office and nominate or be nominated where eligible.\nParticipate in Association functions and activities, subject to applicable rules.\nAppeal and be heard in accordance with the Constitution."],
            'membership.obligations' => ['Obligations of members', 'list', "Pay the applicable membership fees and annual subscriptions.\nRefrain from using GUCO resources and assets for personal enrichment.\nObserve the Constitution and applicable Association rules."],
            'membership.benefits' => ['Membership benefits', 'rich', 'The Constitution provides for participation in Association activities and, for fully paid-up members whose dues are not in arrears, access to Association journals, bulletins, printed materials or other membership benefits that may apply. GUCO may publish additional benefits only after they have been approved and are actually available.'],
            'membership.faq' => ['FAQ', 'list', "Who can apply? | People who meet the eligibility criteria in Article 5 of the GUCO Constitution.\nDoes submitting a form make me a member? | The Constitution states that registration and full payment of the applicable fees are required. The Secretariat confirms when an application is approved.\nCan members vote? | Voting rights depend on the Constitution and active-member requirements.\nHow do I confirm the current membership fees? | Contact the GUCO Secretariat before making payment. Do not rely on an old website amount without confirmation.\nCan I join a chapter? | Members can explore recognised chapters and contact the Secretariat about existing or proposed chapters.", 'One question per line: Question | Answer.'],
        ]],
        'leadership' => ['label' => 'Leadership & Governance', 'url' => 'leadership.php', 'blocks' => [
            'leadership.intro' => ['Introduction', 'rich', 'Article 10 identifies four leadership organs: the General Assembly, the Board, the Executive Committee and the Disciplinary Committee. Office bearers are published after formal confirmation by the Secretariat; positions not yet confirmed show as "To be announced".'],
            'leadership.assembly' => ['General Assembly', 'rich', 'The General Assembly is the supreme organ of GUCO. Its responsibilities include considering annual and financial reports, approving development plans and budgets, electing Executive Committee members, addressing constitutional matters and carrying out other functions that support the Association\'s progress. The Constitution identifies the Chancellor of Gulu University as Patron of the Association.'],
            'leadership.board' => ['The Board', 'rich', 'The Board provides advisory services to the Executive Committee. Article 12 describes its composition as an elected prominent Gulu University alumnus as Board Chairperson, the Vice Chancellor and four members nominated by and approved by the General Assembly, two of whom shall be from outside Gulu University.'],
            'leadership.executive' => ['Executive Committee', 'rich', 'Article 14 specifies nine officials. The Constitution states that the Chairperson and Vice Chairperson shall be of opposite gender. The Executive Committee\'s term is four years, with eligibility for re-election for one further term in the same office.'],
            'leadership.disciplinary' => ['Disciplinary Committee', 'rich', 'The Disciplinary Committee is responsible for the general disciplinary conduct of members. The Constitution provides for a five-member committee elected from active members at the AGM, with disciplinary measures subject to the procedures and approvals described in the Constitution.'],
            'leadership.elections' => ['Elections and accountability', 'rich', 'The Constitution provides for elections at the General Assembly, secret-ballot voting by eligible fully paid-up members physically present, a four-year term for Executive Committee office bearers and an elections appeals process. The website publishes only officially approved election notices, candidate information, results and appeal guidance.'],
            'leadership.notice' => ['Governance notice', 'rich', 'The website summarises selected provisions for public information. The Constitution remains the authoritative source. Where a summary and the Constitution differ, the Constitution and applicable University rules govern.'],
        ]],
        'programmes' => ['label' => 'Programmes & Impact', 'url' => 'programmes.php', 'blocks' => [
            'programmes.intro' => ['Introduction', 'rich', 'GUCO\'s constitutional objectives provide the foundation for its programme areas. Activities listed under impact reports below have been approved and are active.'],
            'programmes.areas' => ['Programme areas', 'list', "Alumni Networking and Engagement | Create opportunities for alumni to reconnect, share professional experience, build relationships and participate in the life of Gulu University.\nMentorship and Career Connections | Facilitate opportunities for alumni to share knowledge and professional experience with students and fellow graduates.\nUniversity Development and Academic Support | Mobilise alumni expertise, networks and resources in support of the University's academic interests, research, teaching and institutional development.\nCommunity Service | Promote alumni participation in community service and initiatives that connect University knowledge and experience with community needs.\nStudent Support and Scholarships | Where approved and funded, coordinate alumni support for student activities, scholarships, research and faculty or college awards.\nAwards and Recognition | Support approved recognition of outstanding achievements and distinguished service by alumni.\nPartnerships and Fundraising | Build relationships with local and international partners and mobilise support for approved GUCO and University priorities.", 'One area per line: Title | Description.'],
        ]],
        'chapters' => ['label' => 'Alumni Chapters', 'url' => 'chapters.php', 'blocks' => [
            'chapters.intro' => ['Introduction', 'rich', 'Alumni chapters help members stay connected through shared disciplines, colleges, faculties, departments, graduation years, geographical areas or other criteria recognised by GUCO. The Constitution places chapters under the umbrella of the Association and requires them to be registered.'],
            'chapters.activities' => ['What chapters do', 'list', "Recruit members and promote participation in GUCO.\nPlan meetings, networking activities and events.\nBuild relationships between alumni and current students.\nSupport approved scholarships, student activities, faculty awards and research.\nVolunteer in University programmes and act as ambassadors for Gulu University.\nSubmit updated membership lists and required activity reports to the Association.\nCoordinate chapter activities with GUCO and maintain contact with the Secretariat and Executive Committee."],
            'chapters.leadership' => ['Chapter leadership', 'rich', 'The Constitution lists a Chapter Chairperson, Vice Chairperson, Secretary, Treasurer, Organising Secretary and ten members for a Chapter Executive Committee. Chapter leaders are expected to file names, submit quarterly activity reports and submit meeting minutes within the period set out in the Constitution.'],
            'chapters.cta' => ['Find or establish a chapter', 'rich', 'Interested in joining a chapter or organising alumni in your region, profession or academic community? Contact the Secretariat for the approved registration procedure.'],
        ]],
        'awards' => ['label' => 'Awards & Recognition', 'url' => 'awards.php', 'blocks' => [
            'awards.intro' => ['Introduction', 'rich', 'GUCO\'s Constitution identifies support for University awards and recognition programmes among its objectives. This page explains each approved award, its purpose, eligibility, nomination process and announcement schedule.'],
            'awards.image' => ['Feature photo (leave empty to use the default event photo)', 'image', ''],
            'awards.feature' => ['Feature text', 'rich', 'GUCO celebrates alumni whose achievements and service advance Gulu University and the community. Award details, eligibility and nomination status are published below once approved.'],
            'awards.notice' => ['Notice', 'rich', 'Nominations are only open where an award is shown as "Nominations open". No award ceremony is implied until the details have been approved and announced.'],
        ]],
        'partnerships' => ['label' => 'Partnerships & Support', 'url' => 'partnerships.php', 'blocks' => [
            'partnerships.intro' => ['Introduction', 'rich', 'GUCO seeks to strengthen connections among alumni, Gulu University and the wider community. Partnerships can help advance alumni engagement, University development, academic interests and community service in line with the Association\'s objectives.'],
            'partnerships.ways' => ['Ways to collaborate', 'list', "Institutional and corporate partnerships.\nAlumni mentorship and knowledge-sharing opportunities.\nSupport for approved student and community initiatives.\nResearch, innovation and academic collaboration.\nSponsorship of approved events and recognition programmes.\nFundraising and in-kind contributions for authorised projects."],
            'partnerships.support' => ['Support GUCO', 'rich', 'Alumni, organisations and friends who wish to support GUCO or Gulu University initiatives are invited to contact the Secretariat to discuss approved priorities and contribution procedures. All contributions must be handled through officially authorised channels and accounted for under the Association\'s financial procedures.'],
        ]],
        'events' => ['label' => 'Events & Contact', 'url' => 'events.php', 'blocks' => [
            'events.agm' => ['Annual General Meeting notice', 'rich', 'The Constitution states that the AGM should be held not later than November each year. Written notice, accompanied by the annual statement of accounts and agenda, is sent at least 21 days before the meeting. Each AGM notice is published here only after it has been formally issued by the Association.'],
            'contact.intro' => ['Contact page introduction', 'rich', 'Have a question about membership, alumni chapters, events or working with GUCO? Contact the Secretariat through the official channels below.'],
            'contact.address' => ['Official address', 'list', "Gulu University Convocation (GUCO)\nGulu University\nP.O. Box 166\nLaroo Road, Gulu City, Uganda", 'One address line per line.'],
            'resources.intro' => ['Resources page introduction', 'rich', 'Official documents and publications of Gulu University Convocation.'],
        ]],
        'footer' => ['label' => 'Footer', 'url' => 'index.php', 'blocks' => [
            'footer.about' => ['About text', 'rich', 'An alumni association committed to mobilising alumni, supporting Gulu University and contributing to community transformation.'],
            'footer.address' => ['Address line', 'text', 'Gulu University, P.O. Box 166, Laroo Road, Gulu City, Uganda.'],
        ]],
        'legal' => ['label' => 'Privacy & Accessibility', 'url' => 'privacy.php', 'blocks' => [
            'privacy.body' => ['Privacy notice', 'rich', <<<'TXT'
This notice is a working draft for review and approval by the GUCO Secretariat before the member portal is launched publicly.

## What we collect
- Membership applications and profiles: name, contact details, date of birth, address, academic and employment details, eligibility details, payment details and uploaded proof of payment, and an optional card photo.
- Enquiries, event registrations and newsletter subscriptions: the details you enter in those forms.

We collect only what is needed to process your request or membership. We do not collect national identity numbers or payment card details through this website.

## How we use it
- To verify eligibility, process applications and payments, and manage membership, subscriptions and membership cards.
- To respond to enquiries, administer events and send the newsletter to subscribers.
- To keep records as required by the Constitution and for audit of sensitive changes.

## Who can see it
Access is restricted by role to authorised GUCO officials. Photos and proofs of payment are visible only to you and authorised officials. The members' directory shows only the name, programme, graduation year, profession and location of members who have given consent; phone numbers and email addresses are never shown. Public card verification shows only a member's name, category and card status.

## Your choices
- Change your profile and withdraw directory consent at any time in the Member Portal.
- Ask the Secretariat to correct or remove your data through the contact form.
- Unsubscribe from the newsletter by contacting the Secretariat.

## Cookies
This website uses a single session cookie, needed to keep you logged in and to protect forms. It does not use advertising or tracking cookies.
TXT],
            'accessibility.body' => ['Accessibility statement', 'rich', <<<'TXT'
GUCO aims to make this website usable by everyone, including people who use assistive technology.

- Clear headings, a "skip to main content" link and full keyboard navigation, including the menus.
- Text contrast designed to meet WCAG AA, visible focus indicators and responsive layouts for phones, tablets and desktops.
- Meaningful alternative text for images and labelled form fields.
- Event pages show accessibility information where the organiser has supplied it.

If you meet a barrier or need information in another format, please contact the Secretariat.
TXT],
        ]],
    ];
    return $r;
}

function content_block(string $key): ?array
{
    foreach (content_registry() as $page) {
        if (isset($page['blocks'][$key])) {
            $b = $page['blocks'][$key];
            return ['label' => $b[0], 'type' => $b[1], 'default' => $b[2], 'hint' => $b[3] ?? ''];
        }
    }
    return null;
}

/** Current value of a block: the saved override, or the default. */
function c(string $key): string
{
    static $saved = null;
    if ($saved === null) {
        try {
            $saved = db()->query('SELECT k, v FROM site_content')->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Throwable $e) {
            $saved = [];
        }
    }
    if (array_key_exists($key, $saved)) {
        return (string) $saved[$key];
    }
    $b = content_block($key);
    return $b ? (string) $b['default'] : '';
}

/** One-line escaped text. */
function ct(string $key): string
{
    return e(c($key));
}

/** Formatted rich text as safe HTML. */
function cr(string $key): string
{
    return render_text(c($key));
}

/** Non-empty trimmed lines of a list block. */
function c_lines(string $key): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', c($key))), fn($l) => $l !== ''));
}

/** Lines split on "|" into trimmed columns, padded to $n columns. */
function c_pairs(string $key, int $n = 2): array
{
    return array_map(function ($line) use ($n) {
        $parts = array_map('trim', explode('|', $line, $n));
        return array_pad($parts, $n, '');
    }, c_lines($key));
}

/** URL of an image block, or null when none was uploaded. */
function c_image(string $key): ?string
{
    $v = c($key);
    return $v !== '' ? url('uploads/' . rawurlencode($v)) : null;
}

function safe_link(string $href): ?string
{
    $href = trim($href);
    if (preg_match('#^(https?://|mailto:|/|\#)#i', $href) || preg_match('#^[A-Za-z0-9_\-./?=&\#%+]+$#', $href)) {
        return preg_match('#^(https?://|mailto:|/|\#)#i', $href) ? $href : url($href);
    }
    return null;
}

/** Small safe formatter: paragraphs, "## " headings, "- " bullets, **bold**, [label](link). All input is escaped first. */
function render_text(string $s): string
{
    $inline = function (string $t): string {
        $t = e($t);
        $t = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $t);
        return preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) {
            $href = safe_link(html_entity_decode($m[2], ENT_QUOTES));
            return $href ? '<a href="' . e($href) . '">' . $m[1] . '</a>' : $m[1];
        }, $t);
    };
    $out = '';
    foreach (preg_split('/\R{2,}/', trim($s)) as $block) {
        $lines = preg_split('/\R/', trim($block));
        if ($lines === [''] || !$lines) {
            continue;
        }
        if (count(array_filter($lines, fn($l) => str_starts_with(ltrim($l), '- '))) === count($lines)) {
            $out .= '<ul class="checks">' . implode('', array_map(fn($l) => '<li>' . $inline(substr(ltrim($l), 2)) . '</li>', $lines)) . '</ul>';
        } elseif (str_starts_with($lines[0], '## ')) {
            $out .= '<h2>' . $inline(substr(array_shift($lines), 3)) . '</h2>';
            if ($lines) {
                $out .= render_text(implode("\n", $lines));
            }
        } else {
            $out .= '<p>' . implode('<br>', array_map($inline, $lines)) . '</p>';
        }
    }
    return $out;
}
