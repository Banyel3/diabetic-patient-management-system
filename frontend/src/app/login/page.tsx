"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Eye, EyeOff, Mail, Lock, Activity, Loader2 } from "lucide-react";
import { type ApiError } from "@/lib/api";
import { useAuthContext } from "@/lib/auth-context";

export default function LoginPage() {
  const router = useRouter();
  const { login, authenticated, loading: authLoading } = useAuthContext();
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formData, setFormData] = useState({
    email: "",
    password: "",
    rememberMe: false,
  });

  // Redirect if already authenticated
  useEffect(() => {
    if (!authLoading && authenticated) {
      router.push("/");
    }
  }, [authenticated, authLoading, router]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      // Use AuthContext login which updates state and handles redirect
      await login(formData.email, formData.password);
      // AuthContext login handles the redirect, no need to call router.push
    } catch (err) {
      const apiError = err as ApiError;
      setError(
        apiError.message || "Invalid email or password. Please try again."
      );
    } finally {
      setLoading(false);
    }
  };

  // Show loading while auth context is checking
  if (authLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background flex">
      {/* Left Side - Branding */}
      <div className="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary to-gray-800 p-12 flex-col justify-between relative overflow-hidden">
        {/* Background Pattern */}
        <div className="absolute inset-0 opacity-10">
          <div className="absolute top-20 left-20 w-72 h-72 bg-accent rounded-full blur-3xl" />
          <div className="absolute bottom-20 right-20 w-96 h-96 bg-accent rounded-full blur-3xl" />
        </div>

        <div className="relative z-10">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-accent rounded-xl flex items-center justify-center shadow-lg">
              <Activity className="w-7 h-7 text-white" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-white">DiabetaCare</h1>
              <p className="text-gray-400 text-sm">Clinic Management System</p>
            </div>
          </div>
        </div>

        <div className="relative z-10">
          <h2 className="text-4xl font-bold text-white leading-tight mb-6">
            Streamline Your
            <br />
            <span className="text-accent">Diabetes Care</span>
            <br />
            Management
          </h2>
          <p className="text-gray-300 text-lg max-w-md">
            Efficiently manage patient records, appointments, medications, and
            lab results all in one secure platform.
          </p>
        </div>

        <div className="relative z-10">
          <div className="flex items-center gap-4">
            <div className="flex -space-x-3">
              {[1, 2, 3, 4].map((i) => (
                <div
                  key={i}
                  className="w-10 h-10 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 border-2 border-primary"
                />
              ))}
            </div>
            <p className="text-gray-400 text-sm">
              Trusted by <span className="text-white font-semibold">500+</span>{" "}
              healthcare providers
            </p>
          </div>
        </div>
      </div>

      {/* Right Side - Login Form */}
      <div className="w-full lg:w-1/2 flex items-center justify-center p-8">
        <div className="w-full max-w-md">
          {/* Mobile Logo */}
          <div className="lg:hidden flex items-center gap-3 mb-8 justify-center">
            <div className="w-12 h-12 bg-accent rounded-xl flex items-center justify-center shadow-lg">
              <Activity className="w-7 h-7 text-white" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-text-primary">
                DiabetaCare
              </h1>
              <p className="text-text-muted text-sm">
                Clinic Management System
              </p>
            </div>
          </div>

          <div className="bg-white rounded-3xl shadow-card p-8">
            <div className="text-center mb-8">
              <h2 className="text-2xl font-bold text-text-primary">
                Welcome Back
              </h2>
              <p className="text-text-muted mt-2">
                Sign in to access your dashboard
              </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-5">
              {/* Error Message */}
              {error && (
                <div className="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm">
                  {error}
                </div>
              )}

              {/* Email Field */}
              <div>
                <label className="block text-sm font-medium text-text-secondary mb-2">
                  Email Address
                </label>
                <div className="relative">
                  <Mail className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) =>
                      setFormData({ ...formData, email: e.target.value })
                    }
                    placeholder="Enter your email"
                    className="w-full pl-12 pr-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                    required
                  />
                </div>
              </div>

              {/* Password Field */}
              <div>
                <label className="block text-sm font-medium text-text-secondary mb-2">
                  Password
                </label>
                <div className="relative">
                  <Lock className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                  <input
                    type={showPassword ? "text" : "password"}
                    value={formData.password}
                    onChange={(e) =>
                      setFormData({ ...formData, password: e.target.value })
                    }
                    placeholder="Enter your password"
                    className="w-full pl-12 pr-12 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-4 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-secondary transition-colors"
                  >
                    {showPassword ? (
                      <EyeOff className="w-5 h-5" />
                    ) : (
                      <Eye className="w-5 h-5" />
                    )}
                  </button>
                </div>
              </div>

              {/* Remember Me & Forgot Password */}
              <div className="flex items-center justify-between">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={formData.rememberMe}
                    onChange={(e) =>
                      setFormData({ ...formData, rememberMe: e.target.checked })
                    }
                    className="w-4 h-4 rounded border-border-light text-accent focus:ring-accent"
                  />
                  <span className="text-sm text-text-secondary">
                    Remember me
                  </span>
                </label>
                <Link
                  href="/forgot-password"
                  className="text-sm text-accent hover:underline"
                >
                  Forgot password?
                </Link>
              </div>

              {/* Submit Button */}
              <button
                type="submit"
                disabled={loading}
                className="w-full py-3 bg-accent text-white font-medium rounded-2xl shadow-lg shadow-accent/20 hover:bg-accent/90 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                {loading ? (
                  <>
                    <Loader2 className="w-5 h-5 animate-spin" />
                    Signing in...
                  </>
                ) : (
                  "Sign In"
                )}
              </button>
            </form>

            {/* Demo Credentials */}
            <div className="mt-4 p-3 bg-blue-50 rounded-xl border border-blue-100">
              <p className="text-xs text-blue-600 text-center font-medium mb-1">
                Demo Credentials
              </p>
              <div className="text-sm text-blue-700 text-center space-y-0.5">
                <p>
                  <span className="font-medium">Email:</span>{" "}
                  admin@diabetacare.test
                </p>
                <p>
                  <span className="font-medium">Password:</span> password
                </p>
              </div>
            </div>

            {/* Divider */}
            <div className="flex items-center gap-4 my-6">
              <div className="flex-1 h-px bg-border-light" />
              <span className="text-text-muted text-sm">or</span>
              <div className="flex-1 h-px bg-border-light" />
            </div>

            {/* Register Link */}
            <p className="text-center text-text-secondary">
              Don&apos;t have an account?{" "}
              <Link
                href="/register"
                className="text-accent font-semibold hover:underline"
              >
                Register your clinic
              </Link>
            </p>
          </div>

          {/* Footer */}
          <p className="text-center text-text-muted text-sm mt-6">
            © 2025 DiabetaCare. All rights reserved.
          </p>
        </div>
      </div>
    </div>
  );
}
