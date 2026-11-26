/**
 * Turn off every artifact that can capture a typed key for the calling spec
 * file; call it at the top level, since trace and screenshot are worker options.
 * Traces record action arguments and request bodies, screenshots show the page,
 * and the error-context page snapshot lists input values, password inputs included.
 * @param {import('@playwright/test').TestType<any, any>} test
 */
function keepSecretsOutOfArtifacts(test) {
  let previousNoCopyPrompt;

  test.use({ trace: 'off', screenshot: 'off' });

  test.beforeAll(() => {
    previousNoCopyPrompt = process.env.PLAYWRIGHT_NO_COPY_PROMPT;
    process.env.PLAYWRIGHT_NO_COPY_PROMPT = '1';
  });

  test.afterAll(() => {
    if (previousNoCopyPrompt === undefined) {
      delete process.env.PLAYWRIGHT_NO_COPY_PROMPT;
    } else {
      process.env.PLAYWRIGHT_NO_COPY_PROMPT = previousNoCopyPrompt;
    }
  });
}

/**
 * Type a secret into an input. locator.fill() writes the value into the step
 * title, which the HTML report keeps for every test, passing ones included.
 * @param {import('@playwright/test').Locator} locator
 * @param {string} secret
 */
async function fillSecret(locator, secret) {
  await locator.waitFor({ state: 'visible' });

  await locator.evaluate((input, value) => {
    input.focus();
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }, secret);
}

module.exports = {
  keepSecretsOutOfArtifacts,
  fillSecret,
};
