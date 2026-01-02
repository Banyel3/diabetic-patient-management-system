"use client";

import Link from "next/link";
import {
  LayoutDashboard,
  Users,
  Calendar,
  Pill,
  Beaker,
  ArrowRight,
  CheckCircle2,
  Lightbulb,
  Rocket,
  BookOpen,
  Clock,
} from "lucide-react";

const modules = [
  {
    name: "Dashboard",
    href: "/",
    icon: LayoutDashboard,
    color: "bg-accent/10 text-accent",
    description:
      "Your command center. View patient stats, upcoming appointments, and key metrics at a glance.",
  },
  {
    name: "Patients",
    href: "/patients",
    icon: Users,
    color: "bg-blue-50 text-blue-500",
    description:
      "Manage your patient registry. Add new patients, view medical history, and track diabetes types.",
  },
  {
    name: "Appointments",
    href: "/appointments",
    icon: Calendar,
    color: "bg-purple-50 text-purple-500",
    description:
      "Schedule and manage patient appointments. Track visit status and maintain your clinic calendar.",
  },
  {
    name: "Medications",
    href: "/medications",
    icon: Pill,
    color: "bg-amber-50 text-amber-500",
    description:
      "Track prescriptions and medications. Monitor dosages, frequencies, and treatment plans.",
  },
  {
    name: "Lab Results",
    href: "/lab-results",
    icon: Beaker,
    color: "bg-green-50 text-green-500",
    description:
      "Record and analyze lab tests. Track HbA1c levels, glucose readings, and other vital markers.",
  },
];

const gettingStartedSteps = [
  {
    step: 1,
    title: "Add Your First Patient",
    description:
      "Start by adding patient information including their diabetes type, contact details, and medical history.",
    href: "/patients?action=add",
    linkText: "Add a Patient",
  },
  {
    step: 2,
    title: "Schedule an Appointment",
    description:
      "Book your patient's first appointment. Set the date, time, and appointment type.",
    href: "/appointments?action=add",
    linkText: "Schedule Appointment",
  },
  {
    step: 3,
    title: "Add Medications",
    description:
      "Record any current medications your patient is taking, including dosages and frequencies.",
    href: "/medications",
    linkText: "Manage Medications",
  },
  {
    step: 4,
    title: "Record Lab Results",
    description:
      "Enter HbA1c readings and other lab results to track your patient's diabetes control over time.",
    href: "/lab-results",
    linkText: "Add Lab Results",
  },
  {
    step: 5,
    title: "Review Your Dashboard",
    description:
      "Head back to the dashboard to see your clinic's overview, alerts, and patient statistics.",
    href: "/",
    linkText: "Go to Dashboard",
  },
];

