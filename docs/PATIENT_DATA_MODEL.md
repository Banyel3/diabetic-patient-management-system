# Patient Data Model Updates

## Emergency Contact Information

### New Fields

The patients table includes emergency contact tracking:

| Field                        | Type         | Default | Description                         |
| ---------------------------- | ------------ | ------- | ----------------------------------- |
| `emergency_contact_name`     | VARCHAR(100) | NULL    | Name of emergency contact person    |
| `emergency_contact_phone`    | VARCHAR(50)  | NULL    | Phone number of emergency contact   |
| `emergency_contact_relation` | VARCHAR(50)  | NULL    | Relationship (spouse, parent, etc.) |

### Common Relationship Values

- Spouse
- Parent
- Sibling
- Child
- Friend
- Other

### Database Migration (SQL Server)

For existing databases, run these ALTER statements:

```sql
ALTER TABLE patients ADD emergency_contact_name NVARCHAR(100) NULL;
ALTER TABLE patients ADD emergency_contact_phone NVARCHAR(50) NULL;
ALTER TABLE patients ADD emergency_contact_relation NVARCHAR(50) NULL;
```

---

## Family History of Diabetes

### New Fields

The patients table now includes family history tracking:

| Field                     | Type | Default   | Description                          |
| ------------------------- | ---- | --------- | ------------------------------------ |
| `family_history_diabetes` | ENUM | `unknown` | Family history category              |
| `family_history_notes`    | TEXT | NULL      | Free-text notes about family members |

### Family History Values

| Value           | Display Text                                      | Clinical Significance                                                        |
| --------------- | ------------------------------------------------- | ---------------------------------------------------------------------------- |
| `none`          | "None"                                            | No known family history of diabetes. Lower genetic risk factor.              |
| `first_degree`  | "First-degree relative (parent/sibling)"          | Parent or sibling has diabetes. Higher genetic risk; priority for screening. |
| `second_degree` | "Second-degree relative (grandparent/aunt/uncle)" | Extended family member has diabetes. Moderate genetic risk.                  |
| `unknown`       | "Unknown / Not recorded"                          | Default value. Should be updated when information becomes available.         |

### Clinical Context

Family history is a significant risk factor for Type 2 diabetes:

- Patients with first-degree relatives with diabetes have 2-3x higher risk
- This information helps clinicians assess patient risk profiles
- The notes field allows capturing details like "Mother diagnosed at 45, managed with oral medication"

## Required vs Optional Patient Fields

### Core Required Fields

These fields are **required** when creating a patient:

| Field         | Backend Key     | Description                                               |
| ------------- | --------------- | --------------------------------------------------------- |
| First Name    | `first_name`    | Patient's first name (max 100 chars)                      |
| Last Name     | `last_name`     | Patient's last name (max 100 chars)                       |
| Date of Birth | `date_of_birth` | Date in YYYY-MM-DD format                                 |
| Gender        | `gender`        | One of: `male`, `female`, `other`                         |
| Diabetes Type | `diabetes_type` | One of: `Type 1`, `Type 2`, `Gestational`, `Pre-diabetic` |

### Optional Fields

All other fields are optional and can be omitted:

| Category          | Fields                                                                            |
| ----------------- | --------------------------------------------------------------------------------- |
| Contact           | `phone`, `email`, `address`                                                       |
| Emergency Contact | `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relation` |
| Diabetes Details  | `diagnosis_date`                                                                  |
| Family History    | `family_history_diabetes`, `family_history_notes`                                 |
| Clinical Values   | `last_hba1c` (auto-updated from lab results)                                      |
| Status            | `status` (defaults to "Active")                                                   |
| Notes             | `notes`                                                                           |

### Display of Missing Values

When optional fields are not provided:

- Phone shows: "Not recorded"
- Other fields show: "N/A"
- HbA1c colors only apply when a real value exists

## Database Migration

For existing databases, run the migration script:

```bash
mysql -u root diabetacare < backend/database/migrations/002_add_family_history.sql
```

Or manually execute:

```sql
ALTER TABLE patients
ADD COLUMN family_history_diabetes ENUM('none', 'first_degree', 'second_degree', 'unknown')
DEFAULT 'unknown'
AFTER diagnosis_date;

ALTER TABLE patients
ADD COLUMN family_history_notes TEXT
AFTER family_history_diabetes;
```

## API Examples

### Create Patient (Minimal - Required Fields Only)

```json
POST /api/patients

{
  "first_name": "Jane",
  "last_name": "Doe",
  "date_of_birth": "1985-06-15",
  "gender": "female",
  "diabetes_type": "Type 2"
}
```

### Create Patient (With Family History)

```json
POST /api/patients

{
  "first_name": "John",
  "last_name": "Smith",
  "date_of_birth": "1978-03-22",
  "gender": "male",
  "diabetes_type": "Type 2",
  "family_history_diabetes": "first_degree",
  "family_history_notes": "Mother diagnosed at age 50, father at 55. Both on oral medications.",
  "phone": "(555) 123-4567"
}
```

### Response (Includes New Fields)

```json
{
  "id": 15,
  "patient_code": "P005",
  "first_name": "John",
  "last_name": "Smith",
  "full_name": "John Smith",
  "date_of_birth": "1978-03-22",
  "gender": "male",
  "phone": "(555) 123-4567",
  "email": null,
  "address": null,
  "emergency_contact_name": "Mary Smith",
  "emergency_contact_phone": "(555) 987-6543",
  "emergency_contact_relation": "Spouse",
  "diabetes_type": "Type 2",
  "diagnosis_date": null,
  "family_history_diabetes": "first_degree",
  "family_history_notes": "Mother diagnosed at age 50, father at 55. Both on oral medications.",
  "last_hba1c": null,
  "last_hba1c_date": null,
  "last_visit_date": null,
  "status": "Active",
  "notes": null,
  "created_at": "2026-01-03T08:00:00Z"
}
```

## Frontend Components

### Patient Form (Add/Edit Modal)

The patient form now includes:

1. **Core Demographics** (required fields marked with \*)

   - First Name \*
   - Last Name \*
   - Date of Birth \*
   - Gender \*

2. **Contact Information** (all optional)

   - Phone
   - Email
   - Address

3. **Emergency Contact** (all optional)

   - Emergency Contact Name
   - Emergency Contact Phone
   - Emergency Contact Relationship

4. **Diabetes Information**

   - Diabetes Type \*
   - Diagnosis Date

5. **Family History of Diabetes** (new section)

   - Family History dropdown
   - Family History Notes text field

6. **Status & Notes**
   - Status
   - Notes

### Patient View Modal

Displays all patient information including:

- Emergency contact details (name, phone, relationship)
- "Family History of Diabetes" field with human-readable value
- "Family History Notes" section (only shown if notes exist)
