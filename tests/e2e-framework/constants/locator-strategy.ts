export const locatorStrategy = {
  preferred: [
    'getByRole with accessible name',
    'getByLabel for form controls',
    'getByPlaceholder for search inputs',
    'getByText only for stable visible copy',
    'CSS data-test/data-testid attributes when available',
    'Scoped CSS selectors for legacy Blade/Vue components'
  ],
  forbidden: [
    'Absolute XPath',
    'Index-only selectors without a semantic scope',
    'Hardcoded generated asset hashes',
    'Selectors tied to translated text when a role/label/test id exists'
  ]
} as const;