export default function QuickStartPage() {
  return (
    <div className="space-y-8 max-w-5xl mx-auto">
      {/* Header */}
      <div className="text-center">
        <div className="inline-flex items-center gap-2 px-4 py-2 bg-accent/10 text-accent rounded-full text-sm font-medium mb-4">
          <Clock className="w-4 h-4" />
          2-minute read
        </div>
        <h1 className="text-3xl font-bold text-text-primary">
          Quick Start Guide
        </h1>
        <p className="text-text-secondary mt-3 text-lg max-w-2xl mx-auto">
          Welcome to DiabetaCare! This guide will help you get your clinic up
          and running in no time.
        </p>
      </div>

      {/* Modules Overview */}
      <div className="bg-white rounded-2xl shadow-card p-6">
        <div className="flex items-center gap-3 mb-6">
          <div className="w-10 h-10 bg-gradient-to-br from-accent to-teal-400 rounded-xl flex items-center justify-center">
            <BookOpen className="w-5 h-5 text-white" />
          </div>
          <div>
            <h2 className="text-xl font-bold text-text-primary">
              Core Modules
            </h2>
            <p className="text-sm text-text-muted">
              Explore the 5 key areas of DiabetaCare
            </p>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {modules.map((module) => (
            <Link
              key={module.name}
              href={module.href}
              className="group p-4 border border-border-light rounded-xl hover:border-accent/30 hover:shadow-card transition-all"
            >
              <div className="flex items-start gap-3">
                <div
                  className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${module.color}`}
                >
                  <module.icon className="w-5 h-5" />
                </div>
                <div className="flex-1 min-w-0">
                  <h3 className="font-semibold text-text-primary group-hover:text-accent transition-colors">
                    {module.name}
                  </h3>
                  <p className="text-sm text-text-muted mt-1 line-clamp-2">
                    {module.description}
                  </p>
                </div>
              </div>
            </Link>
          ))}
        </div>
      </div>

      {/* Getting Started Steps */}
      <div className="bg-white rounded-2xl shadow-card p-6">
        <div className="flex items-center gap-3 mb-6">
          <div className="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-400 rounded-xl flex items-center justify-center">
            <Rocket className="w-5 h-5 text-white" />
          </div>
          <div>
            <h2 className="text-xl font-bold text-text-primary">
              Getting Started
            </h2>
            <p className="text-sm text-text-muted">
              Follow these steps to set up your clinic
            </p>
          </div>
        </div>

        <div className="space-y-4">
          {gettingStartedSteps.map((item, index) => (
            <div
              key={item.step}
              className="flex gap-4 p-4 border border-border-light rounded-xl hover:border-accent/20 transition-colors"
            >
              {/* Step number */}
              <div className="flex-shrink-0 w-8 h-8 bg-accent/10 text-accent rounded-full flex items-center justify-center font-bold text-sm">
                {item.step}
              </div>

              {/* Content */}
              <div className="flex-1 min-w-0">
                <h3 className="font-semibold text-text-primary">
                  {item.title}
                </h3>
                <p className="text-sm text-text-muted mt-1">
                  {item.description}
                </p>
              </div>

              {/* Action link */}
              <Link
                href={item.href}
                className="flex-shrink-0 inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-accent hover:bg-accent/5 rounded-lg transition-colors group"
              >
                {item.linkText}
                <ArrowRight className="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
              </Link>
            </div>
          ))}
        </div>
      </div>

      {/* Tips Card */}
      <div className="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/50 rounded-2xl p-6">
        <div className="flex items-start gap-4">
          <div className="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <Lightbulb className="w-5 h-5 text-amber-600" />
          </div>
          <div>
            <h3 className="font-semibold text-amber-900">Pro Tips</h3>
            <ul className="mt-3 space-y-2">
              <li className="flex items-start gap-2 text-sm text-amber-800">
                <CheckCircle2 className="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" />
                <span>
                  Use the <strong>Dashboard</strong> daily to stay on top of
                  critical alerts and patient follow-ups.
                </span>
              </li>
              <li className="flex items-start gap-2 text-sm text-amber-800">
                <CheckCircle2 className="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" />
                <span>
                  Track <strong>HbA1c levels</strong> regularly to monitor
                  long-term diabetes control.
                </span>
              </li>
              <li className="flex items-start gap-2 text-sm text-amber-800">
                <CheckCircle2 className="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" />
                <span>
                  Keep medication records updated to identify potential
                  interactions.
                </span>
              </li>
              <li className="flex items-start gap-2 text-sm text-amber-800">
                <CheckCircle2 className="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" />
                <span>
                  You can always access this guide from the{" "}
                  <strong>Quick Start</strong> link in the sidebar.
                </span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      {/* Footer CTA */}
      <div className="text-center pb-8">
        <p className="text-text-muted mb-4">
          Ready to get started? Add your first patient now!
        </p>
        <Link
          href="/patients?action=add"
          className="inline-flex items-center gap-2 px-6 py-3 bg-accent text-white rounded-xl font-medium shadow-lg shadow-accent/20 hover:bg-accent/90 transition-all"
        >
          Add Your First Patient
          <ArrowRight className="w-5 h-5" />
        </Link>
      </div>
    </div>
  );
}
