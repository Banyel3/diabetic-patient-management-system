"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { X, Sparkles, ArrowRight } from "lucide-react";
import { useAuthContext } from "@/lib/auth-context";

const STORAGE_KEY_PREFIX = "diabetacare_quickstart_dismissed_";

export default function QuickStartBanner() {
  const { user } = useAuthContext();
  const [isVisible, setIsVisible] = useState(false);
  const [isMounted, setIsMounted] = useState(false);

  // Generate a user-specific storage key
  const getStorageKey = () => {
    const userId = user?.id || "default";
    return `${STORAGE_KEY_PREFIX}${userId}`;
  };

  // Check localStorage on mount (client-side only)
  useEffect(() => {
    setIsMounted(true);

    // Only access localStorage on the client
    if (typeof window !== "undefined") {
      const storageKey = getStorageKey();
      const isDismissed = localStorage.getItem(storageKey) === "true";
      setIsVisible(!isDismissed);
    }
  }, [user?.id]);

  const handleDismiss = () => {
    if (typeof window !== "undefined") {
      const storageKey = getStorageKey();
      localStorage.setItem(storageKey, "true");
    }
    setIsVisible(false);
  };

  // Don't render anything until mounted (avoids SSR hydration issues)
  if (!isMounted || !isVisible) {
    return null;
  }

  return (
    <div className="relative bg-gradient-to-r from-accent/10 via-teal-50 to-accent/5 border border-accent/20 rounded-2xl p-5 mb-6 shadow-sm">
      {/* Dismiss button */}
      <button
        onClick={handleDismiss}
        className="absolute top-3 right-3 p-1.5 text-text-muted hover:text-text-secondary hover:bg-white/50 rounded-lg transition-colors"
        aria-label="Dismiss banner"
      >
        <X className="w-4 h-4" />
      </button>

      <div className="flex flex-col sm:flex-row sm:items-center gap-4">
        {/* Icon */}
        <div className="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-accent to-teal-400 rounded-xl flex items-center justify-center shadow-lg shadow-accent/20">
          <Sparkles className="w-6 h-6 text-white" />
        </div>

        {/* Content */}
        <div className="flex-1 min-w-0">
          <h3 className="text-lg font-semibold text-text-primary">
            Welcome to DiabetaCare! 🎉
          </h3>
          <p className="text-sm text-text-secondary mt-1">
            New here? Check out our 2-minute quick start guide to get your
            clinic up and running.
          </p>
        </div>

        {/* Action button */}
        <Link
          href="/quick-start"
          className="flex-shrink-0 inline-flex items-center gap-2 px-5 py-2.5 bg-accent text-white rounded-xl font-medium shadow-lg shadow-accent/20 hover:bg-accent/90 transition-all group"
        >
          View Quick Start Guide
          <ArrowRight className="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
        </Link>
      </div>
    </div>
  );
}
