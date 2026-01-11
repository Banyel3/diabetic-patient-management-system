<?php
/**
 * DiabetaCare - Lab Results Controller
 * 
 * CRUD operations for patient laboratory test results.
 * Includes auto-population of units and reference ranges for common diabetes tests.
 */

declare(strict_types=1);

namespace DiabetaCare\Controllers;

use DiabetaCare\Core\Request;
use DiabetaCare\Core\Response;
use DiabetaCare\Core\Database;
use DiabetaCare\Core\SqlHelper;
use DiabetaCare\Services\Validator;

class LabResultsController
{
    /**
     * Standard reference values for common diabetes tests
     */
    private const TEST_REFERENCES = [
        'HbA1c' => ['unit' => '%', 'reference_range' => '< 7.0', 'category' => 'Diabetes'],
        'Fasting Glucose' => ['unit' => 'mg/dL', 'reference_range' => '70-100', 'category' => 'Diabetes'],
        'Random Glucose' => ['unit' => 'mg/dL', 'reference_range' => '< 140', 'category' => 'Diabetes'],
        'Post-meal Glucose' => ['unit' => 'mg/dL', 'reference_range' => '< 180', 'category' => 'Diabetes'],
        'Fructosamine' => ['unit' => 'μmol/L', 'reference_range' => '200-285', 'category' => 'Diabetes'],
        'C-Peptide' => ['unit' => 'ng/mL', 'reference_range' => '0.5-2.0', 'category' => 'Diabetes'],
        'Insulin Level' => ['unit' => 'μIU/mL', 'reference_range' => '2.6-24.9', 'category' => 'Diabetes'],
        'Creatinine' => ['unit' => 'mg/dL', 'reference_range' => '0.7-1.3', 'category' => 'Kidney'],
        'eGFR' => ['unit' => 'mL/min/1.73m²', 'reference_range' => '> 90', 'category' => 'Kidney'],
        'Urine Albumin' => ['unit' => 'mg/L', 'reference_range' => '< 30', 'category' => 'Kidney'],
        'Albumin/Creatinine Ratio' => ['unit' => 'mg/g', 'reference_range' => '< 30', 'category' => 'Kidney'],
        'Total Cholesterol' => ['unit' => 'mg/dL', 'reference_range' => '< 200', 'category' => 'Lipids'],
        'LDL Cholesterol' => ['unit' => 'mg/dL', 'reference_range' => '< 100', 'category' => 'Lipids'],
        'HDL Cholesterol' => ['unit' => 'mg/dL', 'reference_range' => '> 40', 'category' => 'Lipids'],
        'Triglycerides' => ['unit' => 'mg/dL', 'reference_range' => '< 150', 'category' => 'Lipids'],
        'TSH' => ['unit' => 'mIU/L', 'reference_range' => '0.4-4.0', 'category' => 'Thyroid'],
        'Blood Pressure' => ['unit' => 'mmHg', 'reference_range' => '< 130/80', 'category' => 'Cardiovascular'],
    ];

