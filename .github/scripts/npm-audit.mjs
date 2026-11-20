import { execFileSync } from 'node:child_process';

const allowed = new Set(process.argv.slice(2));
const threshold = ['high', 'critical'];

let report;
try {
    report = execFileSync('npm', ['audit', '--omit=dev', '--json'], { encoding: 'utf8' });
} catch (error) {
    report = error.stdout;
}

const { vulnerabilities = {} } = JSON.parse(report);

const advisories = new Map();

for (const vulnerability of Object.values(vulnerabilities)) {
    for (const via of vulnerability.via) {
        if (typeof via === 'object' && threshold.includes(via.severity)) {
            advisories.set(via.url, { name: via.name, severity: via.severity });
        }
    }
}

let blocking = 0;

for (const [url, { name, severity }] of advisories) {
    const id = url.split('/').pop();

    if (allowed.has(id)) {
        console.log(`::notice::Accepted risk, not failing: ${name} ${id} (${severity})`);
        continue;
    }

    blocking++;
    console.log(`::error::${name} ${id} (${severity}) has an unresolved advisory`);
}

process.exit(blocking > 0 ? 1 : 0);
