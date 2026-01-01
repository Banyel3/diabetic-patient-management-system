"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  Users,
  Pill,
  Calendar,
  LayoutDashboard,
  Beaker,
  Settings,
  LogOut,
} from "lucide-react";

const navItems = [
  { name: "Dashboard", href: "/", icon: LayoutDashboard },
  { name: "Patients", href: "/patients", icon: Users },
  { name: "Appointments", href: "/appointments", icon: Calendar },
  { name: "Medications", href: "/medications", icon: Pill },
  { name: "Lab Results", href: "/lab-results", icon: Beaker },
];

const bottomNavItems = [
  { name: "Settings", href: "/settings", icon: Settings },
  { name: "Log out", href: "/login", icon: LogOut },
];

export default function Sidebar() {
  const pathname = usePathname();

  return (
    <div className="w-16 bg-transparent flex flex-col items-center py-6 h-screen">
      {/* Logo */}
      <div className="mb-8">
        <div className="w-10 h-10 bg-gradient-to-br from-accent to-teal-400 rounded-xl flex items-center justify-center shadow-lg shadow-accent/30">
          <svg
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            className="text-white"
          >
            <path
              d="M12 2L2 7L12 12L22 7L12 2Z"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
            <path
              d="M2 17L12 22L22 17"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
            <path
              d="M2 12L12 17L22 12"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
        </div>
      </div>

      {/* Main Navigation */}
      <nav className="flex-1 flex flex-col items-center space-y-1">
        {navItems.map((item) => {
          const isActive = pathname === item.href;
          return (
            <Link
              key={item.name}
              href={item.href}
              title={item.name}
              className={`relative flex items-center justify-center w-12 h-12 rounded-2xl transition-all duration-200 group ${
                isActive
                  ? "bg-primary text-white shadow-lg shadow-primary/30"
                  : "text-text-muted hover:bg-white hover:text-primary hover:shadow-card"
              }`}
            >
              <item.icon className="w-5 h-5" />

              {/* Tooltip */}
              <div className="absolute left-full ml-3 px-3 py-1.5 bg-primary text-white text-xs font-medium rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all whitespace-nowrap z-50 shadow-lg">
                {item.name}
                <div className="absolute left-0 top-1/2 -translate-x-1 -translate-y-1/2 w-2 h-2 bg-primary rotate-45" />
              </div>
            </Link>
          );
        })}
      </nav>

      {/* Bottom Navigation */}
      <div className="flex flex-col items-center space-y-1 pt-4 border-t border-border-light/50">
        {bottomNavItems.map((item) => {
          const isActive = pathname === item.href;
          return (
            <Link
              key={item.name}
              href={item.href}
              title={item.name}
              className={`relative flex items-center justify-center w-12 h-12 rounded-2xl transition-all duration-200 group ${
                isActive
                  ? "bg-primary text-white shadow-lg shadow-primary/30"
                  : "text-text-muted hover:bg-white hover:text-primary hover:shadow-card"
              }`}
            >
              <item.icon className="w-5 h-5" />

              {/* Tooltip */}
              <div className="absolute left-full ml-3 px-3 py-1.5 bg-primary text-white text-xs font-medium rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all whitespace-nowrap z-50 shadow-lg">
                {item.name}
                <div className="absolute left-0 top-1/2 -translate-x-1 -translate-y-1/2 w-2 h-2 bg-primary rotate-45" />
              </div>
            </Link>
          );
        })}
      </div>
    </div>
  );
}
