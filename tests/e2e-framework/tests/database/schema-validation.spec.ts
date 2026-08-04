import { test, expect } from '../../fixtures/base-test';
import { modules } from '../../constants/modules';

test.describe('Database validation', () => {
  for (const module of modules.filter((item) => item.table)) {
    test(`@database ${module.name} table is queryable: ${module.table}`, async ({ db }) => {
      const total = await db.count(module.table!);
      expect(total).toBeGreaterThanOrEqual(0);
    });
  }
});
