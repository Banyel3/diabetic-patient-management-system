/**
 * DiabetaCare - API Client
 *
 * Type-safe API client for backend communication.
 * Handles authentication, error handling, and response parsing.
 */

import { getToken, setToken, removeToken, setUser, clearAuth } from "./auth";
import type { User } from "./auth";

// =============================================================================
// CONFIGURATION
// =============================================================================

const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_URL || "http://localhost:8080/api";

// =============================================================================
// TYPES
// =============================================================================

export interface ApiError {
  code: string;
  message: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  items: T[];
  pagination: {
    current_page: number;
    page_size: number;
    total_items: number;
    total_pages: number;
    has_more: boolean;
  };
}

export interface AuthResponse {
  message?: string;
  user: User;
  clinic?: {
    id: number;
    name: string;
  };
  token: string;
  expires_at: number | string;
}

// Re-export User from auth utilities
export type { User } from "./auth";

export interface Patient {
  id: number;
  patient_code: string;
  first_name: string;
  last_name: string;
  name?: string;
  date_of_birth: string;
  age: number;
  gender: "Male" | "Female" | "Other";
  phone: string;
  email: string | null;
  address: string | null;
  emergency_contact_name: string | null;
  emergency_contact_phone: string | null;
  diabetes_type: "Type 1" | "Type 2" | "Pre-diabetes" | "Gestational";
  diagnosis_date: string | null;
  status: "Active" | "Inactive" | "Deceased";
  last_visit_date: string | null;
  last_hba1c: number | null;
  notes: string | null;
  created_at: string;
}

export interface PatientInput {
  first_name: string;
  last_name: string;
  date_of_birth: string;
  gender: "Male" | "Female" | "Other";
  phone: string;
  email?: string | null;
  address?: string | null;
  emergency_contact_name?: string | null;
  emergency_contact_phone?: string | null;
  diabetes_type: "Type 1" | "Type 2" | "Pre-diabetes" | "Gestational";
  diagnosis_date?: string | null;
  status?: "Active" | "Inactive" | "Deceased";
  notes?: string | null;
}

export interface Appointment {
  id: number;
  patient_id: number;
  patient_code: string;
  patient_name: string;
  date: string;
  time: string;
  scheduled_at: string;
  duration_minutes: number;
  type:
    | "Check-up"
    | "Follow-up"
    | "Lab Review"
    | "Consultation"
    | "New Patient";
  status: "Scheduled" | "Completed" | "Cancelled" | "No-show";
  notes: string | null;
  created_at: string;
}

export interface AppointmentInput {
  patient_id: number;
  scheduled_at: string;
  duration_minutes?: number;
  type:
    | "Check-up"
    | "Follow-up"
    | "Lab Review"
    | "Consultation"
    | "New Patient";
  status?: "Scheduled" | "Completed" | "Cancelled" | "No-show";
  notes?: string | null;
}

export interface Medication {
  id: number;
  patient_id: number;
  patient_code: string;
  patient_name: string;
  name: string;
  generic_name: string | null;
  dosage: string;
  frequency: string;
  route:
    | "Oral"
    | "Subcutaneous"
    | "Intramuscular"
    | "Intravenous"
    | "Topical"
    | "Inhalation"
    | "Sublingual";
  start_date: string;
  end_date: string | null;
  prescribing_doctor: string | null;
  status: "active" | "discontinued" | "completed";
  notes: string | null;
  created_at: string;
}

export interface MedicationInput {
  patient_id: number;
  name: string;
  generic_name?: string | null;
  dosage: string;
  frequency: string;
  route?:
    | "Oral"
    | "Subcutaneous"
    | "Intramuscular"
    | "Intravenous"
    | "Topical"
    | "Inhalation"
    | "Sublingual";
  start_date?: string;
  end_date?: string | null;
  prescribing_doctor?: string | null;
  status?: "active" | "discontinued" | "completed";
  notes?: string | null;
}

export interface LabResult {
  id: number;
  patient_id: number;
  patient_code: string;
  patient_name: string;
  test_name: string;
  test_date: string;
  result_value: string;
  unit: string | null;
  reference_range: string | null;
  status: "Normal" | "Abnormal" | "Critical" | "Pending";
  notes: string | null;
  created_at: string;
}

export interface LabResultInput {
  patient_id: number;
  test_name: string;
  test_date: string;
  result_value: string;
  unit?: string | null;
  reference_range?: string | null;
  status?: "Normal" | "Abnormal" | "Critical" | "Pending";
  notes?: string | null;
}

export interface TestType {
  name: string;
  unit: string;
  reference_range: string;
  category: string;
}

