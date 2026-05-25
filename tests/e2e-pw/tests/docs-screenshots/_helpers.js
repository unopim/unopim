const path = require('path');
const fs = require('fs');

const ARTIFACTS = path.resolve(__dirname, '../../test-results/docs-screenshots');
fs.mkdirSync(ARTIFACTS, { recursive: true });

async function stabilize(page) {
  await page.addStyleTag({
    content: `
      *, *::before, *::after { caret-color: transparent !important; animation: none !important; transition: none !important; }
      ::-webkit-scrollbar { display: none !important; }
      time, .timestamp, [data-timestamp] { visibility: hidden !important; }
    `,
  });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.evaluate(() => document.fonts && document.fonts.ready);
}

async function capture(page, shot) {
  await stabilize(page);
  const out = path.join(ARTIFACTS, `${shot.id}.png`);
  if (shot.target.type === 'fullPage') {
    await page.screenshot({ path: out, fullPage: true, type: 'png', animations: 'disabled' });
  } else {
    const loc = page.locator(shot.target.locator).first();
    await loc.waitFor({ state: 'visible', timeout: 15000 });
    await loc.screenshot({ path: out, type: 'png', animations: 'disabled' });
  }
  return out;
}

module.exports = { capture, stabilize, ARTIFACTS };

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';

async function adminPost(request, urlPath, form) {
  return request.post(`${BASE}${urlPath}`, { form, headers: { Accept: 'text/html,application/xhtml+xml' } });
}

async function ensureCredential(request) {
  const list = await request.get(`${BASE}/admin/nextcloud/credentials`);
  const html = await list.text();
  const m = html.match(/credentials\/(\d+)\/edit/);
  if (m) return Number(m[1]);
  await adminPost(request, '/admin/nextcloud/credentials', {
    label: 'Docs Demo User',
    username: 'docs-demo',
    password: 'StrongPass!234',
  });
  const list2 = await request.get(`${BASE}/admin/nextcloud/credentials`);
  const m2 = (await list2.text()).match(/credentials\/(\d+)\/edit/);
  return Number(m2[1]);
}

async function ensureProfile(request, credentialId) {
  const list = await request.get(`${BASE}/admin/nextcloud/profiles`);
  if (/profiles\/\d+\/edit/.test(await list.text())) return;
  await adminPost(request, '/admin/nextcloud/profiles', {
    label: 'Docs Demo Profile',
    credential_id: credentialId,
    directory_id: 1,
    direction: 'two-way',
  });
}

async function ensureRemoteSource(request) {
  const list = await request.get(`${BASE}/admin/nextcloud/remote-sources`);
  if (/remote-sources\/\d+\/edit/.test(await list.text())) return;
  await adminPost(request, '/admin/nextcloud/remote-sources', {
    label: 'Docs Demo Remote',
    url: 'https://demo.nextcloud.example/remote.php/dav/files/demo/',
    username: 'demo',
    password: 'demo',
  });
}

module.exports.ensureCredential = ensureCredential;
module.exports.ensureProfile = ensureProfile;
module.exports.ensureRemoteSource = ensureRemoteSource;
