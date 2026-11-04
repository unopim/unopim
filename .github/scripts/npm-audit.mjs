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

const found = Object.values(vulnerabilities).filter((vulnerability) => threshold.includes(vulnerability.severity));
const skipped = found.filter((vulnerability) => allowed.has(vulnerability.name));
const blocking = found.filter((vulnerability) => !allowed.has(vulnerability.name));

for (const vulnerability of skipped) {
    console.log(`::notice::Accepted risk, not failing: ${vulnerability.name} (${vulnerability.severity})`);
}

for (const vulnerability of blocking) {
    console.log(`::error::${vulnerability.name} (${vulnerability.severity}) has an unresolved advisory`);
}

process.exit(blocking.length > 0 ? 1 : 0);
