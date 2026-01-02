/**
 * DiabetaCare - Authentication Utilities
 *
 * Provides centralized auth state management and token handling.
 */

const TOKEN_KEY = "diabetacare_token";
const USER_KEY = "diabetacare_user";

// =============================================================================
// TOKEN MANAGEMENT
// =============================================================================

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string): void {
  if (typeof window === "undefined") return;
  localStorage.setItem(TOKEN_KEY, token);
}

export function removeToken(): void {
  if (typeof window === "undefined") return;
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}

export function isAuthenticated(): boolean {
  return !!getToken();
}

// =============================================================================
// USER STATE MANAGEMENT
// =============================================================================

export interface User {
  id: number;
  email: string;
  first_name: string;
  last_name: string;
  role: "admin" | "doctor" | "nurse" | "staff";
  clinic: {
    id: number;
    name: string;
  };
}

export function getUser(): User | null {
  if (typeof window === "undefined") return null;
  const userJson = localStorage.getItem(USER_KEY);
  if (!userJson) return null;
  try {
    return JSON.parse(userJson) as User;
  } catch {
    return null;
  }
}

export function setUser(user: User): void {
  if (typeof window === "undefined") return;
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export function clearAuth(): void {
  removeToken();
}

// =============================================================================
// AUTH ACTIONS
// =============================================================================

/**
 * Check if user is authenticated and redirect if not
 * @returns true if authenticated, false otherwise
 */
export function requireAuth(): boolean {
  if (typeof window === "undefined") return false;

  const authenticated = isAuthenticated();

  if (!authenticated) {
    // Store the current path to redirect back after login
    const currentPath = window.location.pathname;
    if (currentPath !== "/login") {
      sessionStorage.setItem("redirect_after_login", currentPath);
    }
    window.location.href = "/login";
    return false;
  }

  return true;
}

/**
 * Get the redirect path after successful login
 */
export function getRedirectPath(): string {
  if (typeof window === "undefined") return "/";
  const redirect = sessionStorage.getItem("redirect_after_login");
  sessionStorage.removeItem("redirect_after_login");
  return redirect || "/";
}

/**
 * Logout user and clear all auth state
 */
export function logout(): void {
  clearAuth();
  if (typeof window !== "undefined") {
    window.location.href = "/login";
  }
}
