/**
 * DiabetaCare - API Hooks
 *
 * React hooks for data fetching with loading and error states.
 */

"use client";

import { useState, useEffect, useCallback } from "react";
import {
  patientsApi,
  appointmentsApi,
  medicationsApi,
  labResultsApi,
  dashboardApi,
  authApi,
  type Patient,
  type PatientFilters,
  type Appointment,
  type AppointmentFilters,
  type Medication,
  type MedicationFilters,
  type LabResult,
  type LabResultFilters,
  type DashboardSummary,
  type PaginatedResponse,
  type ApiError,
  type User,
  isAuthenticated,
  removeToken,
} from "./api";

// =============================================================================
// GENERIC HOOKS
// =============================================================================

interface UseQueryResult<T> {
  data: T | null;
  loading: boolean;
  error: ApiError | null;
  refetch: () => Promise<void>;
}

interface UseMutationResult<TData, TInput> {
  mutate: (input: TInput) => Promise<TData>;
  loading: boolean;
  error: ApiError | null;
  reset: () => void;
}

function useQuery<T>(
  fetchFn: () => Promise<T>,
  dependencies: unknown[] = []
): UseQueryResult<T> {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<ApiError | null>(null);

  const fetch = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await fetchFn();
      setData(result);
    } catch (err) {
      setError(err as ApiError);
    } finally {
      setLoading(false);
    }
  }, dependencies);

  useEffect(() => {
    fetch();
  }, [fetch]);

  return { data, loading, error, refetch: fetch };
}

function useMutation<TData, TInput>(
  mutationFn: (input: TInput) => Promise<TData>
): UseMutationResult<TData, TInput> {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);

  const mutate = useCallback(
    async (input: TInput): Promise<TData> => {
      setLoading(true);
      setError(null);
      try {
        const result = await mutationFn(input);
        return result;
      } catch (err) {
        setError(err as ApiError);
        throw err;
      } finally {
        setLoading(false);
      }
    },
    [mutationFn]
  );

  const reset = useCallback(() => {
    setError(null);
    setLoading(false);
  }, []);

  return { mutate, loading, error, reset };
}

// =============================================================================
// AUTH HOOKS
// =============================================================================

export function useAuth() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [authenticated, setAuthenticated] = useState(false);

  useEffect(() => {
    const checkAuth = async () => {
      if (!isAuthenticated()) {
        setLoading(false);
        setAuthenticated(false);
        return;
      }

      try {
        const userData = await authApi.me();
        setUser(userData);
        setAuthenticated(true);
      } catch {
        removeToken();
        setAuthenticated(false);
      } finally {
        setLoading(false);
      }
    };

    checkAuth();
  }, []);

  const logout = useCallback(async () => {
    await authApi.logout();
    setUser(null);
    setAuthenticated(false);
  }, []);

  return { user, loading, authenticated, logout };
}

// =============================================================================
// PATIENTS HOOKS
// =============================================================================

export function usePatients(filters: PatientFilters = {}) {
  const stringifiedFilters = JSON.stringify(filters);

  return useQuery<PaginatedResponse<Patient>>(
    () => patientsApi.list(filters),
    [stringifiedFilters]
  );
}

export function usePatient(id: number | null) {
  return useQuery<Patient | null>(async () => {
    if (!id) return null;
    return patientsApi.get(id);
  }, [id]);
}

export function useCreatePatient() {
  return useMutation(patientsApi.create);
}

export function useUpdatePatient() {
  return useMutation(
    ({
      id,
      data,
    }: {
      id: number;
      data: Parameters<typeof patientsApi.update>[1];
    }) => patientsApi.update(id, data)
  );
}

export function useDeletePatient() {
  return useMutation(patientsApi.delete);
}

// =============================================================================
// APPOINTMENTS HOOKS
// =============================================================================

export function useAppointments(filters: AppointmentFilters = {}) {
  const stringifiedFilters = JSON.stringify(filters);

  return useQuery<PaginatedResponse<Appointment>>(
    () => appointmentsApi.list(filters),
    [stringifiedFilters]
  );
}

export function useAppointment(id: number | null) {
  return useQuery<Appointment | null>(async () => {
    if (!id) return null;
    return appointmentsApi.get(id);
  }, [id]);
}

export function useCreateAppointment() {
  return useMutation(appointmentsApi.create);
}

export function useUpdateAppointment() {
  return useMutation(
    ({
      id,
      data,
    }: {
      id: number;
      data: Parameters<typeof appointmentsApi.update>[1];
    }) => appointmentsApi.update(id, data)
  );
}

export function useDeleteAppointment() {
  return useMutation(appointmentsApi.delete);
}

// =============================================================================
// MEDICATIONS HOOKS
// =============================================================================

export function useMedications(filters: MedicationFilters = {}) {
  const stringifiedFilters = JSON.stringify(filters);

  return useQuery<PaginatedResponse<Medication>>(
    () => medicationsApi.list(filters),
    [stringifiedFilters]
  );
}

export function useMedication(id: number | null) {
  return useQuery<Medication | null>(async () => {
    if (!id) return null;
    return medicationsApi.get(id);
  }, [id]);
}

export function useCreateMedication() {
  return useMutation(medicationsApi.create);
}

export function useUpdateMedication() {
  return useMutation(
    ({
      id,
      data,
    }: {
      id: number;
      data: Parameters<typeof medicationsApi.update>[1];
    }) => medicationsApi.update(id, data)
  );
}

export function useDeleteMedication() {
  return useMutation(medicationsApi.delete);
}

// =============================================================================
// LAB RESULTS HOOKS
// =============================================================================

export function useLabResults(filters: LabResultFilters = {}) {
  const stringifiedFilters = JSON.stringify(filters);

  return useQuery<PaginatedResponse<LabResult>>(
    () => labResultsApi.list(filters),
    [stringifiedFilters]
  );
}

export function useLabResult(id: number | null) {
  return useQuery<LabResult | null>(async () => {
    if (!id) return null;
    return labResultsApi.get(id);
  }, [id]);
}

export function useTestTypes() {
  return useQuery(labResultsApi.getTestTypes, []);
}

export function useCreateLabResult() {
  return useMutation(labResultsApi.create);
}

export function useUpdateLabResult() {
  return useMutation(
    ({
      id,
      data,
    }: {
      id: number;
      data: Parameters<typeof labResultsApi.update>[1];
    }) => labResultsApi.update(id, data)
  );
}

export function useDeleteLabResult() {
  return useMutation(labResultsApi.delete);
}

// =============================================================================
// DASHBOARD HOOKS
// =============================================================================

export function useDashboardSummary() {
  return useQuery<DashboardSummary>(dashboardApi.getSummary, []);
}

export function useUpcomingAppointments() {
  return useQuery(() => dashboardApi.getUpcomingAppointments(), []);
}

export function useRecentPatients() {
  return useQuery(() => dashboardApi.getRecentPatients(), []);
}

export function useCriticalAlerts() {
  return useQuery(() => dashboardApi.getCriticalAlerts(), []);
}

export function useHbA1cTrends(months: number = 6) {
  return useQuery(() => dashboardApi.getHbA1cTrends(months), [months]);
}

// =============================================================================
// PATIENT SELECT HOOK (for dropdowns)
// =============================================================================

export function usePatientOptions() {
  const { data, loading, error } = usePatients({
    page_size: 1000,
    status: "Active",
  });

  const options =
    data?.items.map((p) => ({
      value: p.id,
      label: `${p.patient_code} - ${p.first_name} ${p.last_name}`,
    })) || [];

  return { options, loading, error };
}
