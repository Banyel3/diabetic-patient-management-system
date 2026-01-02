"use client";

import { useState, useEffect, useCallback } from "react";
import {
  Search,
  Plus,
  Edit2,
  Trash2,
  Eye,
  X,
  ChevronLeft,
  ChevronRight,
  Filter,
  Loader2,
  AlertCircle,
  RefreshCw,
} from "lucide-react";
import {
  patientsApi,
  type Patient,
  type PatientInput,
  type ApiError,
} from "@/lib/api";

// Initial form state for new patients
const emptyPatient: PatientInput = {
  first_name: "",
  last_name: "",
  date_of_birth: "",
  gender: "Male",
  phone: "",
  email: "",
  address: "",
  diabetes_type: "Type 2",
  diagnosis_date: "",
  status: "Active",
  notes: "",
};

export default function PatientsPage() {
  // API state
  const [patients, setPatients] = useState<Patient[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [pagination, setPagination] = useState({
    current_page: 1,
    page_size: 10,
    total_items: 0,
    total_pages: 0,
  });

  // UI state
  const [searchQuery, setSearchQuery] = useState("");
  const [filterType, setFilterType] = useState<string>("all");
  const [currentPage, setCurrentPage] = useState(1);
  const [showModal, setShowModal] = useState(false);
  const [modalMode, setModalMode] = useState<"add" | "edit" | "view">("add");
  const [selectedPatient, setSelectedPatient] = useState<Patient | null>(null);
  const [formData, setFormData] = useState<PatientInput>(emptyPatient);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [patientToDelete, setPatientToDelete] = useState<Patient | null>(null);

  const itemsPerPage = 10;

  // Fetch patients from API
  const fetchPatients = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await patientsApi.list({
        page: currentPage,
        page_size: itemsPerPage,
        search: searchQuery || undefined,
        diabetes_type: filterType !== "all" ? filterType : undefined,
      });
      setPatients(response.items);
      setPagination(response.pagination);
    } catch (err) {
      const apiError = err as ApiError;
      setError(apiError.message || "Failed to load patients");
    } finally {
      setLoading(false);
    }
  }, [currentPage, searchQuery, filterType]);

  // Fetch on mount and when filters change
  useEffect(() => {
    fetchPatients();
  }, [fetchPatients]);

  // Debounced search
  useEffect(() => {
    const timer = setTimeout(() => {
      setCurrentPage(1);
      fetchPatients();
    }, 300);
    return () => clearTimeout(timer);
  }, [searchQuery]);

  // Handlers
  const openAddModal = () => {
    setFormData(emptyPatient);
    setModalMode("add");
    setShowModal(true);
  };

  const openEditModal = (patient: Patient) => {
    setSelectedPatient(patient);
    setFormData({
      first_name: patient.first_name,
      last_name: patient.last_name,
      date_of_birth: patient.date_of_birth,
      gender: patient.gender,
      phone: patient.phone,
      email: patient.email,
      address: patient.address,
      diabetes_type: patient.diabetes_type,
      diagnosis_date: patient.diagnosis_date,
      status: patient.status,
      notes: patient.notes,
    });
    setModalMode("edit");
    setShowModal(true);
  };

  const openViewModal = (patient: Patient) => {
    setSelectedPatient(patient);
    setModalMode("view");
    setShowModal(true);
  };

  const closeModal = () => {
    setShowModal(false);
    setSelectedPatient(null);
    setFormData(emptyPatient);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);

    try {
      if (modalMode === "add") {
        await patientsApi.create(formData);
      } else if (modalMode === "edit" && selectedPatient) {
        await patientsApi.update(selectedPatient.id, formData);
      }
      closeModal();
      fetchPatients();
    } catch (err) {
      const apiError = err as ApiError;
      setError(apiError.message || "Failed to save patient");
    } finally {
      setSaving(false);
    }
  };

  const confirmDelete = (patient: Patient) => {
    setPatientToDelete(patient);
    setShowDeleteConfirm(true);
  };

  const handleDelete = async () => {
    if (patientToDelete) {
      setSaving(true);
      try {
        await patientsApi.delete(patientToDelete.id);
        setShowDeleteConfirm(false);
        setPatientToDelete(null);
        fetchPatients();
      } catch (err) {
        const apiError = err as ApiError;
        setError(apiError.message || "Failed to delete patient");
      } finally {
        setSaving(false);
      }
    }
  };

  const calculateAge = (dob: string) => {
    const today = new Date();
    const birthDate = new Date(dob);
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    return age;
  };

  return (
    <div className="space-y-6">
      {/* Error Banner */}
      {error && (
        <div className="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl flex items-center justify-between">
          <div className="flex items-center gap-2">
            <AlertCircle className="w-5 h-5" />
            <span>{error}</span>
          </div>
          <button
            onClick={() => setError(null)}
            className="text-red-400 hover:text-red-600"
          >
            <X className="w-5 h-5" />
          </button>
        </div>
      )}

      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-text-primary">Patients</h1>
          <p className="text-text-muted mt-1">
            {loading
              ? "Loading..."
              : `${pagination.total_items} patients found`}
          </p>
        </div>
        <div className="flex items-center gap-3">
          <button
            onClick={fetchPatients}
            disabled={loading}
            className="p-2.5 text-text-muted hover:text-text-primary border border-border-light rounded-xl hover:bg-surface-secondary transition-all disabled:opacity-50"
          >
            <RefreshCw className={`w-5 h-5 ${loading ? "animate-spin" : ""}`} />
          </button>
          <button
            onClick={openAddModal}
            className="flex items-center gap-2 px-4 py-2.5 bg-accent text-white rounded-xl font-medium shadow-lg shadow-accent/20 hover:bg-accent/90 transition-all"
          >
            <Plus className="w-5 h-5" />
            Add Patient
          </button>
        </div>
      </div>

      {/* Search and Filter */}
      <div className="bg-white rounded-2xl shadow-card p-4">
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
            <input
              type="text"
              placeholder="Search by name, ID, or phone..."
              value={searchQuery}
              onChange={(e) => {
                setSearchQuery(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full pl-10 pr-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            />
          </div>
          <div className="flex items-center gap-2">
            <Filter className="w-5 h-5 text-text-muted" />
            <select
              value={filterType}
              onChange={(e) => {
                setFilterType(e.target.value);
                setCurrentPage(1);
              }}
              className="px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl text-text-primary focus:outline-none focus:ring-2 focus:ring-accent"
            >
              <option value="all">All Types</option>
              <option value="Type 1">Type 1</option>
              <option value="Type 2">Type 2</option>
              <option value="Gestational">Gestational</option>
              <option value="Pre-diabetic">Pre-diabetic</option>
            </select>
          </div>
        </div>
      </div>

      {/* Patients Table */}
      <div className="bg-white rounded-2xl shadow-card overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-8 h-8 animate-spin text-accent" />
          </div>
        ) : patients.length === 0 ? (
          <div className="text-center py-12">
            <p className="text-text-muted">No patients found</p>
            <button
              onClick={openAddModal}
              className="mt-4 text-accent hover:underline"
            >
              Add your first patient
            </button>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-surface-secondary">
                <tr className="text-left text-xs font-medium text-text-muted uppercase tracking-wider">
                  <th className="px-6 py-4">Patient</th>
                  <th className="px-6 py-4">Type</th>
                  <th className="px-6 py-4">Age/Gender</th>
                  <th className="px-6 py-4">Phone</th>
                  <th className="px-6 py-4">Last HbA1c</th>
                  <th className="px-6 py-4">Status</th>
                  <th className="px-6 py-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border-light">
                {patients.map((patient) => (
                  <tr
                    key={patient.id}
                    className="hover:bg-surface-secondary/50 transition-colors"
                  >
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center text-accent font-semibold">
                          {patient.first_name[0]}
                          {patient.last_name[0]}
                        </div>
                        <div>
                          <p className="font-medium text-text-primary">
                            {patient.first_name} {patient.last_name}
                          </p>
                          <p className="text-xs text-text-muted">
                            {patient.patient_code}
                          </p>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <span
                        className={`px-2.5 py-1 rounded-lg text-xs font-medium ${
                          patient.diabetes_type === "Type 1"
                            ? "bg-blue-50 text-blue-600"
                            : patient.diabetes_type === "Type 2"
                            ? "bg-purple-50 text-purple-600"
                            : patient.diabetes_type === "Gestational"
                            ? "bg-pink-50 text-pink-600"
                            : "bg-amber-50 text-amber-600"
                        }`}
                      >
                        {patient.diabetes_type}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-sm text-text-secondary">
                      {patient.age} yrs / {patient.gender}
                    </td>
                    <td className="px-6 py-4 text-sm text-text-secondary">
                      {patient.phone}
                    </td>
                    <td className="px-6 py-4">
                      {patient.last_hba1c ? (
                        <span
                          className={`font-semibold ${
                            patient.last_hba1c > 7.5
                              ? "text-red-500"
                              : patient.last_hba1c > 6.5
                              ? "text-amber-500"
                              : "text-green-500"
                          }`}
                        >
                          {patient.last_hba1c}%
                        </span>
                      ) : (
                        <span className="text-text-muted">N/A</span>
                      )}
                    </td>
                    <td className="px-6 py-4">
                      <span
                        className={`px-2.5 py-1 rounded-lg text-xs font-medium ${
                          patient.status === "Active"
                            ? "bg-green-50 text-green-600"
                            : "bg-gray-100 text-gray-600"
                        }`}
                      >
                        {patient.status}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={() => openViewModal(patient)}
                          className="p-2 text-text-muted hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors"
                          title="View"
                        >
                          <Eye className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => openEditModal(patient)}
                          className="p-2 text-text-muted hover:text-accent hover:bg-accent/10 rounded-lg transition-colors"
                          title="Edit"
                        >
                          <Edit2 className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => confirmDelete(patient)}
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
          </div>
        )}

        {/* Pagination */}
        {pagination.total_pages > 1 && (
          <div className="flex items-center justify-between px-6 py-4 border-t border-border-light">
            <p className="text-sm text-text-muted">
              Showing {(currentPage - 1) * itemsPerPage + 1} to{" "}
              {Math.min(currentPage * itemsPerPage, pagination.total_items)} of{" "}
              {pagination.total_items}
            </p>
            <div className="flex items-center gap-2">
              <button
                onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                disabled={currentPage === 1}
                className="p-2 rounded-lg border border-border-light hover:bg-surface-secondary disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <ChevronLeft className="w-4 h-4" />
              </button>
              {Array.from(
                { length: pagination.total_pages },
                (_, i) => i + 1
              ).map((page) => (
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
                onClick={() =>
                  setCurrentPage((p) => Math.min(pagination.total_pages, p + 1))
                }
                disabled={currentPage === pagination.total_pages}
                className="p-2 rounded-lg border border-border-light hover:bg-surface-secondary disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Add/Edit Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-6 border-b border-border-light">
              <h2 className="text-xl font-bold text-text-primary">
                {modalMode === "add"
                  ? "Add New Patient"
                  : modalMode === "edit"
                  ? "Edit Patient"
                  : "Patient Details"}
              </h2>
              <button
                onClick={closeModal}
                className="p-2 text-text-muted hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {modalMode === "view" && selectedPatient ? (
              <div className="p-6 space-y-6">
                <div className="flex items-center gap-4">
                  <div className="w-16 h-16 rounded-full bg-accent/10 flex items-center justify-center text-accent font-bold text-xl">
                    {selectedPatient.first_name[0]}
                    {selectedPatient.last_name[0]}
                  </div>
                  <div>
                    <h3 className="text-xl font-bold text-text-primary">
                      {selectedPatient.first_name} {selectedPatient.last_name}
                    </h3>
                    <p className="text-text-muted">
                      {selectedPatient.patient_code}
                    </p>
                  </div>
                  <span
                    className={`ml-auto px-3 py-1.5 rounded-lg text-sm font-medium ${
                      selectedPatient.status === "Active"
                        ? "bg-green-50 text-green-600"
                        : "bg-gray-100 text-gray-600"
                    }`}
                  >
                    {selectedPatient.status}
                  </span>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div className="p-4 bg-surface-secondary rounded-xl">
                    <p className="text-xs text-text-muted mb-1">
                      Date of Birth
                    </p>
                    <p className="font-medium text-text-primary">
                      {new Date(
                        selectedPatient.date_of_birth
                      ).toLocaleDateString()}{" "}
                      ({selectedPatient.age} years)
                    </p>
                  </div>
                  <div className="p-4 bg-surface-secondary rounded-xl">
                    <p className="text-xs text-text-muted mb-1">Gender</p>
                    <p className="font-medium text-text-primary">
                      {selectedPatient.gender}
                    </p>
                  </div>
                  <div className="p-4 bg-surface-secondary rounded-xl">
                    <p className="text-xs text-text-muted mb-1">Phone</p>
                    <p className="font-medium text-text-primary">
                      {selectedPatient.phone}
                    </p>
                  </div>
                  <div className="p-4 bg-surface-secondary rounded-xl">
                    <p className="text-xs text-text-muted mb-1">Email</p>
                    <p className="font-medium text-text-primary">
                      {selectedPatient.email || "N/A"}
                    </p>
                  </div>
                  <div className="p-4 bg-surface-secondary rounded-xl col-span-2">
                    <p className="text-xs text-text-muted mb-1">Address</p>
                    <p className="font-medium text-text-primary">
                      {selectedPatient.address || "N/A"}
                    </p>
                  </div>
                  <div className="p-4 bg-surface-secondary rounded-xl">
                    <p className="text-xs text-text-muted mb-1">
                      Diabetes Type
                    </p>
                    <p className="font-medium text-text-primary">
                      {selectedPatient.diabetes_type}
                    </p>
                  </div>
                  <div className="p-4 bg-surface-secondary rounded-xl">
                    <p className="text-xs text-text-muted mb-1">
                      Diagnosis Date
                    </p>
                    <p className="font-medium text-text-primary">
                      {selectedPatient.diagnosis_date
                        ? new Date(
                            selectedPatient.diagnosis_date
                          ).toLocaleDateString()
                        : "N/A"}
                    </p>
                  </div>
                  <div className="p-4 bg-surface-secondary rounded-xl">
                    <p className="text-xs text-text-muted mb-1">Last HbA1c</p>
                    {selectedPatient.last_hba1c ? (
                      <p
                        className={`font-bold text-lg ${
                          selectedPatient.last_hba1c > 7.5
                            ? "text-red-500"
                            : selectedPatient.last_hba1c > 6.5
                            ? "text-amber-500"
                            : "text-green-500"
                        }`}
                      >
                        {selectedPatient.last_hba1c}%
                      </p>
                    ) : (
                      <p className="text-text-muted">N/A</p>
                    )}
                  </div>
                  <div className="p-4 bg-surface-secondary rounded-xl">
                    <p className="text-xs text-text-muted mb-1">Last Visit</p>
                    <p className="font-medium text-text-primary">
                      {selectedPatient.last_visit_date
                        ? new Date(
                            selectedPatient.last_visit_date
                          ).toLocaleDateString()
                        : "N/A"}
                    </p>
                  </div>
                </div>

                <div className="flex gap-3 pt-4 border-t border-border-light">
                  <button
                    onClick={() => openEditModal(selectedPatient)}
                    className="flex-1 py-2.5 bg-accent text-white rounded-xl font-medium hover:bg-accent/90 transition-colors"
                  >
                    Edit Patient
                  </button>
                  <button
                    onClick={closeModal}
                    className="flex-1 py-2.5 bg-surface-secondary text-text-secondary rounded-xl font-medium hover:bg-border-light transition-colors"
                  >
                    Close
                  </button>
                </div>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="p-6 space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-text-secondary mb-1.5">
                      First Name *
                    </label>
                    <input
                      type="text"
                      required
                      value={formData.first_name}
                      onChange={(e) =>
                        setFormData({ ...formData, first_name: e.target.value })
                      }
                      className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-text-secondary mb-1.5">
                      Last Name *
                    </label>
                    <input
                      type="text"
                      required
                      value={formData.last_name}
                      onChange={(e) =>
                        setFormData({ ...formData, last_name: e.target.value })
                      }
                      className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-text-secondary mb-1.5">
                      Date of Birth *
                    </label>
                    <input
                      type="date"
                      required
                      value={formData.date_of_birth}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          date_of_birth: e.target.value,
                        })
                      }
                      className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-text-secondary mb-1.5">
                      Gender *
                    </label>
                    <select
                      required
                      value={formData.gender}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          gender: e.target.value as PatientInput["gender"],
                        })
                      }
                      className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                    >
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-text-secondary mb-1.5">
                      Phone *
                    </label>
                    <input
                      type="tel"
                      required
                      value={formData.phone}
                      onChange={(e) =>
                        setFormData({ ...formData, phone: e.target.value })
                      }
                      className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-text-secondary mb-1.5">
                      Email
                    </label>
                    <input
                      type="email"
                      value={formData.email || ""}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          email: e.target.value || null,
                        })
                      }
                      className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                    />
                  </div>
                  <div className="col-span-2">
                    <label className="block text-sm font-medium text-text-secondary mb-1.5">
                      Address
                    </label>
                    <input
                      type="text"
                      value={formData.address || ""}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          address: e.target.value || null,
                        })
                      }
                      className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-text-secondary mb-1.5">
                      Diabetes Type *
                    </label>
                    <select
                      required
                      value={formData.diabetes_type}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          diabetes_type: e.target
                            .value as PatientInput["diabetes_type"],
                        })
                      }
                      className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                    >
                      <option value="Type 1">Type 1</option>
                      <option value="Type 2">Type 2</option>
                      <option value="Gestational">Gestational</option>
                      <option value="Pre-diabetes">Pre-diabetes</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-text-secondary mb-1.5">
                      Diagnosis Date
                    </label>
                    <input
                      type="date"
                      value={formData.diagnosis_date || ""}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          diagnosis_date: e.target.value || null,
                        })
                      }
                      className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                    />
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
                          status: e.target.value as PatientInput["status"],
                        })
                      }
                      className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                    >
                      <option value="Active">Active</option>
                      <option value="Inactive">Inactive</option>
                    </select>
                  </div>
                  <div className="col-span-2">
                    <label className="block text-sm font-medium text-text-secondary mb-1.5">
                      Notes
                    </label>
                    <textarea
                      rows={3}
                      value={formData.notes || ""}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          notes: e.target.value || null,
                        })
                      }
                      className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent resize-none"
                    />
                  </div>
                </div>

                <div className="flex gap-3 pt-4 border-t border-border-light">
                  <button
                    type="submit"
                    disabled={saving}
                    className="flex-1 py-2.5 bg-accent text-white rounded-xl font-medium hover:bg-accent/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                  >
                    {saving ? (
                      <>
                        <Loader2 className="w-4 h-4 animate-spin" />
                        Saving...
                      </>
                    ) : modalMode === "add" ? (
                      "Add Patient"
                    ) : (
                      "Save Changes"
                    )}
                  </button>
                  <button
                    type="button"
                    onClick={closeModal}
                    disabled={saving}
                    className="flex-1 py-2.5 bg-surface-secondary text-text-secondary rounded-xl font-medium hover:bg-border-light transition-colors disabled:opacity-50"
                  >
                    Cancel
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>
      )}

      {/* Delete Confirmation Modal */}
      {showDeleteConfirm && patientToDelete && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h3 className="text-lg font-bold text-text-primary mb-2">
              Delete Patient
            </h3>
            <p className="text-text-secondary mb-6">
              Are you sure you want to delete{" "}
              <span className="font-semibold">
                {patientToDelete.first_name} {patientToDelete.last_name}
              </span>
              ? This action cannot be undone.
            </p>
            <div className="flex gap-3">
              <button
                onClick={handleDelete}
                disabled={saving}
                className="flex-1 py-2.5 bg-red-500 text-white rounded-xl font-medium hover:bg-red-600 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
              >
                {saving ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    Deleting...
                  </>
                ) : (
                  "Delete"
                )}
              </button>
              <button
                onClick={() => {
                  setShowDeleteConfirm(false);
                  setPatientToDelete(null);
                }}
                disabled={saving}
                className="flex-1 py-2.5 bg-surface-secondary text-text-secondary rounded-xl font-medium hover:bg-border-light transition-colors disabled:opacity-50"
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
