const { test, expect } = require('../../utils/fixtures');
const { navigateTo, clickEditOnRow } = require('../../utils/helpers');

// Regression: the admin ajax navigation rebuilds the Vue app per visit, but
// Vue's runtime compiler caches a component's render function by its
// `#template-selector` string in a module-global map that survives the rebuild.
// Reusing a selector (e.g. `#v-edit-user-role-template`) across records made the
// FIRST opened role's markup render for every later ajax visit until a full
// reload. Opening two roles without reloading must show each role's own data.
test.describe('Role edit — ajax navigation freshness', () => {
  const editedName = (page) =>
    page.locator('#role-edit-form #name').inputValue();

  const rowNames = async (page) => {
    const rows = page.locator('div.row.grid.cursor-pointer');
    await rows.first().waitFor({ state: 'visible', timeout: 30000 });

    return rows.evaluateAll((els) =>
      els
        .map((el) => el.querySelectorAll('p')[1]?.textContent?.trim())
        .filter(Boolean)
    );
  };

  test('opening two roles via the edit pencil shows each role, not the first', async ({ adminPage }) => {
    await navigateTo(adminPage, 'roles');

    let names = [...new Set(await rowNames(adminPage))];

    if (names.length < 2) {
      await adminPage.getByRole('link', { name: 'Create Role' }).click();
      await adminPage.waitForLoadState('networkidle');
      await adminPage.locator('#name').fill('AjaxNavProbeRole');
      await adminPage.locator('#role-edit-form button[type="submit"], button[type="submit"]').first().click();
      await adminPage.waitForLoadState('networkidle');

      names = [...new Set(await rowNames(adminPage))];
    }

    const [first, second] = names;

    await clickEditOnRow(adminPage, first);
    await expect(adminPage.locator('#role-edit-form #name')).toHaveValue(first);

    // In-app (ajax) return to the grid — a full reload would mask the bug by
    // dropping the compile cache.
    await adminPage.getByRole('link', { name: 'Roles', exact: true }).first().click();
    await adminPage.waitForLoadState('networkidle');

    await clickEditOnRow(adminPage, second);

    const shown = await editedName(adminPage);

    expect(shown).toBe(second);
    expect(shown).not.toBe(first);
  });
});
