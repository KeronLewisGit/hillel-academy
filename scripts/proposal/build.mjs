// Builds the client proposal (letter-size PDF, in Hillel Academy's own palette and fonts)
// from the screenshots taken by capture.mjs.
// Usage: npm run proposal  (or node scripts/proposal/build.mjs after a capture)
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';

const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const SHOTS = path.resolve('dist/proposal/shots');
const chunks = JSON.parse(fs.readFileSync(path.join(SHOTS, 'chunks.json'), 'utf8'));
const OUT_HTML = path.resolve('dist/proposal/proposal.html');
const OUT_PDF = path.resolve('dist/proposal/Hillel-Academy-Website-Proposal.pdf');
const DATE = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
const TESTS = process.env.TEST_COUNT ?? '96';
const img = (f) => 'file://' + f;
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

// The site's own self-hosted fonts (built by Vite into public/build/assets).
const ASSETS = path.resolve('public/build/assets');
const font = (prefix) => {
  const f = fs.readdirSync(ASSETS).find(n => n.startsWith(prefix) && n.endsWith('.woff2'));
  if (!f) throw new Error(`Font ${prefix} not found in public/build/assets – run npm run build first`);
  return 'file://' + path.join(ASSETS, f);
};
const FONTS = `
  @font-face { font-family: "Young Serif"; font-weight: 400; src: url("${font('young-serif-400-normal')}") format("woff2"); }
  ${[400, 500, 600, 700, 800].map(w => `@font-face { font-family: "Plus Jakarta Sans"; font-weight: ${w}; src: url("${font(`plus-jakarta-sans-${w}-normal`)}") format("woff2"); }`).join('\n  ')}`;

/** Hand-written captions per slice (key = screen name, index = slice number), from what each slice shows. */
const CAPTIONS = {
  home: [
    'Hero with the school’s own photograph of students spelling out HILLEL, the founding line from the school’s copy, and the two actions that matter most: start an application or book a campus visit. Below, the “at a glance” facts: 1969, about 800 students, IMYC · IGCSE · IB, NEASC.',
    '“One campus, every stage”: Prep School, Middle School and IB Diploma cards, each linking to its section on the Academics page.',
    '“Apply online, at your own pace”: the four steps from visit to decision, with the international tuition range as published by Hillel for 2026/27.',
    'The campus (“Thirteen acres in the hills above Kingston”), the latest news and the next calendar dates, all pulled from the dashboard.',
    'Campus-visit call to action and the footer with both phone lines, the admissions email, directions and the school’s social links.',
  ],
  about: [
    'About page: the school’s story from its 1969 founding, key facts, vision and mission, taken from Hillel’s own published wording.',
    'Hillel PRIDE values (Personal Responsibility, Respect, Integrity, Determination, Empathy) with the school’s own photographs.',
  ],
  academics: [
    'Academics hero and quick links to each stage, then the Prep School (K1 to Grade 6) and the National Standards Curriculum.',
    'Middle School (IMYC), Cambridge IGCSE and the IB Diploma, each with its ages, grades and a photograph, then a “questions about the curriculum?” prompt.',
  ],
  admissions: [
    'Admissions hero with jump links, then “How to apply” in six numbered steps that match the online application and the family portal.',
    'What the entrance assessment involves for each age group, worded from Hillel’s admissions packages, then “Which grade?”: sections, grades and typical ages from the published K1 age rule.',
    'The documents families upload, then tuition for 2026/27 per term in J$ and US$, as published by Hillel, with the enrolment-fee and sibling notes. The office edits this table in Settings.',
    'Admissions dates from the calendar and a closing call to action with the admissions phone and email.',
  ],
  'school-life': [
    'School life: sport, arts and service activities for the Prep and High Schools, from the school’s own lists.',
    'Photographs (Manning Cup squad, drama), the four houses (Sinai, Masada, Maccabees and Carmel) and upcoming fixtures, shows and events from the calendar.',
  ],
  calendar: [
    'Public calendar filtered by category (admissions, academic, sport, arts, community, school closed). Dates are Hillel’s 2026/27 calendar as published; the “demo session” lines are sample assessment sessions.',
    'The calendar continues month by month through the school year.',
  ],
  news: ['News and events: stories posted from the dashboard, with the next calendar dates beside them. The three stories shown are sample posts about the new site.'],
  visit: ['Campus visits: families pick one of the tour times the office publishes, see how many places are left and book in one form. A confirmation email follows. Times shown are sample sessions.'],
  apply: ['Starting an application: name, email, child and grade. The family receives a secure sign-in link by email and continues in the portal. No password to create.'],
  careers: ['Teaching vacancies with a general application route. The two posts shown are sample vacancies.'],
  job: ['A vacancy page with structured data for Google job search and an “Apply for this post” form with CV upload (sample vacancy).'],
  contact: ['Contact page with the admissions and main-office numbers, both school-office lines, the fees email and a message form that lands in the enquiries inbox.'],
  'family-login': ['Family portal sign-in: enter an email and receive a single-use link. The answer is identical whether or not the address is known, so the form reveals nothing.'],
  'family-dashboard': ['Family dashboard: every child’s application with its plain-language status, and “Applying for another child?” which copies the family’s details across. Sample family.'],
  'family-step-1': ['Online application, step 1 of 5 (Student). Each step saves on its own, and “Save and finish later” emails a fresh link.'],
  'family-step-2': ['Step 2 (Parents & guardians) with an optional second contact. Answers are validated as the family goes, with clear messages; the step tabs show what is done.'],
  'family-booking': ['Once documents are complete, the family books an entrance assessment from the sessions the office publishes (places left are shown and seats are locked when booking), next to the document checklist and progress tracker. Sample data.'],
  'family-offer': ['With a place offered, the family accepts or declines the offer online before the reply-by date. Accepting needs a confirmation tick; an expired offer gives its seat back to the grade. Sample data.'],
  'admin-dashboard': [
    'Admissions overview: applications waiting for document review lead the page, then enquiries, assessment-ready families, decisions due and offers awaiting a reply, the weekly submissions chart, the pipeline by stage, the next sessions and tours, and the latest submitted applications with their document status.',
  ],
  'admin-board': ['Pipeline board: drag a card between columns (Submitted, Documents complete, Assessment booked, Assessed and on to Offered, Waitlisted, Accepted, Enrolled). An offer that would overfill a grade is refused with the reason.'],
  'admin-applications': ['Applications list with a count per status, search, grade and year filters, “documents to review”, CSV export for the office and pagination.'],
  'admin-application': ['One application: accept or return each document with a reason, student and family details, the timeline, the next step with seats free in the grade, an email composer with templates, and the owner and staff notes.'],
  'admin-capacity': ['Capacity and waitlist: seats per grade for each entry year, with offers, acceptances, enrolments, free seats and pipeline counts, and below it each grade’s waiting list in order, with a one-click offer when a seat frees.'],
  'admin-assessments': ['Assessment sessions with who is booked, and a form to add a session (or repeat it weekly) for chosen grades.'],
  'admin-enquiries': ['Enquiries inbox: admissions questions, campus-visit bookings and general messages, each marked handled when done. Sample enquiries.'],
  'admin-templates': ['Email templates the office edits: automatic ones for each pipeline step and one-off templates, with placeholders for names, dates and links.'],
  'admin-teachers': ['Teacher recruitment: applicants by status and vacancy, with CVs kept private to administrators. Sample applicants.'],
  'admin-settings': ['Settings: phone numbers, admissions email, whether online applications are open, entry years, offer window, alert addresses and the fees wording.', 'The fee table editor: each row is saved on its own and shown on the Admissions page.'],
};

