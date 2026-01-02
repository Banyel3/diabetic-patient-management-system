/**
 * DiabetaCare - Authentication Guard
 *
 * Protects routes by checking authentication status via AuthContext.
 * Redirects unauthenticated users to login page.
 * Uses a state machine approach for deterministic behavior:
 *   loading → checking token (from AuthContext)
 *   authenticated → render children
 *   unauthenticated → redirect to /login
 *
 * CRITICAL: Children are NEVER rendered until auth is definitively confirmed.
 */

"use client";

import { useEffect } from "react";
import { useRouter, usePathname } from "next/navigation";
import { useAuthContext } from "@/lib/auth-context";

interface AuthGuardProps {
  children: React.ReactNode;
}

export default function AuthGuard({ children }: AuthGuardProps) {
  const router = useRouter();
  const pathname = usePathname();
  const { authenticated, loading } = useAuthContext();

  useEffect(() => {
    // Wait for auth context to finish loading
    if (loading) return;

    if (!authenticated) {
      // Store current path for redirect after login
      if (pathname !== "/login") {
        sessionStorage.setItem("redirect_after_login", pathname);
      }
      router.replace("/login");
    }
  }, [authenticated, loading, pathname, router]);

  // Show loading spinner while AuthContext is checking
  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-background">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
          <p className="text-text-secondary">Loading...</p>
        </div>
      </div>
    );
  }

  // Don't render anything while redirecting to login
  if (!authenticated) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-background">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
          <p className="text-text-secondary">Redirecting to login...</p>
        </div>
      </div>
    );
  }

  // Only render children when definitively authenticated
  return <>{children}</>;
}
