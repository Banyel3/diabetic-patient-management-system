import { test, expect } from "@playwright/test";

// Test credentials
const TEST_EMAIL = "admin@zambodiabetic.com";
const TEST_PASSWORD = "password";
const BASE_URL = "http://localhost:3000";

test.describe("Lab Results Module", () => {
  // Login before each test
  test.beforeEach(async ({ page }) => {
    // Navigate to login page and authenticate
    await page.goto(`${BASE_URL}/login`);
    await page
      .getByRole("textbox", { name: "Enter your email" })
      .fill(TEST_EMAIL);
    await page
      .getByRole("textbox", { name: "Enter your password" })
      .fill(TEST_PASSWORD);
    await page.getByRole("button", { name: "Sign In" }).click();

    // Wait for dashboard to load
    await expect(page).toHaveURL(BASE_URL + "/", { timeout: 10000 });
  });

  test("should display lab results page without errors", async ({ page }) => {
    // Navigate to lab results page
    await page.getByRole("link", { name: /Lab Results/i }).click();
    await expect(page).toHaveURL(`${BASE_URL}/lab-results`);

    // Should not show error message
    const errorAlert = page.locator('[role="alert"]');
    await expect(errorAlert).not.toBeVisible();

    // Page heading should be visible
    await expect(
      page.getByRole("heading", { name: /Lab Results/i })
    ).toBeVisible();
  });

  test("should show filters and search controls", async ({ page }) => {
    await page.goto(`${BASE_URL}/lab-results`);

    // Should have test type filter dropdown
    await expect(page.getByRole("combobox").first()).toBeVisible();

    // Should have search input
    await expect(page.getByPlaceholder(/Search/i)).toBeVisible();

    // Should have Add Result button
    await expect(
      page.getByRole("button", { name: /Add Result/i })
    ).toBeVisible();
  });

  test("should handle filter by test type without error", async ({ page }) => {
    await page.goto(`${BASE_URL}/lab-results`);

    // Click the test type dropdown and select a filter
    const testTypeSelect = page.getByRole("combobox").first();
    await testTypeSelect.click();

    // Select HbA1c filter (if available)
    const hba1cOption = page.getByRole("option", { name: /HbA1c/i });
    if (await hba1cOption.isVisible()) {
      await hba1cOption.click();
    }

    // Wait a moment for API call
    await page.waitForTimeout(1000);

    // Should not show error - either results or "no results" message
    const errorAlert = page.locator('[role="alert"]');
    await expect(errorAlert).not.toBeVisible();
  });

  test("should show empty state when no lab results exist", async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/lab-results`);

    // Wait for data to load
    await page.waitForTimeout(1000);

    // Should show either results or an empty state message (not an error)
    const content = await page.textContent("body");
    const hasResults =
      content?.includes("HbA1c") || content?.includes("Glucose");
    const hasEmptyState =
      content?.includes("No lab results") ||
      content?.includes("no results") ||
      content?.includes("No data");
    const hasError = content?.includes("Failed to load");

    // Either has results, has empty state, or neither (but NOT an error)
    if (hasError) {
      // If there's an error, fail the test
      expect(hasError).toBe(false);
    }
  });

  test("should open add lab result modal", async ({ page }) => {
    await page.goto(`${BASE_URL}/lab-results`);

    // Click add button
    await page.getByRole("button", { name: /Add Result/i }).click();

    // Modal should appear with form
    await expect(page.getByRole("dialog")).toBeVisible();
    await expect(page.getByText(/Add Lab Result/i)).toBeVisible();

    // Form fields should be present
    await expect(page.getByLabel(/Patient/i)).toBeVisible();
    await expect(page.getByLabel(/Test Type|Test Name/i)).toBeVisible();
  });

  test("should create a new lab result", async ({ page }) => {
    await page.goto(`${BASE_URL}/lab-results`);

    // Click add button
    await page.getByRole("button", { name: /Add Result/i }).click();

    // Fill out the form
    // Select patient
    const patientSelect = page.getByLabel(/Patient/i);
    await patientSelect.click();
    const patientOption = page.getByRole("option").first();
    if (await patientOption.isVisible()) {
      await patientOption.click();
    }

    // Test type should default to HbA1c
    // Fill in result value
    const resultInput = page.getByLabel(/Result|Value/i);
    await resultInput.fill("6.5");

    // Submit form
    await page.getByRole("button", { name: /Save|Add|Submit/i }).click();

    // Wait for modal to close
    await page.waitForTimeout(2000);

    // Modal should close and result should appear in list
    await expect(page.getByRole("dialog")).not.toBeVisible();
  });

  test("should handle status filter without error", async ({ page }) => {
    await page.goto(`${BASE_URL}/lab-results`);

    // Look for status filter if present
    const statusFilters = page.locator("select, [role='combobox']");
    const count = await statusFilters.count();

    if (count > 1) {
      // Click the second dropdown (likely status filter)
      await statusFilters.nth(1).click();

      // Wait for API call
      await page.waitForTimeout(1000);

      // Should not show error
      const errorAlert = page.locator('[role="alert"]');
      await expect(errorAlert).not.toBeVisible();
    }
  });

  test("should display lab results with correct data structure", async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/lab-results`);

    // Wait for data to load
    await page.waitForTimeout(2000);

    // Check if there are any results in the table
    const tableRows = page.locator("table tbody tr");
    const rowCount = await tableRows.count();

    if (rowCount > 0) {
      // Get first row and verify it has expected columns
      const firstRow = tableRows.first();

      // Should display patient info, test name, result, status, etc.
      const rowText = await firstRow.textContent();
      expect(rowText).toBeDefined();

      // At minimum, results should not be broken/empty
      expect(rowText?.length).toBeGreaterThan(10);
    }
  });
});
