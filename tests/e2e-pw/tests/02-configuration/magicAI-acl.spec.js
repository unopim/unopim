const { test, expect } = require("../../utils/fixtures");
const { navigateTo } = require("../../utils/helpers");

test.describe("UnoPim Magic AI ACL Test Cases", () => {
    test("Verify Platforms permission exists in Role creation", async ({
        adminPage,
    }) => {
        await navigateTo(adminPage, "roles");
        await adminPage.getByRole("link", { name: "Create Role" }).click();

        await adminPage.waitForLoadState("networkidle");

        const magicAILayer = adminPage
            .locator("label")
            .filter({ hasText: "Magic AI" })
            .first();
        await expect(magicAILayer).toBeVisible();

        const platformsText = adminPage.getByText("Platform").first();
        if (!(await platformsText.isVisible())) {
            // Find any expand icon associated with Magic AI and click it
            const expander = adminPage
                .locator(".v-tree-item", { has: magicAILayer })
                .locator("i.icon-chevron-right")
                .first();
            if (await expander.isVisible()) {
                await expander.click();
            } else {
                // Secondary attempt (label click often works)
                await magicAILayer.click();
            }
        }

        await expect(adminPage.getByText("Platform").first()).toBeAttached({
            timeout: 10000,
        });
    });

    test("Verify Platforms menu visibility based on ACL", async ({
        adminPage,
    }) => {
        await adminPage.goto("/admin/magic-ai/platform", {
            waitUntil: "networkidle",
        });
        await expect(
            adminPage.locator('#app').getByText("AI Platforms", { exact: true }).first(),
        ).toBeVisible();

        // Check sidebar visibility
        const sidebar = adminPage.locator("nav");
        const magicAIMenu = sidebar.getByText("Magic AI").first();
        await expect(magicAIMenu).toBeVisible();
    });
});
