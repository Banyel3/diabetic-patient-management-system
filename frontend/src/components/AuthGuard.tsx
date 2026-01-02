/**
 * DiabetaCare - Authentication Guard
 *
 * Protects routes by checking authentication status.
 * Redirects unauthenticated users to login page.
 */

"use client";

import { useEffect, useState } from "react";
import { useRouter, usePathname } from "next/navigation";
import { isAuthenticated, getRedirectPath } from "@/lib/auth";

interface AuthGuardProps {
  children: React.ReactNode;
}

export default function AuthGuard({ children }: AuthGuardProps) {
  const router = useRouter();
  const pathname = usePathname();
  const [isChecking, setIsChecking] = useState(true);
  const [isAuthorized, setIsAuthorized] = useState(false);

  useEffect(() => {
    const checkAuth = () => {
      const authenticated = isAuthenticated();

      if (!authenticated) {
        // Store current path for redirect after login
        if (pathname !== "/login") {
          sessionStorage.setItem("redirect_after_login", pathname);
        }
        router.push("/login");
        return;
      }

      setIsAuthorized(true);
      setIsChecking(false);
    };

    checkAuth();
  }, [pathname, router]);

  // Show nothing while checking (prevents flash of protected content)
  if (isChecking || !isAuthorized) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
          <p className="text-text-secondary">Verifying authentication...</p>
        </div>
      </div>
    );
  }

  return <>{children}</>;
}
