import { test, expect } from '../../fixtures/base-test';
import { modules } from '../../constants/modules';
import { expectSuccessfulJson } from '../../custom-matchers/response-matchers';

const apiModules = modules.flatMap((module) =>
  (module.apiPaths ?? []).map((path) => ({
    module: module.name,
    path
  }))
);

test.describe('Admin API validation', () => {
  test.beforeEach(async ({ api }) => {
    test.skip(!(await api.authenticate()), 'API credentials are not configured');
  });

  for (const item of apiModules) {
    test(`@api ${item.module} list endpoint returns JSON envelope: ${item.path}`, async ({ api }) => {
      const response = await api.get(item.path);
      await expectSuccessfulJson(response);
      const body = await response.json();
      expect(body).toBeTruthy();
    });
  }
});
