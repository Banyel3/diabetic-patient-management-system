"use client";

import { useState, useEffect, useCallback } from "react";
import {
  Search,
  Plus,
  Edit2,
  Trash2,
  X,
  ChevronLeft,
  ChevronRight,
  Clock,
  Calendar,
  Loader2,
  AlertCircle,
  RefreshCw,
} from "lucide-react";
import {
  appointmentsApi,
  patientsApi,
  Appointment,
  AppointmentInput,
  Patient,
} from "@/lib/api";

interface PatientOption {
  id: number;
  name: string;
}

export default function AppointmentsPage() {
  const [appointments, setAppointments] = useState<Appointment[]>([]);
  const [patients, setPatients] = useState<PatientOption[]>([]);
  const [searchQuery, setSearchQuery] = useState("");
  const [filterStatus, setFilterStatus] = useState<string>("all");
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [showModal, setShowModal] = useState(false);
  const [modalMode, setModalMode] = useState<"add" | "edit">("add");
  const [selectedAppointment, setSelectedAppointment] =
    useState<Appointment | null>(null);
  const [formData, setFormData] = useState<AppointmentInput>({
    patient_id: 0,
    scheduled_at: "",
    duration_minutes: 30,
    type: "Check-up",
    status: "Scheduled",
    notes: "",
  });
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [appointmentToDelete, setAppointmentToDelete] =
    useState<Appointment | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const itemsPerPage = 6;

  const fetchAppointments = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await appointmentsApi.list({
        page: currentPage,
        page_size: itemsPerPage,
        search: searchQuery || undefined,
        status: filterStatus !== "all" ? filterStatus : undefined,
      });
      setAppointments(response.items);
      setTotalPages(response.pagination.total_pages);
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "Failed to load appointments"
      );
    } finally {
      setLoading(false);
    }
  }, [currentPage, searchQuery, filterStatus]);

  const fetchPatients = useCallback(async () => {
    try {
      const response = await patientsApi.list({ page_size: 100 });
      setPatients(
        response.items.map((p: Patient) => ({
          id: p.id,
          name: `${p.first_name} ${p.last_name}`,
        }))
      );
    } catch (err) {
      console.error("Failed to load patients:", err);
    }
  }, []);

  useEffect(() => {
    fetchAppointments();
  }, [fetchAppointments]);

  useEffect(() => {
    fetchPatients();
  }, [fetchPatients]);

  // Handlers
  const openAddModal = () => {
    setFormData({
      patient_id: 0,
      scheduled_at: "",
      duration_minutes: 30,
      type: "Check-up",
      status: "Scheduled",
      notes: "",
    });
    setModalMode("add");
    setShowModal(true);
  };

  const openEditModal = (apt: Appointment) => {
    setSelectedAppointment(apt);
    setFormData({
      patient_id: apt.patient_id,
      scheduled_at: apt.scheduled_at,
      duration_minutes: apt.duration_minutes,
      type: apt.type,
      status: apt.status,
      notes: apt.notes || "",
    });
    setModalMode("edit");
    setShowModal(true);
  };

  const closeModal = () => {
    setShowModal(false);
    setSelectedAppointment(null);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSaving(true);
      if (modalMode === "add") {
        await appointmentsApi.create(formData);
      } else if (modalMode === "edit" && selectedAppointment) {
        await appointmentsApi.update(selectedAppointment.id, formData);
      }
      closeModal();
      fetchAppointments();
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "Failed to save appointment"
      );
    } finally {
      setSaving(false);
    }
  };

  const confirmDelete = (apt: Appointment) => {
    setAppointmentToDelete(apt);
    setShowDeleteConfirm(true);
  };

  const handleDelete = async () => {
    if (appointmentToDelete) {
      try {
        setSaving(true);
        await appointmentsApi.delete(appointmentToDelete.id);
        setShowDeleteConfirm(false);
        setAppointmentToDelete(null);
        fetchAppointments();
      } catch (err) {
        setError(
          err instanceof Error ? err.message : "Failed to delete appointment"
        );
      } finally {
        setSaving(false);
      }
    }
  };

  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString("en-US", {
      weekday: "short",
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  };

  const formatTime = (timeStr: string) => {
    if (!timeStr) return "";
    const [hours, minutes] = timeStr.split(":");
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? "PM" : "AM";
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
  };

  if (loading && appointments.length === 0) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="w-8 h-8 animate-spin text-accent" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Error Banner */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3">
          <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0" />
          <p className="text-red-700 flex-1">{error}</p>
          <button
            onClick={() => setError(null)}
            className="text-red-500 hover:text-red-700"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      )}

      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-text-primary">Appointments</h1>
          <p className="text-text-muted mt-1">
            {appointments.length} appointments found
          </p>
        </div>
        <button
          onClick={openAddModal}
          className="flex items-center gap-2 px-4 py-2.5 bg-accent text-white rounded-xl font-medium shadow-lg shadow-accent/20 hover:bg-accent/90 transition-all"
        >
          <Plus className="w-5 h-5" />
          Schedule Appointment
        </button>
      </div>

      {/* Search and Filter */}
      <div className="bg-white rounded-2xl shadow-card p-4">
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
            <input
              type="text"
              placeholder="Search by patient name..."
              value={searchQuery}
              onChange={(e) => {
                setSearchQuery(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full pl-10 pr-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            />
          </div>
          <select
            value={filterStatus}
            onChange={(e) => {
              setFilterStatus(e.target.value);
              setCurrentPage(1);
            }}
            className="px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl text-text-primary focus:outline-none focus:ring-2 focus:ring-accent"
          >
            <option value="all">All Status</option>
            <option value="Scheduled">Scheduled</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
            <option value="No-show">No-show</option>
          </select>
          <button
            onClick={fetchAppointments}
            disabled={loading}
            className="p-2.5 bg-surface-secondary border border-border-light rounded-xl text-text-muted hover:text-accent hover:border-accent transition-colors disabled:opacity-50"
          >
            <RefreshCw className={`w-5 h-5 ${loading ? "animate-spin" : ""}`} />
          </button>
        </div>
      </div>

      {/* Appointments List */}
      {loading ? (
        <div className="flex items-center justify-center py-12">
          <Loader2 className="w-8 h-8 animate-spin text-accent" />
        </div>
      ) : appointments.length === 0 ? (
        <div className="text-center py-12 bg-white rounded-2xl shadow-card">
          <Calendar className="w-12 h-12 text-text-muted mx-auto mb-3 opacity-50" />
          <p className="text-text-muted">No appointments found</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {appointments.map((apt) => (
            <div
              key={apt.id}
              className="bg-white rounded-2xl shadow-card p-5 hover:shadow-card-hover transition-shadow"
            >
              <div className="flex items-start justify-between mb-3">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center text-accent font-semibold">
                    {apt.patient_name
                      .split(" ")
                      .map((n) => n[0])
                      .join("")}
                  </div>
                  <div>
                    <p className="font-medium text-text-primary">
                      {apt.patient_name}
                    </p>
                    <p className="text-xs text-text-muted">
                      {apt.patient_code}
                    </p>
                  </div>
                </div>
                <span
                  className={`px-2 py-1 rounded-lg text-xs font-medium ${
                    apt.status === "Scheduled"
                      ? "bg-blue-50 text-blue-600"
                      : apt.status === "Completed"
                      ? "bg-green-50 text-green-600"
                      : apt.status === "Cancelled"
                      ? "bg-gray-100 text-gray-600"
                      : "bg-red-50 text-red-600"
                  }`}
                >
                  {apt.status}
                </span>
              </div>

              <div className="space-y-2 mb-4">
                <div className="flex items-center gap-2 text-sm text-text-secondary">
                  <Calendar className="w-4 h-4" />
                  <span>{formatDate(apt.date)}</span>
                </div>
                <div className="flex items-center gap-2 text-sm text-text-secondary">
                  <Clock className="w-4 h-4" />
                  <span>{formatTime(apt.time)}</span>
                </div>
              </div>

              <div className="flex items-center justify-between pt-3 border-t border-border-light">
                <span className="px-2 py-1 bg-surface-secondary text-text-secondary text-xs rounded-lg">
                  {apt.type}
                </span>
                <div className="flex items-center gap-1">
                  <button
                    onClick={() => openEditModal(apt)}
                    className="p-2 text-text-muted hover:text-accent hover:bg-accent/10 rounded-lg transition-colors"
                    title="Edit"
                  >
                    <Edit2 className="w-4 h-4" />
                  </button>
                  <button
                    onClick={() => confirmDelete(apt)}
                    className="p-2 text-text-muted hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                    title="Delete"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex items-center justify-center gap-2">
          <button
            onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
            disabled={currentPage === 1}
            className="p-2 rounded-lg border border-border-light hover:bg-surface-secondary disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <ChevronLeft className="w-4 h-4" />
          </button>
          {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
            <button
              key={page}
              onClick={() => setCurrentPage(page)}
              className={`w-8 h-8 rounded-lg text-sm font-medium transition-colors ${
                currentPage === page
                  ? "bg-accent text-white"
                  : "hover:bg-surface-secondary text-text-secondary"
              }`}
            >
              {page}
            </button>
          ))}
          <button
            onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
            disabled={currentPage === totalPages}
            className="p-2 rounded-lg border border-border-light hover:bg-surface-secondary disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <ChevronRight className="w-4 h-4" />
          </button>
        </div>
      )}

      {/* Add/Edit Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md">
            <div className="flex items-center justify-between p-6 border-b border-border-light">
              <h2 className="text-xl font-bold text-text-primary">
                {modalMode === "add"
                  ? "Schedule Appointment"
                  : "Edit Appointment"}
              </h2>
              <button
                onClick={closeModal}
                className="p-2 text-text-muted hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-text-secondary mb-1.5">
                  Patient *
                </label>
                <select
                  required
                  value={formData.patient_id}
                  onChange={(e) => {
                    setFormData({
                      ...formData,
                      patient_id: parseInt(e.target.value),
                    });
                  }}
                  className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                >
                  <option value={0}>Select a patient</option>
                  {patients.map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.name}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-text-secondary mb-1.5">
                  Date &amp; Time *
                </label>
                <input
                  type="datetime-local"
                  required
                  value={
                    formData.scheduled_at
                      ? formData.scheduled_at.slice(0, 16)
                      : ""
                  }
                  onChange={(e) =>
                    setFormData({ ...formData, scheduled_at: e.target.value })
                  }
                  className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-text-secondary mb-1.5">
                    Duration (minutes)
                  </label>
                  <input
                    type="number"
                    value={formData.duration_minutes}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        duration_minutes: parseInt(e.target.value),
                      })
                    }
                    className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-text-secondary mb-1.5">
                    Type *
                  </label>
                  <select
                    required
                    value={formData.type}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        type: e.target.value as AppointmentInput["type"],
                      })
                    }
                    className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                  >
                    <option value="Check-up">Check-up</option>
                    <option value="Follow-up">Follow-up</option>
                    <option value="Lab Review">Lab Review</option>
                    <option value="Consultation">Consultation</option>
                    <option value="New Patient">New Patient</option>
                  </select>
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-text-secondary mb-1.5">
                  Status
                </label>
                <select
                  value={formData.status}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      status: e.target.value as AppointmentInput["status"],
                    })
                  }
                  className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                >
                  <option value="Scheduled">Scheduled</option>
                  <option value="Completed">Completed</option>
                  <option value="Cancelled">Cancelled</option>
                  <option value="No-show">No-show</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-text-secondary mb-1.5">
                  Notes
                </label>
                <textarea
                  rows={2}
                  value={formData.notes || ""}
                  onChange={(e) =>
                    setFormData({ ...formData, notes: e.target.value })
                  }
                  className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent resize-none"
                />
              </div>

              <div className="flex gap-3 pt-4 border-t border-border-light">
                <button
                  type="submit"
                  disabled={saving}
                  className="flex-1 py-2.5 bg-accent text-white rounded-xl font-medium hover:bg-accent/90 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  {saving && <Loader2 className="w-4 h-4 animate-spin" />}
                  {modalMode === "add" ? "Schedule" : "Save Changes"}
                </button>
                <button
                  type="button"
                  onClick={closeModal}
                  className="flex-1 py-2.5 bg-surface-secondary text-text-secondary rounded-xl font-medium hover:bg-border-light transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Delete Confirmation Modal */}
      {showDeleteConfirm && appointmentToDelete && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h3 className="text-lg font-bold text-text-primary mb-2">
              Cancel Appointment
            </h3>
            <p className="text-text-secondary mb-6">
              Are you sure you want to cancel the appointment for{" "}
              <span className="font-semibold">
                {appointmentToDelete.patient_name}
              </span>{" "}
              on {formatDate(appointmentToDelete.date)}?
            </p>
            <div className="flex gap-3">
              <button
                onClick={handleDelete}
                disabled={saving}
                className="flex-1 py-2.5 bg-red-500 text-white rounded-xl font-medium hover:bg-red-600 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
              >
                {saving && <Loader2 className="w-4 h-4 animate-spin" />}
                Delete
              </button>
              <button
                onClick={() => {
                  setShowDeleteConfirm(false);
                  setAppointmentToDelete(null);
                }}
                className="flex-1 py-2.5 bg-surface-secondary text-text-secondary rounded-xl font-medium hover:bg-border-light transition-colors"
              >
                Keep
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
