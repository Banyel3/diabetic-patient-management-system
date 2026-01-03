"use client";

import { useState, useEffect, useCallback } from "react";
import { useRouter } from "next/navigation";
import {
  Users,
  Calendar,
  Pill,
  Beaker,
  TrendingUp,
  Clock,
  Plus,
  ArrowRight,
  Loader2,
  AlertCircle,
  RefreshCw,
  AlertTriangle,
  Activity,
} from "lucide-react";
import Link from "next/link";
import {
  dashboardApi,
  DashboardSummary,
  UpcomingAppointment,
  RecentPatient,
  Alert,
  HbA1cTrend,
} from "@/lib/api";
import { useAuthContext } from "@/lib/auth-context";
import QuickStartBanner from "@/components/QuickStartBanner";

export default function Dashboard() {
  const router = useRouter();
  const { authenticated, loading: authLoading } = useAuthContext();
  const [summary, setSummary] = useState<DashboardSummary | null>(null);
  const [upcomingAppointments, setUpcomingAppointments] = useState<
    UpcomingAppointment[]
  >([]);
  const [recentPatients, setRecentPatients] = useState<RecentPatient[]>([]);
  const [alerts, setAlerts] = useState<{
    high_hba1c: Alert[];
    no_recent_visit: Alert[];
    critical_labs: Alert[];
  } | null>(null);
  const [hba1cTrends, setHba1cTrends] = useState<HbA1cTrend[]>([]);
  const [totalAlerts, setTotalAlerts] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchDashboardData = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      // Fetch sequentially to ensure token is available for each request
      // This avoids race conditions where parallel requests might not all have the token
      const summaryData = await dashboardApi.getSummary();
      setSummary(summaryData);

      const appointmentsData = await dashboardApi.getUpcomingAppointments();
      setUpcomingAppointments(appointmentsData.appointments || []);

      const patientsData = await dashboardApi.getRecentPatients();
      setRecentPatients(patientsData.patients || []);

      const alertsData = await dashboardApi.getCriticalAlerts();
      setAlerts(alertsData.alerts);
      setTotalAlerts(alertsData.total_alerts);

      const trendsData = await dashboardApi.getHbA1cTrends(6);
      setHba1cTrends(trendsData.trends || []);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load dashboard");
      console.error("Dashboard error:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  // Only fetch data when authenticated and auth check is complete
  useEffect(() => {
    if (!authLoading && authenticated) {
      fetchDashboardData();
    }
  }, [fetchDashboardData, authenticated, authLoading]);

  // Show loading while auth is being checked
  if (authLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="w-8 h-8 animate-spin text-primary" />
      </div>
    );
  }

  // Build stats from summary
  const stats = summary
    ? [
        {
          label: "Total Patients",
          value: summary.patients.total,
          change: `${summary.patients.active} active`,
          icon: Users,
          color: "bg-accent/10 text-accent",
        },
        {
          label: "Appointments Today",
          value: summary.appointments.today.total,
          change: `${summary.appointments.today.scheduled} scheduled`,
          icon: Calendar,
          color: "bg-blue-50 text-blue-500",
        },
        {
          label: "Active Prescriptions",
          value: summary.medications.active_prescriptions,
          change: `${summary.medications.patients_on_medications} patients`,
          icon: Pill,
          color: "bg-purple-50 text-purple-500",
        },
        {
          label: "Lab Results (30d)",
          value: summary.lab_results.last_30_days,
          change: `${summary.hba1c_control.patients_tracked} HbA1c tracked`,
          icon: Beaker,
          color: "bg-amber-50 text-amber-500",
        },
      ]
    : [];

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="w-8 h-8 animate-spin text-accent" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px] text-center">
        <AlertCircle className="w-12 h-12 text-red-500 mb-4" />
        <p className="text-text-secondary mb-4">{error}</p>
        <button
          onClick={fetchDashboardData}
          className="flex items-center gap-2 px-4 py-2 bg-accent text-white rounded-xl hover:bg-accent/90 transition-colors"
        >
          <RefreshCw className="w-4 h-4" />
          Try Again
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Quick Start Banner - shown on first login */}
      <QuickStartBanner />

      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-text-primary">Dashboard</h1>
          <p className="text-text-muted mt-1">Welcome back, Doctor</p>
        </div>
        <Link
          href="/patients?action=add"
          className="flex items-center gap-2 px-4 py-2.5 bg-accent text-white rounded-xl font-medium shadow-lg shadow-accent/20 hover:bg-accent/90 transition-all"
        >
          <Plus className="w-5 h-5" />
          Add Patient
        </Link>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((stat) => (
          <div
            key={stat.label}
            className="bg-white p-5 rounded-2xl shadow-card hover:shadow-card-hover transition-shadow"
          >
            <div className="flex items-center justify-between mb-3">
              <div
                className={`w-10 h-10 rounded-xl flex items-center justify-center ${stat.color}`}
              >
                <stat.icon className="w-5 h-5" />
              </div>
              <TrendingUp className="w-4 h-4 text-green-500" />
            </div>
            <p className="text-2xl font-bold text-text-primary">{stat.value}</p>
            <p className="text-sm text-text-muted mt-1">{stat.label}</p>
            <p className="text-xs text-accent mt-2">{stat.change}</p>
          </div>
        ))}
      </div>

      {/* Main Content Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Recent Patients */}
        <div className="lg:col-span-2 bg-white rounded-2xl shadow-card p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-bold text-text-primary">
              Recent Patients
            </h2>
            <Link
              href="/patients"
              className="flex items-center gap-1 text-sm text-accent hover:underline"
            >
              View all <ArrowRight className="w-4 h-4" />
            </Link>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="text-left text-xs font-medium text-text-muted border-b border-border-light">
                  <th className="pb-3">Patient</th>
                  <th className="pb-3">Type</th>
                  <th className="pb-3">Status</th>
                  <th className="pb-3">HbA1c</th>
                </tr>
              </thead>
              <tbody>
                {recentPatients.length === 0 ? (
                  <tr>
                    <td
                      colSpan={4}
                      className="py-8 text-center text-text-muted"
                    >
                      No recent patients
                    </td>
                  </tr>
                ) : (
                  recentPatients.map((patient) => (
                    <tr
                      key={patient.id}
                      onClick={() => router.push(`/patients/${patient.id}`)}
                      className="border-b border-border-light/50 last:border-0 hover:bg-surface-secondary/50 transition-colors cursor-pointer"
                    >
                      <td className="py-3">
                        <div className="flex items-center gap-3">
                          <div className="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center text-accent font-medium text-sm">
                            {patient.name
                              .split(" ")
                              .map((n) => n[0])
                              .join("")}
                          </div>
                          <div>
                            <p className="font-medium text-text-primary text-sm">
                              {patient.name}
                            </p>
                            <p className="text-xs text-text-muted">
                              {patient.patient_code}
                            </p>
                          </div>
                        </div>
                      </td>
                      <td className="py-3">
                        <span className="px-2 py-1 bg-surface-secondary text-text-secondary text-xs rounded-lg">
                          {patient.diabetes_type}
                        </span>
                      </td>
                      <td className="py-3">
                        <span
                          className={`px-2 py-1 text-xs rounded-lg ${
                            patient.status === "Active"
                              ? "bg-green-50 text-green-600"
                              : "bg-gray-100 text-gray-600"
                          }`}
                        >
                          {patient.status}
                        </span>
                      </td>
                      <td className="py-3">
                        <span
                          className={`font-semibold text-sm ${
                            patient.last_hba1c === null
                              ? "text-text-muted"
                              : patient.last_hba1c > 7.5
                              ? "text-red-500"
                              : patient.last_hba1c > 6.5
                              ? "text-amber-500"
                              : "text-green-500"
                          }`}
                        >
                          {patient.last_hba1c !== null
                            ? `${patient.last_hba1c}%`
                            : "N/A"}
                        </span>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Today's Appointments */}
        <div className="bg-white rounded-2xl shadow-card p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-bold text-text-primary">
              Today&apos;s Schedule
            </h2>
            <Link
              href="/appointments"
              className="flex items-center gap-1 text-sm text-accent hover:underline"
            >
              View all <ArrowRight className="w-4 h-4" />
            </Link>
          </div>

          <div className="space-y-3">
            {upcomingAppointments.length === 0 ? (
              <p className="text-center text-text-muted py-8">
                No upcoming appointments
              </p>
            ) : (
              upcomingAppointments.slice(0, 5).map((apt) => (
                <div
                  key={apt.id}
                  onClick={() => router.push(`/appointments`)}
                  className="flex items-center gap-3 p-3 bg-surface-secondary rounded-xl hover:bg-accent/5 transition-colors cursor-pointer"
                >
                  <div className="flex items-center gap-2 text-text-muted">
                    <Clock className="w-4 h-4" />
                    <span className="text-xs font-medium w-16">{apt.time}</span>
                  </div>
                  <div className="flex-1">
                    <p className="text-sm font-medium text-text-primary">
                      {apt.patient_name}
                    </p>
                    <p className="text-xs text-text-muted">{apt.type}</p>
                  </div>
                </div>
              ))
            )}
          </div>

          <Link
            href="/appointments?action=add"
            className="mt-4 w-full flex items-center justify-center gap-2 py-2.5 border-2 border-dashed border-border-light text-text-muted rounded-xl hover:border-accent hover:text-accent transition-colors"
          >
            <Plus className="w-4 h-4" />
            <span className="text-sm">Schedule Appointment</span>
          </Link>
        </div>
      </div>

      {/* Critical Alerts Section */}
      {alerts && totalAlerts > 0 && (
        <div className="bg-white rounded-2xl shadow-card p-5">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                <AlertTriangle className="w-5 h-5 text-red-600" />
              </div>
              <div>
                <h2 className="text-lg font-bold text-text-primary">
                  Critical Alerts
                </h2>
                <p className="text-sm text-text-muted">
                  {totalAlerts} patients need attention
                </p>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {/* High HbA1c Alerts */}
            {alerts.high_hba1c.length > 0 && (
              <div className="bg-red-50 rounded-xl p-4">
                <h3 className="font-semibold text-red-700 mb-3 flex items-center gap-2">
                  <Activity className="w-4 h-4" />
                  High HbA1c ({alerts.high_hba1c.length})
                </h3>
                <div className="space-y-2">
                  {alerts.high_hba1c.slice(0, 3).map((alert, idx) => (
                    <Link
                      key={idx}
                      href={`/patients/${alert.patient_id}`}
                      className="block p-2 bg-white rounded-lg hover:bg-red-100 transition-colors"
                    >
                      <p className="font-medium text-sm text-text-primary">
                        {alert.patient_name}
                      </p>
                      <p className="text-xs text-red-600">
                        HbA1c: {alert.hba1c}%
                      </p>
                    </Link>
                  ))}
                </div>
              </div>
            )}

            {/* No Recent Visit Alerts */}
            {alerts.no_recent_visit.length > 0 && (
              <div className="bg-amber-50 rounded-xl p-4">
                <h3 className="font-semibold text-amber-700 mb-3 flex items-center gap-2">
                  <Clock className="w-4 h-4" />
                  No Recent Visit ({alerts.no_recent_visit.length})
                </h3>
                <div className="space-y-2">
                  {alerts.no_recent_visit.slice(0, 3).map((alert, idx) => (
                    <Link
                      key={idx}
                      href={`/patients/${alert.patient_id}`}
                      className="block p-2 bg-white rounded-lg hover:bg-amber-100 transition-colors"
                    >
                      <p className="font-medium text-sm text-text-primary">
                        {alert.patient_name}
                      </p>
                      <p className="text-xs text-amber-600">
                        {alert.days_since_visit
                          ? `${alert.days_since_visit} days ago`
                          : "Never visited"}
                      </p>
                    </Link>
                  ))}
                </div>
              </div>
            )}

            {/* Critical Labs Alerts */}
            {alerts.critical_labs.length > 0 && (
              <div className="bg-orange-50 rounded-xl p-4">
                <h3 className="font-semibold text-orange-700 mb-3 flex items-center gap-2">
                  <Beaker className="w-4 h-4" />
                  Critical Labs ({alerts.critical_labs.length})
                </h3>
                <div className="space-y-2">
                  {alerts.critical_labs.slice(0, 3).map((alert, idx) => (
                    <Link
                      key={idx}
                      href={`/patients/${alert.patient_id}`}
                      className="block p-2 bg-white rounded-lg hover:bg-orange-100 transition-colors"
                    >
                      <p className="font-medium text-sm text-text-primary">
                        {alert.patient_name}
                      </p>
                      <p className="text-xs text-orange-600">
                        {alert.test_name}: {alert.result}
                      </p>
                    </Link>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* HbA1c Trends Chart */}
      {hba1cTrends.length > 0 && (
        <div className="bg-white rounded-2xl shadow-card p-5">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center">
                <TrendingUp className="w-5 h-5 text-accent" />
              </div>
              <div>
                <h2 className="text-lg font-bold text-text-primary">
                  HbA1c Trends
                </h2>
                <p className="text-sm text-text-muted">
                  Average HbA1c over the last 6 months
                </p>
              </div>
            </div>
          </div>

          {/* Simple Bar Chart */}
          <div className="flex items-end justify-between gap-2 h-48 px-4">
            {hba1cTrends.map((trend) => {
              const maxHba1c = 12;
              const heightPercent = Math.min(
                (trend.average_hba1c / maxHba1c) * 100,
                100
              );
              const barColor =
                trend.average_hba1c < 7
                  ? "bg-green-500"
                  : trend.average_hba1c < 8
                  ? "bg-amber-500"
                  : trend.average_hba1c < 9
                  ? "bg-orange-500"
                  : "bg-red-500";

              return (
                <div
                  key={trend.month}
                  className="flex-1 flex flex-col items-center gap-2"
                >
                  <div className="w-full flex flex-col items-center justify-end h-36">
                    <span className="text-xs font-medium text-text-primary mb-1">
                      {trend.average_hba1c.toFixed(1)}%
                    </span>
                    <div
                      className={`w-full max-w-[40px] ${barColor} rounded-t-lg transition-all`}
                      style={{ height: `${heightPercent}%` }}
                    />
                  </div>
                  <div className="text-center">
                    <p className="text-xs font-medium text-text-secondary">
                      {trend.label}
                    </p>
                    <p className="text-xs text-text-muted">
                      {trend.test_count} tests
                    </p>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* HbA1c Control Summary */}
      {summary && (
        <div className="bg-white rounded-2xl shadow-card p-5">
          <h2 className="text-lg font-bold text-text-primary mb-4">
            HbA1c Control Overview
          </h2>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center p-4 bg-green-50 rounded-xl">
              <p className="text-2xl font-bold text-green-600">
                {summary.hba1c_control.distribution.well_controlled}
              </p>
              <p className="text-sm text-green-700">Well Controlled (&lt;7%)</p>
            </div>
            <div className="text-center p-4 bg-amber-50 rounded-xl">
              <p className="text-2xl font-bold text-amber-600">
                {summary.hba1c_control.distribution.moderate}
              </p>
              <p className="text-sm text-amber-700">Moderate (7-8%)</p>
            </div>
            <div className="text-center p-4 bg-orange-50 rounded-xl">
              <p className="text-2xl font-bold text-orange-600">
                {summary.hba1c_control.distribution.poor}
              </p>
              <p className="text-sm text-orange-700">Poor (8-9%)</p>
            </div>
            <div className="text-center p-4 bg-red-50 rounded-xl">
              <p className="text-2xl font-bold text-red-600">
                {summary.hba1c_control.distribution.very_poor}
              </p>
              <p className="text-sm text-red-700">Very Poor (&gt;9%)</p>
            </div>
          </div>
          <div className="mt-4 text-center">
            <p className="text-text-muted">
              Average HbA1c:{" "}
              <span className="font-semibold text-text-primary">
                {summary.hba1c_control.average.toFixed(1)}%
              </span>{" "}
              across {summary.hba1c_control.patients_tracked} patients
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