/** Some long pages only need their first slices in a proposal. */
const MAX_SLICES = { calendar: 1 };

const SECTIONS = [
  { id: 'public', eyebrow: 'Part 1', title: 'The public website', intro: 'A refreshed hillelacademyjm.com: the same school, its own words and photographs, but every admissions fact on a web page instead of in a PDF, and every page built for a parent’s phone.', screens: [
    ['home', 'Home page', ['The school’s own photography and wording, with “Start an application” and “Book a campus visit” on every screen.', 'News and calendar dates are published from the dashboard, so the home page stays current without a developer.']],
    ['about', 'About Hillel', ['Story, vision, mission and the Hillel PRIDE values as the school publishes them.']],
    ['academics', 'Academics', ['One page per stage: Prep School, Middle School (IMYC), Cambridge IGCSE and the IB Diploma.']],
    ['admissions', 'Admissions', ['How to apply, assessments by grade, the entry-age chart, documents, the 2026/27 fee table as published by Hillel, and key dates: what today lives in PDF packs, on one page.', 'The fee table and wording are edited by the office in Settings.']],
    ['school-life', 'School life', ['Activities, houses and upcoming fixtures and events.']],
    ['calendar', 'Calendar', ['Hillel’s 2026/27 calendar as published, filterable by category and kept up to date from the dashboard.']],
    ['news', 'News', ['Stories with photographs, posted by the communications role.']],
    ['visit', 'Campus visits', ['Families book a published tour time; the office sees every booking and gets an alert.']],
    ['apply', 'Start an application', ['The front door: a few details, then a secure link by email into the family portal.']],
    ['careers', 'Careers', ['Teaching vacancies and general applications with CV upload.']],
    ['job', 'Vacancy page', ['Structured data for Google job search; CVs go to a private store.']],
    ['contact', 'Contact', ['Every published number and a message form that reaches the right inbox.']],
  ]},
  { id: 'portal', eyebrow: 'Part 2', title: 'The family portal', intro: 'Parents sign in with a single-use link, with no password to forget. One account covers every child in the family: the five-step application, document uploads, booking an entrance assessment, the decision and accepting or declining an offer.', screens: [
    ['family-login', 'Family sign-in', ['Single-use links that expire after 60 minutes. Opening the link shows a “Sign in” button, so email security scanners cannot use it up.']],
    ['family-dashboard', 'Family dashboard', ['Each child’s application and status in plain language; a sibling application starts with the family’s details filled in.']],
    ['family-step-1', 'Online application', ['Five steps: student, parents and guardians, schooling, documents, review and submit.', 'Saves as the family goes; “Save and finish later” emails a fresh link.']],
    ['family-step-2', 'Parents & guardians', ['A second parent or emergency contact is optional.']],
    ['family-booking', 'Documents and assessment booking', ['Upload PDF, JPG or PNG scans or phone photos (8 MB each); returned documents show the office’s reason.', 'Once documents are accepted, the family books an assessment session themselves.']],
    ['family-offer', 'Offer of a place', ['The decision appears in the portal and by email; the family accepts or declines online before the reply-by date.']],
  ]},
  { id: 'admin', eyebrow: 'Part 3', title: 'The admissions office', intro: 'A private, role-based dashboard for the admissions office (and the communications and leadership teams). It replaces email attachments and spreadsheets with one pipeline, enforces seats per grade and keeps waiting lists in order.', screens: [
    ['admin-dashboard', 'Admissions overview', ['Leads with the work that unblocks families: documents waiting for review.', 'Weekly submissions, pipeline by stage, offers close to their reply-by date and the next sessions.']],
    ['admin-board', 'Pipeline board', ['Drag-and-drop between stages; capacity is checked before any offer, and families are emailed automatically.']],
    ['admin-applications', 'Applications list', ['Search, filters and CSV export (formula-safe for Excel).']],
    ['admin-application', 'Application record', ['Document review, decisions, staff notes, assessment booking for families who phone in, and templated emails.']],
    ['admin-capacity', 'Capacity & waitlist', ['Seats per grade and entry year; expired offers give their seats back; the waitlist stays in order.']],
    ['admin-assessments', 'Assessment sessions', ['Publish sessions for chosen grades; families book them from the portal.']],
    ['admin-enquiries', 'Enquiries', ['Admissions questions, tour bookings and messages in one inbox, with alerts by email.']],
    ['admin-templates', 'Email templates', ['The office writes every email the system sends.']],
    ['admin-teachers', 'Teacher recruitment', ['Vacancies and applicants with private CVs (administrators only).']],
    ['admin-settings', 'Settings & fees', ['Phones, emails, entry years, offer window, alert addresses and the fee table, all without a developer.']],
  ]},
];

