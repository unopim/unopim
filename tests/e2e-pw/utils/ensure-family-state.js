// Ensures a valid admin storage state exists for the server this suite targets.
// It keeps its own state file (the shared one may be generated against a
// different host), regenerating by logging in whenever the file is missing or
// older than the session-safe window. Target follows BASE_URL so the whole
// suite moves with tests/e2e-pw/.env instead of being pinned to one port.
const fs = require('fs');
const path = require('path');
const { chromium } = require('@playwright/test');
const { loadEnv } = require('./load-env');

loadEnv();

const STATE_PATH = path.resolve(__dirname, '../.state/admin-auth-family.json');
const BASE = process.env.FAMILY_BASE_URL || process.env.BASE_URL || 'http://127.0.0.1:8000';
const EMAIL = process.env.FAMILY_ADMIN_EMAIL || process.env.ADMIN_EMAIL || process.env.ADMIN_USERNAME || 'admin@example.com';
// Never hardcode the password: supply it at run time via FAMILY_ADMIN_PASSWORD
// (or ADMIN_PASSWORD). Only needed when the saved session is missing/stale.
const PASSWORD = process.env.FAMILY_ADMIN_PASSWORD || process.env.ADMIN_PASSWORD || '';
const MAX_AGE_MS = 6 * 60 * 60 * 1000; // 6h

function isFresh() {
  try {
    const stat = fs.statSync(STATE_PATH);
    return Date.now() - stat.mtimeMs < MAX_AGE_MS;
  } catch {
    return false;
  }
}

let inFlight = null;

async function ensureFamilyState() {
  if (isFresh()) {
    return STATE_PATH;
  }
  if (inFlight) {
    return inFlight;
  }
  if (!PASSWORD) {
    throw new Error(
      'No saved admin session and no FAMILY_ADMIN_PASSWORD (or ADMIN_PASSWORD) set. '
      + 'Run with the admin password in the env to regenerate .state/admin-auth-family.json.'
    );
  }
  inFlight = (async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext({ baseURL: BASE });
    const page = await context.newPage();
    await page.goto('/admin/login', { waitUntil: 'domcontentloaded' });

    // The server may already consider this browser signed in (it redirects
    // /admin/login straight to the dashboard); only drive the form when it does
    // actually render.
    if (/\/admin\/login/.test(page.url())) {
      // vee-validate ajax form needs real key events, not fill().
      await page.getByRole('textbox', { name: 'Email Address' }).click();
      await page.getByRole('textbox', { name: 'Email Address' }).pressSequentially(EMAIL, { delay: 10 });
      await page.getByRole('textbox', { name: 'Password' }).click();
      await page.getByRole('textbox', { name: 'Password' }).pressSequentially(PASSWORD, { delay: 10 });
      await Promise.all([
        page.waitForResponse((r) => r.url().includes('/admin/login') && r.request().method() === 'POST', { timeout: 20000 }).catch(() => null),
        page.getByRole('button', { name: 'Sign In' }).click(),
      ]);
      await page.waitForURL(/\/admin\/dashboard/, { timeout: 20000 });
    }
    fs.mkdirSync(path.dirname(STATE_PATH), { recursive: true });
    await context.storageState({ path: STATE_PATH });
    await browser.close();
    return STATE_PATH;
  })();
  return inFlight;
}

module.exports = { ensureFamilyState, STATE_PATH, FAMILY_BASE_URL: BASE };
