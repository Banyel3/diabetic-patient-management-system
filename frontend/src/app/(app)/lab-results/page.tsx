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
  FileText,
  TrendingUp,
  TrendingDown,
  Loader2,
  AlertCircle,
  RefreshCw,
} from "lucide-react";
import {
  labResultsApi,
  patientsApi,
  LabResult,
  LabResultInput,
  Patient,
  TestType,
} from "@/lib/api";

interface PatientOption {
  id: number;
  name: string;
}

// Default test types (fallback)
const defaultTestTypes: TestType[] = [
  {
    name: "HbA1c",
    unit: "%",
    reference_range: "4.0-5.6%",
    category: "Diabetes",
  },
  {
    name: "Fasting Glucose",
    unit: "mg/dL",
    reference_range: "70-99 mg/dL",
    category: "Diabetes",
  },
  {
    name: "Random Glucose",
    unit: "mg/dL",
    reference_range: "<140 mg/dL",
    category: "Diabetes",
  },
  {
    name: "Total Cholesterol",
    unit: "mg/dL",
    reference_range: "<200 mg/dL",
    category: "Lipids",
  },
  {
    name: "Creatinine",
    unit: "mg/dL",
    reference_range: "0.7-1.3 mg/dL",
    category: "Kidney",
  },
  {
    name: "eGFR",
    unit: "mL/min",
    reference_range: ">60 mL/min",
    category: "Kidney",
  },
  {
    name: "Urine Albumin",
    unit: "mg/L",
    reference_range: "<30 mg/L",
    category: "Kidney",
  },
];

