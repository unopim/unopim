const { test, expect } = require('../../utils/fixtures');
const { navigateTo, searchInDataGrid } = require('../../utils/helpers');

test.describe('Association Type - Case Insensitive Search and Filter (#1240)', () => {
  test.setTimeout(90000);

  test('should return the same records for every casing in the grid search', async ({ adminPage }) => {
    await navigateTo(adminPage, 'associationTypes');

    for (const term of ['Related', 'related', 'RELATED']) {
      await searchInDataGrid(adminPage, term);

      await expect(adminPage.getByText('related_products', { exact: true })).toBeVisible();
    }
  });

  test('should return the same records for every casing in the association type picker search', async ({ adminPage }) => {
    await navigateTo(adminPage, 'associationTypes');

    const results = {};

    for (const term of ['Related', 'related', 'RELATED']) {
      results[term] = await adminPage.evaluate(async (query) => {
        const response = await fetch(`/admin/catalog/association-types/search?query=${encodeURIComponent(query)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });

        const body = await response.json();

        return (body.data || []).map((type) => type.code).sort();
      }, term);
    }

    expect(results.related).toContain('related_products');
    expect(results.related).toEqual(results.Related);
    expect(results.RELATED).toEqual(results.Related);
  });
});