    /**
     * GET /api/lab-results
     * 
     * List lab results with filters.
     * 
     * Query Parameters:
     *   - page, page_size: Pagination
     *   - patient_id: Filter by specific patient
     *   - test_name: Filter by test type
     *   - date_from, date_to: Date range filter
     */
    public function index(Request $request): Response
    {
        $clinicId = $request->clinicId;
        $pagination = $request->pagination();
        
        $search = trim((string) $request->query('search', ''));
        $patientId = $request->query('patient_id');
        $testName = $request->query('test_name') ?? $request->query('test_type'); // Accept both parameter names
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        // =========================================================================
        // LAB RESULTS QUERY OPTIMIZATION
        // 
        // Primary index used: idx_lab_results_patient_test (patient_id, test_name, test_date)
        // This index efficiently supports patient-specific test history queries.
        // 
        // For HbA1c trend analysis: idx_lab_results_hba1c (clinic_id, test_name, test_date)
        // Optimized for clinic-wide HbA1c reporting and dashboard metrics.
        // =========================================================================

        $conditions = ['l.clinic_id = ?'];
        $params = [$clinicId];

        if ($search !== '') {
            // Search by patient name
            $searchConcat = Database::isSqlServer()
                ? "(p.first_name + ' ' + p.last_name LIKE ?)"
                : "(CONCAT(p.first_name, ' ', p.last_name) LIKE ?)";
            $conditions[] = $searchConcat;
            $params[] = "%{$search}%";
        }

        if ($patientId) {
            $conditions[] = 'l.patient_id = ?';
            $params[] = (int) $patientId;
        }

        if ($testName) {
            $conditions[] = 'l.test_name = ?';
            $params[] = $testName;
        }

        if ($dateFrom) {
            $conditions[] = 'l.test_date >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $conditions[] = 'l.test_date <= ?';
            $params[] = $dateTo;
        }

        $whereClause = implode(' AND ', $conditions);

        // Get total count
        $totalItems = (int) Database::queryValue(
            "SELECT COUNT(*) 
             FROM lab_results l
             WHERE {$whereClause}",
            $params
        );

        // Get paginated results with patient info
        $paginationClause = SqlHelper::paginate($pagination['page_size'], $pagination['offset']);
        $labResults = Database::query(
            "SELECT l.id, l.patient_id, l.test_name, l.test_date, l.result_value,
                    l.unit, l.reference_range, l.status, l.notes, l.created_at, l.updated_at,
                    p.patient_code, p.first_name as patient_first_name, 
                    p.last_name as patient_last_name, p.last_hba1c, p.last_hba1c_date
             FROM lab_results l
             JOIN patients p ON p.id = l.patient_id
             WHERE {$whereClause}
             ORDER BY l.test_date DESC, l.created_at DESC
             {$paginationClause}",
            $params
        );

        $items = array_map([$this, 'transformLabResult'], $labResults);

        return Response::paginated(
            $items,
            $pagination['page'],
            $pagination['page_size'],
            $totalItems
        );
    }

    /**
     * GET /api/lab-results/test-types
     * 
     * Get list of available test types with units and reference ranges.
     */
    public function testTypes(Request $request): Response
    {
        $types = [];
        foreach (self::TEST_REFERENCES as $name => $info) {
            $types[] = [
                'name' => $name,
                'unit' => $info['unit'],
                'reference_range' => $info['reference_range'],
                'category' => $info['category'],
            ];
        }

        return Response::json([
            'test_types' => $types,
            'categories' => ['Diabetes', 'Kidney', 'Lipids', 'Thyroid', 'Cardiovascular'],
        ]);
    }

    /**
     * GET /api/lab-results/{id}
     */
    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;

        $labResult = Database::queryOne(
            "SELECT l.*, p.patient_code, p.first_name as patient_first_name, 
                    p.last_name as patient_last_name
             FROM lab_results l
             JOIN patients p ON p.id = l.patient_id
             WHERE l.id = ? AND l.clinic_id = ?",
            [$id, $clinicId]
        );

        if (!$labResult) {
            return Response::notFound('Lab result not found.');
        }

