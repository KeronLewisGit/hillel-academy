# Hillel Academy

Website, online admissions and family portal, and admissions back office for Hillel Academy, the independent, not-for-profit international school at 51 Upper Mark Way, Kingston 8, Jamaica (founded 1969, K1 to Grade 13, IMYC / Cambridge IGCSE / IB Diploma). Built as a concept for the school.

## What it does

- **Public site.** Home, about (story, vision, mission, Hillel PRIDE values), academics by stage (Prep School, Middle School / IMYC, IGCSE, IB Diploma), admissions (process, assessment by grade, entry chart, documents, the published 2026/27 fee table, key dates), school life (activities from the school's own lists, houses, traditions), news, calendar (the school's real 2026/27 dates), careers with CV upload, campus-visit booking, contact, privacy, photo credits, sitemap. Mobile first, light and dark themes, sticky "Book a visit / Apply" bar on phones, works without JavaScript.
- **Online application.** A parent enters an email, a name and the child's grade, and gets a single-use sign-in link. The form has five steps (student, parents and guardians, schooling, documents, review), saves as it goes, has "save and finish later", and produces a reference such as `HA-26-00042`.
- **Family portal** (`/family`, its own `family` guard, password-free). Track each child's application, upload or replace documents, book an entrance assessment from published sessions, see the decision, accept or decline an offer, and apply for a sibling with details copied across. Asking for a link never reveals whether an email is known.
- **Admissions office** (`/admin`). The dashboard leads with how many applications have documents waiting for review. There is a list with filters and CSV export, a kanban board (drag to move, capacity enforced), document review (accept or return with a reason), per-grade seat capacity with waiting lists in order, assessment and tour sessions, an enquiries inbox, office-editable email templates (automatic at each stage, plus one-off messages), news and calendar, teacher vacancies and applicants, settings (phones, fees year, fee table, entry years, offer window) and staff accounts with roles.

Roles: `admin` (everything), `admissions` (families, applications, enquiries, sessions, templates), `communications` (news and calendar only).

## Local setup

```sh
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seeder=DemoSeeder
npm install && npm run build
php artisan serve --port=8103
```

Staff sign in at `/login`: `admin@hillel.test`, `admissions@hillel.test` or `comms@hillel.test`, password `password`. The demo family is `family@hillel.test`. It has one application half-way through the form and one submitted. Sign in at `/family/login`; with `HILLEL_DEMO_LINKS=true` the link is shown on screen. You can also run `php artisan app:family-link family@hillel.test`. Demo data is for local use only.

## Checks

```sh
php artisan test
vendor/bin/phpstan analyse
vendor/bin/pint --test
```

## Deploying

See [INSTALL-HOSTINGER.md](INSTALL-HOSTINGER.md). `scripts/package-hostinger.sh` builds `dist/hillel-hostinger.zip`, which extracts straight into `public_html/` with the app in `hillel-app/`.

## Before launch: confirm with the school

- **Their current website.** Hillel already runs hillelacademyjm.com (WordPress, with Rediker forms and PDF admissions packs). gohillel.com is their email domain and shows a parked page. Decide which domain this replaces.
- **Facts in `config/hillel.php`.** Everything there is sourced (see the header comment), but some of it is the school's own wording and should be re-confirmed: "largest international school in Jamaica", "only school in Jamaica" offering the IMYC, about 800 students from 40+ countries, 13 acres, accreditation.
- **Fees** come from the 2026–27 schedules dated 1 April 2026 (per term, J$ and US$) and can be edited in Settings.
- **Assessment wording** is from the school's admissions packages. The online flow books assessment sessions only after documents are complete, so check this matches how the office works.
- **Ages chart.** Ages are derived from the published K1 rule (age 3 by 31 August).
- **Logo.** The mark is a placeholder, not the crest. Swap in the school's artwork.
- **Leadership.** No staff are named. Search Associates advertised a Head of School vacancy in January 2026.
- **Photos.** Seven photos are the school's own, taken from hillelacademyjm.com for the concept. Replace them with originals. One Blue Mountains photo is CC BY 4.0. See `/photo-credits`.
- **Privacy notice.** Add the data-protection contact.
- **Demo content** (sample vacancies, seats per grade, assessment and tour times, news about the website) comes from `DemoSeeder` only and is never loaded in production.
