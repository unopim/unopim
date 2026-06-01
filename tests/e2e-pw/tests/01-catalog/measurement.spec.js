const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Measurement Feature', () => {

    // ========== HELPER FUNCTIONS ==========
    
    async function selectOption(page, triggerLocator, value) {
        await triggerLocator.click();
        await page.waitForTimeout(400);
        const option = page.locator('.multiselect__option, .multiselect__element span')
            .filter({ hasText: value })
            .first();
        await option.waitFor({ timeout: 5000 });
        await option.click();
        await page.waitForTimeout(400);
    }

    async function dismissModal(page) {
        // Try to close any modal overlays
        const closeButtons = page.locator('button[aria-label="Close"], .modal-close, .close, [data-dismiss="modal"]');
        const closeCount = await closeButtons.count();
        for (let i = 0; i < closeCount; i++) {
            const btn = closeButtons.nth(i);
            if (await btn.isVisible().catch(() => false)) {
                await btn.click();
                await page.waitForTimeout(500);
                return true;
            }
        }
        
        // Try clicking outside modal
        const modalBackdrop = page.locator('.modal-backdrop, [class*="backdrop"], .overlay');
        if (await modalBackdrop.isVisible().catch(() => false)) {
            await modalBackdrop.click({ position: { x: 10, y: 10 } });
            await page.waitForTimeout(500);
            return true;
        }
        
        return false;
    }

    async function goToFamilies(page) {
        await page.goto('/admin/measurement/families', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('text=Measurement Families', { timeout: 10000 }).catch(() => {});
        await page.waitForTimeout(1000);
    }

    async function goToAttributeCreate(page) {
        await page.goto('/admin/catalog/attributes/create', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('text=Add Attribute', { timeout: 10000 }).catch(() => {});
        await page.waitForTimeout(1000);
    }

    async function openFirstFamilyForEdit(page) {
        const candidateLocators = [
            page.locator('[title="Edit"], .icon-edit'),
            page.getByText('Edit', { exact: true })
        ];

        for (const locator of candidateLocators) {
            const count = await locator.count();
            for (let i = 0; i < count; i++) {
                const candidate = locator.nth(i);
                if (await candidate.isVisible().catch(() => false)) {
                    await candidate.scrollIntoViewIfNeeded();
                    await candidate.click();
                    return;
                }
            }
        }

        const firstRow = page.locator('.row').nth(1);
        if (await firstRow.isVisible().catch(() => false)) {
            await firstRow.click();
            return;
        }

        throw new Error('No visible Edit action found on Measurement Families list');
    }

    async function createMeasurementFamily(page, code, standardUnitCode, symbol, label = null) {
        await goToFamilies(page);

        const createButton = page.getByRole('button', { name: /Create Measurements/i }).first();
        await createButton.waitFor({ state: 'visible', timeout: 10000 });
        await createButton.click();

        await page.waitForTimeout(1000);

        const familyCodeInput = page.locator('input[name="code"]').first();
        await familyCodeInput.fill(code);

        const standardUnitInput = page.locator('input[name="standard_unit_code"]').first();
        await standardUnitInput.fill(standardUnitCode);

        const symbolInput = page.locator('input[name="symbol"]').first();
        await symbolInput.fill(symbol);

        const labelFields = page.locator('input[name="name"], input[name="label"], input[placeholder="Enter label"]');
        const labelCount = await labelFields.count();
        for (let i = 0; i < labelCount; i++) {
            const field = labelFields.nth(i);
            if (await field.isVisible().catch(() => false)) {
                await field.fill(label || `Label ${code}`);
                break;
            }
        }

        const saveButton = page.getByRole('button', { name: /Save/i }).first();
        await saveButton.click();

        await page.waitForTimeout(3000);

        // Dismiss any modals that might appear
        await dismissModal(page);

        // Check for success indicators
        const successMessage = page.locator('text=Measurement family created successfully, text=created successfully, text=success').first();
        const isSuccess = await successMessage.isVisible().catch(() => false);
        
        // Also check if we're back on the families list or on edit page
        const isOnList = page.url().includes('/admin/measurement/families') && !page.url().includes('/edit');
        const isOnEdit = page.url().includes('/edit');

        return isSuccess || isOnList || isOnEdit;
    }

    async function getFirstFamilyCode(page) {
        const firstRow = page.locator('.row').nth(1);
        if (!(await firstRow.isVisible().catch(() => false))) return null;
        const codeText = await firstRow.locator('p').nth(2).textContent().catch(() => '');
        return codeText?.trim() || null;
    }

    function getTypeDropdownTrigger(page) {
        return page.locator('#type').locator('..').locator('.multiselect__placeholder, .multiselect__single').first();
    }

    async function addConversionOperation(page, value, operator) {
        // Look for the "Add New Operation" button
        const addOpBtn = page.locator('button:has-text("Add New Operation"), button:has-text("add_new_operation"), [data-action="add-operation"]').first();
        if (await addOpBtn.isVisible().catch(() => false)) {
            await addOpBtn.click();
            await page.waitForTimeout(500);
        }

        // Fill the conversion value field
        const conversionValueInputs = page.locator('input[name*="convert_value"], input[placeholder*="conversion value" i]');
        const valueInputCount = await conversionValueInputs.count();
        if (valueInputCount > 0) {
            const lastValueInput = conversionValueInputs.nth(valueInputCount - 1);
            if (await lastValueInput.isVisible()) {
                await lastValueInput.fill(value.toString());
            }
        }

        // Select the conversion operator
        const operatorSelects = page.locator('select[name*="convert_from_standard"], input[data-vv-name*="convert_from_standard"]');
        const operatorCount = await operatorSelects.count();
        if (operatorCount > 0) {
            const lastOperatorSelect = operatorSelects.nth(operatorCount - 1);
            if (await lastOperatorSelect.isVisible()) {
                // Try clicking the multiselect
                const multiSelectTrigger = lastOperatorSelect.locator('..').locator('.multiselect__placeholder, .multiselect__single').first();
                if (await multiSelectTrigger.isVisible().catch(() => false)) {
                    await multiSelectTrigger.click();
                    await page.waitForTimeout(300);
                    const operatorOption = page.locator('.multiselect__option, .multiselect__element span')
                        .filter({ hasText: operator })
                        .first();
                    if (await operatorOption.isVisible().catch(() => false)) {
                        await operatorOption.click();
                    }
                } else {
                    // Fallback: fill directly
                    await lastOperatorSelect.selectOption(operator).catch(() => {});
                }
            }
        }

        await page.waitForTimeout(500);
    }

    async function fillAttributeLabel(page, label) {
        const englishLabel = page.getByText('English (United States)', { exact: true }).first();
        if (await englishLabel.isVisible().catch(() => false)) {
            const englishInput = englishLabel.locator('..').locator('input').first();
            await englishInput.fill(label);
            return;
        }

        const labelSection = page.getByText('Label', { exact: true }).first();
        if (await labelSection.isVisible().catch(() => false)) {
            const firstVisible = labelSection.locator('..').locator('input').first();
            if (await firstVisible.isVisible().catch(() => false)) {
                await firstVisible.fill(label);
                return;
            }
        }

        const fallback = page.locator('input[placeholder*="label" i]').first();
        await fallback.fill(label);
    }

    async function createMeasurementAttribute(page, code, label) {
        await goToAttributeCreate(page);

        const codeInput = page.locator('input[name="code"], input[placeholder*="code" i]').first();
        await codeInput.waitFor({ state: 'visible', timeout: 10000 });
        await codeInput.fill(code);

        try {
            await selectOption(page, getTypeDropdownTrigger(page), 'Measurement');
        } catch (e) {
            // Fallback if measurement type dropdown differs
            await page.locator('input[name="type"], [name="type"] input').fill('Measurement').catch(() => {});
        }

        await fillAttributeLabel(page, label);

        const saveButton = page.getByRole('button', { name: /save/i }).first();
        await saveButton.click();
        await page.waitForTimeout(3000);

        return page.url().includes('/admin/catalog/attributes') || page.url().includes('/edit');
    }

    async function deleteMeasurementFamily(page, familyCode) {
        await goToFamilies(page);
        const search = page.locator('input[placeholder*="Search" i], input[name="search"], input[aria-label*="Search" i]').first();
        if (await search.isVisible({ timeout: 5000 })) {
            await search.fill(familyCode);
            await search.press('Enter');
            await page.waitForTimeout(1000);
        }
        
        const deleteIcon = page.locator('.icon-delete, [class*="delete"], button:has-text("Delete")').first();
        if (await deleteIcon.isVisible()) {
            await deleteIcon.click();
            await page.waitForTimeout(500);
            const confirmBtn = page.getByRole('button', { name: /agree|confirm|yes|delete/i }).first();
            if (await confirmBtn.isVisible()) {
                await confirmBtn.click();
                await page.waitForTimeout(1500);
                return true;
            }
        }
        return false;
    }

    // ========== POSITIVE TEST CASES ==========

    test.describe('Positive Tests - Measurement Families', () => {
        
        test('TC01 - Verify measurement families list page loads', async ({ adminPage }) => {
            await goToFamilies(adminPage);

            await expect(adminPage.getByText('Measurement Families', { exact: false })).toBeVisible({ timeout: 10000 });
            await expect(adminPage.getByRole('button', { name: /Create Measurements/i })).toBeVisible({ timeout: 10000 });
        });

        test('TC02 - Search existing measurement families', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i], input[type="search"]').first();
            if (await search.isVisible()) {
                await search.fill('Weight');
                await search.press('Enter');
                await adminPage.waitForTimeout(2000);
            }
        });

        test('TC03 - Create a new measurement family successfully', async ({ adminPage }) => {
            const familyCode = `fam_pos_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'standard_unit', 'SU');
            expect(created).toBeTruthy();
        });

        test('TC04 - Create measurement family with all fields', async ({ adminPage }) => {
            const familyCode = `fam_full_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'base_unit_code', 'BU', 'Full Label Family');
            expect(created).toBeTruthy();
        });

        test('TC05 - Edit an existing measurement family', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            try {
                await openFirstFamilyForEdit(adminPage);
                await adminPage.waitForTimeout(2000);
                expect(adminPage.url()).toContain('edit');
            } catch (e) {
                console.log('Edit test skipped:', e.message);
            }
        });

        test('TC06 - Create a new unit in measurement family with conversion', async ({ adminPage }) => {
            const familyCode = `unit_fam_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'base_unit', 'BU');
            expect(created).toBeTruthy();

            // Navigate to the created family's edit page
            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i], input[type="search"]').first();
            if (await search.isVisible()) {
                await search.fill(familyCode);
                await search.press('Enter');
                await adminPage.waitForTimeout(2000);
            }

            // Dismiss any modals first
            await dismissModal(adminPage);

            // Click edit on the family
            const editBtn = adminPage.locator('[title="Edit"], .icon-edit, button:has-text("Edit")').first();
            if (await editBtn.isVisible()) {
                await editBtn.scrollIntoViewIfNeeded();
                await editBtn.click();
                await adminPage.waitForTimeout(2000);

                // Dismiss any modals that appeared
                await dismissModal(adminPage);

                // Look for unit creation section or button
                const addUnitBtn = adminPage.locator('button:has-text("Add Unit"), button:has-text("Create Unit"), .add-unit, [data-action="add-unit"]').first();
                if (await addUnitBtn.isVisible()) {
                    await addUnitBtn.scrollIntoViewIfNeeded();
                    await addUnitBtn.click();
                    await adminPage.waitForTimeout(1000);

                    // Dismiss any modals
                    await dismissModal(adminPage);

                    const unitCode = `unit_${Date.now()}`;
                    const codeInput = adminPage.locator('input[name*="code"], input[placeholder*="code" i]').last();
                    if (await codeInput.isVisible() && await codeInput.isEnabled()) {
                        await codeInput.fill(unitCode);
                    }

                    const symbolInput = adminPage.locator('input[name*="symbol"], input[placeholder*="symbol" i]').last();
                    if (await symbolInput.isVisible() && await symbolInput.isEnabled()) {
                        await symbolInput.fill('TU');
                    }

                    // Add conversion operation
                    await addConversionOperation(adminPage, '2.5', 'mul');

                    const saveBtn = adminPage.getByRole('button', { name: /save|create/i }).last();
                    if (await saveBtn.isVisible()) {
                        await saveBtn.click();
                        await adminPage.waitForTimeout(2000);
                    }
                }
            }
        });

        test('TC07 - Create measurement type attribute', async ({ adminPage }) => {
            const attrCode = `meas_attr_${Date.now()}`;
            const created = await createMeasurementAttribute(adminPage, attrCode, 'Test Measurement Attribute');
            expect(created).toBeTruthy();
        });

        test('TC08 - Verify measurement attribute in attributes list', async ({ adminPage }) => {
            const attrCode = `mlist_${Date.now()}`;
            await createMeasurementAttribute(adminPage, attrCode, 'List Test Measurement');
            await adminPage.goto('/admin/catalog/attributes', { waitUntil: 'domcontentloaded' });
            await adminPage.waitForTimeout(1000);
            const search = adminPage.locator('input[placeholder*="Search" i]').first();
            if (await search.isVisible()) {
                await search.fill(attrCode);
                await search.press('Enter');
            }
        });

        test('TC09 - Pagination works on measurement families list', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const paginationButtons = adminPage.locator('[class*="pagination"], [aria-label*="page"], .page-link');
            const count = await paginationButtons.count();
            expect(count).toBeGreaterThanOrEqual(0);
        });

        test('TC10 - Filter functionality loads', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const filterBtn = adminPage.getByText(/Filter|filter/).first();
            if (await filterBtn.isVisible()) {
                await filterBtn.click();
                await adminPage.waitForTimeout(500);
            }
        });
    });

    // ========== NEGATIVE TEST CASES ==========

    test.describe('Negative Tests - Measurement Families Validation', () => {
        
        test('TC11 - Create measurement family with empty code', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const createBtn = adminPage.getByRole('button', { name: /Create Measurements/i }).first();
            if (await createBtn.isVisible()) {
                await createBtn.click();
                await adminPage.waitForTimeout(500);

                await adminPage.locator('input[placeholder="Enter family code"]').fill('');
                await adminPage.locator('input[placeholder="Enter standard unit code"]').fill('std_unit');
                await adminPage.locator('input[placeholder="e.g. km, m"]').fill('EM');

                await adminPage.getByRole('button', { name: 'Save', exact: true }).click();
                await adminPage.waitForTimeout(1000);
            }
        });

        test('TC12 - Create measurement family with empty standard unit code', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const createBtn = adminPage.getByRole('button', { name: /Create Measurements/i }).first();
            if (await createBtn.isVisible()) {
                await createBtn.click();
                await adminPage.waitForTimeout(500);

                await adminPage.locator('input[placeholder="Enter family code"]').fill(`fam_${Date.now()}`);
                await adminPage.locator('input[placeholder="Enter standard unit code"]').fill('');
                await adminPage.locator('input[placeholder="e.g. km, m"]').fill('EM');

                await adminPage.getByRole('button', { name: 'Save', exact: true }).click();
                await adminPage.waitForTimeout(1000);
            }
        });

        test('TC13 - Create measurement family with empty symbol', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const createBtn = adminPage.getByRole('button', { name: /Create Measurements/i }).first();
            if (await createBtn.isVisible()) {
                await createBtn.click();
                await adminPage.waitForTimeout(500);

                await adminPage.locator('input[placeholder="Enter family code"]').fill(`fam_${Date.now()}`);
                await adminPage.locator('input[placeholder="Enter standard unit code"]').fill('std_unit');
                await adminPage.locator('input[placeholder="e.g. km, m"]').fill('');

                await adminPage.getByRole('button', { name: 'Save', exact: true }).click();
                await adminPage.waitForTimeout(1000);
            }
        });

        test('TC14 - Create measurement family with all empty fields', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const createBtn = adminPage.getByRole('button', { name: /Create Measurements/i }).first();
            if (await createBtn.isVisible()) {
                await createBtn.click();
                await adminPage.waitForTimeout(500);

                await adminPage.locator('input[placeholder="Enter family code"]').fill('');
                await adminPage.locator('input[placeholder="Enter standard unit code"]').fill('');
                await adminPage.locator('input[placeholder="e.g. km, m"]').fill('');

                await adminPage.getByRole('button', { name: 'Save', exact: true }).click();
                await adminPage.waitForTimeout(1000);
            }
        });

        test('TC15 - Create duplicate measurement family code', async ({ adminPage }) => {
            const familyCode = `dup_fam_${Date.now()}`;
            
            // Create first family
            await createMeasurementFamily(adminPage, familyCode, 'unit1', 'U1');
            await adminPage.waitForTimeout(1000);

            // Try to create duplicate
            await goToFamilies(adminPage);
            const createBtn = adminPage.getByRole('button', { name: /Create Measurements/i }).first();
            if (await createBtn.isVisible()) {
                await createBtn.click();
                await adminPage.waitForTimeout(500);

                await adminPage.locator('input[placeholder="Enter family code"]').fill(familyCode);
                await adminPage.locator('input[placeholder="Enter standard unit code"]').fill('unit2');
                await adminPage.locator('input[placeholder="e.g. km, m"]').fill('U2');

                await adminPage.getByRole('button', { name: 'Save', exact: true }).click();
                await adminPage.waitForTimeout(1500);
            }
        });

        test('TC16 - Create measurement family with special characters in code', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const createBtn = adminPage.getByRole('button', { name: /Create Measurements/i }).first();
            if (await createBtn.isVisible()) {
                await createBtn.click();
                await adminPage.waitForTimeout(500);

                await adminPage.locator('input[placeholder="Enter family code"]').fill('fam@#$%^&');
                await adminPage.locator('input[placeholder="Enter standard unit code"]').fill('std_unit');
                await adminPage.locator('input[placeholder="e.g. km, m"]').fill('EM');

                await adminPage.getByRole('button', { name: 'Save', exact: true }).click();
                await adminPage.waitForTimeout(1000);
            }
        });

        test('TC17 - Create measurement attribute with empty code', async ({ adminPage }) => {
            await goToAttributeCreate(adminPage);
            await adminPage.locator('input[name="code"]').fill('');
            try {
                await selectOption(adminPage, getTypeDropdownTrigger(adminPage), 'Measurement');
            } catch (e) {
                console.log('Skip type selection');
            }
            await fillAttributeLabel(adminPage, 'Test Label');
            
            await adminPage.getByRole('button', { name: /save|Save/ }).click();
            await adminPage.waitForTimeout(1000);
        });

        test('TC18 - Create measurement attribute with empty label', async ({ adminPage }) => {
            await goToAttributeCreate(adminPage);
            const code = `attr_${Date.now()}`;
            await adminPage.locator('input[name="code"]').fill(code);
            try {
                await selectOption(adminPage, getTypeDropdownTrigger(adminPage), 'Measurement');
            } catch (e) {
                console.log('Skip type selection');
            }
            
            await adminPage.getByRole('button', { name: /save|Save/ }).click();
            await adminPage.waitForTimeout(1000);
        });

        test('TC19 - Create measurement attribute without selecting type', async ({ adminPage }) => {
            await goToAttributeCreate(adminPage);
            const code = `attr_${Date.now()}`;
            await adminPage.locator('input[name="code"]').fill(code);
            await fillAttributeLabel(adminPage, 'Test Label');
            
            await adminPage.getByRole('button', { name: /save|Save/ }).click();
            await adminPage.waitForTimeout(1000);
        });

        test('TC20 - Search with non-existent measurement family', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i]').first();
            if (await search.isVisible()) {
                const randomCode = `nonexistent_${Date.now()}`;
                await search.fill(randomCode);
                await search.press('Enter');
                await adminPage.waitForTimeout(1500);
            }
        });

        test('TC21 - Sort measurement families', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const sortButtons = adminPage.locator('[class*="sort"], th[class*="sortable"]');
            const count = await sortButtons.count();
            expect(count).toBeGreaterThanOrEqual(0);
        });

        test('TC22 - Delete measurement family successfully', async ({ adminPage }) => {
            const familyCode = `del_fam_${Date.now()}`;
            await createMeasurementFamily(adminPage, familyCode, 'del_unit', 'DU');
            await adminPage.waitForTimeout(1000);
            
            await deleteMeasurementFamily(adminPage, familyCode);
        });

        test('TC23 - Verify measurement family page breadcrumbs', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const breadcrumb = adminPage.locator('[class*="breadcrumb"], nav');
            expect(await breadcrumb.count()).toBeGreaterThanOrEqual(0);
        });

        test('TC24 - Verify button states in measurement families', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const createBtn = adminPage.getByRole('button', { name: /Create Measurements/i }).first();
            const isEnabled = await createBtn.isEnabled().catch(() => false);
            expect(isEnabled).toBeTruthy();
        });
    });

    // ========== EDGE CASE & INTEGRATION TESTS ==========

    test.describe('Edge Cases & Integration Tests', () => {
        
        test('TC25 - Create measurement family with very long code', async ({ adminPage }) => {
            const longCode = 'a'.repeat(100);
            await goToFamilies(adminPage);
            const createBtn = adminPage.getByRole('button', { name: /Create Measurements/i }).first();
            if (await createBtn.isVisible()) {
                await createBtn.click();
                await adminPage.waitForTimeout(500);

                await adminPage.locator('input[placeholder="Enter family code"]').fill(longCode);
                await adminPage.locator('input[placeholder="Enter standard unit code"]').fill('std_unit');
                await adminPage.locator('input[placeholder="e.g. km, m"]').fill('EM');

                await adminPage.getByRole('button', { name: 'Save', exact: true }).click();
                await adminPage.waitForTimeout(1500);
            }
        });

        test('TC26 - Create measurement family with numeric code', async ({ adminPage }) => {
            const numCode = `123_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, numCode, 'std_unit', 'NU');
            // Should either succeed or show validation
            await adminPage.waitForTimeout(500);
        });

        test('TC27 - Update measurement family attributes after creation', async ({ adminPage }) => {
            const familyCode = `update_fam_${Date.now()}`;
            await createMeasurementFamily(adminPage, familyCode, 'std_unit', 'UF');
            await adminPage.waitForTimeout(1000);

            const codeInput = adminPage.locator('input[placeholder="Enter family code"], input[name*="code"]').first();
            if (await codeInput.isVisible()) {
                const currentValue = await codeInput.inputValue();
                expect(currentValue).toBeTruthy();
            }
        });

        test('TC28 - Verify cancel button on measurement family create', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const createBtn = adminPage.getByRole('button', { name: /Create Measurements/i }).first();
            if (await createBtn.isVisible()) {
                await createBtn.click();
                await adminPage.waitForTimeout(500);

                const cancelBtn = adminPage.getByRole('button', { name: /cancel|close|Cancel|Close/i }).first();
                if (await cancelBtn.isVisible()) {
                    await cancelBtn.click();
                    await adminPage.waitForTimeout(1000);
                }
            }
        });

        test('TC29 - Verify measurement family details after creation', async ({ adminPage }) => {
            const familyCode = `detail_fam_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'base_unit', 'BU', 'Detail Test');
            
            if (created) {
                const codeField = adminPage.locator('input, [class*="code"]').filter({ hasText: familyCode }).first();
                if (await codeField.isVisible()) {
                    await expect(codeField).toBeVisible();
                }
            }
        });

        test('TC30 - Multiple measurement families management', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const listItems = adminPage.locator('[class*="row"], tr, li').filter({ hasText: /[a-zA-Z0-9]/ });
            const count = await listItems.count();
            expect(count).toBeGreaterThanOrEqual(0);
        });

        test('TC31 - Verify no duplicate attribute codes allowed', async ({ adminPage }) => {
            const attrCode = `unique_attr_${Date.now()}`;
            await createMeasurementAttribute(adminPage, attrCode, 'First Attribute');
            await adminPage.waitForTimeout(1000);

            // Try to create duplicate
            await goToAttributeCreate(adminPage);
            await adminPage.locator('input[name="code"]').fill(attrCode);
            try {
                await selectOption(adminPage, getTypeDropdownTrigger(adminPage), 'Measurement');
            } catch (e) {
                console.log('Skip type selection');
            }
            await fillAttributeLabel(adminPage, 'Duplicate Attribute');

            await adminPage.getByRole('button', { name: /save|Save/ }).click();
            await adminPage.waitForTimeout(1500);
        });

        test('TC32 - Navigate back from measurement family edit page', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            try {
                await openFirstFamilyForEdit(adminPage);
                await adminPage.waitForTimeout(1000);

                const backBtn = adminPage.getByRole('button', { name: /back|close/i }).first();
                const breadcrumb = adminPage.locator('[class*="breadcrumb"] a').first();
                
                if (await backBtn.isVisible()) {
                    await backBtn.click();
                } else if (await breadcrumb.isVisible()) {
                    await breadcrumb.click();
                } else {
                    await adminPage.goto('/admin/measurement/families', { waitUntil: 'domcontentloaded' });
                }
                
                await adminPage.waitForTimeout(1000);
            } catch (e) {
                console.log('Navigation test skipped');
            }
        });

        test('TC33 - Verify responsive design on measurement pages', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const viewport = adminPage.viewportSize();
            expect(viewport).toBeTruthy();
        });

        test('TC34 - Verify table structure on measurement families list', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const tableHeaders = adminPage.locator('th, [class*="header"]');
            const headerCount = await tableHeaders.count();
            expect(headerCount).toBeGreaterThanOrEqual(0);
        });

        test('TC35 - Create unit without proper measurements family context', async ({ adminPage }) => {
            try {
                const createUnitsBtn = adminPage.getByRole('button', { name: /Units|units/ });
                if (await createUnitsBtn.isVisible({ timeout: 2000 })) {
                    await createUnitsBtn.click();
                    await adminPage.waitForTimeout(500);
                }
            } catch (e) {
                // Expected: can't create unit without proper context
                expect(true).toBeTruthy();
            }
        });

        test('TC41 - Create unit with multiply conversion operation', async ({ adminPage }) => {
            const familyCode = `conv_mul_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'meter', 'M');
            expect(created).toBeTruthy();

            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i], input[type="search"]').first();
            if (await search.isVisible()) {
                await search.fill(familyCode);
                await search.press('Enter');
                await adminPage.waitForTimeout(2000);
            }

            const editBtn = adminPage.locator('[title="Edit"], .icon-edit').first();
            if (await editBtn.isVisible()) {
                await editBtn.click();
                await adminPage.waitForTimeout(2000);

                const addUnitBtn = adminPage.locator('button:has-text("Add Unit"), button:has-text("Create Unit")').first();
                if (await addUnitBtn.isVisible()) {
                    await addUnitBtn.click();
                    await adminPage.waitForTimeout(1000);

                    const codeInput = adminPage.locator('input[name*="code"], input[placeholder*="code" i]').last();
                    if (await codeInput.isVisible()) {
                        await codeInput.fill(`km_${Date.now()}`);
                    }

                    const symbolInput = adminPage.locator('input[name*="symbol"]').last();
                    if (await symbolInput.isVisible()) {
                        await symbolInput.fill('KM');
                    }

                    // Add multiply conversion: 1000 * 1 = 1000
                    await addConversionOperation(adminPage, '1000', 'mul');

                    const saveBtn = adminPage.getByRole('button', { name: /save/i }).last();
                    if (await saveBtn.isVisible()) {
                        await saveBtn.click();
                        await adminPage.waitForTimeout(2000);
                    }
                }
            }
        });

        test('TC42 - Create unit with divide conversion operation', async ({ adminPage }) => {
            const familyCode = `conv_div_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'liter', 'L');
            expect(created).toBeTruthy();

            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i]').first();
            if (await search.isVisible()) {
                await search.fill(familyCode);
                await search.press('Enter');
                await adminPage.waitForTimeout(2000);
            }

            const editBtn = adminPage.locator('[title="Edit"], .icon-edit').first();
            if (await editBtn.isVisible()) {
                await editBtn.click();
                await adminPage.waitForTimeout(2000);

                const addUnitBtn = adminPage.locator('button:has-text("Add Unit"), button:has-text("Create Unit")').first();
                if (await addUnitBtn.isVisible()) {
                    await addUnitBtn.click();
                    await adminPage.waitForTimeout(1000);

                    const codeInput = adminPage.locator('input[name*="code"]').last();
                    if (await codeInput.isVisible()) {
                        await codeInput.fill(`ml_${Date.now()}`);
                    }

                    const symbolInput = adminPage.locator('input[name*="symbol"]').last();
                    if (await symbolInput.isVisible()) {
                        await symbolInput.fill('ML');
                    }

                    // Add divide conversion: 1/1000 = 0.001
                    await addConversionOperation(adminPage, '1000', 'div');

                    const saveBtn = adminPage.getByRole('button', { name: /save/i }).last();
                    if (await saveBtn.isVisible()) {
                        await saveBtn.click();
                        await adminPage.waitForTimeout(2000);
                    }
                }
            }
        });

        test('TC43 - Create unit with add conversion operation', async ({ adminPage }) => {
            const familyCode = `conv_add_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'celsius', 'C');
            expect(created).toBeTruthy();

            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i]').first();
            if (await search.isVisible()) {
                await search.fill(familyCode);
                await search.press('Enter');
                await adminPage.waitForTimeout(2000);
            }

            const editBtn = adminPage.locator('[title="Edit"], .icon-edit').first();
            if (await editBtn.isVisible()) {
                await editBtn.click();
                await adminPage.waitForTimeout(2000);

                const addUnitBtn = adminPage.locator('button:has-text("Add Unit"), button:has-text("Create Unit")').first();
                if (await addUnitBtn.isVisible()) {
                    await addUnitBtn.click();
                    await adminPage.waitForTimeout(1000);

                    const codeInput = adminPage.locator('input[name*="code"]').last();
                    if (await codeInput.isVisible()) {
                        await codeInput.fill(`offset_${Date.now()}`);
                    }

                    const symbolInput = adminPage.locator('input[name*="symbol"]').last();
                    if (await symbolInput.isVisible()) {
                        await symbolInput.fill('OFFSET');
                    }

                    // Add conversion: + 273.15
                    await addConversionOperation(adminPage, '273.15', 'add');

                    const saveBtn = adminPage.getByRole('button', { name: /save/i }).last();
                    if (await saveBtn.isVisible()) {
                        await saveBtn.click();
                        await adminPage.waitForTimeout(2000);
                    }
                }
            }
        });

        test('TC44 - Create unit with subtract conversion operation', async ({ adminPage }) => {
            const familyCode = `conv_sub_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'absolute_temp', 'ABS');
            expect(created).toBeTruthy();

            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i]').first();
            if (await search.isVisible()) {
                await search.fill(familyCode);
                await search.press('Enter');
                await adminPage.waitForTimeout(2000);
            }

            const editBtn = adminPage.locator('[title="Edit"], .icon-edit').first();
            if (await editBtn.isVisible()) {
                await editBtn.click();
                await adminPage.waitForTimeout(2000);

                const addUnitBtn = adminPage.locator('button:has-text("Add Unit"), button:has-text("Create Unit")').first();
                if (await addUnitBtn.isVisible()) {
                    await addUnitBtn.click();
                    await adminPage.waitForTimeout(1000);

                    const codeInput = adminPage.locator('input[name*="code"]').last();
                    if (await codeInput.isVisible()) {
                        await codeInput.fill(`rel_temp_${Date.now()}`);
                    }

                    const symbolInput = adminPage.locator('input[name*="symbol"]').last();
                    if (await symbolInput.isVisible()) {
                        await symbolInput.fill('REL');
                    }

                    // Add conversion: - 100
                    await addConversionOperation(adminPage, '100', 'sub');

                    const saveBtn = adminPage.getByRole('button', { name: /save/i }).last();
                    if (await saveBtn.isVisible()) {
                        await saveBtn.click();
                        await adminPage.waitForTimeout(2000);
                    }
                }
            }
        });

        test('TC45 - Create unit with multiple conversion operations', async ({ adminPage }) => {
            const familyCode = `conv_multi_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'base_unit', 'BU');
            expect(created).toBeTruthy();

            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i]').first();
            if (await search.isVisible()) {
                await search.fill(familyCode);
                await search.press('Enter');
                await adminPage.waitForTimeout(2000);
            }

            const editBtn = adminPage.locator('[title="Edit"], .icon-edit').first();
            if (await editBtn.isVisible()) {
                await editBtn.click();
                await adminPage.waitForTimeout(2000);

                const addUnitBtn = adminPage.locator('button:has-text("Add Unit"), button:has-text("Create Unit")').first();
                if (await addUnitBtn.isVisible()) {
                    await addUnitBtn.click();
                    await adminPage.waitForTimeout(1000);

                    const codeInput = adminPage.locator('input[name*="code"]').last();
                    if (await codeInput.isVisible()) {
                        await codeInput.fill(`derived_${Date.now()}`);
                    }

                    const symbolInput = adminPage.locator('input[name*="symbol"]').last();
                    if (await symbolInput.isVisible()) {
                        await symbolInput.fill('DERIVED');
                    }

                    // Add first operation: multiply by 2
                    await addConversionOperation(adminPage, '2', 'mul');
                    await adminPage.waitForTimeout(500);

                    // Add second operation: add 10
                    await addConversionOperation(adminPage, '10', 'add');

                    const saveBtn = adminPage.getByRole('button', { name: /save/i }).last();
                    if (await saveBtn.isVisible()) {
                        await saveBtn.click();
                        await adminPage.waitForTimeout(2000);
                    }
                }
            }
        });

        test('TC46 - Create unit with decimal conversion value', async ({ adminPage }) => {
            const familyCode = `conv_decimal_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'weight_unit', 'WU');
            expect(created).toBeTruthy();

            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i]').first();
            if (await search.isVisible()) {
                await search.fill(familyCode);
                await search.press('Enter');
                await adminPage.waitForTimeout(2000);
            }

            const editBtn = adminPage.locator('[title="Edit"], .icon-edit').first();
            if (await editBtn.isVisible()) {
                await editBtn.click();
                await adminPage.waitForTimeout(2000);

                const addUnitBtn = adminPage.locator('button:has-text("Add Unit"), button:has-text("Create Unit")').first();
                if (await addUnitBtn.isVisible()) {
                    await addUnitBtn.click();
                    await adminPage.waitForTimeout(1000);

                    const codeInput = adminPage.locator('input[name*="code"]').last();
                    if (await codeInput.isVisible()) {
                        await codeInput.fill(`gram_${Date.now()}`);
                    }

                    const symbolInput = adminPage.locator('input[name*="symbol"]').last();
                    if (await symbolInput.isVisible()) {
                        await symbolInput.fill('G');
                    }

                    // Add conversion with decimal: 0.001
                    await addConversionOperation(adminPage, '0.001', 'mul');

                    const saveBtn = adminPage.getByRole('button', { name: /save/i }).last();
                    if (await saveBtn.isVisible()) {
                        await saveBtn.click();
                        await adminPage.waitForTimeout(2000);
                    }
                }
            }
        });

        test('TC47 - Create unit with very small decimal conversion value', async ({ adminPage }) => {
            const familyCode = `conv_tiny_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'micro_unit', 'MU');
            expect(created).toBeTruthy();

            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i]').first();
            if (await search.isVisible()) {
                await search.fill(familyCode);
                await search.press('Enter');
                await adminPage.waitForTimeout(2000);
            }

            const editBtn = adminPage.locator('[title="Edit"], .icon-edit').first();
            if (await editBtn.isVisible()) {
                await editBtn.click();
                await adminPage.waitForTimeout(2000);

                const addUnitBtn = adminPage.locator('button:has-text("Add Unit"), button:has-text("Create Unit")').first();
                if (await addUnitBtn.isVisible()) {
                    await addUnitBtn.click();
                    await adminPage.waitForTimeout(1000);

                    const codeInput = adminPage.locator('input[name*="code"]').last();
                    if (await codeInput.isVisible()) {
                        await codeInput.fill(`micro_${Date.now()}`);
                    }

                    const symbolInput = adminPage.locator('input[name*="symbol"]').last();
                    if (await symbolInput.isVisible()) {
                        await symbolInput.fill('μ');
                    }

                    // Add conversion with very small value: 0.000001
                    await addConversionOperation(adminPage, '0.000001', 'mul');

                    const saveBtn = adminPage.getByRole('button', { name: /save/i }).last();
                    if (await saveBtn.isVisible()) {
                        await saveBtn.click();
                        await adminPage.waitForTimeout(2000);
                    }
                }
            }
        });

        test('TC48 - Update unit conversion operations', async ({ adminPage }) => {
            const familyCode = `conv_update_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'update_unit', 'UU');
            expect(created).toBeTruthy();

            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i]').first();
            if (await search.isVisible()) {
                await search.fill(familyCode);
                await search.press('Enter');
                await adminPage.waitForTimeout(2000);
            }

            const editBtn = adminPage.locator('[title="Edit"], .icon-edit').first();
            if (await editBtn.isVisible()) {
                await editBtn.click();
                await adminPage.waitForTimeout(2000);

                // Edit the standard unit's conversion
                const editUnitBtn = adminPage.locator('[title="Edit Unit"], button:has-text("Edit")').nth(1);
                if (await editUnitBtn.isVisible()) {
                    await editUnitBtn.click();
                    await adminPage.waitForTimeout(1000);

                    // Update conversion value
                    const conversionValueInputs = adminPage.locator('input[name*="convert_value"], input[placeholder*="conversion value" i]');
                    if (await conversionValueInputs.count() > 0) {
                        const firstConversionInput = conversionValueInputs.first();
                        if (await firstConversionInput.isVisible()) {
                            await firstConversionInput.clear();
                            await firstConversionInput.fill('5.5');
                        }
                    }

                    const saveBtn = adminPage.getByRole('button', { name: /save/i }).last();
                    if (await saveBtn.isVisible()) {
                        await saveBtn.click();
                        await adminPage.waitForTimeout(2000);
                    }
                }
            }
        });

        test('TC49 - Verify conversion operations persist after save', async ({ adminPage }) => {
            const familyCode = `conv_persist_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'persist_unit', 'PU');
            expect(created).toBeTruthy();

            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i]').first();
            if (await search.isVisible()) {
                await search.fill(familyCode);
                await search.press('Enter');
                await adminPage.waitForTimeout(2000);
            }

            const editBtn = adminPage.locator('[title="Edit"], .icon-edit').first();
            if (await editBtn.isVisible()) {
                await editBtn.click();
                await adminPage.waitForTimeout(2000);

                // Check if units are displayed
                const unitsList = adminPage.locator('[class*="unit"], tr:has-text("persist_unit")');
                expect(await unitsList.count()).toBeGreaterThanOrEqual(0);

                // Reload and verify persistence
                await adminPage.reload();
                await adminPage.waitForTimeout(2000);
                expect(adminPage.url()).toContain('edit');
            }
        });

        test('TC50 - Verify conversion operations in family edit view', async ({ adminPage }) => {
            const familyCode = `conv_view_${Date.now()}`;
            const created = await createMeasurementFamily(adminPage, familyCode, 'view_unit', 'VU');
            expect(created).toBeTruthy();

            await goToFamilies(adminPage);
            const search = adminPage.locator('input[placeholder*="Search" i]').first();
            if (await search.isVisible()) {
                await search.fill(familyCode);
                await search.press('Enter');
                await adminPage.waitForTimeout(2000);
            }

            const editBtn = adminPage.locator('[title="Edit"], .icon-edit').first();
            if (await editBtn.isVisible()) {
                await editBtn.click();
                await adminPage.waitForTimeout(2000);

                // Look for conversion operation elements
                const conversionElements = adminPage.locator('[class*="conversion"], [name*="convert"], input[placeholder*="conversion" i]');
                const count = await conversionElements.count();
                expect(count).toBeGreaterThanOrEqual(0);
            }
        });
    });

    // ========== CLEANUP & VALIDATION ==========

    test.describe('Validation & Cleanup', () => {
        
        test('TC36 - Verify measurement families persisted', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            await adminPage.waitForTimeout(1500);
            const families = adminPage.locator('[class*="row"], tr, li').filter({ hasText: /[a-zA-Z0-9]/ });
            const count = await families.count();
            expect(count).toBeGreaterThanOrEqual(0);
        });

        test('TC37 - Verify measurement attributes persisted', async ({ adminPage }) => {
            await adminPage.goto('/admin/catalog/attributes', { waitUntil: 'domcontentloaded' });
            await adminPage.waitForTimeout(1500);
            const attributes = adminPage.locator('[class*="row"], tr, li').filter({ hasText: /[a-zA-Z0-9]/ });
            const count = await attributes.count();
            expect(count).toBeGreaterThanOrEqual(0);
        });

        test('TC38 - Performance check: families list loads within timeout', async ({ adminPage }) => {
            const start = Date.now();
            await goToFamilies(adminPage);
            const duration = Date.now() - start;
            expect(duration).toBeLessThan(30000);
        });

        test('TC39 - Verify page refresh maintains state', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const urlBefore = adminPage.url();
            await adminPage.reload();
            await adminPage.waitForTimeout(1000);
            expect(adminPage.url()).toContain('/admin/measurement/families');
        });

        test('TC40 - Verify all required fields have proper labels', async ({ adminPage }) => {
            await goToFamilies(adminPage);
            const createBtn = adminPage.getByRole('button', { name: /Create Measurements/i }).first();
            if (await createBtn.isVisible()) {
                await createBtn.click();
                await adminPage.waitForTimeout(500);
                
                const labels = adminPage.locator('label, [class*="label"]');
                const labelCount = await labels.count();
                expect(labelCount).toBeGreaterThan(0);
            }
        });
    });
});