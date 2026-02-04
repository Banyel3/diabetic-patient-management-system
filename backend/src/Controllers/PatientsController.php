<?php
/**
 * DiabetaCare - Patients Controller
 * 
 * Full CRUD operations for patient management.
 * All operations are scoped to the authenticated user's clinic.
 * 
 * @see docs/DATABASE_OBJECTS_BY_CONTROLLER.md for database objects documentation
 */

declare(strict_types=1);

namespace DiabetaCare\Controllers;

use DiabetaCare\Core\Request;
use DiabetaCare\Core\Response;
use DiabetaCare\Core\Database;
use DiabetaCare\Core\SqlHelper;
use DiabetaCare\Services\Validator;

class PatientsController
{
    /**
     * GET /api/patients/list
     * 
     * Lightweight endpoint for patient dropdowns.
     * Returns only essential fields: id, patient_code, first_name, last_name, full_name.
     * No pagination - returns all active patients (for dropdown use).
     */
    public function list(Request $request): Response
    {
        $clinicId = $request->clinicId;
        
        // Use optimized query with minimal columns for dropdown lists
        // Returns ALL non-deleted patients (including inactive) so edit forms can show current patient
        $patients = Database::query(
            'SELECT id, patient_code, first_name, last_name, status
             FROM patients 
             WHERE clinic_id = ? AND deleted_at IS NULL
             ORDER BY first_name, last_name',
            [$clinicId]
        );

        // Transform to include full_name and status
        $items = array_map(function ($patient) {
            return [
                'id' => (int) $patient['id'],
                'patient_code' => $patient['patient_code'],
                'first_name' => $patient['first_name'],
                'last_name' => $patient['last_name'],
                'full_name' => $patient['first_name'] . ' ' . $patient['last_name'],
                'status' => $patient['status'],
            ];
        }, $patients);

        return Response::json(['data' => $items]);
    }

