// helpers/login.js
export async function login(page) {
    await page.goto('http://localhost:8000'); // Replace with actual URL
    await page.fill('input[name="email"]', 'admin@example.com'); // Replace with valid email
    await page.fill('input[name="password"]', 'admin123'); // Replace with valid password
    await page.click('.primary-button');
    await page.waitForURL('http://localhost:8000/admin/dashboard'); // Wait for navigation
}

