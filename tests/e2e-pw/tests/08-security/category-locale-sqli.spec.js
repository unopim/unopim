const { test, expect } = require('@playwright/test');

// Reproduces SQL injection via the unvalidated `locale` request param on the
// Category DataGrid (DatabaseProductQueryBuilder path, ELASTICSEARCH_ENABLED=false).
//
// The grid data is loaded by an AJAX call, so a plain browser navigation
// (request()->ajax() === false) only returns the HTML shell and never runs the
// vulnerable query. We must send the request with X-Requested-With.

const ENDPOINT = '/admin/catalog/categories';

const ajax = (request, locale) =>
  request.get(`${ENDPOINT}?locale=${encodeURIComponent(locale)}`, {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      Accept: 'application/json',
    },
  });

test.describe('Category DataGrid locale SQL injection', () => {
  test('baseline: a valid locale returns 200 JSON', async ({ request }) => {
    const res = await ajax(request, 'en_US');
    expect(res.status()).toBe(200);
  });

  test('a single quote in locale no longer breaks the SQL', async ({ request }) => {
    const res = await ajax(request, "en_US'");
    // Patched: invalid locale falls back to default, never a SQL 500.
    expect(res.status()).not.toBe(500);
  });

  test('error-based version() payload leaks nothing', async ({ request }) => {
    const payload = "en_US.name',extractvalue(1,concat(0x7e,(select version()))),'";
    const res = await ajax(request, payload);
    const body = await res.text();

    expect(res.status()).not.toBe(500);
    expect(body).not.toMatch(/XPATH syntax error/);
  });

  test('error-based database() payload leaks nothing', async ({ request }) => {
    const payload = "en_US.name',extractvalue(1,concat(0x7e,(select database()))),'";
    const res = await ajax(request, payload);
    const body = await res.text();

    expect(res.status()).not.toBe(500);
    expect(body).not.toMatch(/XPATH syntax error/);
  });
});
