const base = require('../../playwright.config');

module.exports = {
  ...base,
  testDir: __dirname,
  testIgnore: undefined,
  testMatch: '**/route-perf-sweep.spec.js',
  workers: 1,
  reporter: [['list']],
};