export default function LabResultsPage() {
  const [labResults, setLabResults] = useState<LabResult[]>([]);
  const [patients, setPatients] = useState<PatientOption[]>([]);
  const [testTypes, setTestTypes] = useState<TestType[]>(defaultTestTypes);
  const [searchQuery, setSearchQuery] = useState("");
  const [filterStatus, setFilterStatus] = useState<string>("all");
  const [filterType, setFilterType] = useState<string>("all");
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [showModal, setShowModal] = useState(false);
  const [modalMode, setModalMode] = useState<"add" | "edit">("add");
  const [selectedResult, setSelectedResult] = useState<LabResult | null>(null);
  const [formData, setFormData] = useState<LabResultInput>({
    patient_id: 0,
    test_name: "HbA1c",
    test_date: new Date().toISOString().split("T")[0],
    result_value: "",
    unit: "%",
    reference_range: "4.0-5.6%",
    status: "Pending",
    notes: "",
  });
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [resultToDelete, setResultToDelete] = useState<LabResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const itemsPerPage = 6;

  const fetchLabResults = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await labResultsApi.list({
        page: currentPage,
        page_size: itemsPerPage,
        test_name: filterType !== "all" ? filterType : undefined,
      });
      setLabResults(response.items);
      setTotalPages(response.pagination.total_pages);
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "Failed to load lab results"
      );
    } finally {
      setLoading(false);
    }
  }, [currentPage, filterType]);

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

  const fetchTestTypes = useCallback(async () => {
    try {
      const response = await labResultsApi.getTestTypes();
      if (response.test_types && response.test_types.length > 0) {
        setTestTypes(response.test_types);
      }
    } catch (err) {
      console.error("Failed to load test types:", err);
    }
  }, []);

  useEffect(() => {
    fetchLabResults();
  }, [fetchLabResults]);

  useEffect(() => {
    fetchPatients();
    fetchTestTypes();
  }, [fetchPatients, fetchTestTypes]);

  // Filter by search locally (API doesn't support search on lab results)
  const filteredResults = labResults.filter((result) => {
    const matchesSearch = result.patient_name
      .toLowerCase()
      .includes(searchQuery.toLowerCase());
    const matchesStatus =
      filterStatus === "all" || result.status === filterStatus;
    return matchesSearch && matchesStatus;
  });

  // Handlers
  const openAddModal = () => {
    setFormData({
      patient_id: 0,
      test_name: "HbA1c",
      test_date: new Date().toISOString().split("T")[0],
      result_value: "",
      unit: "%",
      reference_range: "4.0-5.6%",
      status: "Pending",
      notes: "",
    });
    setModalMode("add");
    setShowModal(true);
  };

  const openEditModal = (result: LabResult) => {
    setSelectedResult(result);
    setFormData({
      patient_id: result.patient_id,
      test_name: result.test_name,
      test_date: result.test_date,
      result_value: result.result_value,
      unit: result.unit || "",
      reference_range: result.reference_range || "",
      status: result.status,
      notes: result.notes || "",
    });
    setModalMode("edit");
    setShowModal(true);
  };

  const closeModal = () => {
    setShowModal(false);
    setSelectedResult(null);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSaving(true);
      if (modalMode === "add") {
        await labResultsApi.create(formData);
      } else if (modalMode === "edit" && selectedResult) {
        await labResultsApi.update(selectedResult.id, formData);
      }
      closeModal();
      fetchLabResults();
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "Failed to save lab result"
      );
    } finally {
      setSaving(false);
    }
  };

  const confirmDelete = (result: LabResult) => {
    setResultToDelete(result);
    setShowDeleteConfirm(true);
  };

  const handleDelete = async () => {
    if (resultToDelete) {
      try {
        setSaving(true);
        await labResultsApi.delete(resultToDelete.id);
        setShowDeleteConfirm(false);
        setResultToDelete(null);
        fetchLabResults();
      } catch (err) {
        setError(
          err instanceof Error ? err.message : "Failed to delete lab result"
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

  const getValueStatus = (result: LabResult) => {
    // Simple logic to determine if value is high, low, or normal
    const value = parseFloat(result.result_value);
    if (isNaN(value)) return result.status === "Normal" ? "normal" : "high";

    if (result.test_name === "HbA1c") {
      if (value > 7) return "high";
      if (value > 5.6) return "elevated";
      return "normal";
    }
    if (result.test_name === "Fasting Glucose") {
      if (value > 126) return "high";
      if (value > 99) return "elevated";
      return "normal";
    }
    // Default logic based on status
    return result.status === "Normal"
      ? "normal"
      : result.status === "Critical"
      ? "high"
      : "elevated";
  };

  if (loading && labResults.length === 0) {
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
          <h1 className="text-2xl font-bold text-text-primary">Lab Results</h1>
          <p className="text-text-muted mt-1">
            {filteredResults.length} results found
          </p>
        </div>
        <button
          onClick={openAddModal}
          className="flex items-center gap-2 px-4 py-2.5 bg-accent text-white rounded-xl font-medium shadow-lg shadow-accent/20 hover:bg-accent/90 transition-all"
        >
          <Plus className="w-5 h-5" />
          Add Lab Result
        </button>
      </div>

      {/* Search and Filters */}
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
            value={filterType}
            onChange={(e) => {
              setFilterType(e.target.value);
              setCurrentPage(1);
            }}
            className="px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl text-text-primary focus:outline-none focus:ring-2 focus:ring-accent"
          >
            <option value="all">All Tests</option>
            {testTypes.map((t) => (
              <option key={t.name} value={t.name}>
                {t.name}
              </option>
            ))}
          </select>
          <select
            value={filterStatus}
            onChange={(e) => {
              setFilterStatus(e.target.value);
              setCurrentPage(1);
            }}
            className="px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl text-text-primary focus:outline-none focus:ring-2 focus:ring-accent"
          >
            <option value="all">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Normal">Normal</option>
            <option value="Abnormal">Abnormal</option>
            <option value="Critical">Critical</option>
          </select>
          <button
            onClick={fetchLabResults}
            disabled={loading}
            className="p-2.5 bg-surface-secondary border border-border-light rounded-xl text-text-muted hover:text-accent hover:border-accent transition-colors disabled:opacity-50"
          >
            <RefreshCw className={`w-5 h-5 ${loading ? "animate-spin" : ""}`} />
          </button>
        </div>
      </div>

      {/* Lab Results Table */}
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
                  Patient
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Test Type
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Result
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Reference
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Date
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
              {filteredResults.map((result) => {
                const valueStatus = getValueStatus(result);
                return (
                  <tr
                    key={result.id}
                    className="hover:bg-surface-secondary/50 transition-colors"
                  >
                    <td className="px-6 py-4">
                      <div>
                        <p className="font-medium text-text-primary">
                          {result.patient_name}
                        </p>
                        <p className="text-xs text-text-muted">
                          {result.patient_code}
                        </p>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-2">
                        <FileText className="w-4 h-4 text-accent" />
                        <span className="text-sm text-text-primary">
                          {result.test_name}
                        </span>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-2">
                        <span
                          className={`font-semibold ${
                            valueStatus === "high"
                              ? "text-red-600"
                              : valueStatus === "elevated"
                              ? "text-amber-600"
                              : "text-green-600"
                          }`}
                        >
                          {result.result_value} {result.unit}
                        </span>
                        {valueStatus === "high" && (
                          <TrendingUp className="w-4 h-4 text-red-500" />
                        )}
                        {valueStatus === "elevated" && (
                          <TrendingUp className="w-4 h-4 text-amber-500" />
                        )}
                        {valueStatus === "normal" && (
                          <TrendingDown className="w-4 h-4 text-green-500" />
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm text-text-secondary">
                      {result.reference_range}
                    </td>
                    <td className="px-6 py-4 text-sm text-text-secondary">
                      {formatDate(result.test_date)}
                    </td>
                    <td className="px-6 py-4">
                      <span
                        className={`px-2.5 py-1 rounded-lg text-xs font-medium ${
                          result.status === "Normal"
                            ? "bg-green-50 text-green-600"
                            : result.status === "Critical"
                            ? "bg-red-50 text-red-600"
                            : result.status === "Abnormal"
                            ? "bg-amber-50 text-amber-600"
                            : "bg-blue-50 text-blue-600"
                        }`}
                      >
                        {result.status}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center justify-end gap-1">
                        <button
                          onClick={() => openEditModal(result)}
                          className="p-2 text-text-muted hover:text-accent hover:bg-accent/10 rounded-lg transition-colors"
                          title="Edit"
                        >
                          <Edit2 className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => confirmDelete(result)}
                          className="p-2 text-text-muted hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                          title="Delete"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        )}

        {!loading && filteredResults.length === 0 && (
          <div className="text-center py-12">
            <FileText className="w-12 h-12 text-text-muted mx-auto mb-3 opacity-50" />
            <p className="text-text-muted">No lab results found</p>
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
                {modalMode === "add" ? "Add Lab Result" : "Edit Lab Result"}
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
                  Test Type *
                </label>
                <select
                  required
                  value={formData.test_name}
                  onChange={(e) => {
                    const test = testTypes.find(
                      (t) => t.name === e.target.value
                    );
                    setFormData({
                      ...formData,
                      test_name: e.target.value,
                      unit: test?.unit || "",
                      reference_range: test?.reference_range || "",
                    });
                  }}
                  className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                >
                  {testTypes.map((t) => (
                    <option key={t.name} value={t.name}>
                      {t.name}
                    </option>
                  ))}
                </select>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-text-secondary mb-1.5">
                    Value *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.result_value}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        result_value: e.target.value,
                      })
                    }
                    className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-text-secondary mb-1.5">
                    Unit
                  </label>
                  <input
                    type="text"
                    readOnly
                    value={formData.unit || ""}
                    className="w-full px-4 py-2.5 bg-gray-100 border border-border-light rounded-xl text-text-muted cursor-not-allowed"
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-text-secondary mb-1.5">
                  Reference Range
                </label>
                <input
                  type="text"
                  readOnly
                  value={formData.reference_range || ""}
                  className="w-full px-4 py-2.5 bg-gray-100 border border-border-light rounded-xl text-text-muted cursor-not-allowed"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-text-secondary mb-1.5">
                  Date *
                </label>
                <input
                  type="date"
                  required
                  value={formData.test_date}
                  onChange={(e) =>
                    setFormData({ ...formData, test_date: e.target.value })
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
                      status: e.target.value as LabResultInput["status"],
                    })
                  }
                  className="w-full px-4 py-2.5 bg-surface-secondary border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-accent"
                >
                  <option value="Pending">Pending</option>
                  <option value="Normal">Normal</option>
                  <option value="Abnormal">Abnormal</option>
                  <option value="Critical">Critical</option>
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
                  placeholder="Clinical notes..."
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
                  {modalMode === "add" ? "Add Result" : "Save Changes"}
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
      {showDeleteConfirm && resultToDelete && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h3 className="text-lg font-bold text-text-primary mb-2">
              Delete Lab Result
            </h3>
            <p className="text-text-secondary mb-6">
              Are you sure you want to delete the {resultToDelete.test_name}{" "}
              result for{" "}
              <span className="font-semibold">
                {resultToDelete.patient_name}
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
                  setResultToDelete(null);
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
