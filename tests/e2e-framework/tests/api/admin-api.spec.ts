import { test, expect } from '../../fixtures/base-test';
import { modules } from '../../constants/modules';
import { environment } from '../../config/environment';
import { expectSuccessfulJson } from '../../custom-matchers/response-matchers';

const apiModules = modules.flatMap((module) =>
  (module.apiPaths ?? []).map((path) => ({
    module: module.name,
    path
  }))
);

test.describe('Admin API validation', () => {
  for (const item of apiModules) {
    test(`@api ${item.module} REST endpoint is protected: ${item.path}`, async ({ request, api }) => {
      const unauthenticated = await request.get(`${environment.apiBaseUrl}${item.path}`, {
        headers: { Accept: 'application/json' }
      });
      expect(unauthenticated.ok(), 'unauthenticated REST access must be denied').toBeFalsy();

      // Envelope validation needs a token; only runs when API credentials are set.
      const token = await api.authenticate();
      if (!token) {
        return;
      }

      const response = await api.get(item.path);
      await expectSuccessfulJson(response);
      expect(await response.json()).toBeTruthy();
    });
  }
});
