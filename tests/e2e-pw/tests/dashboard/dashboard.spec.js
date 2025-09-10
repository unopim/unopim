const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Dashboard', () => {
test('Shows dashboard overview text', async ({ adminPage }) => {
  await expect(
  adminPage.getByText("Quickly monitoring, what's count in your PIM")
).toBeVisible();
});

test('Shows total products count', async ({ adminPage }) => {
  const count = adminPage.locator('text=Total Products').locator('..').locator('p.text-3xl');
  await expect(count).not.toHaveText('');
});

test('Shows total categories count', async ({ adminPage }) => {
  const count = adminPage.locator('text=Total Categories').locator('..').locator('p.text-3xl');
  await expect(count).not.toHaveText('');
});

test('Shows total attributes count', async ({ adminPage }) => {
  const count = adminPage.locator('text=Total Attributes').locator('..').locator('p.text-3xl');
  await expect(count).not.toHaveText('');
});

test('Shows total groups count', async ({ adminPage }) => {
  const count = adminPage.locator('text=Total Groups').locator('..').locator('p.text-3xl');
  await expect(count).not.toHaveText('');
});

test('Shows total families count', async ({ adminPage }) => {
  const count = adminPage.locator('text=Total families').locator('..').locator('p.text-3xl');
  await expect(count).not.toHaveText('');
});

test('Shows total locales count', async ({ adminPage }) => {
  const count = adminPage.locator('text=Total Locales').locator('..').locator('p.text-3xl');
  await expect(count).not.toHaveText('');
});

test('Shows total currencies count', async ({ adminPage }) => {
  const count = adminPage.locator('text=Total Currencies').locator('..').locator('p.text-3xl');
  await expect(count).not.toHaveText('');
});

test('Shows total channels count', async ({ adminPage }) => {
  const count = adminPage.locator('text=Total Channels').locator('..').locator('p.text-3xl');
  await expect(count).not.toHaveText('');
});

test('Total Products section shows icon and number', async ({ adminPage }) => {
  const section = adminPage.locator('text=Total Products').locator('..').locator('..');
  await expect(section.locator('img[title="Total Products"]')).toBeVisible();
  const numberText = await section.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('Total Categories section shows icon and number', async ({ adminPage }) => {
  const section = adminPage.locator('text=Total Categories').locator('..').locator('..');
  await expect(section.locator('img[title="Total Categories"]')).toBeVisible();
  const numberText = await section.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('Total Attributes section shows icon and number', async ({ adminPage }) => {
  const section = adminPage.locator('text=Total Attributes').locator('..').locator('..');
  await expect(section.locator('img[title="Total Attributes"]')).toBeVisible();
  const numberText = await section.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('Total Groups section shows icon and number', async ({ adminPage }) => {
  const section = adminPage.locator('text=Total Groups').locator('..').locator('..');
  await expect(section.locator('img[title="Total Groups"]')).toBeVisible();
  const numberText = await section.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('Total families section shows icon and number', async ({ adminPage }) => {
  const section = adminPage.locator('text=Total families').locator('..').locator('..');
  await expect(section.locator('img[title="Total families"]')).toBeVisible();
  const numberText = await section.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('Total Locales section shows icon and number', async ({ adminPage }) => {
  const section = adminPage.locator('text=Total Locales').locator('..').locator('..');
  await expect(section.locator('img[title="Total Locales"]')).toBeVisible();
  const numberText = await section.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('Total Currencies section shows icon and number', async ({ adminPage }) => {
  const section = adminPage.locator('text=Total Currencies').locator('..').locator('..');
  await expect(section.locator('img[title="Total Currencies"]')).toBeVisible();
  const numberText = await section.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('Total Channels section shows icon and number', async ({ adminPage }) => {
  const section = adminPage.locator('text=Total Channels').locator('..').locator('..');
  await expect(section.locator('img[title="Total Channels"]')).toBeVisible();
  const numberText = await section.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('Checks Dashboard text color in dark or light mode', async ({ adminPage }) => {
  const hasDarkMode = await adminPage.evaluate(() =>
  document.body?.classList.contains('dark-mode')
  );
  const greetingText = adminPage.getByText('Hi! Example');
  if (hasDarkMode) {
  await expect(greetingText).toHaveCSS('color', 'rgb(248, 250, 252)');
  console.log("Dark Theme")
  } else {
    await expect(greetingText).toHaveCSS('color', 'rgb(39, 39, 42)');
    console.log("Light Theme")
  }
  await adminPage.getByRole('banner').locator('span').first().click();
  await expect(greetingText).toHaveCSS('color', 'rgb(248, 250, 252)');
  console.log("Dark Theme")
});
});

