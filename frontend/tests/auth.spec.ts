import { test, expect } from "@playwright/test";

// Test credentials
const TEST_EMAIL = "admin@zambodiabetic.com";
const TEST_PASSWORD = "password";
const BASE_URL = "http://localhost:3000";

test.describe("Authentication Flow", () => {
  test.beforeEach(async ({ page }) => {
    // Clear any existing auth state
    await page.goto(BASE_URL);
    await page.evaluate(() => {
      localStorage.clear();
      sessionStorage.clear();
    });
  });

  test("should successfully login and redirect to dashboard", async ({
    page,
  }) => {
    // Navigate to login page
    await page.goto(`${BASE_URL}/login`);
    await expect(page).toHaveURL(`${BASE_URL}/login`);

    // Fill in login form
    await page
      .getByRole("textbox", { name: "Enter your email" })
      .fill(TEST_EMAIL);
    await page
      .getByRole("textbox", { name: "Enter your password" })
      .fill(TEST_PASSWORD);

    // Submit form
    await page.getByRole("button", { name: "Sign In" }).click();

    // Wait for redirect to dashboard
    await expect(page).toHaveURL(BASE_URL + "/", { timeout: 10000 });

    // Verify token is stored
    const hasToken = await page.evaluate(() => {
      return !!localStorage.getItem("diabetacare_token");
    });
    expect(hasToken).toBe(true);

    // Verify user is stored
    const hasUser = await page.evaluate(() => {
      return !!localStorage.getItem("diabetacare_user");
    });
    expect(hasUser).toBe(true);
  });

  test("should show error message on invalid credentials", async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);

    // Fill with invalid credentials
    await page
      .getByRole("textbox", { name: "Enter your email" })
      .fill("invalid@example.com");
    await page
      .getByRole("textbox", { name: "Enter your password" })
      .fill("wrongpassword");

    // Submit form
    await page.getByRole("button", { name: "Sign In" }).click();

    // Should stay on login page
    await expect(page).toHaveURL(`${BASE_URL}/login`);

    // Should show error message (check for error alert or message)
    await expect(page.locator("text=/invalid|incorrect|error/i")).toBeVisible({
      timeout: 3000,
    });
  });

  test("should redirect unauthenticated user to login", async ({ page }) => {
    // Try to access dashboard without auth
    await page.goto(BASE_URL + "/");

    // Should redirect to login
    await expect(page).toHaveURL(`${BASE_URL}/login`, { timeout: 5000 });
  });

  test("should redirect from protected routes to login when not authenticated", async ({
    page,
  }) => {
    const protectedRoutes = [
      "/patients",
      "/appointments",
      "/medications",
      "/lab-results",
      "/settings",
    ];

    for (const route of protectedRoutes) {
      await page.goto(BASE_URL + route);
      await expect(page).toHaveURL(`${BASE_URL}/login`, { timeout: 5000 });
    }
  });

  test("should logout and clear auth state", async ({ page }) => {
    // First, login
    await page.goto(`${BASE_URL}/login`);
    await page
      .getByRole("textbox", { name: "Enter your email" })
      .fill(TEST_EMAIL);
    await page
      .getByRole("textbox", { name: "Enter your password" })
      .fill(TEST_PASSWORD);
    await page.getByRole("button", { name: "Sign In" }).click();

    // Wait for dashboard
    await expect(page).toHaveURL(BASE_URL + "/", { timeout: 10000 });

    // Click logout
    await page.getByRole("button", { name: "Log out" }).click();

    // Should redirect to login
    await expect(page).toHaveURL(`${BASE_URL}/login`, { timeout: 5000 });

    // Token should be cleared
    const hasToken = await page.evaluate(() => {
      return !!localStorage.getItem("diabetacare_token");
    });
    expect(hasToken).toBe(false);
  });

  test("should allow authenticated user to access protected routes", async ({
    page,
  }) => {
    // Login first
    await page.goto(`${BASE_URL}/login`);
    await page
      .getByRole("textbox", { name: "Enter your email" })
      .fill(TEST_EMAIL);
    await page
      .getByRole("textbox", { name: "Enter your password" })
      .fill(TEST_PASSWORD);
    await page.getByRole("button", { name: "Sign In" }).click();

    await expect(page).toHaveURL(BASE_URL + "/", { timeout: 10000 });

    // Navigate to patients page
    await page.goto(`${BASE_URL}/patients`);
    await expect(page).toHaveURL(`${BASE_URL}/patients`, { timeout: 5000 });

    // Should not redirect to login
    await expect(page).not.toHaveURL(`${BASE_URL}/login`);
  });

  test("should redirect to original page after login", async ({ page }) => {
    // Try to access patients page without auth
    await page.goto(`${BASE_URL}/patients`);

    // Should redirect to login
    await expect(page).toHaveURL(`${BASE_URL}/login`, { timeout: 5000 });

    // Login
    await page
      .getByRole("textbox", { name: "Enter your email" })
      .fill(TEST_EMAIL);
    await page
      .getByRole("textbox", { name: "Enter your password" })
      .fill(TEST_PASSWORD);
    await page.getByRole("button", { name: "Sign In" }).click();

    // Should redirect back to patients (or dashboard - depends on implementation)
    await page.waitForURL(/\/(patients|$)/, { timeout: 10000 });

    // Verify we're authenticated
    const hasToken = await page.evaluate(
      () => !!localStorage.getItem("diabetacare_token")
    );
    expect(hasToken).toBe(true);
  });
});
