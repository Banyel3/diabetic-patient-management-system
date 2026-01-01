# DiabetaCare - REST API Documentation

## Base URL

```
http://localhost:8080/api
```

## Authentication

All endpoints except `/auth/login`, `/auth/register`, and `/health` require authentication.

Include the JWT token in the Authorization header:

```
Authorization: Bearer <token>
```

---

## Health Check

### GET /health

Check API status.

**Response:**

```json
{
  "status": "healthy",
  "version": "2.0.0",
  "timestamp": "2025-01-16T10:00:00Z"
}
```

---

## Authentication

### POST /auth/register

Register a new clinic and admin user.

**Request Body:**

```json
{
  "clinic_name": "My Diabetes Clinic",
  "email": "admin@clinic.com",
  "password": "SecurePass123!",
  "first_name": "John",
  "last_name": "Doe"
}
```

**Response (201 Created):**

```json
{
  "user": {
    "id": 1,
    "email": "admin@clinic.com",
    "first_name": "John",
    "last_name": "Doe",
    "role": "admin",
    "clinic": {
      "id": 1,
      "name": "My Diabetes Clinic"
    }
  },
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "expires_at": "2025-01-17T10:00:00Z"
}
```

### POST /auth/login

Authenticate and receive a token.

**Request Body:**

```json
{
  "email": "admin@clinic.com",
  "password": "SecurePass123!"
}
```

**Response (200 OK):**

```json
{
  "user": { ... },
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "expires_at": "2025-01-17T10:00:00Z"
}
```

### POST /auth/logout

Invalidate the current token.

**Response (204 No Content)**

### GET /auth/me

Get current user information.

**Response (200 OK):**

```json
{
  "id": 1,
  "email": "admin@clinic.com",
  "first_name": "John",
  "last_name": "Doe",
  "role": "admin",
  "clinic": {
    "id": 1,
    "name": "My Diabetes Clinic"
  }
}
```

---

## Patients

### GET /patients

List patients with pagination and filters.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| page | int | Page number (default: 1) |
| page_size | int | Items per page (default: 20, max: 100) |
| search | string | Search by name, patient code, or phone |
| diabetes_type | string | Filter by diabetes type |
| status | string | Filter by status (Active/Inactive) |
| sort_by | string | Sort field |
| sort_dir | string | Sort direction (asc/desc) |

**Response (200 OK):**

```json
{
  "items": [
    {
      "id": 1,
      "patient_code": "PAT-0001",
      "first_name": "Sarah",
      "last_name": "Connor",
      "date_of_birth": "1985-03-15",
      "age": 39,
      "gender": "Female",
      "phone": "(555) 123-4567",
      "email": "sarah@email.com",
      "address": "123 Main St",
      "diabetes_type": "Type 2",
      "diagnosis_date": "2020-06-10",
      "status": "Active",
      "last_visit_date": "2025-01-10",
      "last_hba1c": 6.8,
      "notes": null,
      "created_at": "2025-01-01T08:00:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "page_size": 20,
    "total_items": 150,
    "total_pages": 8,
    "has_more": true
  }
}
```

### GET /patients/{id}

Get a single patient.

### POST /patients

Create a new patient.

**Request Body:**

```json
{
  "first_name": "John",
  "last_name": "Smith",
  "date_of_birth": "1978-08-22",
  "gender": "Male",
  "phone": "(555) 234-5678",
  "email": "john@email.com",
  "address": "456 Oak Ave",
  "diabetes_type": "Type 1",
  "diagnosis_date": "2010-02-14",
  "status": "Active",
  "notes": "Insulin-dependent"
}
```

**Response (201 Created):**

```json
{
  "id": 2,
  "patient_code": "PAT-0002",
  ...
}
```

### PUT /patients/{id}

Update a patient.

### DELETE /patients/{id}

Soft-delete a patient.

**Response (204 No Content)**

---

## Appointments

### GET /appointments

List appointments with filters.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| page | int | Page number |
| page_size | int | Items per page |
| search | string | Search by patient name |
| status | string | Filter by status |
| date_from | date | Start date (YYYY-MM-DD) |
| date_to | date | End date (YYYY-MM-DD) |
| patient_id | int | Filter by patient |

**Response (200 OK):**

