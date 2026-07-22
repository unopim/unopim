// Load tests/e2e-pw/.env into process.env without a dotenv dependency. Existing
// env vars win, so `BASE_URL=... npx playwright test` still overrides the file.
// Required by both the config and the helpers: a Playwright worker may resolve a
// helper before the config's own load runs, so neither can rely on the other.
const fs = require('fs');
const path = require('path');

let loaded = false;

function loadEnv() {
    if (loaded) {
        return;
    }

    loaded = true;

    const envPath = path.resolve(__dirname, '..', '.env');

    if (! fs.existsSync(envPath)) {
        return;
    }

    for (const line of fs.readFileSync(envPath, 'utf8').split('\n')) {
        const match = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/i);

        if (! match) {
            continue;
        }

        if (process.env[match[1]] === undefined) {
            process.env[match[1]] = match[2].replace(/^["']|["']$/g, '');
        }
    }
}

module.exports = { loadEnv };
