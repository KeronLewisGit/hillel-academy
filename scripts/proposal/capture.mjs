// Captures screenshots of every important screen (desktop + phone) for the client proposal.
// Slices follow the page's own sections so a screenshot never breaks mid-block.
// Usage: npm run proposal  (or node scripts/proposal/capture.mjs)
// Env: BASE_URL (default http://127.0.0.1:8103), ADMIN_EMAIL / ADMIN_PASSWORD, CHROME_PATH.
// Families sign in by magic link: the script asks `php artisan app:family-link` for one and
// presses the "Sign in" button on the confirm page, exactly as a parent would.
import puppeteer from 'puppeteer-core';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8103';
const OUT = path.resolve('dist/proposal/shots');
const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ADMIN = { email: process.env.ADMIN_EMAIL ?? 'admin@hillel.test', password: process.env.ADMIN_PASSWORD ?? 'password' };

// Demo families from DemoSeeder, chosen for the stage their application is at.
const FAMILIES = {
  draft: { email: 'family@hillel.test', draft: 'HA-26-00001', submitted: 'HA-26-00002' },
  booking: { email: 'family6@example.com', ref: 'HA-26-00009' }, // documents complete: books an assessment
  offer: { email: 'family19@example.com', ref: 'HA-26-00022' }, // place offered: accept or decline
};

fs.mkdirSync(OUT, { recursive: true });
for (const f of fs.readdirSync(OUT)) if (f.endsWith('.jpg')) fs.unlinkSync(path.join(OUT, f));

const PUBLIC = [
  ['home', '/'], ['about', '/about'], ['academics', '/academics'], ['admissions', '/admissions'],
  ['school-life', '/school-life'], ['calendar', '/calendar'], ['news', '/news'], ['visit', '/visit'],
  ['apply', '/apply'], ['careers', '/careers'], ['job', '/careers/mathematics-teacher'], ['contact', '/contact'],
  ['family-login', '/family/login'],
];
const ADMIN_PAGES = [
  ['admin-dashboard', '/admin'], ['admin-board', '/admin/applications/board'], ['admin-applications', '/admin/applications'],
  ['admin-application', `/admin/applications/${FAMILIES.draft.submitted}`], ['admin-capacity', '/admin/capacity'],
  ['admin-assessments', '/admin/assessments'], ['admin-enquiries', '/admin/enquiries'], ['admin-templates', '/admin/templates'],
  ['admin-teachers', '/admin/teachers'], ['admin-settings', '/admin/settings'],
];
const MOBILE = [
  ['m-home', '/'], ['m-admissions', '/admissions#fees'], ['m-apply', '/apply'],
  ['m-portal', `/family/applications/${FAMILIES.offer.ref}#offer-title`], ['m-admin-dashboard', '/admin'], ['m-admin-application', `/admin/applications/${FAMILIES.draft.submitted}`],
];

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', protocolTimeout: 90000, args: ['--hide-scrollbars', '--no-sandbox'] });
const page = await browser.newPage();
await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }, { name: 'prefers-color-scheme', value: 'light' }]);

async function settle() {
  await page.evaluate(async () => {
    // Scroll reveal: show everything at once (the site marks revealed blocks with .is-visible).
    document.querySelectorAll('[data-reveal]').forEach(e => e.classList.add('is-visible'));
    // The status toast and the demo-link banner are not part of the design.
    document.querySelectorAll('[role="status"].fixed').forEach(e => e.remove());
    window.scrollTo(0, document.body.scrollHeight); await new Promise(r => setTimeout(r, 400)); window.scrollTo(0, 0);
    // Admin sidebar is position: fixed at viewport height; in a full-page capture it would stop
    // after the first screen, so pin it to the full document height instead.
    const aside = document.querySelector('body.admin > aside');
    if (aside) aside.style.cssText += `;position:absolute;bottom:auto;height:${document.documentElement.scrollHeight}px`;
    await Promise.race([
      Promise.all([...document.images].filter(i => !i.complete).map(i => new Promise(r => { i.onload = i.onerror = r; }))),
      new Promise(r => setTimeout(r, 1500)),
    ]);
  });
  await new Promise(r => setTimeout(r, 400));
}

/**
 * Measure natural content blocks (header, each section, footer). Blocks taller than the
 * page budget are replaced by their children, recursively, so slices follow real section
 * boundaries instead of cutting wherever the pixel budget runs out.
 */
