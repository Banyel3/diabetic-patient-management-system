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
  Pill,
  Loader2,
  AlertCircle,
  RefreshCw,
} from "lucide-react";
import {
  medicationsApi,
  patientsApi,
  Medication,
  MedicationInput,
  Patient,
} from "@/lib/api";

interface PatientOption {
  id: number;
  name: string;
}

// Common diabetes medications
const commonMedications = [
  "Metformin",
  "Insulin Glargine",
  "Insulin Lispro",
  "Glipizide",
  "Glyburide",
  "Sitagliptin",
  "Empagliflozin",
  "Liraglutide",
  "Pioglitazone",
  "Lisinopril",
  "Atorvastatin",
  "Aspirin",
];

export default function MedicationsPage() {
  const [medications, setMedications] = useState<Medication[]>([]);
  const [patients, setPatients] = useState<PatientOption[]>([]);
  const [searchQuery, setSearchQuery] = useState("");
  const [filterStatus, setFilterStatus] = useState<string>("all");
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [showModal, setShowModal] = useState(false);
  const [modalMode, setModalMode] = useState<"add" | "edit">("add");
  const [selectedMedication, setSelectedMedication] =
    useState<Medication | null>(null);
  const [formData, setFormData] = useState<MedicationInput>({
    patient_id: 0,
    name: "",
    dosage: "",
    frequency: "",
    route: "Oral",
    start_date: new Date().toISOString().split("T")[0],
    status: "active",
    notes: "",
  });
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [medicationToDelete, setMedicationToDelete] =
    useState<Medication | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const itemsPerPage = 6;

  const fetchMedications = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await medicationsApi.list({
        page: currentPage,
        page_size: itemsPerPage,
        search: searchQuery || undefined,
        status: filterStatus !== "all" ? filterStatus : undefined,
      });
      setMedications(response.items);
      setTotalPages(response.pagination.total_pages);
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "Failed to load medications"
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
    fetchMedications();
  }, [fetchMedications]);

  useEffect(() => {
    fetchPatients();
  }, [fetchPatients]);

  // Handlers
  const openAddModal = () => {
    setFormData({
      patient_id: 0,
      name: "",
      dosage: "",
      frequency: "",
      route: "Oral",
      start_date: new Date().toISOString().split("T")[0],
      status: "active",
      notes: "",
    });
    setModalMode("add");
    setShowModal(true);
  };

  const openEditModal = (med: Medication) => {
    setSelectedMedication(med);
    setFormData({
      patient_id: med.patient_id,
      name: med.name,
      generic_name: med.generic_name,
      dosage: med.dosage,
      frequency: med.frequency,
      route: med.route,
      start_date: med.start_date,
      end_date: med.end_date,
      prescribing_doctor: med.prescribing_doctor,
      status: med.status,
      notes: med.notes || "",
    });
    setModalMode("edit");
    setShowModal(true);
  };

  const closeModal = () => {
    setShowModal(false);
    setSelectedMedication(null);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSaving(true);
      if (modalMode === "add") {
        await medicationsApi.create(formData);
      } else if (modalMode === "edit" && selectedMedication) {
        await medicationsApi.update(selectedMedication.id, formData);
      }
      closeModal();
      fetchMedications();
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "Failed to save medication"
      );
    } finally {
      setSaving(false);
    }
  };

  const confirmDelete = (med: Medication) => {
    setMedicationToDelete(med);
    setShowDeleteConfirm(true);
  };

  const handleDelete = async () => {
    if (medicationToDelete) {
      try {
        setSaving(true);
        await medicationsApi.delete(medicationToDelete.id);
        setShowDeleteConfirm(false);
        setMedicationToDelete(null);
        fetchMedications();
      } catch (err) {
        setError(
          err instanceof Error ? err.message : "Failed to delete medication"
        );
      } finally {
        setSaving(false);
      }
    }
  };

  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  };

  if (loading && medications.length === 0) {
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
          <h1 className="text-2xl font-bold text-text-primary">Medications</h1>
          <p className="text-text-muted mt-1">
            {medications.length} prescriptions found
          </p>
        </div>
        <button
          onClick={openAddModal}
          className="flex items-center gap-2 px-4 py-2.5 bg-accent text-white rounded-xl font-medium shadow-lg shadow-accent/20 hover:bg-accent/90 transition-all"
        >
          <Plus className="w-5 h-5" />
          Add Medication
        </button>
      </div>

      {/* Search and Filter */}
      <div className="bg-white rounded-2xl shadow-card p-4">
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
            <input
              type="text"
              placeholder="Search by patient or medication..."
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
            <option value="active">Active</option>
            <option value="discontinued">Discontinued</option>
          </select>
          <button
            onClick={fetchMedications}
            disabled={loading}
            className="p-2.5 bg-surface-secondary border border-border-light rounded-xl text-text-muted hover:text-accent hover:border-accent transition-colors disabled:opacity-50"
          >
            <RefreshCw className={`w-5 h-5 ${loading ? "animate-spin" : ""}`} />
          </button>
        </div>
      </div>

      {/* Medications Table */}
      <div className="bg-white rounded-2xl shadow-card overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-8 h-8 animate-spin text-accent" />
          </div>
        ) : (
          <table className="w-full">
            <thead>
              <tr className="bg-surface-secondary border-b border-border-light">
                <th className="px-6 py-4 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Medication
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Patient
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Dosage
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Frequency
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-4 text-right text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border-light">
              {medications.map((med) => (
                <tr
                  key={med.id}
                  className="hover:bg-surface-secondary/50 transition-colors"
                >
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <div className="w-9 h-9 rounded-xl bg-accent/10 flex items-center justify-center text-accent">
                        <Pill className="w-5 h-5" />
                      </div>
                      <div>
                        <p className="font-medium text-text-primary">
                          {med.name}
                        </p>
                        <p className="text-xs text-text-muted">
                          Since {formatDate(med.start_date)}
                        </p>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <p className="text-sm text-text-primary">
                      {med.patient_name}
                    </p>
                    <p className="text-xs text-text-muted">
                      {med.patient_code}
                    </p>
                  </td>
                  <td className="px-6 py-4 text-sm text-text-secondary">
                    {med.dosage}
                  </td>
                  <td className="px-6 py-4 text-sm text-text-secondary">
                    {med.frequency}
                  </td>
                  <td className="px-6 py-4">
                    <span
                      className={`px-2.5 py-1 rounded-lg text-xs font-medium ${
                        med.status === "active"
                          ? "bg-green-50 text-green-600"
                          : "bg-gray-100 text-gray-600"
                      }`}
                    >
                      {med.status === "active" ? "Active" : "Discontinued"}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center justify-end gap-1">
                      <button
                        onClick={() => openEditModal(med)}
                        className="p-2 text-text-muted hover:text-accent hover:bg-accent/10 rounded-lg transition-colors"
                        title="Edit"
                      >
                        <Edit2 className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => confirmDelete(med)}
                        className="p-2 text-text-muted hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                        title="Delete"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}

        {!loading && medications.length === 0 && (
          <div className="text-center py-12">
            <Pill className="w-12 h-12 text-text-muted mx-auto mb-3 opacity-50" />
            <p className="text-text-muted">No medications found</p>
          </div>
        )}
      </div>

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
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-6 border-b border-border-light sticky top-0 bg-white">
              <h2 className="text-xl font-bold text-text-primary">
                {modalMode === "add" ? "Add Medication" : "Edit Medication"}
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
                  Medication Name *
                </label>
                <input
                  type="text"
                  required
                  list="medication-suggestions"
                  value={formData.name}
                  onChange={(e) =>
                    setFormData({ ...formData, name: e.target.value })
                  }
                  placeholder="e.g., Metformin"
                  className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                />
                <datalist id="medication-suggestions">
                  {commonMedications.map((med) => (
                    <option key={med} value={med} />
                  ))}
                </datalist>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-text-secondary mb-1.5">
                    Dosage *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.dosage}
                    onChange={(e) =>
                      setFormData({ ...formData, dosage: e.target.value })
                    }
                    placeholder="e.g., 500mg"
                    className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-text-secondary mb-1.5">
                    Start Date *
                  </label>
                  <input
                    type="date"
                    required
                    value={formData.start_date}
                    onChange={(e) =>
                      setFormData({ ...formData, start_date: e.target.value })
                    }
                    className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-text-secondary mb-1.5">
                  Frequency *
                </label>
                <select
                  required
                  value={formData.frequency}
                  onChange={(e) =>
                    setFormData({ ...formData, frequency: e.target.value })
                  }
                  className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                >
                  <option value="">Select frequency</option>
                  <option value="Once daily">Once daily</option>
                  <option value="Twice daily">Twice daily</option>
                  <option value="Three times daily">Three times daily</option>
                  <option value="Once daily at bedtime">
                    Once daily at bedtime
                  </option>
                  <option value="Once daily before breakfast">
                    Once daily before breakfast
                  </option>
                  <option value="As needed">As needed</option>
                  <option value="Weekly">Weekly</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-text-secondary mb-1.5">
                  Route
                </label>
                <select
                  value={formData.route}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      route: e.target.value as MedicationInput["route"],
                    })
                  }
                  className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                >
                  <option value="Oral">Oral</option>
                  <option value="Subcutaneous">Subcutaneous</option>
                  <option value="Intramuscular">Intramuscular</option>
                  <option value="Intravenous">Intravenous</option>
                  <option value="Topical">Topical</option>
                  <option value="Inhalation">Inhalation</option>
                  <option value="Sublingual">Sublingual</option>
                </select>
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
                      status: e.target.value as MedicationInput["status"],
                    })
                  }
                  className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                >
                  <option value="active">Active</option>
                  <option value="discontinued">Discontinued</option>
                  <option value="completed">Completed</option>
                </select>
              </div>
              {formData.status === "discontinued" && (
                <div>
                  <label className="block text-sm font-medium text-text-secondary mb-1.5">
                    End Date
                  </label>
                  <input
                    type="date"
                    value={formData.end_date || ""}
                    onChange={(e) =>
                      setFormData({ ...formData, end_date: e.target.value })
                    }
                    className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                  />
                </div>
              )}
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
                  placeholder="Special instructions..."
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
                  {modalMode === "add" ? "Add Medication" : "Save Changes"}
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
      {showDeleteConfirm && medicationToDelete && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h3 className="text-lg font-bold text-text-primary mb-2">
              Delete Medication
            </h3>
            <p className="text-text-secondary mb-6">
              Are you sure you want to delete{" "}
              <span className="font-semibold">{medicationToDelete.name}</span>{" "}
              for{" "}
              <span className="font-semibold">
                {medicationToDelete.patient_name}
              </span>
              ?
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
                  setMedicationToDelete(null);
                }}
                className="flex-1 py-2.5 bg-surface-secondary text-text-secondary rounded-xl font-medium hover:bg-border-light transition-colors"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