const FEATURES = [
  ['Public website', ['Every page mobile-first, light and dark', 'School’s own photography and wording', 'Admissions, fees and dates as web pages, not PDFs', 'Campus-visit booking', 'News and calendar from the dashboard', 'Careers with CV upload', 'Sitemap and structured data for Google']],
  ['Family portal', ['Password-free single-use sign-in links', 'Five-step application that saves as it goes', 'Document upload from a phone', 'Assessment self-booking', 'Status tracker and updates', 'Accept or decline an offer online', 'Sibling applications']],
  ['Admissions office', ['Document review with reasons', 'Drag-and-drop pipeline board', 'Seats per grade and ordered waitlists', 'Assessment and tour sessions', 'Enquiries inbox', 'Editable email templates', 'CSV export and dashboard']],
  ['Platform', ['Laravel 13 on PHP 8.3, MySQL', `${TESTS} automated tests`, 'Three staff roles: admin, admissions, communications', 'Private document and CV storage', 'Rate-limited forms, HTTPS', 'Managed hosting, backups and monitoring in the plan']],
];

function caption(key, i, c) {
  const text = CAPTIONS[key]?.[i];
  const heads = (c.labels?.[i] ?? []).filter(Boolean).slice(0, 5);
  if (!text && !heads.length) return '';
  const n = c.chunks.length > 1 ? `${i + 1} of ${c.chunks.length} · ` : '';
  return `<figcaption class="cap"><b>${esc(n)}${text ? esc(text) : ''}</b>${heads.length ? `<span class="heads">Sections: ${heads.map(esc).join(' · ')}</span>` : ''}</figcaption>`;
}

/** Group consecutive slices onto one page while their combined height fits a letter page. */
function pageGroups(c, max) {
  const ratios = c.chunks.slice(0, max).map((_, i) => {
    const f = c.chunks[i];
    return { i, f };
  });
  const groups = []; let cur = [], h = 0;
  for (const s of ratios) {
    const r = sliceRatio(s.f);
    if (cur.length && h + r > 1.02) { groups.push(cur); cur = []; h = 0; }
    cur.push(s); h += r + 0.06;
  }
  if (cur.length) groups.push(cur);
  return groups;
}

/** Height ÷ width of a JPEG, read from its SOF marker (no image library needed). */
function sliceRatio(file) {
  const b = fs.readFileSync(file);
  for (let p = 2; p < b.length;) {
    if (b[p] !== 0xff) { p++; continue; }
    const m = b[p + 1], len = b.readUInt16BE(p + 2);
    if (m >= 0xc0 && m <= 0xc3) return b.readUInt16BE(p + 5) / b.readUInt16BE(p + 7);
    p += 2 + len;
  }
  return 1;
}

function screenPages(sec, [key, title, bullets]) {
  const c = chunks[key];
  if (!c) return `<section class="page"><h2>${esc(title)}</h2><p class="muted">Screenshot missing: ${key}</p></section>`;
  const max = MAX_SLICES[key] ?? c.chunks.length;
  const shown = { ...c, chunks: c.chunks.slice(0, max) };
  return pageGroups(c, max).map((group, gi) => `
    <section class="page shot">
      <header class="shot-head">
        <div><p class="eyebrow">${esc(sec.title)}${gi ? ' · continued' : ''}</p><h2>${esc(title)}</h2></div>
        <p class="url">${esc(c.url.replace(/#.*/, ''))}</p>
      </header>
      ${gi === 0 ? `<ul class="bullets">${bullets.map(b => `<li>${esc(b)}</li>`).join('')}</ul>` : ''}
      <div class="figs">${group.map(({ i, f }) => `<figure class="${gi === 0 && group.length === 1 ? 'first' : ''}"><img src="${img(f)}" alt="${esc(title)}">${caption(key, i, shown)}</figure>`).join('')}</div>
      ${foot()}
    </section>`).join('');
}

