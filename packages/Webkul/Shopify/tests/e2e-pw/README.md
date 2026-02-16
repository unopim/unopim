# UnoPim Shopify Connector End-to-End Testing Guide

This document provides a step-by-step guide to set up and run end-to-end (E2E) tests for the UnoPim Shopify Connector using Playwright.

---

## **Prerequisites**

Before running the tests, ensure that the following dependencies are installed:

### **UnoPim Installation**

```bash
composer create-project unopim/unopim
```

### **UnoPim Dependencies**

```bash
composer install
```

### **Shopify Connector Plugin Installation**

```bash
composer require unopim/shopify-connector
composer dump-autoload
php artisan shopify-package:install
php artisan optimize:clear
```

---

## **Playwright Installation**

Install Playwright and its dependencies:

```bash
npm install @playwright/test
npx playwright install
```

---

## **Configure Playwright**

The `playwright.config.js` file is already configured to run tests in headless mode, with support for Firefox and Chromium browsers.

---

## **Run E2E Tests Locally**

To run the tests locally using the terminal, execute:

```bash
npx playwright test
```

Tests will run in headless mode, and the terminal will close automatically after the video recording is generated.

---

## **Run E2E Tests in CI/CD (GitHub Actions)**

A GitHub Actions YAML file is provided to automate the end-to-end testing process in the CI/CD pipeline.

### **Additional Commands for Shopify Installation in CI**

```bash
composer dump-autoload
php artisan shopify-package:install
php artisan optimize:clear
```

---

## **Test GitHub Actions Workflow Locally**

You can use the `act` tool to test the GitHub Actions YAML workflow locally:

```bash
act
```

Refer to the detailed guide [here](https://github.com/nektos/act) for installing and using `act`.

---

## **Video Recording**

All tests will generate video recordings, which will be saved automatically in the configured `output` directory within Playwright.

---

## **Final Notes**

- Ensure that UnoPim and Shopify Connector are properly installed and configured before running tests.
- Use the provided `playwright.config.js` and `act` tool for a seamless local and CI testing experience.

Happy Testing! 🎉