async function measureBlocks(maxH) {
  return page.evaluate((maxH) => {
    const rect = (el) => { const r = el.getBoundingClientRect(); return { top: Math.round(r.top + window.scrollY), bottom: Math.round(r.bottom + window.scrollY) }; };
    const visible = (el) => { const cs = getComputedStyle(el); const r = el.getBoundingClientRect(); return cs.display !== 'none' && cs.visibility !== 'hidden' && r.height > 24 && cs.position !== 'fixed'; };
    const skip = (el) => el.matches('script, style, template, [x-cloak], [aria-hidden="true"]:not(section)');
    const label = (el) => {
      const hs = [...el.querySelectorAll('h1, h2, h3')].filter(h => visible(h) && h.closest('nav, footer, aside, [aria-hidden="true"]') === null);
      const seen = new Set();
      const text = (h) => { const c = h.cloneNode(true); c.querySelectorAll('[aria-hidden="true"], template, .sr-only').forEach(e => e.remove()); return c.textContent.replace(/\s+/g, ' ').trim(); };
      return hs.map(text).filter(t => t && !seen.has(t.toLowerCase()) && seen.add(t.toLowerCase())).slice(0, 4);
    };
    const explode = (el) => {
      const r = { ...rect(el), labels: label(el) };
      if (r.bottom - r.top <= maxH) return [r];
      const kids = [...el.children].filter(k => !skip(k) && visible(k));
      if (kids.length === 0) return [{ top: r.top, bottom: r.bottom, labels: r.labels }];
      if (kids.length === 1) return explode(kids[0]); // wrapper div: look inside it
      return kids.flatMap(explode);
    };
    // Admin layout: the content column is <main id="content"> beside a fixed sidebar and a
    // sticky top bar; public and family pages: body-level top bar, header, main, footer.
    const adminMain = document.querySelector('body.admin main#content');
    let roots;
    if (adminMain) {
      const top = adminMain.previousElementSibling; // the sticky search bar
      roots = [...(top ? [{ el: top, trailing: false }] : []), ...[...adminMain.children].map(el => ({ el, trailing: false }))];
    } else {
      roots = [...document.body.children].filter(el => ['HEADER', 'MAIN', 'FOOTER', 'DIV', 'SECTION'].includes(el.tagName) && !skip(el) && visible(el))
        .flatMap(el => el.tagName === 'MAIN' ? [...el.children].map(k => ({ el: k, trailing: false })) : [{ el, trailing: el.tagName === 'FOOTER' }]);
    }
    const blocks = roots.filter(r => visible(r.el)).flatMap(r => explode(r.el).map(b => ({ ...b, trailing: r.trailing }))).filter(b => b.bottom > b.top).sort((a, b) => a.top - b.top);
    const merged = [];
    for (const b of blocks) {
      const last = merged[merged.length - 1];
      if (last && b.top < last.bottom - 2) { last.bottom = Math.max(last.bottom, b.bottom); last.trailing = last.trailing && b.trailing; last.labels = [...new Set([...last.labels, ...b.labels])]; } else merged.push({ ...b, labels: [...b.labels] });
    }
    const docH = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);
    if (merged.length) { merged[0].top = 0; merged[merged.length - 1].bottom = Math.max(merged[merged.length - 1].bottom, docH); for (let i = 1; i < merged.length; i++) merged[i].top = merged[i - 1].bottom; }
    return { blocks: merged, docH };
  }, maxH);
}

/** Greedily pack consecutive blocks into page-sized groups; a lone oversize block is hard-cut as a last resort. */
function packBlocks(blocks, maxH, docH) {
  const groups = [];
  let cur = null;
  for (const b of blocks) {
    const h = b.bottom - b.top;
    if (cur && b.bottom - cur.top <= maxH) { cur.bottom = b.bottom; cur.labels = [...new Set([...cur.labels, ...(b.labels ?? [])])]; continue; }
    if (cur) groups.push(cur);
    if (h > maxH) {
      for (let y = b.top; y < b.bottom; y += maxH) groups.push({ top: y, bottom: Math.min(y + maxH, b.bottom), labels: b.labels ?? [] });
      cur = null;
    } else cur = { top: b.top, bottom: b.bottom, labels: [...(b.labels ?? [])] };
  }
  if (cur) groups.push(cur);
  // swallow a tiny trailing sliver into the previous group when it fits
  if (groups.length > 1) {
    const last = groups[groups.length - 1], prev = groups[groups.length - 2];
    if (last.bottom - last.top < maxH * 0.15 && last.bottom - prev.top <= maxH * 1.05) { prev.bottom = last.bottom; prev.labels = [...new Set([...prev.labels, ...last.labels])]; groups.pop(); }
  }
  // A short slice (a page header on its own, a closing band) wastes a whole proposal page:
  // join it to its neighbour when the pair is still readable on one page.
  for (let i = 0; i < groups.length; i++) {
    const g = groups[i], h = g.bottom - g.top;
    if (h >= maxH * 0.5) continue;
    const next = groups[i + 1], prev = groups[i - 1];
    const join = (a, b) => ({ top: a.top, bottom: b.bottom, labels: [...new Set([...a.labels, ...b.labels])] });
    if (next && next.bottom - g.top <= maxH * 1.4) { groups.splice(i, 2, join(g, next)); i--; }
    else if (prev && g.bottom - prev.top <= maxH * 1.4) { groups.splice(i - 1, 2, join(prev, g)); i -= 2; }
  }
  groups[groups.length - 1].bottom = Math.min(groups[groups.length - 1].bottom, docH);
  return groups;
}