```json
{
  "items": [
    {
      "id": 1,
      "patient_id": 1,
      "patient_code": "PAT-0001",
      "patient_name": "Sarah Connor",
      "date": "2025-01-20",
      "time": "09:30",
      "scheduled_at": "2025-01-20 09:30:00",
      "duration_minutes": 30,
      "type": "Check-up",
      "status": "Scheduled",
      "notes": null,
      "created_at": "2025-01-15T10:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

### POST /appointments

Create an appointment.

**Request Body:**

```json
{
  "patient_id": 1,
  "scheduled_at": "2025-01-20 09:30:00",
  "duration_minutes": 30,
  "type": "Check-up",
  "status": "Scheduled",
  "notes": null
}
```

**Appointment Types:** Check-up, Follow-up, Lab Review, Consultation, New Patient

**Status Values:** Scheduled, Completed, Cancelled, No-show

---

## Medications

### GET /medications

List medications.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| page | int | Page number |
| page_size | int | Items per page |
| search | string | Search by medication name |
| patient_id | int | Filter by patient |
| status | string | Filter by status (active/discontinued) |

**Response (200 OK):**

```json
{
  "items": [
    {
      "id": 1,
      "patient_id": 1,
      "patient_code": "PAT-0001",
      "patient_name": "Sarah Connor",
      "name": "Metformin",
      "generic_name": "Metformin HCl",
      "dosage": "500mg",
      "frequency": "Twice daily",
      "route": "Oral",
      "start_date": "2020-06-15",
      "end_date": null,
      "prescribing_doctor": "Dr. Smith",
      "status": "active",
      "notes": "Take with meals",
      "created_at": "2020-06-15T10:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

### POST /medications

**Request Body:**

```json
{
  "patient_id": 1,
  "name": "Metformin",
  "generic_name": "Metformin HCl",
  "dosage": "500mg",
  "frequency": "Twice daily",
  "route": "Oral",
  "start_date": "2020-06-15",
  "prescribing_doctor": "Dr. Smith",
  "status": "active",
  "notes": "Take with meals"
}
```

**Frequency Values:** Once daily, Twice daily, Three times daily, Four times daily, Every 12 hours, Every 8 hours, Every 6 hours, As needed, Weekly, With meals

**Route Values:** Oral, Subcutaneous, Intramuscular, Intravenous, Topical, Inhalation, Sublingual

---

## Lab Results

### GET /lab-results

List lab results.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| page | int | Page number |
| page_size | int | Items per page |
| patient_id | int | Filter by patient |
| test_name | string | Filter by test type |
| date_from | date | Start date |
| date_to | date | End date |

**Response (200 OK):**

```json
{
  "items": [
    {
      "id": 1,
      "patient_id": 1,
      "patient_code": "PAT-0001",
      "patient_name": "Sarah Connor",
      "test_name": "HbA1c",
      "test_date": "2025-01-10",
      "result_value": "6.8",
      "unit": "%",
      "reference_range": "< 7.0",
      "status": "Normal",
      "notes": null,
      "created_at": "2025-01-10T10:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

### GET /lab-results/test-types

Get available test types with reference values.

**Response (200 OK):**

```json
{
  "test_types": [
    {
      "name": "HbA1c",
      "unit": "%",
      "reference_range": "< 7.0",
      "category": "Diabetes"
    },
    {
      "name": "Fasting Glucose",
      "unit": "mg/dL",
      "reference_range": "70-100",
      "category": "Diabetes"
    }
  ],
  "categories": ["Diabetes", "Kidney", "Lipids", "Thyroid", "Cardiovascular"]
}
```

### POST /lab-results

**Request Body:**

```json
{
  "patient_id": 1,
  "test_name": "HbA1c",
  "test_date": "2025-01-10",
  "result_value": "6.8",
  "unit": "%",
  "reference_range": "< 7.0",
  "status": "Normal",
  "notes": null
}
```

**Note:** If `test_name` matches a known test type, `unit` and `reference_range` will be auto-populated if not provided. Status is auto-calculated based on result vs reference range.

---

## Dashboard

### GET /dashboard/summary

Get comprehensive clinic statistics using CTEs for efficient aggregation.

**Response (200 OK):**

```json
{
  "patients": {
    "total": 150,
    "active": 142,
    "by_type": {
      "Type 1": 25,
      "Type 2": 95,
      "Pre-diabetes": 20,
      "Gestational": 10
    }
  },
  "appointments": {
    "today": {
      "total": 12,
      "scheduled": 8,
      "completed": 3,
      "cancelled": 1,
      "no_show": 0
    },
    "this_week": 45
  },
  "hba1c_control": {
    "patients_tracked": 130,
    "average": 7.2,
    "distribution": {
      "well_controlled": 65,
      "moderate": 40,
      "poor": 18,
      "very_poor": 7
    }
  },
  "medications": {
    "active_prescriptions": 380,
    "patients_on_medications": 135
  },
  "lab_results": {
    "last_30_days": 156
  },
  "generated_at": "2025-01-16T10:00:00Z"
}
```

### GET /dashboard/upcoming-appointments

Get next 10 upcoming appointments.

### GET /dashboard/recent-patients

Get 5 most recently added/updated patients.

### GET /dashboard/critical-alerts

Get patients needing attention (high HbA1c, missed visits, critical labs).

### GET /dashboard/hba1c-trends

Get monthly HbA1c averages for charts.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| months | int | Number of months (1-24, default: 6) |

---

## Error Responses

All errors follow this format:

```json
{
  "code": "ERROR_CODE",
  "message": "Human-readable error message",
  "errors": {
    "field_name": ["Error 1", "Error 2"]
  }
}
```

### Common Error Codes

| Code             | HTTP Status | Description              |
| ---------------- | ----------- | ------------------------ |
| UNAUTHORIZED     | 401         | Missing or invalid token |
| FORBIDDEN        | 403         | Insufficient permissions |
| NOT_FOUND        | 404         | Resource not found       |
| VALIDATION_ERROR | 422         | Invalid input data       |
| SERVER_ERROR     | 500         | Internal server error    |

---

## Rate Limiting

Currently not implemented. Consider adding for production.

---

## CORS

The API allows requests from any origin in development. Configure `CORS_ORIGIN` in production.
