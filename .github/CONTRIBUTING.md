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
2. Target the `master` branch with your pull request, unless a maintainer asks you to backport the fix to a specific version branch (e.g. `2.1`).
3. Separate each fix into a new branch in your repository and name it with the issue ID e.g. `issue-1234`.
4. Use the [Conventional Commits](https://www.conventionalcommits.org/) format for your commit messages and reference the issue, e.g.
```fix(admin): prevent duplicate SKU on product creation (#1234)```
5. Please follow the pull request [template](https://github.com/unopim/unopim/blob/master/.github/PULL_REQUEST_TEMPLATE.md) as much as possible.

### **Did you create a new feature or enhancement?**

1. To provide a code contribution for a new feature or enhancement a [feature request](https://github.com/unopim/unopim/issues/new?template=feature_request.yml) report should be created in case it doesn't exist.
2. To contribute a feature to UnoPim, you must create a forked repository and set up your git and development environment.
3. Make sure your commit messages are relevant and descriptive.
4. Please follow the pull request [template](https://github.com/unopim/unopim/blob/master/.github/PULL_REQUEST_TEMPLATE.md) as much as possible.

### **Before submitting a pull request**

Continuous integration runs these checks on every pull request — run them locally first:

```bash
vendor/bin/pest         # PHP tests must pass
vendor/bin/pint --test  # code style must pass (run `vendor/bin/pint` to auto-fix)
```
