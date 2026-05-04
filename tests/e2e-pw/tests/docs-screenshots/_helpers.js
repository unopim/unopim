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
