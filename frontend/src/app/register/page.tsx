"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  Eye,
  EyeOff,
  Mail,
  Lock,
  Activity,
  Building2,
  Phone,
  MapPin,
  FileText,
  User,
  ArrowLeft,
  ArrowRight,
  Check,
  Loader2,
  AlertCircle,
} from "lucide-react";
import { authApi, setToken } from "@/lib/api";

export default function RegisterPage() {
  const router = useRouter();
  const [step, setStep] = useState(1);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formData, setFormData] = useState({
    // Clinic Information
    clinicName: "",
    registrationNumber: "",
    licenseNumber: "",
    clinicPhone: "",
    clinicEmail: "",
    address: "",
    city: "",
    state: "",
    zipCode: "",
    // Admin Information
    adminFirstName: "",
    adminLastName: "",
    adminEmail: "",
    adminPhone: "",
    password: "",
    confirmPassword: "",
    agreeToTerms: false,
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (step < 3) {
      setStep(step + 1);
      return;
    }

    // Validate passwords match
    if (formData.password !== formData.confirmPassword) {
      setError("Passwords do not match");
      return;
    }

    // Submit registration
    setLoading(true);
    try {
      const response = await authApi.register({
        clinic_name: formData.clinicName,
        clinic_email: formData.clinicEmail,
        clinic_phone: formData.clinicPhone,
        registration_number: formData.registrationNumber,
        license_number: formData.licenseNumber,
        street_address: formData.address,
        city: formData.city,
        state_province: formData.state,
        zip_postal_code: formData.zipCode,
        first_name: formData.adminFirstName,
        last_name: formData.adminLastName,
        email: formData.adminEmail,
        phone: formData.adminPhone,
        password: formData.password,
        terms_accepted: formData.agreeToTerms,
      });

      // Store token and redirect to dashboard
      setToken(response.token);
      router.push("/");
    } catch (err) {
      const apiError = err as {
        message?: string;
        errors?: Record<string, string[]>;
      };
      if (apiError.errors) {
        const firstError = Object.values(apiError.errors)[0]?.[0];
        setError(firstError || apiError.message || "Registration failed");
      } else {
        setError(apiError.message || "Registration failed. Please try again.");
      }
    } finally {
      setLoading(false);
    }
  };

  const handleBack = () => {
    if (step > 1) {
      setStep(step - 1);
    }
  };

  const steps = [
    { number: 1, title: "Clinic Info", description: "Basic details" },
    { number: 2, title: "Address", description: "Location details" },
    { number: 3, title: "Admin Account", description: "Login credentials" },
  ];

  return (
    <div className="min-h-screen bg-background flex">
      {/* Left Side - Branding */}
      <div className="hidden lg:flex lg:w-2/5 bg-gradient-to-br from-primary to-gray-800 p-12 flex-col justify-between relative overflow-hidden">
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
            Register Your
            <br />
            <span className="text-accent">Diabetes Clinic</span>
            <br />
            Today
          </h2>
          <p className="text-gray-300 text-lg max-w-md">
            Join hundreds of healthcare providers who trust DiabetaCare for
            managing their diabetic patients efficiently and securely.
          </p>

          {/* Features List */}
          <div className="mt-8 space-y-4">
            {[
              "Complete patient management",
              "HbA1c tracking & analytics",
              "Medication management",
              "Secure & HIPAA compliant",
            ].map((feature, index) => (
              <div key={index} className="flex items-center gap-3">
                <div className="w-6 h-6 bg-accent/20 rounded-full flex items-center justify-center">
                  <Check className="w-4 h-4 text-accent" />
                </div>
                <span className="text-gray-300">{feature}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="relative z-10">
          <p className="text-gray-400 text-sm">
            Already have an account?{" "}
            <Link href="/login" className="text-accent hover:underline">
              Sign in
            </Link>
          </p>
        </div>
      </div>

      {/* Right Side - Registration Form */}
      <div className="w-full lg:w-3/5 flex items-center justify-center p-6 overflow-y-auto">
        <div className="w-full max-w-2xl">
          {/* Mobile Logo */}
          <div className="lg:hidden flex items-center gap-3 mb-6 justify-center">
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
            {/* Error Message */}
            {error && (
              <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                <p className="text-sm text-red-700">{error}</p>
              </div>
            )}

            {/* Progress Steps */}
            <div className="flex items-center justify-between mb-8">
              {steps.map((s, index) => (
                <div key={s.number} className="flex items-center">
                  <div className="flex flex-col items-center">
                    <div
                      className={`w-10 h-10 rounded-full flex items-center justify-center font-semibold transition-all ${
                        step >= s.number
                          ? "bg-accent text-white"
                          : "bg-surface-secondary text-text-muted"
                      }`}
                    >
                      {step > s.number ? (
                        <Check className="w-5 h-5" />
                      ) : (
                        s.number
                      )}
                    </div>
                    <div className="text-center mt-2">
                      <p
                        className={`text-sm font-medium ${
                          step >= s.number
                            ? "text-text-primary"
                            : "text-text-muted"
                        }`}
                      >
                        {s.title}
                      </p>
                      <p className="text-xs text-text-muted hidden sm:block">
                        {s.description}
                      </p>
                    </div>
                  </div>
                  {index < steps.length - 1 && (
                    <div
                      className={`w-16 sm:w-24 h-1 mx-2 rounded-full transition-all ${
                        step > s.number ? "bg-accent" : "bg-surface-secondary"
                      }`}
                    />
                  )}
                </div>
              ))}
            </div>

            <form onSubmit={handleSubmit} className="space-y-5">
              {/* Step 1: Clinic Information */}
              {step === 1 && (
                <>
                  <div className="text-center mb-6">
                    <h2 className="text-2xl font-bold text-text-primary">
                      Clinic Information
                    </h2>
                    <p className="text-text-muted mt-2">
                      Enter your clinic&apos;s basic details
                    </p>
                  </div>

                  {/* Clinic Name */}
                  <div>
                    <label className="block text-sm font-medium text-text-secondary mb-2">
                      Clinic Registered Name *
                    </label>
                    <div className="relative">
                      <Building2 className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                      <input
                        type="text"
                        value={formData.clinicName}
                        onChange={(e) =>
                          setFormData({
                            ...formData,
                            clinicName: e.target.value,
                          })
                        }
                        placeholder="Enter clinic name"
                        className="w-full pl-12 pr-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        required
                      />
                    </div>
                  </div>

                  {/* Registration & License Numbers */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-text-secondary mb-2">
                        Registration Number *
                      </label>
                      <div className="relative">
                        <FileText className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                        <input
                          type="text"
                          value={formData.registrationNumber}
                          onChange={(e) =>
                            setFormData({
                              ...formData,
                              registrationNumber: e.target.value,
                            })
                          }
                          placeholder="Business reg. number"
                          className="w-full pl-12 pr-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                          required
                        />
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-text-secondary mb-2">
                        Medical License Number *
                      </label>
                      <div className="relative">
                        <FileText className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                        <input
                          type="text"
                          value={formData.licenseNumber}
                          onChange={(e) =>
                            setFormData({
                              ...formData,
                              licenseNumber: e.target.value,
                            })
                          }
                          placeholder="Medical license number"
                          className="w-full pl-12 pr-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                          required
                        />
                      </div>
                    </div>
                  </div>

                  {/* Clinic Contact */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-text-secondary mb-2">
                        Clinic Phone *
                      </label>
                      <div className="relative">
                        <Phone className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                        <input
                          type="tel"
                          value={formData.clinicPhone}
                          onChange={(e) =>
                            setFormData({
                              ...formData,
                              clinicPhone: e.target.value,
                            })
                          }
                          placeholder="(000) 000-0000"
                          className="w-full pl-12 pr-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                          required
                        />
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-text-secondary mb-2">
                        Clinic Email *
                      </label>
                      <div className="relative">
                        <Mail className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                        <input
                          type="email"
                          value={formData.clinicEmail}
                          onChange={(e) =>
                            setFormData({
                              ...formData,
                              clinicEmail: e.target.value,
                            })
                          }
                          placeholder="clinic@example.com"
                          className="w-full pl-12 pr-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                          required
                        />
                      </div>
                    </div>
                  </div>
                </>
              )}

              {/* Step 2: Address Information */}
              {step === 2 && (
                <>
                  <div className="text-center mb-6">
                    <h2 className="text-2xl font-bold text-text-primary">
                      Clinic Address
                    </h2>
                    <p className="text-text-muted mt-2">
                      Enter your clinic&apos;s location details
                    </p>
                  </div>

                  {/* Street Address */}
                  <div>
                    <label className="block text-sm font-medium text-text-secondary mb-2">
                      Street Address *
                    </label>
                    <div className="relative">
                      <MapPin className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                      <input
                        type="text"
                        value={formData.address}
                        onChange={(e) =>
                          setFormData({ ...formData, address: e.target.value })
                        }
                        placeholder="123 Healthcare Street, Suite 100"
                        className="w-full pl-12 pr-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        required
                      />
                    </div>
                  </div>

                  {/* City & State */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-text-secondary mb-2">
                        City *
                      </label>
                      <input
                        type="text"
                        value={formData.city}
                        onChange={(e) =>
                          setFormData({ ...formData, city: e.target.value })
                        }
                        placeholder="City"
                        className="w-full px-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        required
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-text-secondary mb-2">
                        State/Province *
                      </label>
                      <input
                        type="text"
                        value={formData.state}
                        onChange={(e) =>
                          setFormData({ ...formData, state: e.target.value })
                        }
                        placeholder="State"
                        className="w-full px-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        required
                      />
                    </div>
                  </div>

                  {/* ZIP Code */}
                  <div className="w-1/2">
                    <label className="block text-sm font-medium text-text-secondary mb-2">
                      ZIP/Postal Code *
                    </label>
                    <input
                      type="text"
                      value={formData.zipCode}
                      onChange={(e) =>
                        setFormData({ ...formData, zipCode: e.target.value })
                      }
                      placeholder="00000"
                      className="w-full px-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                      required
                    />
                  </div>
                </>
              )}

              {/* Step 3: Admin Account */}
              {step === 3 && (
                <>
                  <div className="text-center mb-6">
                    <h2 className="text-2xl font-bold text-text-primary">
                      Admin Account
                    </h2>
                    <p className="text-text-muted mt-2">
                      Create your administrator login credentials
                    </p>
                  </div>

                  {/* Admin Name */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-text-secondary mb-2">
                        First Name *
                      </label>
                      <div className="relative">
                        <User className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                        <input
                          type="text"
                          value={formData.adminFirstName}
                          onChange={(e) =>
                            setFormData({
                              ...formData,
                              adminFirstName: e.target.value,
                            })
                          }
                          placeholder="First name"
                          className="w-full pl-12 pr-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                          required
                        />
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-text-secondary mb-2">
                        Last Name *
                      </label>
                      <div className="relative">
                        <User className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                        <input
                          type="text"
                          value={formData.adminLastName}
                          onChange={(e) =>
                            setFormData({
                              ...formData,
                              adminLastName: e.target.value,
                            })
                          }
                          placeholder="Last name"
                          className="w-full pl-12 pr-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                          required
                        />
                      </div>
                    </div>
                  </div>

                  {/* Admin Contact */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-text-secondary mb-2">
                        Admin Email *
                      </label>
                      <div className="relative">
                        <Mail className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                        <input
                          type="email"
                          value={formData.adminEmail}
                          onChange={(e) =>
                            setFormData({
                              ...formData,
                              adminEmail: e.target.value,
                            })
                          }
                          placeholder="admin@example.com"
                          className="w-full pl-12 pr-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                          required
                        />
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-text-secondary mb-2">
                        Admin Phone *
                      </label>
                      <div className="relative">
                        <Phone className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                        <input
                          type="tel"
                          value={formData.adminPhone}
                          onChange={(e) =>
                            setFormData({
                              ...formData,
                              adminPhone: e.target.value,
                            })
                          }
                          placeholder="(000) 000-0000"
                          className="w-full pl-12 pr-4 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                          required
                        />
                      </div>
                    </div>
                  </div>

                  {/* Password */}
                  <div>
                    <label className="block text-sm font-medium text-text-secondary mb-2">
                      Password *
                    </label>
                    <div className="relative">
                      <Lock className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                      <input
                        type={showPassword ? "text" : "password"}
                        value={formData.password}
                        onChange={(e) =>
                          setFormData({ ...formData, password: e.target.value })
                        }
                        placeholder="Create a strong password"
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

                  {/* Confirm Password */}
                  <div>
                    <label className="block text-sm font-medium text-text-secondary mb-2">
                      Confirm Password *
                    </label>
                    <div className="relative">
                      <Lock className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                      <input
                        type={showConfirmPassword ? "text" : "password"}
                        value={formData.confirmPassword}
                        onChange={(e) =>
                          setFormData({
                            ...formData,
                            confirmPassword: e.target.value,
                          })
                        }
                        placeholder="Confirm your password"
                        className="w-full pl-12 pr-12 py-3 bg-surface-secondary border border-border-light rounded-2xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        required
                      />
                      <button
                        type="button"
                        onClick={() =>
                          setShowConfirmPassword(!showConfirmPassword)
                        }
                        className="absolute right-4 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-secondary transition-colors"
                      >
                        {showConfirmPassword ? (
                          <EyeOff className="w-5 h-5" />
                        ) : (
                          <Eye className="w-5 h-5" />
                        )}
                      </button>
                    </div>
                  </div>

                  {/* Terms Agreement */}
                  <label className="flex items-start gap-3 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={formData.agreeToTerms}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          agreeToTerms: e.target.checked,
                        })
                      }
                      className="w-5 h-5 rounded border-border-light text-accent focus:ring-accent mt-0.5"
                      required
                    />
                    <span className="text-sm text-text-secondary">
                      I agree to the{" "}
                      <Link
                        href="/terms"
                        className="text-accent hover:underline"
                      >
                        Terms of Service
                      </Link>{" "}
                      and{" "}
                      <Link
                        href="/privacy"
                        className="text-accent hover:underline"
                      >
                        Privacy Policy
                      </Link>
                      . I understand that my clinic data will be handled in
                      compliance with healthcare regulations.
                    </span>
                  </label>
                </>
              )}

              {/* Navigation Buttons */}
              <div className="flex items-center justify-between pt-4">
                {step > 1 ? (
                  <button
                    type="button"
                    onClick={handleBack}
                    disabled={loading}
                    className="flex items-center gap-2 px-6 py-3 text-text-secondary hover:text-text-primary transition-colors disabled:opacity-50"
                  >
                    <ArrowLeft className="w-5 h-5" />
                    Back
                  </button>
                ) : (
                  <div />
                )}
                <button
                  type="submit"
                  disabled={loading}
                  className="flex items-center gap-2 px-8 py-3 bg-accent text-white font-medium rounded-2xl shadow-lg shadow-accent/20 hover:bg-accent/90 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {loading ? (
                    <>
                      <Loader2 className="w-5 h-5 animate-spin" />
                      Creating Account...
                    </>
                  ) : step === 3 ? (
                    "Create Account"
                  ) : (
                    <>
                      Continue
                      <ArrowRight className="w-5 h-5" />
                    </>
                  )}
                </button>
              </div>
            </form>
          </div>

          {/* Footer */}
          <div className="text-center mt-6">
            <p className="text-text-secondary lg:hidden mb-2">
              Already have an account?{" "}
              <Link
                href="/login"
                className="text-accent font-semibold hover:underline"
              >
                Sign in
              </Link>
            </p>
            <p className="text-text-muted text-sm">
              © 2025 DiabetaCare. All rights reserved.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
