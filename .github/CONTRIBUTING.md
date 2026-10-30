# How to contribute to UnoPim

### **Security Vulnerabilities**

Please do **not** report security vulnerabilities through public GitHub issues. Refer to our [Security Policy](../SECURITY.md) for responsible disclosure instructions.

### **Bug Reporting**

1. Verify that the bug was not already reported by searching on GitHub in the [Issues section](https://github.com/unopim/unopim/issues).
If you're unable to find an open issue, [open a new one](https://github.com/unopim/unopim/issues/new?template=bug.yml).

2. Verify that the bug you are reporting is a general issue and not specific to your individual setup.

#### **Did you fix a bug?**

1. To provide a code contribution for an issue you will need to set up your own fork of the [UnoPim repository](https://github.com/unopim/unopim).
Make your code changes, commit the changes and make a [Pull Request](https://help.github.com/articles/about-pull-requests/) to the [UnoPim repository](https://github.com/unopim/unopim).
2. Target the `3.x` branch, the current release line, with your pull request. `master` holds the next major version; a maintainer may ask you to backport a fix to an older supported branch (e.g. `2.1`).
3. Separate each fix into a new branch in your repository and name it with the issue ID e.g. `issue-1234`.
4. Use the [Conventional Commits](https://www.conventionalcommits.org/) format for your commit messages and reference the issue, e.g.
```fix(admin): prevent duplicate SKU on product creation (#1234)```
5. Please follow the pull request [template](https://github.com/unopim/unopim/blob/3.x/.github/PULL_REQUEST_TEMPLATE.md) as much as possible.

### **Did you create a new feature or enhancement?**

1. To provide a code contribution for a new feature or enhancement a [feature request](https://github.com/unopim/unopim/issues/new?template=feature_request.yml) report should be created in case it doesn't exist.
2. To contribute a feature to UnoPim, you must create a forked repository and set up your git and development environment.
3. Make sure your commit messages are relevant and descriptive.
4. Please follow the pull request [template](https://github.com/unopim/unopim/blob/3.x/.github/PULL_REQUEST_TEMPLATE.md) as much as possible.

### **Tests**

Every pull request that adds functionality or changes behaviour **must** include automated tests that cover it. Pull requests without tests for new code are not merged.

- **New features** — add [Pest](https://pestphp.com/) feature or unit tests under the package's `tests/` directory, e.g. `packages/Webkul/Product/tests/Feature/`.
- **Bug fixes** — add a test that fails without the fix and passes with it, so the bug cannot return.
- **Admin UI flows** — add or update a [Playwright](https://playwright.dev/) spec under `tests/e2e-pw/` when the change affects a user-visible flow.

### **Coding standards**

- PHP code follows the [Laravel Pint](https://laravel.com/docs/pint) `laravel` preset (PSR-12), configured in `pint.json`.
- Static analysis runs [Larastan](https://github.com/larastan/larastan) at the level set in `phpstan.neon`; new code must not add errors.
- User-facing text uses translation keys, added to every locale under the package's `Resources/lang/` directory.
- Database code must work on MySQL, MariaDB and PostgreSQL.

### **Before submitting a pull request**

Continuous integration runs these checks on every pull request — run them locally first:

```bash
vendor/bin/pest                          # PHP tests must pass
vendor/bin/pint --test                   # code style must pass (run `vendor/bin/pint` to auto-fix)
vendor/bin/phpstan analyse               # static analysis must report no errors
php artisan unopim:translations:check    # every locale must have every key
composer audit --locked                  # no known vulnerable PHP dependencies
```

CI also runs the Pest suite on MySQL, MariaDB and PostgreSQL, the Playwright end-to-end tests, Rector, and security scanning.
