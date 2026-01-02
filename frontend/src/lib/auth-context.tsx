/**
 * DiabetaCare - Auth Context
 *
 * Provides authentication state throughout the application.
 */

"use client";

import {
  createContext,
  useContext,
  useState,
  useEffect,
  useCallback,
  ReactNode,
} from "react";
import { useRouter } from "next/navigation";
import { authApi, type ApiError } from "./api";
import {
  isAuthenticated as checkToken,
  clearAuth,
  getUser,
  getRedirectPath,
  type User,
} from "./auth";

interface AuthContextType {
  user: User | null;
  loading: boolean;
  authenticated: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  error: ApiError | null;
  clearError: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<ApiError | null>(null);
  const router = useRouter();

  // Check authentication on mount
  useEffect(() => {
    const checkAuth = async () => {
      if (!checkToken()) {
        setLoading(false);
        return;
      }

      // Try to get user from localStorage first
      const cachedUser = getUser();
      if (cachedUser) {
        setUser(cachedUser);
        setLoading(false);
        return;
      }

      // Validate token with backend
      try {
        const userData = await authApi.me();
        setUser(userData);
      } catch {
        clearAuth();
      } finally {
        setLoading(false);
      }
    };

    checkAuth();
  }, []);

  const login = useCallback(
    async (email: string, password: string) => {
      setError(null);
      try {
        const response = await authApi.login(email, password);
        setUser(response.user);

        // Redirect to stored path or dashboard
        const redirectPath = getRedirectPath();
        router.push(redirectPath);
      } catch (err) {
        setError(err as ApiError);
        throw err;
      }
    },
    [router]
  );

  const logout = useCallback(async () => {
    try {
      await authApi.logout();
    } catch {
      // Ignore errors during logout
    } finally {
      setUser(null);
      router.push("/login");
    }
  }, [router]);

  const clearError = useCallback(() => {
    setError(null);
  }, []);

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        authenticated: !!user,
        login,
        logout,
        error,
        clearError,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuthContext() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error("useAuthContext must be used within an AuthProvider");
  }
  return context;
}

// Higher-order component for protected routes
export function withAuth<P extends object>(Component: React.ComponentType<P>) {
  return function AuthenticatedComponent(props: P) {
    const { authenticated, loading } = useAuthContext();
    const router = useRouter();

    useEffect(() => {
      if (!loading && !authenticated) {
        router.push("/login");
      }
    }, [loading, authenticated, router]);

    if (loading) {
      return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-teal-600"></div>
        </div>
      );
    }

    if (!authenticated) {
      return null;
    }

    return <Component {...props} />;
  };
}
