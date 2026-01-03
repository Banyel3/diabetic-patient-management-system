// Family history of diabetes type
export type FamilyHistoryDiabetes =
  | "none"
  | "first_degree"
  | "second_degree"
  | "unknown";

export interface Patient {
  id: string;
  firstName: string;
  lastName: string;
  dateOfBirth: string;
  gender: "Male" | "Female" | "Other";
  phone?: string;
  email?: string;
  address?: string;
  diabetesType: "Type 1" | "Type 2" | "Gestational" | "Pre-diabetic";
  diagnosisDate?: string;
  familyHistoryDiabetes?: FamilyHistoryDiabetes;
  familyHistoryNotes?: string;
  lastHbA1c?: string;
  lastVisit?: string;
  status: "Active" | "Inactive";
  notes?: string;
}

export interface Appointment {
  id: string;
  patientId: string;
  patientName: string;
  date: string;
  time: string;
  type:
    | "Check-up"
    | "Follow-up"
    | "Lab Review"
    | "Consultation"
    | "New Patient";
  status: "Scheduled" | "Completed" | "Cancelled" | "No-show";
  notes?: string;
}

export interface Medication {
  id: string;
  patientId: string;
  patientName: string;
  name: string;
  type: "Oral" | "Injectable" | "Insulin";
  dosage: string;
  frequency: string;
  startDate: string;
  endDate?: string;
  status: "Active" | "Discontinued" | "Completed";
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
  status: "Pending" | "Normal" | "Abnormal" | "Critical";
  notes: string | null;
  created_at: string;
}