    //VIEW - Uses: vw_PatientDashboardSummary, vw_HbA1cDistribution
    /**
     * GET /api/patients/stats
     * 
     * Get summary statistics for patients page dashboard.
     * Uses database views for optimized aggregation.
     */
    public function stats(Request $request): Response
    {
        $clinicId = $request->clinicId;

        // Get HbA1c distribution from view
        $hba1cDistribution = Database::queryOne(
            'SELECT * FROM vw_HbA1cDistribution WHERE clinic_id = ?',
            [$clinicId]
        );

        // Get patient stats using vw_PatientDashboardSummary
        $patientStats = Database::queryOne("
            SELECT 
                COUNT(*) AS total_patients,
                SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS active_patients,
                SUM(CASE WHEN needs_followup = 1 THEN 1 ELSE 0 END) AS needs_followup,
                SUM(CASE WHEN diabetes_type = 'Type 1' THEN 1 ELSE 0 END) AS type1_count,
                SUM(CASE WHEN diabetes_type = 'Type 2' THEN 1 ELSE 0 END) AS type2_count,
                SUM(CASE WHEN diabetes_type = 'Pre-diabetic' THEN 1 ELSE 0 END) AS prediabetic_count,
                SUM(CASE WHEN diabetes_type = 'Gestational' THEN 1 ELSE 0 END) AS gestational_count,
                SUM(CASE WHEN hba1c_risk_level = 'Critical' THEN 1 ELSE 0 END) AS critical_count
            FROM vw_PatientDashboardSummary 
            WHERE clinic_id = ?
        ", [$clinicId]);

        return Response::json([
            'total_patients' => (int) ($patientStats['total_patients'] ?? 0),
            'active_patients' => (int) ($patientStats['active_patients'] ?? 0),
            'needs_followup' => (int) ($patientStats['needs_followup'] ?? 0),
            'critical_hba1c' => (int) ($patientStats['critical_count'] ?? 0),
            'diabetes_types' => [
                'type1' => (int) ($patientStats['type1_count'] ?? 0),
                'type2' => (int) ($patientStats['type2_count'] ?? 0),
                'prediabetic' => (int) ($patientStats['prediabetic_count'] ?? 0),
                'gestational' => (int) ($patientStats['gestational_count'] ?? 0),
            ],
            'hba1c_distribution' => [
                'well_controlled' => (int) ($hba1cDistribution['well_controlled_count'] ?? 0),
                'moderate' => (int) ($hba1cDistribution['moderate_count'] ?? 0),
                'poor' => (int) ($hba1cDistribution['poor_count'] ?? 0),
                'critical' => (int) ($hba1cDistribution['critical_count'] ?? 0),
            ],
        ]);
    }

    //INDEX - Uses: idx_patients_search, idx_patients_clinic_type, idx_patients_clinic_status, idx_patients_created_at
    //VIEW - Can use: vw_PatientDashboardSummary
    //FUNCTION - Related: fn_CalculateAge, fn_GetHbA1cRiskLevel
    /**
     * GET /api/patients
     * 
     * List patients with pagination, search, and filters.
     * 
     * Query Parameters:
     *   - page: Page number (default: 1)
     *   - page_size: Items per page (default: 10, max: 100)
     *   - search: Search by name, patient_code, or phone
     *   - diabetes_type: Filter by diabetes type
     *   - status: Filter by Active/Inactive
     *   - sort: Field to sort by (default: created_at)
     *   - order: asc or desc (default: desc)
     */
    public function index(Request $request): Response
    {
        $clinicId = $request->clinicId;
        $pagination = $request->pagination();
        
        $search = trim((string) $request->query('search', ''));
        $diabetesType = $request->query('diabetes_type');
        $status = $request->query('status');
        $sortField = $request->query('sort', 'created_at');
        $sortOrder = strtoupper($request->query('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['created_at', 'first_name', 'last_name', 'last_visit_date', 'last_hba1c'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'created_at';
        }

        // Build query with conditions
        $conditions = ['clinic_id = ?', 'deleted_at IS NULL'];
        $params = [$clinicId];

        if ($search !== '') {
            // =========================================================================
            // SEARCH QUERY OPTIMIZATION
            // 
            // Uses idx_patients_search (clinic_id, first_name, last_name, phone) index.
            // LIKE 'term%' prefix matching can use index; '%term%' cannot, but is more
            // user-friendly for partial name searches.
            // 
            // For high-volume clinics (>10,000 patients), consider implementing
            // full-text search or an external search service.
            // =========================================================================
            $conditions[] = '(first_name LIKE ? OR last_name LIKE ? OR patient_code LIKE ? OR phone LIKE ?)';
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if ($diabetesType) {
            // Uses idx_patients_clinic_type (clinic_id, diabetes_type) index
            $conditions[] = 'diabetes_type = ?';
            $params[] = $diabetesType;
        }

        if ($status) {
            // Uses idx_patients_clinic_status (clinic_id, status) index
            $conditions[] = 'status = ?';
            $params[] = $status;
        }

        $whereClause = implode(' AND ', $conditions);

        // Get total count
        $totalItems = (int) Database::queryValue(
            "SELECT COUNT(*) FROM patients WHERE {$whereClause}",
            $params
        );

        // Get paginated results (SQL Server compatible)
        $paginationClause = SqlHelper::paginate($pagination['page_size'], $pagination['offset']);
        $patients = Database::query(
            "SELECT id, patient_code, first_name, last_name, date_of_birth, gender,
                    phone, email, address, diabetes_type, diagnosis_date,
                    family_history_diabetes, family_history_notes,
                    emergency_contact_name, emergency_contact_phone, emergency_contact_relation,
                    height_cm, weight_kg, blood_pressure,
                    last_hba1c, last_hba1c_date, last_visit_date, status, notes, created_at, updated_at
             FROM patients 
             WHERE {$whereClause}
             ORDER BY {$sortField} {$sortOrder}
             {$paginationClause}",
            $params
        );

        // Transform data for frontend
        $items = array_map(function ($patient) {
            return $this->transformPatient($patient);
        }, $patients);

        return Response::paginated(
            $items,
            $pagination['page'],
            $pagination['page_size'],
            $totalItems
        );
    }

    /**
     * GET /api/patients/{id}
     * 
     * Get single patient by ID.
     */
    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;

        $patient = Database::queryOne(
            'SELECT id, patient_code, first_name, last_name, date_of_birth, gender,
                    phone, email, address, diabetes_type, diagnosis_date,
                    family_history_diabetes, family_history_notes,
                    emergency_contact_name, emergency_contact_phone, emergency_contact_relation,
                    height_cm, weight_kg, blood_pressure,
                    last_hba1c, last_hba1c_date, last_visit_date, status, notes, created_at
             FROM patients 
             WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL',
            [$id, $clinicId]
        );

        if (!$patient) {
            return Response::notFound('Patient not found.');
        }

        return Response::json($this->transformPatient($patient));
    }
    
    //TRIGGER - Fires: trg_Patients_SetUpdatedAt (sets created_at/updated_at), trg_Patients_AuditLog (HIPAA audit)
    //STOREDPROCEDURE - Related: sp_GeneratePatientCode (auto-generates patient codes)
    /**
     * POST /api/patients
     * 
     * Create new patient.
     */
    public function store(Request $request): Response
    {
        $data = $request->all();
        $clinicId = $request->clinicId;

        // Validation: Only core fields are required
        // Required: first_name, last_name, date_of_birth, gender, diabetes_type
        // Optional: phone, email, address, diagnosis_date, vitals, family history, notes
        $validator = new Validator($data);
        $validator
            ->required('first_name')
            ->maxLength('first_name', 100)
            ->required('last_name')
            ->maxLength('last_name', 100)
            ->required('date_of_birth')
            ->date('date_of_birth')
            ->required('gender')
            ->inArray('gender', ['male', 'female', 'other'])
            ->required('diabetes_type')
            ->inArray('diabetes_type', ['Type 1', 'Type 2', 'Gestational', 'Pre-diabetic'])
            ->email('email')
            ->date('diagnosis_date')
            ->inArray('status', ['Active', 'Inactive'])
            ->inArray('family_history_diabetes', ['none', 'first_degree', 'second_degree', 'unknown']);

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        // Generate unique patient code for this clinic (SQL Server compatible)
        $topClause = SqlHelper::selectTop('patient_code', 1);
        $limitClause = SqlHelper::limit1AtEnd();
        $lastCode = Database::queryValue(
            "{$topClause} FROM patients WHERE clinic_id = ? ORDER BY id DESC {$limitClause}",
            [$clinicId]
        );

        $nextNumber = 1;
        if ($lastCode && preg_match('/P(\d+)/', $lastCode, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }
        $patientCode = 'P' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);

        try {
            $patientId = Database::insertAndGetId(
                'INSERT INTO patients (clinic_id, patient_code, first_name, last_name, date_of_birth, 
                                       gender, phone, email, address, diabetes_type, diagnosis_date, 
                                       family_history_diabetes, family_history_notes,
                                       emergency_contact_name, emergency_contact_phone, emergency_contact_relation,
                                       height_cm, weight_kg, blood_pressure,
                                       last_hba1c, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $clinicId,
                    $patientCode,
                    $data['first_name'],
                    $data['last_name'],
                    $data['date_of_birth'],
                    $data['gender'],
                    $data['phone'] ?? null,
                    $data['email'] ?? null,
                    $data['address'] ?? null,
                    $data['diabetes_type'],
                    $data['diagnosis_date'] ?? null,
                    $data['family_history_diabetes'] ?? 'unknown',
                    $data['family_history_notes'] ?? null,
                    $data['emergency_contact_name'] ?? null,
                    $data['emergency_contact_phone'] ?? null,
                    $data['emergency_contact_relation'] ?? null,
                    $data['height_cm'] ?? null,
                    $data['weight_kg'] ?? null,
                    $data['blood_pressure'] ?? null,
                    $data['last_hba1c'] ?? null,
                    $data['status'] ?? 'Active',
                    $data['notes'] ?? null,
                ]
            );

            // Fetch and return created patient
            $patient = Database::queryOne(
                'SELECT * FROM patients WHERE id = ?',
                [$patientId]
            );

            if (!$patient) {
                error_log("Failed to fetch newly created patient. ID: {$patientId}");
                return Response::error('CREATE_FAILED', 'Patient was created but could not be retrieved.', [], 500);
            }

            return Response::created($this->transformPatient($patient));

        } catch (\Throwable $e) {
            error_log("Error creating patient: " . $e->getMessage());
            return Response::error('CREATE_FAILED', 'Failed to create patient.', [], 500);
        }
    }

    //TRIGGER - Fires: trg_Patients_SetUpdatedAt (auto-updates updated_at), trg_Patients_AuditLog (HIPAA audit)
    /**
     * PUT /api/patients/{id}
     * 
     * Update existing patient.
     */
    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;
        $data = $request->all();

        // Verify patient exists and belongs to clinic
        $existing = Database::queryOne(
            'SELECT id FROM patients WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL',
            [$id, $clinicId]
        );

        if (!$existing) {
            return Response::notFound('Patient not found.');
        }

        // Validation: Only core fields are required
        // Required: first_name, last_name, date_of_birth, gender, diabetes_type
        // Optional: phone, email, address, diagnosis_date, vitals, family history, notes
        $validator = new Validator($data);
        $validator
            ->required('first_name')
            ->maxLength('first_name', 100)
            ->required('last_name')
            ->maxLength('last_name', 100)
            ->required('date_of_birth')
            ->date('date_of_birth')
            ->required('gender')
            ->inArray('gender', ['male', 'female', 'other'])
            ->required('diabetes_type')
            ->inArray('diabetes_type', ['Type 1', 'Type 2', 'Gestational', 'Pre-diabetic'])
            ->email('email')
            ->date('diagnosis_date')
            ->inArray('status', ['Active', 'Inactive'])
            ->inArray('family_history_diabetes', ['none', 'first_degree', 'second_degree', 'unknown']);

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        try {
            $nowFunc = SqlHelper::now();
            Database::execute(
                "UPDATE patients SET 
                    first_name = ?, last_name = ?, date_of_birth = ?, gender = ?,
                    phone = ?, email = ?, address = ?, diabetes_type = ?, diagnosis_date = ?,
                    family_history_diabetes = ?, family_history_notes = ?,
                    emergency_contact_name = ?, emergency_contact_phone = ?, emergency_contact_relation = ?,
                    height_cm = ?, weight_kg = ?, blood_pressure = ?,
                    last_hba1c = ?, status = ?, notes = ?, updated_at = {$nowFunc}
                 WHERE id = ? AND clinic_id = ?",
                [
                    $data['first_name'],
                    $data['last_name'],
                    $data['date_of_birth'],
                    $data['gender'],
                    $data['phone'] ?? null,
                    $data['email'] ?? null,
                    $data['address'] ?? null,
                    $data['diabetes_type'],
                    $data['diagnosis_date'] ?? null,
                    $data['family_history_diabetes'] ?? 'unknown',
                    $data['family_history_notes'] ?? null,
                    $data['emergency_contact_name'] ?? null,
                    $data['emergency_contact_phone'] ?? null,
                    $data['emergency_contact_relation'] ?? null,
                    $data['height_cm'] ?? null,
                    $data['weight_kg'] ?? null,
                    $data['blood_pressure'] ?? null,
                    $data['last_hba1c'] ?? null,
                    $data['status'] ?? 'Active',
                    $data['notes'] ?? null,
                    $id,
                    $clinicId,
                ]
            );

            // Fetch and return updated patient
            $patient = Database::queryOne(
                'SELECT * FROM patients WHERE id = ?',
                [$id]
            );

            return Response::json($this->transformPatient($patient));

        } catch (\Throwable $e) {
            error_log("Error updating patient: " . $e->getMessage());
            return Response::error('UPDATE_FAILED', 'Failed to update patient.', [], 500);
        }
    }