// The school's own crest, taken from hillelacademyjm.com (public/img/brand).
const CREST = img(path.resolve('public/img/brand/hillel-crest.png'));
const foot = () => `<footer class="foot"><span class="fl"><img src="${CREST}" alt="">Hillel Academy · Online admissions, family portal and website</span><span>Code Canvas Consultants Ltd · ${esc(DATE)}</span></footer>`;
const phone = (k, label) => `<figure><img src="${img(chunks[k].chunks[0])}" alt="${esc(label)}"><figcaption>${esc(label)}</figcaption></figure>`;
const WORDMARK = (light = false) => `<div class="wordmark ${light ? 'light' : ''}"><img src="${CREST}" alt="Hillel Academy crest"><div><span class="n">Hillel Academy</span><span class="s">Kingston · Jamaica · 1969</span></div></div>`;

const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Hillel Academy – Online Admissions, Family Portal and Website Proposal</title>
<style>
  ${FONTS}
  @page { size: Letter; margin: 0.5in 0.6in 0.55in; }
  :root { --ink: #14213d; --ink-900: #111d38; --ink-950: #0b1428; --ink-2: #33405c; --ink-3: #4f5b73; --paper: #fbfaf6; --sky: #edf3fa; --line: #dde3ec; --rule: #c9daf0; --margin: #d8604c; --accent: #f0ac1c; --accent-soft: #fdf1d3; --link: #0566a7; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; font-family: "Plus Jakarta Sans", "Helvetica Neue", Arial, sans-serif; color: var(--ink-2); font-size: 10.2pt; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  h1, h2, h3 { font-family: "Young Serif", Georgia, serif; font-weight: 400; color: var(--ink); margin: 0; line-height: 1.08; letter-spacing: -0.005em; }
  b, strong { color: var(--ink); font-weight: 700; }
  .page { page-break-after: always; break-after: page; height: 9.95in; display: flex; flex-direction: column; position: relative; overflow: hidden; }
  .page:last-child { page-break-after: auto; }
  .eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 7.8pt; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--ink-3); margin: 0 0 6px; }
  .eyebrow::before { content: ''; width: 3px; height: 13px; border-left: 1px solid var(--margin); border-right: 1px solid var(--accent); }
  .muted { color: var(--ink-3); }
  .ruled { background-image: repeating-linear-gradient(180deg, transparent 0 calc(26px - 1px), rgba(255,255,255,.06) calc(26px - 1px) 26px); }
  .wordmark { display: flex; align-items: center; gap: 12px; }
  .wordmark img { height: 0.82in; width: auto; display: block; }
  .wordmark .n { font-family: "Young Serif", Georgia, serif; font-size: 20pt; color: var(--ink); display: block; line-height: 1; }
  .wordmark .s { display: block; margin-top: 4px; font-size: 7pt; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--ink-3); }
  .wordmark.light .n { color: #fff; } .wordmark.light .s { color: rgba(255,255,255,.72); }
  /* cover */
  .cover { background: var(--ink-900); color: #fff; border-radius: 6px; padding: 0.6in 0.6in 0.5in 0.85in; justify-content: space-between; }
  .cover::before { content: ''; position: absolute; top: 0; bottom: 0; left: 0.55in; border-left: 1px solid rgba(216,96,76,.75); }
  .cover::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 8px; background: var(--accent); }
  .cover h1 { color: #fff; font-size: 40pt; line-height: 1.04; margin: 14px 0 16px; }
  .cover h1 em { font-style: normal; color: var(--accent); }
  .cover .eyebrow { color: rgba(255,255,255,.82); }
  .cover p.lead { color: rgba(255,255,255,.86); font-size: 12.5pt; max-width: 5.6in; margin: 0; }
  .cover .photo { margin: 22px 0 0; border-radius: 6px; overflow: hidden; height: 2.55in; border: 1px solid rgba(255,255,255,.15); }
  .cover .photo img { width: 100%; height: 100%; object-fit: cover; object-position: 50% 62%; display: block; }
  .cover .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; border-top: 1px solid rgba(255,255,255,.18); padding-top: 16px; font-size: 9.2pt; color: rgba(255,255,255,.8); }
  .cover .meta b { display: block; color: var(--accent); font-size: 7.6pt; letter-spacing: .14em; text-transform: uppercase; margin-bottom: 4px; }
  .cover .meta span.big { display: block; color: #fff; font-size: 11pt; font-weight: 700; }
  /* text pages */
  .prose h1 { font-size: 26pt; margin-bottom: 10px; }
  .prose h2 { font-size: 15pt; margin: 16px 0 6px; }
  .prose p { margin: 0 0 8px; }
  .prose ul.tight { margin: 4px 0 8px; padding-left: 16px; } .prose ul.tight li { margin: 2px 0; }
  .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .card { border: 1px solid var(--line); border-top: 3px solid var(--accent); border-radius: 0 6px 6px 6px; padding: 10px 13px; background: #fff; }
  .card h3 { font-size: 12pt; margin-bottom: 5px; }
  .card ul { margin: 0; padding-left: 15px; } .card li { margin: 2px 0; }
  .pill-row { display: flex; flex-wrap: wrap; gap: 5px; } .pill { border: 1px solid var(--rule); background: var(--sky); color: var(--ink); border-radius: 999px; padding: 2px 9px; font-size: 8.3pt; font-weight: 600; }
  .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 9px; margin: 12px 0 6px; }
  .stat { background: var(--ink-900); color: #fff; border-radius: 6px; padding: 11px 12px; border-bottom: 4px solid var(--accent); }
  .stat b { display: block; font-family: "Young Serif", Georgia, serif; font-weight: 400; font-size: 23pt; line-height: 1; color: #fff; margin-bottom: 4px; }
  .stat span { font-size: 8.2pt; color: rgba(255,255,255,.82); }
  table.feat { width: 100%; border-collapse: collapse; font-size: 9.4pt; } table.feat td { border-bottom: 1px solid var(--line); padding: 8px 6px; vertical-align: top; } table.feat td:first-child { width: 20%; font-family: "Young Serif", Georgia, serif; color: var(--ink); font-size: 11pt; }
  .note { background: var(--sky); border-left: 3px solid var(--link); padding: 8px 12px; border-radius: 0 6px 6px 0; font-size: 9.2pt; margin: 8px 0; }
  .section-divider { background: var(--ink-900); color: #fff; border-radius: 6px; padding: 0.8in 0.7in 0.8in 0.95in; justify-content: center; }
  .section-divider::before { content: ''; position: absolute; top: 0; bottom: 0; left: 0.6in; border-left: 1px solid rgba(216,96,76,.75); }
  .section-divider::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 8px; background: var(--accent); }
  .section-divider h1 { color: #fff; font-size: 40pt; margin: 8px 0 16px; } .section-divider p { color: rgba(255,255,255,.86); font-size: 12pt; max-width: 5.7in; } .section-divider .eyebrow { color: rgba(255,255,255,.8); }
  .section-divider ol { color: rgba(255,255,255,.86); columns: 2; font-size: 10pt; margin-top: 20px; padding-left: 18px; } .section-divider li { break-inside: avoid; margin: 3px 0; }
  /* screenshot pages */
  .shot-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; border-bottom: 2px solid var(--ink); padding-bottom: 6px; margin-bottom: 7px; }
  .shot-head h2 { font-size: 18pt; } .url { font-family: Menlo, Consolas, monospace; font-size: 8pt; color: var(--ink-3); margin: 0; white-space: nowrap; }
  .bullets { margin: 0 0 7px; padding-left: 16px; font-size: 9.2pt; } .bullets li { margin: 1px 0; }
  .figs { display: flex; flex-direction: column; gap: 10px; min-height: 0; flex: 1; }
  figure { margin: 0; display: block; min-height: 0; }
  figure img { display: block; width: 100%; max-height: 7.55in; object-fit: contain; object-position: top left; border: 1px solid var(--line); border-radius: 5px; }
  figure.first img { max-height: 7.0in; }
  .cap { margin: 5px 0 0; font-size: 8.6pt; line-height: 1.38; color: var(--ink-2); border-left: 3px solid var(--accent); padding-left: 8px; }
  .cap b { font-weight: 600; color: var(--ink); } .cap .heads { display: block; margin-top: 1px; font-size: 7.6pt; color: var(--ink-3); }
  .phones { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 12px; } .phones figure { display: block; }
  .phones img { width: 100%; max-height: 6.9in; object-fit: cover; object-position: top; border-radius: 20px; border: 6px solid var(--ink-900); }
  .phones figcaption { text-align: center; font-size: 9pt; color: var(--ink-3); margin-top: 6px; }
  /* investment */
  .price { display: grid; grid-template-columns: 1.15fr 1fr; gap: 14px; align-items: stretch; margin: 6px 0 4px; }
  .price .big { background: var(--ink-900); color: #fff; border-radius: 6px; padding: 14px 16px; border-bottom: 4px solid var(--accent); }
  .price .big b { display: block; font-family: "Young Serif", Georgia, serif; font-weight: 400; color: #fff; font-size: 30pt; line-height: 1; }
  .price .big span { color: rgba(255,255,255,.85); font-size: 9.4pt; }
  .price .year { background: var(--accent-soft); border: 1px solid var(--accent); border-radius: 6px; padding: 12px 14px; font-size: 9.6pt; }
  .price .year b { font-family: "Young Serif", Georgia, serif; font-weight: 400; font-size: 13pt; display: block; margin-bottom: 3px; }
  .terms { font-size: 9pt; } .terms li { margin: 2px 0; }
  table.sla { width: 100%; border-collapse: collapse; font-size: 8.8pt; margin-top: 4px; } table.sla th, table.sla td { border-bottom: 1px solid var(--line); padding: 4px 6px; text-align: left; } table.sla th { background: var(--sky); color: var(--ink); font-weight: 700; }
  .weeks { display: grid; grid-template-columns: repeat(3, 1fr); gap: 9px; margin: 8px 0; }
  .week { border: 1px solid var(--line); border-top: 3px solid var(--accent); border-radius: 0 6px 6px 6px; padding: 9px 11px; background: #fff; font-size: 9.2pt; }
  .week b { display: block; font-family: "Young Serif", Georgia, serif; font-weight: 400; font-size: 12pt; margin-bottom: 2px; }
  .next li { margin: 5px 0; }
  .contact { margin-top: 14px; background: var(--ink-900); color: rgba(255,255,255,.86); border-radius: 6px; padding: 16px 18px; border-bottom: 4px solid var(--accent); }
  .contact b { color: #fff; font-family: "Young Serif", Georgia, serif; font-weight: 400; font-size: 15pt; display: block; margin-bottom: 4px; }
  footer.foot { margin-top: auto; padding-top: 6px; font-size: 7.6pt; color: #8a93a6; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--line); }
  footer.foot .fl { display: inline-flex; align-items: center; gap: 6px; }
  footer.foot .fl img { height: 15px; width: auto; display: block; }
</style></head><body>

<section class="page cover ruled">
  ${WORDMARK(true)}
  <div>
    <p class="eyebrow">Proposal · ${esc(DATE)}</p>
    <h1>One front door for admissions, <em>from enquiry to offer</em></h1>
    <p class="lead">An online admissions and family-portal platform for Hillel Academy, with a refreshed website and a dashboard for the admissions office. It replaces PDF packs, the Rediker form and emailed attachments with a single, mobile-first journey.</p>
    <div class="photo"><img src="${img(path.resolve('public/img/campus-buildings-960.webp'))}" alt=""></div>
  </div>
  <div class="meta">
    <div><b>Prepared for</b><span class="big">The Director / Head of School</span>Hillel Academy<br>51 Upper Mark Way, Kingston 8, Jamaica<br>Main office (876) 925-1980</div>
    <div><b>Prepared by</b><span class="big">Keron Lewis</span>Code Canvas Consultants Ltd, Port of Spain<br>codecanvastt.com · +1 868 466-CODE · info@codecanvastt.com</div>
  </div>
</section>

<section class="page prose">
  <p class="eyebrow">Executive summary</p>
  <h1>Why now</h1>
  <p>Hillel Academy already has a website, <b>hillelacademyjm.com</b>, that tells the school’s story well. What it cannot yet do is take a family from first enquiry to an accepted place. Today the admissions information sits in downloadable PDF packages (admissions packs, tuition schedules and the school calendar), applications go through a Rediker form, and documents travel as email attachments. The school’s published email domain, gohillel.com, shows a “Launching Soon” placeholder to anyone who visits it.</p>
  <p>For a school of about 800 students from more than 40 countries, with applications at every grade from K1 to Grade 13, that means a lot of manual work for the admissions office and very little visibility for families: we found no online way for a parent to see where an application stands, book an entrance assessment or accept a place.</p>
  <p>This proposal adds the missing piece without changing what already works. Families apply online in five saved steps, upload documents from a phone, book their own assessment and accept or decline an offer, all in a password-free <b>family portal</b>. The <b>admissions office</b> reviews documents, moves applications along a pipeline that respects seats per grade, keeps waiting lists in order and sends the school’s own email templates automatically. The <b>public website</b> is refreshed around the same journey, using Hillel’s own words, photographs, fees and dates.</p>
  <div class="stat-grid">
    <div class="stat"><b>28</b><span>screens shown in this proposal, all working today</span></div>
    <div class="stat"><b>5</b><span>saved steps in the online application</span></div>
    <div class="stat"><b>11</b><span>application statuses, from draft to enrolled</span></div>
    <div class="stat"><b>${esc(TESTS)}</b><span>automated tests in the suite</span></div>
  </div>
  <h2>What Hillel gains</h2>
  <div class="cols">
    <div class="card"><h3>For families</h3><ul><li>Apply from a phone, at their own pace</li><li>Know at every moment what happens next</li><li>Book an assessment and accept a place online</li></ul></div>
    <div class="card"><h3>For the admissions office</h3><ul><li>Documents to review lead the dashboard</li><li>Seats per grade enforced, waitlists in order</li><li>Emails go out at each step, in the school’s words</li></ul></div>
    <div class="card"><h3>For leadership</h3><ul><li>Live numbers by stage, grade and entry year</li><li>CSV export for reporting</li><li>Role-based access for each team</li></ul></div>
    <div class="card"><h3>For communications</h3><ul><li>News, calendar and fees edited in the dashboard</li><li>Admissions facts as web pages, not PDFs</li><li>Search-engine ready pages and structured data</li></ul></div>
  </div>
  ${foot()}
</section>

<section class="page prose">
  <p class="eyebrow">At a glance</p>
  <h1>What is included</h1>
  <table class="feat"><tbody>
    ${FEATURES.map(([a, f]) => `<tr><td>${esc(a)}</td><td><div class="pill-row">${f.map(x => `<span class="pill">${esc(x)}</span>`).join('')}</div></td></tr>`).join('')}
  </tbody></table>
  <h2>How the pieces connect</h2>
  <p>A parent reads the admissions page, books a campus visit or asks a question, then starts an application with a name, an email and a grade. A secure link opens the <b>family portal</b>, where the five steps save as they go and documents are uploaded from a phone. The application arrives in the <b>admissions office</b> dashboard with any documents waiting for review. When every required document is accepted, the application moves to “documents complete” by itself and the family is invited to book an assessment session. After the assessment the office offers a place, waitlists or declines. An offer is refused if the grade is already full, and the family accepts or declines online before the reply-by date. Every step writes to the family’s timeline and sends the matching email.</p>
  <h2>Content and sources</h2>
  <p>School facts, programmes, the 2026/27 fees and the 2026/27 calendar are taken from Hillel’s own published material (hillelacademyjm.com, the 2026–27 tuition schedules dated 1 April 2026 and the 2026–27 calendar). They are shown <b>as published by Hillel</b> and stay editable by the office. Seven photographs are the school’s own, used here for the concept, and one is a Creative Commons photograph of the Blue Mountains.</p>
  <div class="note"><b>About the screenshots.</b> Names, applications, enquiries, vacancies, news stories and assessment or tour sessions in the following pages are <b>sample data</b> created for the demonstration. The working demo can be shown live on a call.</div>
  ${foot()}
</section>

${SECTIONS.map(sec => `
<section class="page section-divider ruled">
  <p class="eyebrow">${esc(sec.eyebrow)}</p>
  <h1>${esc(sec.title)}</h1>
  <p>${esc(sec.intro)}</p>
  <ol>${sec.screens.map(s => `<li>${esc(s[1])}</li>`).join('')}</ol>
</section>
${sec.screens.map(s => screenPages(sec, s)).join('')}
`).join('')}

<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design</p><h2>Built for a parent’s phone</h2></div></header>
  <p style="margin:0 0 4px">Most families will meet Hillel on a phone. Every page adapts to small screens, and a “Book a visit / Apply” bar stays within thumb reach on public pages.</p>
  <div class="phones">${phone('m-home', 'Home')}${phone('m-admissions', 'Fees 2026/27 (as published)')}${phone('m-apply', 'Start an application')}</div>
  ${foot()}
</section>
<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design · continued</p><h2>Portal and office on a phone</h2></div></header>
  <p style="margin:0 0 4px">Parents can accept a place from their phone, and admissions staff can check the dashboard and review an application away from the desk. Sample data.</p>
  <div class="phones">${phone('m-portal', 'Family portal: offer of a place')}${phone('m-admin-dashboard', 'Admissions overview (staff)')}${phone('m-admin-application', 'Application record (staff)')}</div>
  ${foot()}
</section>

<section class="page prose">
  <p class="eyebrow">Technology, hosting &amp; security</p>
  <h1>Secure by design, simple to run</h1>
  <div class="cols">
    <div class="card"><h3>Platform</h3><ul><li>Laravel 13 on PHP 8.3 with MySQL, a widely supported open-source stack with no licence fees</li><li>${esc(TESTS)} automated tests cover applications, sign-in, documents, capacity, offers and staff access</li><li>Static analysis and code-style checks on every change</li><li>No background workers needed; runs on standard managed hosting</li></ul></div>
    <div class="card"><h3>Family data and documents</h3><ul><li>Documents and CVs stored privately, outside the public web folder, and only streamed to the family or authorised staff</li><li>Uploads limited to PDF, JPG and PNG (8 MB), checked by content, not by file name</li><li>Each family sees only its own applications</li></ul></div>
    <div class="card"><h3>Sign-in and access</h3><ul><li>Families: single-use links valid for 60 minutes, stored only as a hash, with a confirm step so email scanners cannot use them up</li><li>Asking for a link never reveals whether an email is registered</li><li>Staff: individual accounts and three roles (administrator, admissions, communications), instantly deactivated</li></ul></div>
    <div class="card"><h3>Quality and reach</h3><ul><li>HTTPS everywhere, security headers, rate-limited forms and spam protection</li><li>Accessible: keyboard use, screen-reader labels and AA colour contrast; light and dark themes</li><li>Responsive WebP images and self-hosted fonts</li><li>Sitemap and structured data (school, news, job postings)</li></ul></div>
  </div>
  <h2>Emails the system sends</h2>
  <p>Sign-in links; application received; document returned with the reason; documents complete; assessment booked; place offered (with the reply-by date); waitlisted; not offered; enrolled; campus-visit confirmation; and office alerts for new applications, uploads, enquiries, tours and teacher applications. Every family email is an office-editable template, sent from the school’s own mailbox.</p>
  ${foot()}
</section>

<section class="page prose">
  <p class="eyebrow">Investment</p>
  <h1>The US$1,000 per month plan</h1>
  <div class="price">
    <div class="big"><b>US$1,000 / month</b><span>Invoiced in US dollars. <strong style="color:#fff">US$0 for design and development</strong>. No setup fee and no deposit.</span></div>
    <div class="year"><b>Year one: US$12,000</b>or US$10,800 if paid annually in advance (10% off).<br><strong>Build cost: US$0.</strong></div>
  </div>
  <div class="cols">
    <div>
      <h2>Terms</h2>
      <ul class="tight terms">
        <li>Billing starts at the end of the month in which the site launches, prorated for the days it was live. Monthly invoices are due within 14 days.</li>
        <li>No minimum term: 30 days’ notice by email from either side.</li>
        <li>The price is fixed for the first 24 months.</li>
        <li>Optional annual prepayment at 10% off: US$10,800 a year.</li>
        <li><b>Handover fee: US$7,000, only if Hillel leaves.</b> It buys the platform outright and moves it to hosting in the school’s name. It is waived if Code Canvas stops trading.</li>
        <li>Hillel’s content, domain and family data belong to the school at all times.</li>
      </ul>
      <h2>Quoted separately, only with approval</h2>
      <ul class="tight terms">
        <li>Online card payments for application or enrolment fees</li>
        <li>SMS or WhatsApp messages to families</li>
        <li>Integration with the school’s student information or accounting systems (for example Rediker)</li>
        <li>A current-parent portal (reports, fee statements)</li>
        <li>Paid advertising, SEO campaigns, copywriting, photography or video</li>
        <li>Work beyond 10 hours in a month: US$50 per hour, or a fixed quote</li>
      </ul>
    </div>
    <div>
      <h2>Included every month</h2>
      <ul class="tight terms">
        <li>Complete design and build of the platform and website</li>
        <li>Managed hosting with CDN and SSL</li>
        <li>Daily backups plus a weekly off-site copy</li>
        <li>Security patches and software updates</li>
        <li>24/7 uptime monitoring</li>
        <li><b>Up to 10 hours a month</b> of content changes and small improvements</li>
        <li>Staff training for the admissions and communications teams</li>
        <li>Google Analytics, Search Console and a monthly summary</li>
        <li>Email, WhatsApp and phone support, plus a quarterly review call</li>
        <li>A 30-day post-launch warranty</li>
      </ul>
      <h2>Service levels</h2>
      <table class="sla"><thead><tr><th>Issue</th><th>Response</th><th>Fix target</th></tr></thead><tbody>
        <tr><td>Site down or forms not sending</td><td>1 hour</td><td>4 hours</td></tr>
        <tr><td>Broken page or feature</td><td>4 business hours</td><td>1 business day</td></tr>
        <tr><td>Content change</td><td>1 business day</td><td>2 business days</td></tr>
      </tbody></table>
    </div>
  </div>
  ${foot()}
</section>

<section class="page prose">
  <p class="eyebrow">Timeline</p>
  <h1>Six weeks from approval to launch</h1>
  <div class="weeks">
    <div class="week"><b>Week 1</b>Kick-off call, brand assets (crest artwork, colours, photographs) and content.</div>
    <div class="week"><b>Week 2</b>Revision round 1. Forms, emails and staff roles configured with the school’s real addresses.</div>
    <div class="week"><b>Week 3</b>Data and content load: fees, calendar, capacity per grade, sessions, templates.</div>
    <div class="week"><b>Week 4</b>Revision round 2.</div>
    <div class="week"><b>Week 5</b>Formal testing with the admissions office and a few families.</div>
    <div class="week"><b>Week 6</b>Launch, redirects from old addresses, Search Console and staff training.</div>
  </div>
  <p>The complete platform is on a private preview link throughout, and nothing goes live until the school approves it in writing. <b>Billing does not start until launch.</b></p>
  <h2>What we need from Hillel</h2>
  <ul class="tight">
    <li>A vector or high-resolution copy of the crest (the concept uses the version on hillelacademyjm.com) and any brand guidelines, and original photographs (the concept uses images from hillelacademyjm.com).</li>
    <li>Confirmation of the facts we used: about 800 students from 40+ countries, the campus, programmes and accreditation wording.</li>
    <li>How the office runs admissions today: required documents by grade, assessment wording, seats per grade and entry years, and the offer reply window.</li>
    <li>The admissions mailbox and who receives office alerts; staff to set up as administrators, admissions or communications users.</li>
    <li>A decision on the domain (hillelacademyjm.com, gohillel.com or both) and access to DNS, Google Analytics and Search Console.</li>
    <li>The data-protection contact for the privacy notice.</li>
  </ul>
  ${foot()}
</section>

<section class="page prose">
  <p class="eyebrow">Next steps</p>
  <h1>From proposal to launch</h1>
  <ol class="next">
    <li><b>Walk through the working demo</b> together on a call, as a family and as the admissions office.</li>
    <li><b>Send any changes</b> to pages, wording or the admissions workflow; the first revision round can start straight away.</li>
    <li><b>On approval</b>, we send a one-page agreement with these terms: US$1,000 per month, month to month on 30 days’ notice, US$0 for design and development, and a US$7,000 handover fee only if the school leaves.</li>
    <li><b>Kick-off</b> the same week, and launch six weeks later.</li>
  </ol>
  <div class="contact">
    <b>Keron Lewis</b>
    Director, Code Canvas Consultants Ltd · Port of Spain, Trinidad and Tobago<br>
    codecanvastt.com · +1 868 466-CODE · info@codecanvastt.com<br>Book a call: calendly.com/keronlewis/30min
  </div>
  <div style="margin-top:auto"></div>
  ${foot()}
</section>
</body></html>`;

fs.writeFileSync(OUT_HTML, html);
const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--allow-file-access-from-files'] });
const page = await browser.newPage();
await page.goto('file://' + OUT_HTML, { waitUntil: 'networkidle0' });
await page.evaluate(() => document.fonts.ready);
await page.pdf({ path: OUT_PDF, format: 'Letter', printBackground: true, preferCSSPageSize: true, displayHeaderFooter: false });
await browser.close();
console.log('PDF written:', OUT_PDF, Math.round(fs.statSync(OUT_PDF).size / 1024 / 1024 * 10) / 10 + ' MB');
