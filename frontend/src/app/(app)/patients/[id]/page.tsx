"use client";

import { useState, useEffect, useCallback } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import {
  ArrowLeft,
  User,
  Phone,
  Mail,
  MapPin,
  Calendar,
  Pill,
  Beaker,
  AlertCircle,
  Loader2,
  RefreshCw,
  Edit2,
  Activity,
  Clock,
  TrendingUp,
  Heart,
  X,
} from "lucide-react";
import {
  patientsApi,
  appointmentsApi,
  medicationsApi,
  labResultsApi,
  Patient,
  Appointment,
  Medication,
  LabResult,
  PatientInput,
  FamilyHistoryDiabetes,
} from "@/lib/api";

export default function PatientDetailPage() {
  const params = useParams();
  const router = useRouter();
  const patientId = Number(params.id);

  const [patient, setPatient] = useState<Patient | null>(null);
  const [appointments, setAppointments] = useState<Appointment[]>([]);
  const [medications, setMedications] = useState<Medication[]>([]);
  const [labResults, setLabResults] = useState<LabResult[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<
    "overview" | "appointments" | "medications" | "labs"
  >("overview");

  // Edit modal state
  const [showEditModal, setShowEditModal] = useState(false);
  const [saving, setSaving] = useState(false);
  const [editFormData, setEditFormData] = useState<PatientInput | null>(null);

  const fetchPatientData = useCallback(async () => {
    if (!patientId) return;

    try {
      setLoading(true);
      setError(null);

      const [patientData, appointmentsData, medicationsData, labResultsData] =
        await Promise.all([
          patientsApi.get(patientId),
          appointmentsApi.list({ patient_id: patientId, page_size: 50 }),
          medicationsApi.list({ patient_id: patientId, page_size: 50 }),
          labResultsApi.list({ patient_id: patientId, page_size: 50 }),
        ]);

      setPatient(patientData);
      setAppointments(appointmentsData.items);
      setMedications(medicationsData.items);
      setLabResults(labResultsData.items);
    } catch (err) {
      const apiError = err as { message?: string };
      setError(apiError.message || "Failed to load patient data");
    } finally {
      setLoading(false);
    }
  }, [patientId]);

  const openEditModal = () => {
    if (!patient) return;
    setEditFormData({
      first_name: patient.first_name,
      last_name: patient.last_name,
      date_of_birth: patient.date_of_birth,
      gender: patient.gender.toLowerCase() as "male" | "female" | "other",
      phone: patient.phone || "",
      email: patient.email || "",
      address: patient.address || "",
      diabetes_type: patient.diabetes_type,
      diagnosis_date: patient.diagnosis_date || "",
      family_history_diabetes: patient.family_history_diabetes || "unknown",
      family_history_notes: patient.family_history_notes || "",
      status: patient.status,
      notes: patient.notes || "",
    });
    setShowEditModal(true);
  };

  const handleEditSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editFormData || !patient) return;

    try {
      setSaving(true);
      const updatedPatient = await patientsApi.update(patient.id, editFormData);
      setPatient(updatedPatient);
      setShowEditModal(false);
      setEditFormData(null);
    } catch (err) {
      const apiError = err as { message?: string };
      alert(apiError.message || "Failed to update patient");
    } finally {
      setSaving(false);
    }
  };

  const handleEditFieldChange = (field: keyof PatientInput, value: string) => {
    if (!editFormData) return;
    setEditFormData({ ...editFormData, [field]: value });
  };

  useEffect(() => {
    fetchPatientData();
  }, [fetchPatientData]);

  const getStatusColor = (status: string) => {
    switch (status) {
      case "Active":
        return "bg-green-100 text-green-700";
      case "Inactive":
        return "bg-gray-100 text-gray-700";
      case "Deceased":
        return "bg-red-100 text-red-700";
      default:
        return "bg-gray-100 text-gray-700";
    }
  };

  const getHbA1cColor = (value: number | null) => {
    if (value === null) return "text-text-muted";
    if (value < 7) return "text-green-600";
    if (value < 8) return "text-amber-600";
    return "text-red-600";
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="w-8 h-8 animate-spin text-accent" />
      </div>
    );
  }

  if (error || !patient) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px] text-center">
        <AlertCircle className="w-12 h-12 text-red-500 mb-4" />
        <p className="text-text-secondary mb-4">
          {error || "Patient not found"}
        </p>
        <div className="flex gap-3">
          <button
            onClick={() => router.back()}
            className="flex items-center gap-2 px-4 py-2 text-text-secondary hover:text-text-primary"
          >
            <ArrowLeft className="w-4 h-4" />
            Go Back
          </button>
          <button
            onClick={fetchPatientData}
            className="flex items-center gap-2 px-4 py-2 bg-accent text-white rounded-xl hover:bg-accent/90"
          >
            <RefreshCw className="w-4 h-4" />
            Try Again
          </button>
        </div>
      </div>
    );
  }

  const activeMedications = medications.filter((m) => m.status === "active");
  const upcomingAppointments = appointments.filter(
    (a) => a.status === "Scheduled"
  );
  const recentLabs = labResults.slice(0, 5);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <button
            onClick={() => router.back()}
            className="p-2 hover:bg-surface-secondary rounded-xl transition-colors"
          >
            <ArrowLeft className="w-5 h-5 text-text-secondary" />
          </button>
          <div>
            <h1 className="text-2xl font-bold text-text-primary">
              {patient.first_name} {patient.last_name}
            </h1>
            <p className="text-text-muted mt-1">{patient.patient_code}</p>
          </div>
        </div>
        <button
          onClick={openEditModal}
          className="flex items-center gap-2 px-4 py-2.5 bg-accent text-white rounded-xl font-medium hover:bg-accent/90 transition-all"
        >
          <Edit2 className="w-4 h-4" />
          Edit Patient
        </button>
      </div>

      {/* Patient Info Card */}
      <div className="bg-white rounded-2xl shadow-card p-6">
        <div className="flex flex-col lg:flex-row gap-6">
          {/* Avatar & Basic Info */}
          <div className="flex items-start gap-4">
            <div className="w-20 h-20 rounded-2xl bg-accent/10 flex items-center justify-center text-accent text-2xl font-bold">
              {patient.first_name[0]}
              {patient.last_name[0]}
            </div>
            <div>
              <div className="flex items-center gap-3 mb-2">
                <span
                  className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(
                    patient.status
                  )}`}
                >
                  {patient.status}
                </span>
                <span className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                  {patient.diabetes_type}
                </span>
              </div>
              <div className="flex items-center gap-2 text-text-muted text-sm">
                <Calendar className="w-4 h-4" />
                <span>
                  Age: {patient.age} years ({patient.date_of_birth})
                </span>
              </div>
              <div className="flex items-center gap-2 text-text-muted text-sm mt-1">
                <User className="w-4 h-4" />
                <span>{patient.gender}</span>
              </div>
            </div>
          </div>

          {/* Contact Info */}
          <div className="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-surface-secondary flex items-center justify-center">
                <Phone className="w-5 h-5 text-text-muted" />
              </div>
              <div>
                <p className="text-xs text-text-muted">Phone</p>
                <p className="font-medium text-text-primary">{patient.phone}</p>
              </div>
            </div>
            {patient.email && (
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-xl bg-surface-secondary flex items-center justify-center">
                  <Mail className="w-5 h-5 text-text-muted" />
                </div>
                <div>
                  <p className="text-xs text-text-muted">Email</p>
                  <p className="font-medium text-text-primary">
                    {patient.email}
                  </p>
                </div>
              </div>
            )}
            {patient.address && (
              <div className="flex items-center gap-3 md:col-span-2">
                <div className="w-10 h-10 rounded-xl bg-surface-secondary flex items-center justify-center">
                  <MapPin className="w-5 h-5 text-text-muted" />
                </div>
                <div>
                  <p className="text-xs text-text-muted">Address</p>
                  <p className="font-medium text-text-primary">
                    {patient.address}
                  </p>
                </div>
              </div>
            )}
          </div>

          {/* HbA1c Card */}
          <div className="bg-surface-secondary rounded-xl p-4 min-w-[200px]">
            <div className="flex items-center gap-2 mb-2">
              <Activity className="w-5 h-5 text-accent" />
              <span className="text-sm font-medium text-text-secondary">
                Last HbA1c
              </span>
            </div>
            <p
              className={`text-3xl font-bold ${getHbA1cColor(
                patient.last_hba1c
              )}`}
            >
              {patient.last_hba1c !== null ? `${patient.last_hba1c}%` : "N/A"}
            </p>
            {patient.diagnosis_date && (
              <p className="text-xs text-text-muted mt-2">
                Diagnosed:{" "}
                {new Date(patient.diagnosis_date).toLocaleDateString()}
              </p>
            )}
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="bg-white rounded-2xl shadow-card overflow-hidden">
        <div className="flex border-b border-border-light">
          {[
            { id: "overview", label: "Overview", icon: Heart },
            {
              id: "appointments",
              label: "Appointments",
              icon: Calendar,
              count: appointments.length,
            },
            {
              id: "medications",
              label: "Medications",
              icon: Pill,
              count: medications.length,
            },
            {
              id: "labs",
              label: "Lab Results",
              icon: Beaker,
              count: labResults.length,
            },
          ].map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id as typeof activeTab)}
              className={`flex items-center gap-2 px-6 py-4 font-medium transition-colors ${
                activeTab === tab.id
                  ? "text-accent border-b-2 border-accent bg-accent/5"
                  : "text-text-muted hover:text-text-primary hover:bg-surface-secondary"
              }`}
            >
              <tab.icon className="w-4 h-4" />
              {tab.label}
              {tab.count !== undefined && (
                <span className="px-2 py-0.5 bg-surface-secondary rounded-full text-xs">
                  {tab.count}
                </span>
              )}
            </button>
          ))}
        </div>

        <div className="p-6">
          {/* Overview Tab */}
          {activeTab === "overview" && (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {/* Quick Stats */}
              <div className="lg:col-span-3 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="bg-surface-secondary rounded-xl p-4 text-center">
                  <p className="text-2xl font-bold text-accent">
                    {upcomingAppointments.length}
                  </p>
                  <p className="text-sm text-text-muted">
                    Upcoming Appointments
                  </p>
                </div>
                <div className="bg-surface-secondary rounded-xl p-4 text-center">
                  <p className="text-2xl font-bold text-green-600">
                    {activeMedications.length}
                  </p>
                  <p className="text-sm text-text-muted">Active Medications</p>
                </div>
                <div className="bg-surface-secondary rounded-xl p-4 text-center">
                  <p className="text-2xl font-bold text-blue-600">
                    {labResults.length}
                  </p>
                  <p className="text-sm text-text-muted">Lab Results</p>
                </div>
                <div className="bg-surface-secondary rounded-xl p-4 text-center">
                  <p className="text-2xl font-bold text-text-primary">
                    {patient.last_visit_date
                      ? new Date(patient.last_visit_date).toLocaleDateString()
                      : "N/A"}
                  </p>
                  <p className="text-sm text-text-muted">Last Visit</p>
                </div>
              </div>

              {/* Recent Appointments */}
              <div>
                <h3 className="font-semibold text-text-primary mb-3">
                  Upcoming Appointments
                </h3>
                {upcomingAppointments.length === 0 ? (
                  <p className="text-text-muted text-sm">
                    No upcoming appointments
                  </p>
                ) : (
                  <div className="space-y-2">
                    {upcomingAppointments.slice(0, 3).map((apt) => (
                      <div
                        key={apt.id}
                        className="p-3 bg-surface-secondary rounded-xl"
                      >
                        <div className="flex items-center gap-2 text-sm">
                          <Clock className="w-4 h-4 text-text-muted" />
                          <span className="font-medium">
                            {apt.date} at {apt.time}
                          </span>
                        </div>
                        <p className="text-xs text-text-muted mt-1">
                          {apt.type}
                        </p>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {/* Active Medications */}
              <div>
                <h3 className="font-semibold text-text-primary mb-3">
                  Active Medications
                </h3>
                {activeMedications.length === 0 ? (
                  <p className="text-text-muted text-sm">
                    No active medications
                  </p>
                ) : (
                  <div className="space-y-2">
                    {activeMedications.slice(0, 3).map((med) => (
                      <div
                        key={med.id}
                        className="p-3 bg-surface-secondary rounded-xl"
                      >
                        <p className="font-medium text-sm">{med.name}</p>
                        <p className="text-xs text-text-muted">
                          {med.dosage} - {med.frequency}
                        </p>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {/* Recent Labs */}
              <div>
                <h3 className="font-semibold text-text-primary mb-3">
                  Recent Lab Results
                </h3>
                {recentLabs.length === 0 ? (
                  <p className="text-text-muted text-sm">No lab results</p>
                ) : (
                  <div className="space-y-2">
                    {recentLabs.slice(0, 3).map((lab) => (
                      <div
                        key={lab.id}
                        className="p-3 bg-surface-secondary rounded-xl"
                      >
                        <p className="font-medium text-sm">{lab.test_name}</p>
                        <div className="flex justify-between items-center mt-1">
                          <span className="text-xs text-text-muted">
                            {lab.test_date}
                          </span>
                          <span
                            className={`text-sm font-medium ${
                              lab.status === "Critical"
                                ? "text-red-600"
                                : lab.status === "Abnormal"
                                ? "text-amber-600"
                                : "text-green-600"
                            }`}
                          >
                            {lab.result_value} {lab.unit}
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {/* Notes */}
              {patient.notes && (
                <div className="lg:col-span-3">
                  <h3 className="font-semibold text-text-primary mb-3">
                    Notes
                  </h3>
                  <div className="p-4 bg-surface-secondary rounded-xl">
                    <p className="text-sm text-text-secondary whitespace-pre-wrap">
                      {patient.notes}
                    </p>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Appointments Tab */}
          {activeTab === "appointments" && (
            <div>
              {appointments.length === 0 ? (
                <p className="text-center text-text-muted py-8">
                  No appointments found
                </p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="text-left text-xs font-medium text-text-muted border-b border-border-light">
                        <th className="pb-3 pr-4">Date & Time</th>
                        <th className="pb-3 pr-4">Type</th>
                        <th className="pb-3 pr-4">Duration</th>
                        <th className="pb-3 pr-4">Status</th>
                        <th className="pb-3">Notes</th>
                      </tr>
                    </thead>
                    <tbody>
                      {appointments.map((apt) => (
                        <tr
                          key={apt.id}
                          className="border-b border-border-light/50 last:border-0"
                        >
                          <td className="py-3 pr-4">
                            <p className="font-medium text-text-primary">
                              {apt.date}
                            </p>
                            <p className="text-xs text-text-muted">
                              {apt.time}
                            </p>
                          </td>
                          <td className="py-3 pr-4">{apt.type}</td>
                          <td className="py-3 pr-4">
                            {apt.duration_minutes} min
                          </td>
                          <td className="py-3 pr-4">
                            <span
                              className={`px-2 py-1 rounded-full text-xs font-medium ${
                                apt.status === "Scheduled"
                                  ? "bg-blue-100 text-blue-700"
                                  : apt.status === "Completed"
                                  ? "bg-green-100 text-green-700"
                                  : apt.status === "Cancelled"
                                  ? "bg-red-100 text-red-700"
                                  : "bg-gray-100 text-gray-700"
                              }`}
                            >
                              {apt.status}
                            </span>
                          </td>
                          <td className="py-3 text-sm text-text-muted">
                            {apt.notes || "-"}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          )}

          {/* Medications Tab */}
          {activeTab === "medications" && (
            <div>
              {medications.length === 0 ? (
                <p className="text-center text-text-muted py-8">
                  No medications found
                </p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="text-left text-xs font-medium text-text-muted border-b border-border-light">
                        <th className="pb-3 pr-4">Medication</th>
                        <th className="pb-3 pr-4">Dosage</th>
                        <th className="pb-3 pr-4">Frequency</th>
                        <th className="pb-3 pr-4">Start Date</th>
                        <th className="pb-3 pr-4">Status</th>
                        <th className="pb-3">Notes</th>
                      </tr>
                    </thead>
                    <tbody>
                      {medications.map((med) => (
                        <tr
                          key={med.id}
                          className="border-b border-border-light/50 last:border-0"
                        >
                          <td className="py-3 pr-4 font-medium text-text-primary">
                            {med.name}
                          </td>
                          <td className="py-3 pr-4">{med.dosage}</td>
                          <td className="py-3 pr-4">{med.frequency}</td>
                          <td className="py-3 pr-4">{med.start_date}</td>
                          <td className="py-3 pr-4">
                            <span
                              className={`px-2 py-1 rounded-full text-xs font-medium ${
                                med.status === "active"
                                  ? "bg-green-100 text-green-700"
                                  : med.status === "discontinued"
                                  ? "bg-red-100 text-red-700"
                                  : "bg-gray-100 text-gray-700"
                              }`}
                            >
                              {med.status}
                            </span>
                          </td>
                          <td className="py-3 text-sm text-text-muted">
                            {med.notes || "-"}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          )}

          {/* Lab Results Tab */}
          {activeTab === "labs" && (
            <div>
              {labResults.length === 0 ? (
                <p className="text-center text-text-muted py-8">
                  No lab results found
                </p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="text-left text-xs font-medium text-text-muted border-b border-border-light">
                        <th className="pb-3 pr-4">Test Name</th>
                        <th className="pb-3 pr-4">Date</th>
                        <th className="pb-3 pr-4">Result</th>
                        <th className="pb-3 pr-4">Reference Range</th>
                        <th className="pb-3 pr-4">Status</th>
                        <th className="pb-3">Notes</th>
                      </tr>
                    </thead>
                    <tbody>
                      {labResults.map((lab) => (
                        <tr
                          key={lab.id}
                          className="border-b border-border-light/50 last:border-0"
                        >
                          <td className="py-3 pr-4 font-medium text-text-primary">
                            {lab.test_name}
                          </td>
                          <td className="py-3 pr-4">{lab.test_date}</td>
                          <td className="py-3 pr-4">
                            <span
                              className={`font-medium ${
                                lab.status === "Critical"
                                  ? "text-red-600"
                                  : lab.status === "Abnormal"
                                  ? "text-amber-600"
                                  : "text-green-600"
                              }`}
                            >
                              {lab.result_value} {lab.unit}
                            </span>
                          </td>
                          <td className="py-3 pr-4 text-text-muted">
                            {lab.reference_range || "-"}
                          </td>
                          <td className="py-3 pr-4">
                            <span
                              className={`px-2 py-1 rounded-full text-xs font-medium ${
                                lab.status === "Normal"
                                  ? "bg-green-100 text-green-700"
                                  : lab.status === "Abnormal"
                                  ? "bg-amber-100 text-amber-700"
                                  : lab.status === "Critical"
                                  ? "bg-red-100 text-red-700"
                                  : "bg-gray-100 text-gray-700"
                              }`}
                            >
                              {lab.status}
                            </span>
                          </td>
                          <td className="py-3 text-sm text-text-muted">
                            {lab.notes || "-"}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Edit Patient Modal */}
      {showEditModal && editFormData && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="sticky top-0 bg-white border-b border-border-light p-6 flex items-center justify-between">
              <h2 className="text-xl font-bold text-text-primary">
                Edit Patient
              </h2>
              <button
                onClick={() => {
                  setShowEditModal(false);
                  setEditFormData(null);
                }}
                className="p-2 hover:bg-surface-secondary rounded-lg transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleEditSubmit} className="p-6 space-y-4">
              {/* Basic Information */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-text-primary mb-1.5">
                    First Name *
                  </label>
                  <input
                    type="text"
                    value={editFormData.first_name}
                    onChange={(e) =>
                      handleEditFieldChange("first_name", e.target.value)
                    }
                    required
                    className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-text-primary mb-1.5">
                    Last Name *
                  </label>
                  <input
                    type="text"
                    value={editFormData.last_name}
                    onChange={(e) =>
                      handleEditFieldChange("last_name", e.target.value)
                    }
                    required
                    className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-text-primary mb-1.5">
                    Date of Birth *
                  </label>
                  <input
                    type="date"
                    value={editFormData.date_of_birth}
                    onChange={(e) =>
                      handleEditFieldChange("date_of_birth", e.target.value)
                    }
                    required
                    className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-text-primary mb-1.5">
                    Gender *
                  </label>
                  <select
                    value={editFormData.gender}
                    onChange={(e) =>
                      handleEditFieldChange("gender", e.target.value)
                    }
                    required
                    className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none"
                  >
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                  </select>
                </div>
              </div>

              {/* Contact Information */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-text-primary mb-1.5">
                    Phone
                  </label>
                  <input
                    type="tel"
                    value={editFormData.phone || ""}
                    onChange={(e) =>
                      handleEditFieldChange("phone", e.target.value)
                    }
                    className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-text-primary mb-1.5">
                    Email
                  </label>
                  <input
                    type="email"
                    value={editFormData.email || ""}
                    onChange={(e) =>
                      handleEditFieldChange("email", e.target.value)
                    }
                    className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-text-primary mb-1.5">
                  Address
                </label>
                <input
                  type="text"
                  value={editFormData.address || ""}
                  onChange={(e) =>
                    handleEditFieldChange("address", e.target.value)
                  }
                  className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none"
                />
              </div>

              {/* Medical Information */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-text-primary mb-1.5">
                    Diabetes Type *
                  </label>
                  <select
                    value={editFormData.diabetes_type}
                    onChange={(e) =>
                      handleEditFieldChange("diabetes_type", e.target.value)
                    }
                    required
                    className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none"
                  >
                    <option value="Type 1">Type 1</option>
                    <option value="Type 2">Type 2</option>
                    <option value="Gestational">Gestational</option>
                    <option value="Pre-diabetic">Pre-diabetic</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-text-primary mb-1.5">
                    Diagnosis Date
                  </label>
                  <input
                    type="date"
                    value={editFormData.diagnosis_date || ""}
                    onChange={(e) =>
                      handleEditFieldChange("diagnosis_date", e.target.value)
                    }
                    className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none"
                  />
                </div>
              </div>

              {/* Family History */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-text-primary mb-1.5">
                    Family History of Diabetes
                  </label>
                  <select
                    value={editFormData.family_history_diabetes}
                    onChange={(e) =>
                      handleEditFieldChange(
                        "family_history_diabetes",
                        e.target.value
                      )
                    }
                    className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none"
                  >
                    <option value="unknown">Unknown</option>
                    <option value="none">None</option>
                    <option value="first_degree">First Degree Relative</option>
                    <option value="second_degree">
                      Second Degree Relative
                    </option>
                    <option value="both">Both</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-text-primary mb-1.5">
                    Status
                  </label>
                  <select
                    value={editFormData.status}
                    onChange={(e) =>
                      handleEditFieldChange("status", e.target.value)
                    }
                    className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none"
                  >
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Deceased">Deceased</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-text-primary mb-1.5">
                  Family History Notes
                </label>
                <textarea
                  value={editFormData.family_history_notes || ""}
                  onChange={(e) =>
                    handleEditFieldChange(
                      "family_history_notes",
                      e.target.value
                    )
                  }
                  rows={2}
                  className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none resize-none"
                  placeholder="Add details about family history..."
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-text-primary mb-1.5">
                  Notes
                </label>
                <textarea
                  value={editFormData.notes || ""}
                  onChange={(e) =>
                    handleEditFieldChange("notes", e.target.value)
                  }
                  rows={3}
                  className="w-full px-4 py-2.5 border border-border-light rounded-xl focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none resize-none"
                  placeholder="Additional notes..."
                />
              </div>

              {/* Form Actions */}
              <div className="flex gap-3 pt-4">
                <button
                  type="button"
                  onClick={() => {
                    setShowEditModal(false);
                    setEditFormData(null);
                  }}
                  className="flex-1 px-4 py-2.5 border border-border-light text-text-secondary rounded-xl hover:bg-surface-secondary transition-colors"
                  disabled={saving}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={saving}
                  className="flex-1 px-4 py-2.5 bg-accent text-white rounded-xl font-medium hover:bg-accent/90 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  {saving ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" />
                      Saving...
                    </>
                  ) : (
                    "Save Changes"
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