    //TRIGGER - Fires: trg_Patients_AuditLog (HIPAA audit on soft delete)
    /**
     * DELETE /api/patients/{id}
     * 
     * Soft delete patient.
     */
    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;

        $existing = Database::queryOne(
            'SELECT id FROM patients WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL',
            [$id, $clinicId]
        );

        if (!$existing) {
            return Response::notFound('Patient not found.');
        }

        // Soft delete
        $nowFunc = SqlHelper::now();
        Database::execute(
            "UPDATE patients SET deleted_at = {$nowFunc} WHERE id = ?",
            [$id]
        );

        return Response::noContent();
    }

    /**
     * GET /api/patients/{id}/summary
     * 
     * Get patient with all related data (appointments, medications, lab results).
     */
    public function summary(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;

        // Get patient
        $patient = Database::queryOne(
            'SELECT id, patient_code, first_name, last_name, date_of_birth, gender,
                    phone, email, address, diabetes_type, diagnosis_date,
                    family_history_diabetes, family_history_notes,
                    emergency_contact_name, emergency_contact_phone, emergency_contact_relation,
                    height_cm, weight_kg, blood_pressure,
                    last_hba1c, last_hba1c_date, last_visit_date, status, notes, created_at
             FROM patients 
             WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL',
            [$id, $clinicId]
        );

        if (!$patient) {
            return Response::notFound('Patient not found.');
        }

        // Get appointments (last 20) - SQL Server compatible
        $appointmentsQuery = Database::isSqlServer()
            ? 'SELECT TOP 20 id, scheduled_at, type, duration_minutes, status, notes
               FROM appointments
               WHERE patient_id = ? AND deleted_at IS NULL
               ORDER BY scheduled_at DESC'
            : 'SELECT id, scheduled_at, type, duration_minutes, status, notes
               FROM appointments
               WHERE patient_id = ? AND deleted_at IS NULL
               ORDER BY scheduled_at DESC
               LIMIT 20';
        $appointments = Database::query($appointmentsQuery, [$id]);

        // Get medications - SQL Server compatible
        $medicationsQuery = Database::isSqlServer()
            ? "SELECT id, name, dosage, frequency, route, start_date, end_date, status, notes
               FROM medications
               WHERE patient_id = ? AND deleted_at IS NULL
               ORDER BY CASE status 
                   WHEN 'Active' THEN 1 
                   WHEN 'On-hold' THEN 2 
                   WHEN 'Completed' THEN 3 
                   WHEN 'Discontinued' THEN 4 
                   ELSE 5 END, start_date DESC"
            : "SELECT id, name, dosage, frequency, route, start_date, end_date, status, notes
               FROM medications
               WHERE patient_id = ? AND deleted_at IS NULL
               ORDER BY FIELD(status, 'active', 'on_hold', 'completed', 'discontinued'), start_date DESC";
        $medications = Database::query($medicationsQuery, [$id]);

        // Get lab results (last 30) - SQL Server compatible
        $labResultsQuery = Database::isSqlServer()
            ? 'SELECT TOP 30 id, test_name, test_date, result_value, unit, reference_range, status, notes
               FROM lab_results
               WHERE patient_id = ? AND deleted_at IS NULL
               ORDER BY test_date DESC'
            : 'SELECT id, test_name, test_date, result_value, unit, reference_range, status, notes
               FROM lab_results
               WHERE patient_id = ? AND deleted_at IS NULL
               ORDER BY test_date DESC
               LIMIT 30';
        $labResults = Database::query($labResultsQuery, [$id]);

        // Transform appointments
        $transformedAppointments = array_map(function ($apt) {
            $scheduledAt = new \DateTime($apt['scheduled_at']);
            return [
                'id' => (int) $apt['id'],
                'date' => $scheduledAt->format('Y-m-d'),
                'time' => $scheduledAt->format('H:i'),
                'type' => $apt['type'],
                'duration_minutes' => (int) $apt['duration_minutes'],
                'status' => $apt['status'],
                'notes' => $apt['notes'],
            ];
        }, $appointments);

        // Transform medications
        $transformedMedications = array_map(function ($med) {
            return [
                'id' => (int) $med['id'],
                'name' => $med['name'],
                'dosage' => $med['dosage'],
                'frequency' => $med['frequency'],
                'route' => $med['route'],
                'start_date' => $med['start_date'],
                'end_date' => $med['end_date'],
                'status' => $med['status'],
                'notes' => $med['notes'],
            ];
        }, $medications);

        // Transform lab results
        $transformedLabResults = array_map(function ($lab) {
            return [
                'id' => (int) $lab['id'],
                'test_name' => $lab['test_name'],
                'test_date' => $lab['test_date'],
                'result_value' => $lab['result_value'],
                'unit' => $lab['unit'],
                'reference_range' => $lab['reference_range'],
                'status' => $lab['status'],
                'notes' => $lab['notes'],
            ];
        }, $labResults);

        return Response::json([
            'patient' => $this->transformPatient($patient),
            'appointments' => $transformedAppointments,
            'medications' => $transformedMedications,
            'lab_results' => $transformedLabResults,
            'counts' => [
                'appointments' => count($appointments),
                'medications' => count($medications),
                'lab_results' => count($labResults),
                'active_medications' => count(array_filter($medications, fn($m) => $m['status'] === 'active')),
                'upcoming_appointments' => count(array_filter($appointments, fn($a) => $a['status'] === 'scheduled')),
            ],
        ]);
    }

    /**
     * Transform patient record for API response
     */
    private function transformPatient(array $patient): array
    {
        // Calculate BMI if height and weight are available
        $bmi = null;
        $heightCm = isset($patient['height_cm']) ? (float) $patient['height_cm'] : null;
        $weightKg = isset($patient['weight_kg']) ? (float) $patient['weight_kg'] : null;
        if ($heightCm && $weightKg && $heightCm > 0) {
            $heightM = $heightCm / 100;
            $bmi = round($weightKg / ($heightM * $heightM), 1);
        }

        return [
            'id' => (int) $patient['id'],
            'patient_code' => $patient['patient_code'],
            'first_name' => $patient['first_name'],
            'last_name' => $patient['last_name'],
            'full_name' => $patient['first_name'] . ' ' . $patient['last_name'],
            'date_of_birth' => $patient['date_of_birth'],
            'gender' => $patient['gender'],
            'phone' => $patient['phone'],
            'email' => $patient['email'],
            'address' => $patient['address'],
            'diabetes_type' => $patient['diabetes_type'],
            'diagnosis_date' => $patient['diagnosis_date'],
            'family_history_diabetes' => $patient['family_history_diabetes'] ?? 'unknown',
            'family_history_notes' => $patient['family_history_notes'] ?? null,
            'emergency_contact_name' => $patient['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $patient['emergency_contact_phone'] ?? null,
            'emergency_contact_relation' => $patient['emergency_contact_relation'] ?? null,
            'height_cm' => $heightCm,
            'weight_kg' => $weightKg,
            'bmi' => $bmi,
            'blood_pressure' => $patient['blood_pressure'] ?? null,
            'last_hba1c' => $patient['last_hba1c'] ? (float) $patient['last_hba1c'] : null,
            'last_hba1c_date' => $patient['last_hba1c_date'] ?? null,
            'last_visit_date' => $patient['last_visit_date'] ?? null,
            'status' => $patient['status'],
            'notes' => $patient['notes'],
            'created_at' => $patient['created_at'],
            'updated_at' => $patient['updated_at'] ?? null,
        ];
    }
}