export interface DashboardSummary {
  patients: {
    total: number;
    active: number;
    by_type: Record<string, number>;
  };
  appointments: {
    today: {
      total: number;
      scheduled: number;
      completed: number;
      cancelled: number;
      no_show: number;
    };
    this_week: number;
  };
  hba1c_control: {
    patients_tracked: number;
    average: number;
    distribution: {
      well_controlled: number;
      moderate: number;
      poor: number;
      very_poor: number;
    };
  };
  medications: {
    active_prescriptions: number;
    patients_on_medications: number;
  };
  lab_results: {
    last_30_days: number;
  };
  generated_at: string;
}

// =============================================================================
// API CLIENT
// =============================================================================

class ApiClient {
  private baseUrl: string;

  constructor(baseUrl: string) {
    this.baseUrl = baseUrl;
  }

  private async request<T>(
    method: string,
    endpoint: string,
    data?: unknown,
    options: RequestInit = {}
  ): Promise<T> {
    const url = `${this.baseUrl}${endpoint}`;
    const headers: Record<string, string> = {
      "Content-Type": "application/json",
      Accept: "application/json",
    };

    const token = getToken();
    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    }

    const config: RequestInit = {
      method,
      headers,
      ...options,
    };

    if (data && method !== "GET") {
      config.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(url, config);

      // Handle 204 No Content
      if (response.status === 204) {
        return {} as T;
      }

      const json = await response.json();

      if (!response.ok) {
        // Handle 401 Unauthorized
        if (response.status === 401) {
          removeToken();
          if (typeof window !== "undefined") {
            window.location.href = "/login";
          }
        }

        const error: ApiError = {
          code: json.code || "ERROR",
          message: json.message || "An error occurred",
          errors: json.errors,
        };
        throw error;
      }

      return json as T;
    } catch (error) {
      if ((error as ApiError).code) {
        throw error;
      }
      throw {
        code: "NETWORK_ERROR",
        message:
          "Unable to connect to the server. Please check your connection.",
      } as ApiErroauthentication errors
        if (response.status === 401) {
          clearAuth();
          if (typeof window !== "undefined" && window.location.pathname !== "/login") {
            window.location.href = "/login";
          }
        }

        // Parse error from backend
        const error: ApiError = {
          code: json.error?.code || json.code || "ERROR",
          message: json.error?.message || json.message || "An error occurred",
          errors: json.error?.details ||ies(params).forEach(([key, value]) => {
        if (value !== undefined && value !== "") {
          searchParams.append(key, String(value));
        }
      });
      const queryString = searchParams.toString();
      if (queryString) {
        url += `?${queryString}`;
      }
    }
    return this.request<T>("GET", url);
  }

  async post<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>("POST", endpoint, data);
  }

  async put<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>("PUT", endpoint, data);
  }

  async delete<T>(endpoint: string): Promise<T> {
    return this.request<T>("DELETE", endpoint);
  }
}

// Create singleton instance
export const api = new ApiClient(API_BASE_URL);

// =============================================================================
// AUTH API
// =============================================================================

export interface RegisterInput {
  clinic_name: string;
  clinic_email: string;
  clinic_phone?: string;
  registration_number?: string;
  license_number?: string;
  street_address?: string;
  city?: string;
  state_province?: string;
  zip_postal_code?: string;
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
  password: string;
  terms_accepted: boolean;
}

export const authApi = {
  async register(data: RegisterInput): Promise<AuthResponse> {
    const response = await api.post<AuthResponse>("/auth/register", data);
    setToken(response.token);
    setUser(response.user);
    return response;
  },

  async login(email: string, password: string): Promise<AuthResponse> {
    const response = await api.post<AuthResponse>("/auth/login", {
      email,
      password,
    });
    setToken(response.token);
    setUser(response.user);
    return response;
  },

  async logout(): Promise<void> {
    try {
      await api.post("/auth/logout");
    } finally {
      clearAuth();
    }
  },

  async me(): Promise<User> {
    return api.get<User>("/auth/me");
  },
};

// =============================================================================
// PATIENTS API
// =============================================================================

export interface PatientFilters {
  page?: number;
  page_size?: number;
  search?: string;
  diabetes_type?: string;
  status?: string;
  sort_by?: string;
  sort_dir?: "asc" | "desc";
}

export const patientsApi = {
  async list(
    filters: PatientFilters = {}
  ): Promise<PaginatedResponse<Patient>> {
    return api.get<PaginatedResponse<Patient>>(
      "/patients",
      filters as Record<string, string | number | boolean | undefined>
    );
  },

  async get(id: number): Promise<Patient> {
    return api.get<Patient>(`/patients/${id}`);
  },

  async create(data: PatientInput): Promise<Patient> {
    return api.post<Patient>("/patients", data);
  },

  async update(id: number, data: PatientInput): Promise<Patient> {
    return api.put<Patient>(`/patients/${id}`, data);
  },

  async delete(id: number): Promise<void> {
    return api.delete(`/patients/${id}`);
  },
};

// =============================================================================
// APPOINTMENTS API
// =============================================================================