const RATIO = 1.08; // slice height ÷ width that fits a letter page beneath the heading and bullets

async function shoot(name, url, { width = 1440, height = 900, fullPage = true, mobile = false } = {}) {
  await page.setViewport({ width, height, deviceScaleFactor: mobile ? 2 : 1.25, isMobile: mobile, hasTouch: mobile });
  await page.goto(BASE + url, { waitUntil: 'networkidle0' });
  await settle();
  if (url.includes('#')) await page.evaluate((id) => { document.getElementById(id)?.scrollIntoView({ block: 'start' }); window.scrollBy(0, -96); }, url.split('#')[1]);
  if (!fullPage) {
    const file = path.join(OUT, `${name}.jpg`);
    await page.screenshot({ path: file, type: 'jpeg', quality: 80 });
    console.log(`✓ ${name} viewport`);
    return { name, url, chunks: [path.resolve(file)], labels: [[]], mobile };
  }
  const maxH = Math.round(width * RATIO);
  let { blocks, docH } = await measureBlocks(maxH);
  if (name !== 'home') { blocks = blocks.filter(b => !b.trailing); docH = blocks[blocks.length - 1].bottom; } // the footer is shown once (home)
  const groups = packBlocks(blocks, maxH, docH);
  const files = []; const labels = [];
  for (const [i, g] of groups.entries()) {
    const file = path.join(OUT, groups.length > 1 ? `${name}-${i}.jpg` : `${name}.jpg`);
    await page.screenshot({ path: file, type: 'jpeg', quality: 80, captureBeyondViewport: true, clip: { x: 0, y: g.top, width, height: g.bottom - g.top } });
    files.push(path.resolve(file)); labels.push(g.labels ?? []);
  }
  console.log(`✓ ${name} → ${groups.length} slice(s): ${groups.map(g => (g.labels ?? []).join(' | ') || '(no heading)').join('  ·  ')}`);
  return { name, url, chunks: files, labels, w: width, h: docH };
}

async function staffLogin() {
  await page.setViewport({ width: 1440, height: 900 });
  await page.goto(BASE + '/login', { waitUntil: 'networkidle0' });
  await page.type('input[name=email]', ADMIN.email);
  await page.type('input[name=password]', ADMIN.password);
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('form button')]);
}

async function familyLogin(email) {
  const out = execFileSync('php', ['artisan', 'app:family-link', email]).toString();
  const link = new URL(out.match(/https?:\/\/\S+/)[0]);
  await page.setViewport({ width: 1440, height: 900 });
  await page.goto(BASE + link.pathname, { waitUntil: 'networkidle0' }); // the confirm page
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('main form button')]);
}

const shots = [];
for (const [n, u] of PUBLIC) shots.push(await shoot(n, u));

await familyLogin(FAMILIES.draft.email);
shots.push(await shoot('family-dashboard', '/family'));
shots.push(await shoot('family-step-1', `/family/applications/${FAMILIES.draft.draft}/step/1`));
shots.push(await shoot('family-step-2', `/family/applications/${FAMILIES.draft.draft}/step/2`));
await familyLogin(FAMILIES.booking.email);
shots.push(await shoot('family-booking', `/family/applications/${FAMILIES.booking.ref}`));
await familyLogin(FAMILIES.offer.email);
shots.push(await shoot('family-offer', `/family/applications/${FAMILIES.offer.ref}`));

await staffLogin();
for (const [n, u] of ADMIN_PAGES) shots.push(await shoot(n, u, { fullPage: !u.endsWith('/board') }));
for (const [n, u] of MOBILE) shots.push(await shoot(n, u, { width: 390, height: 844, mobile: true, fullPage: false }));

fs.writeFileSync(path.join(OUT, 'chunks.json'), JSON.stringify(Object.fromEntries(shots.map(s => [s.name, s])), null, 1));
await browser.close();
console.log('done');