        return Response::json($this->transformLabResult($labResult));
    }

    /**
     * POST /api/lab-results
     */
    public function store(Request $request): Response
    {
        $data = $request->all();
        $clinicId = $request->clinicId;

        $validator = new Validator($data);
        $validator
            ->required('patient_id')
            ->integer('patient_id')
            ->required('test_name')
            ->minLength('test_name', 2)
            ->maxLength('test_name', 100)
            ->required('test_date')
            ->date('test_date')
            ->required('result_value')
            ->inArray('status', ['Normal', 'Abnormal', 'Critical', 'Pending']);

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        // Verify patient belongs to clinic
        $patient = Database::queryOne(
            'SELECT id FROM patients WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL',
            [(int) $data['patient_id'], $clinicId]
        );

        if (!$patient) {
            return Response::validationError('Invalid patient.', [
                'patient_id' => ['Patient not found.']
            ]);
        }

        // Auto-populate unit and reference range for known tests
        $unit = $data['unit'] ?? null;
        $referenceRange = $data['reference_range'] ?? null;
        $testName = $data['test_name'];

        if (isset(self::TEST_REFERENCES[$testName])) {
            $ref = self::TEST_REFERENCES[$testName];
            $unit = $unit ?? $ref['unit'];
            $referenceRange = $referenceRange ?? $ref['reference_range'];
        }

        // Auto-determine status based on result vs reference range (always calculated, not user-editable)
        $status = $this->determineStatus($testName, (string) $data['result_value']);

        try {
            Database::execute(
                'INSERT INTO lab_results (clinic_id, patient_id, test_name, test_date, result_value, unit, reference_range, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $clinicId,
                    (int) $data['patient_id'],
                    $testName,
                    $data['test_date'],
                    (string) $data['result_value'],
                    $unit,
                    $referenceRange,
                    $status,
                    $data['notes'] ?? null,
                ]
            );

            $labResultId = (int) Database::lastInsertId();

            // If this is an HbA1c test, update patient's last_hba1c
            // Note: There's also a trigger for this, but we do it here for immediate feedback
            if ($testName === 'HbA1c') {
                $this->updatePatientHbA1c((int) $data['patient_id']);
            }

            $labResult = Database::queryOne(
                "SELECT l.*, p.patient_code, p.first_name as patient_first_name, 
                        p.last_name as patient_last_name, p.last_hba1c, p.last_hba1c_date
                 FROM lab_results l
                 JOIN patients p ON p.id = l.patient_id
                 WHERE l.id = ?",
                [$labResultId]
            );

            if (!$labResult) {
                error_log("Failed to fetch newly created lab result. ID: {$labResultId}");
                return Response::error('CREATE_FAILED', 'Lab result was created but could not be retrieved.', [], 500);
            }

            return Response::created($this->transformLabResult($labResult));

        } catch (\Throwable $e) {
            error_log("Error creating lab result: " . $e->getMessage());
            return Response::error('CREATE_FAILED', 'Failed to create lab result.', [], 500);
        }
    }

    /**
     * PUT /api/lab-results/{id}
     */
    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;
        $data = $request->all();

        $existing = Database::queryOne(
            'SELECT id, patient_id FROM lab_results WHERE id = ? AND clinic_id = ?',
            [$id, $clinicId]
        );

        if (!$existing) {
            return Response::notFound('Lab result not found.');
        }

        $validator = new Validator($data);
        $validator
            ->required('patient_id')
            ->integer('patient_id')
            ->required('test_name')
            ->minLength('test_name', 2)
            ->maxLength('test_name', 100)
            ->required('test_date')
            ->date('test_date')
            ->required('result_value')
            ->inArray('status', ['Normal', 'Abnormal', 'Critical', 'Pending']);

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        // Auto-populate unit and reference range
        $testName = $data['test_name'];
        $unit = $data['unit'] ?? null;
        $referenceRange = $data['reference_range'] ?? null;

        if (isset(self::TEST_REFERENCES[$testName])) {
            $ref = self::TEST_REFERENCES[$testName];
            $unit = $unit ?? $ref['unit'];
            $referenceRange = $referenceRange ?? $ref['reference_range'];
        }

        // Auto-determine status based on result vs reference range (always calculated, not user-editable)
        $status = $this->determineStatus($testName, (string) $data['result_value']);

        try {
            $nowFunc = SqlHelper::now();
            Database::execute(
                "UPDATE lab_results SET 
                    patient_id = ?, test_name = ?, test_date = ?, result_value = ?,
                    unit = ?, reference_range = ?, status = ?, notes = ?, updated_at = {$nowFunc}
                 WHERE id = ? AND clinic_id = ?",
                [
                    (int) $data['patient_id'],
                    $testName,
                    $data['test_date'],
                    (string) $data['result_value'],
                    $unit,
                    $referenceRange,
                    $status,
                    $data['notes'] ?? null,
                    $id,
                    $clinicId,
                ]
            );

            // Update patient's last_hba1c if needed
            if ($testName === 'HbA1c') {
                $this->updatePatientHbA1c((int) $data['patient_id']);
            }

            $labResult = Database::queryOne(
                "SELECT l.*, p.patient_code, p.first_name as patient_first_name, 
                        p.last_name as patient_last_name
                 FROM lab_results l
                 JOIN patients p ON p.id = l.patient_id
                 WHERE l.id = ?",
                [$id]
            );

            return Response::json($this->transformLabResult($labResult));

        } catch (\Throwable $e) {
            error_log("Error updating lab result: " . $e->getMessage());
            return Response::error('UPDATE_FAILED', 'Failed to update lab result.', [], 500);
        }
    }

    /**
     * DELETE /api/lab-results/{id}
     */
    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;

        $existing = Database::queryOne(
            'SELECT id, patient_id, test_name FROM lab_results WHERE id = ? AND clinic_id = ?',
            [$id, $clinicId]
        );

        if (!$existing) {
            return Response::notFound('Lab result not found.');
        }

        Database::execute('DELETE FROM lab_results WHERE id = ?', [$id]);

        // Update patient's HbA1c if this was an HbA1c result
        if ($existing['test_name'] === 'HbA1c') {
            $this->updatePatientHbA1c((int) $existing['patient_id']);
        }

        return Response::noContent();
    }

    /**
     * Determine test status based on result value
     */
    private function determineStatus(string $testName, string $resultValue): string
    {
        if (!isset(self::TEST_REFERENCES[$testName])) {
            return 'Normal';
        }

        $ref = self::TEST_REFERENCES[$testName];
        $range = $ref['reference_range'];
        $value = (float) $resultValue;

        // Parse reference range patterns
        if (str_starts_with($range, '<')) {
            $limit = (float) trim(substr($range, 1));
            if ($value >= $limit * 1.5) return 'Critical';
            if ($value >= $limit) return 'Abnormal';
            return 'Normal';
        }

        if (str_starts_with($range, '>')) {
            $limit = (float) trim(substr($range, 1));
            if ($value <= $limit * 0.5) return 'Critical';
            if ($value < $limit) return 'Abnormal';
            return 'Normal';
        }

        // Range format: "70-100"
        if (preg_match('/^([\d.]+)-([\d.]+)$/', $range, $matches)) {
            $low = (float) $matches[1];
            $high = (float) $matches[2];
            
            if ($value < $low * 0.7 || $value > $high * 1.5) return 'Critical';
            if ($value < $low || $value > $high) return 'Abnormal';
            return 'Normal';
        }

        return 'Normal';
    }

    /**
     * Update patient's last HbA1c value
     */
    private function updatePatientHbA1c(int $patientId): void
    {
        $query = Database::isSqlServer()
            ? "SELECT TOP 1 result_value FROM lab_results 
               WHERE patient_id = ? AND test_name = 'HbA1c' 
               ORDER BY test_date DESC, created_at DESC"
            : "SELECT result_value FROM lab_results 
               WHERE patient_id = ? AND test_name = 'HbA1c' 
               ORDER BY test_date DESC, created_at DESC 
               LIMIT 1";
        
        $latestHba1c = Database::queryValue($query, [$patientId]);

        Database::execute(
            'UPDATE patients SET last_hba1c = ? WHERE id = ?',
            [$latestHba1c, $patientId]
        );
    }

    /**
     * Transform lab result for API response
     */
    private function transformLabResult(array $lab): array
    {
        return [
            'id' => (int) $lab['id'],
            'patient_id' => (int) $lab['patient_id'],
            'patient_code' => $lab['patient_code'] ?? null,
            'patient_name' => ($lab['patient_first_name'] ?? '') . ' ' . ($lab['patient_last_name'] ?? ''),
            'test_name' => $lab['test_name'],
            'test_date' => $lab['test_date'],
            'result_value' => $lab['result_value'],
            'unit' => $lab['unit'],
            'reference_range' => $lab['reference_range'],
            'status' => $lab['status'],
            'notes' => $lab['notes'],
            'created_at' => $lab['created_at'],
            'updated_at' => $lab['updated_at'] ?? null,
            'patient_last_hba1c' => isset($lab['last_hba1c']) ? (float) $lab['last_hba1c'] : null,
            'patient_last_hba1c_date' => $lab['last_hba1c_date'] ?? null,
        ];
    }
}
