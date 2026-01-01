export interface Patient {
  id: string;
  firstName: string;
  lastName: string;
  dateOfBirth: string;
  gender: "Male" | "Female" | "Other";
  phone: string;
  email: string;
  address: string;
  diabetesType: "Type 1" | "Type 2" | "Gestational" | "Pre-diabetic";
  diagnosisDate: string;
  lastHbA1c: string;
  lastVisit: string;
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
  id: string;
  patientId: string;
  patientName: string;
  testType: string;
  testDate: string;
  result: string;
  unit: string;
  referenceRange: string;
  status: "Normal" | "Abnormal" | "Critical";
  notes?: string;
}