export interface AppointmentFilters {
  page?: number;
  page_size?: number;
  search?: string;
  status?: string;
  date_from?: string;
  date_to?: string;
  patient_id?: number;
}

export const appointmentsApi = {
  async list(
    filters: AppointmentFilters = {}
  ): Promise<PaginatedResponse<Appointment>> {
    return api.get<PaginatedResponse<Appointment>>(
      "/appointments",
      filters as Record<string, string | number | boolean | undefined>
    );
  },

  async get(id: number): Promise<Appointment> {
    return api.get<Appointment>(`/appointments/${id}`);
  },

  async create(data: AppointmentInput): Promise<Appointment> {
    return api.post<Appointment>("/appointments", data);
  },

  async update(id: number, data: AppointmentInput): Promise<Appointment> {
    return api.put<Appointment>(`/appointments/${id}`, data);
  },

  async delete(id: number): Promise<void> {
    return api.delete(`/appointments/${id}`);
  },
};

// =============================================================================
// MEDICATIONS API
// =============================================================================

export interface MedicationFilters {
  page?: number;
  page_size?: number;
  search?: string;
  patient_id?: number;
  status?: string;
}

export const medicationsApi = {
  async list(
    filters: MedicationFilters = {}
  ): Promise<PaginatedResponse<Medication>> {
    return api.get<PaginatedResponse<Medication>>(
      "/medications",
      filters as Record<string, string | number | boolean | undefined>
    );
  },

  async get(id: number): Promise<Medication> {
    return api.get<Medication>(`/medications/${id}`);
  },

  async create(data: MedicationInput): Promise<Medication> {
    return api.post<Medication>("/medications", data);
  },

  async update(id: number, data: MedicationInput): Promise<Medication> {
    return api.put<Medication>(`/medications/${id}`, data);
  },

  async delete(id: number): Promise<void> {
    return api.delete(`/medications/${id}`);
  },
};

// =============================================================================
// LAB RESULTS API
// =============================================================================

export interface LabResultFilters {
  page?: number;
  page_size?: number;
  patient_id?: number;
  test_name?: string;
  date_from?: string;
  date_to?: string;
}

export const labResultsApi = {
  async list(
    filters: LabResultFilters = {}
  ): Promise<PaginatedResponse<LabResult>> {
    return api.get<PaginatedResponse<LabResult>>(
      "/lab-results",
      filters as Record<string, string | number | boolean | undefined>
    );
  },

  async get(id: number): Promise<LabResult> {
    return api.get<LabResult>(`/lab-results/${id}`);
  },

  async create(data: LabResultInput): Promise<LabResult> {
    return api.post<LabResult>("/lab-results", data);
  },

  async update(id: number, data: LabResultInput): Promise<LabResult> {
    return api.put<LabResult>(`/lab-results/${id}`, data);
  },

  async delete(id: number): Promise<void> {
    return api.delete(`/lab-results/${id}`);
  },

  async getTestTypes(): Promise<{
    test_types: TestType[];
    categories: string[];
  }> {
    return api.get("/lab-results/test-types");
  },
};

// =============================================================================
// DASHBOARD API
// =============================================================================

export interface UpcomingAppointment {
  id: number;
  patient_code: string;
  patient_name: string;
  date: string;
  time: string;
  type: string;
  duration_minutes: number;
}

export interface RecentPatient {
  id: number;
  patient_code: string;
  name: string;
  diabetes_type: string;
  status: string;
  last_hba1c: number | null;
}

export interface Alert {
  patient_id?: number;
  lab_result_id?: number;
  patient_code: string;
  patient_name: string;
  severity: "warning" | "critical";
  message: string;
  hba1c?: number;
  last_visit?: string;
  days_since_visit?: number | null;
  test_name?: string;
  result?: string;
  test_date?: string;
}

export interface HbA1cTrend {
  month: string;
  label: string;
  average_hba1c: number;
  test_count: number;
}

export const dashboardApi = {
  async getSummary(): Promise<DashboardSummary> {
    return api.get<DashboardSummary>("/dashboard/summary");
  },

  async getUpcomingAppointments(): Promise<{
    appointments: UpcomingAppointment[];
  }> {
    return api.get("/dashboard/upcoming-appointments");
  },

  async getRecentPatients(): Promise<{ patients: RecentPatient[] }> {
    return api.get("/dashboard/recent-patients");
  },

  async getCriticalAlerts(): Promise<{
    alerts: {
      high_hba1c: Alert[];
      no_recent_visit: Alert[];
      critical_labs: Alert[];
    };
    total_alerts: number;
  }> {
    return api.get("/dashboard/critical-alerts");
  },

  async getHbA1cTrends(
    months: number = 6
  ): Promise<{ trends: HbA1cTrend[]; period_months: number }> {
    return api.get("/dashboard/hba1c-trends", { months });
  },
};
