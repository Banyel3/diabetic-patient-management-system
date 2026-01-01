<?php
/**
 * DiabetaCare - Medications Controller
 * 
 * CRUD operations for patient medications/prescriptions.
 */

declare(strict_types=1);

namespace DiabetaCare\Controllers;

use DiabetaCare\Core\Request;
use DiabetaCare\Core\Response;
use DiabetaCare\Core\Database;
use DiabetaCare\Services\Validator;

class MedicationsController
{
    /**
     * GET /api/medications
     * 
     * List medications with filters.
     * 
     * Query Parameters:
     *   - page, page_size: Pagination
     *   - search: Search by medication name
     *   - patient_id: Filter by specific patient
     *   - status: Filter by status (active/discontinued)
     */
    public function index(Request $request): Response
    {
        $clinicId = $request->clinicId;
        $pagination = $request->pagination();
        
        $search = trim((string) $request->query('search', ''));
        $patientId = $request->query('patient_id');
        $status = $request->query('status');

        // =========================================================================
        // MEDICATIONS QUERY OPTIMIZATION
        // 
        // Primary index used: idx_medications_patient (patient_id, status)
        // This supports efficient filtering by patient and active status.
        // 
        // For name-based searches: idx_medications_name (name)
        // Combined with clinic_id FK constraint for security.
        // =========================================================================

        $conditions = ['m.clinic_id = ?'];
        $params = [$clinicId];

        if ($search !== '') {
            $conditions[] = '(m.name LIKE ? OR m.generic_name LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($patientId) {
            $conditions[] = 'm.patient_id = ?';
            $params[] = (int) $patientId;
        }

        if ($status) {
            $conditions[] = 'm.status = ?';
            $params[] = $status;
        }

        $whereClause = implode(' AND ', $conditions);

        // Get total count
        $totalItems = (int) Database::queryValue(
            "SELECT COUNT(*) 
             FROM medications m
             WHERE {$whereClause}",
            $params
        );

        // Get paginated results with patient info
        $medications = Database::query(
            "SELECT m.id, m.patient_id, m.name, m.generic_name, m.dosage, 
                    m.frequency, m.route, m.start_date, m.end_date,
                    m.prescribing_doctor, m.status, m.notes, m.created_at,
                    p.patient_code, p.first_name as patient_first_name, 
                    p.last_name as patient_last_name
             FROM medications m
             JOIN patients p ON p.id = m.patient_id
             WHERE {$whereClause}
             ORDER BY m.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$pagination['page_size'], $pagination['offset']])
        );

        $items = array_map([$this, 'transformMedication'], $medications);

        return Response::paginated(
            $items,
            $pagination['page'],
            $pagination['page_size'],
            $totalItems
        );
    }

    /**
     * GET /api/medications/{id}
     */
    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;

        $medication = Database::queryOne(
            "SELECT m.*, p.patient_code, p.first_name as patient_first_name, 
                    p.last_name as patient_last_name
             FROM medications m
             JOIN patients p ON p.id = m.patient_id
             WHERE m.id = ? AND m.clinic_id = ?",
            [$id, $clinicId]
        );

        if (!$medication) {
            return Response::notFound('Medication not found.');
        }

        return Response::json($this->transformMedication($medication));
    }

    /**
     * POST /api/medications
     */
    public function store(Request $request): Response
    {
        $data = $request->all();
        $clinicId = $request->clinicId;

        $validator = new Validator($data);
        $validator
            ->required('patient_id')
            ->integer('patient_id')
            ->required('name')
            ->minLength('name', 2)
            ->maxLength('name', 100)
            ->required('dosage')
            ->required('frequency')
            ->inArray('frequency', ['Once daily', 'Twice daily', 'Three times daily', 'Four times daily', 'Every 12 hours', 'Every 8 hours', 'Every 6 hours', 'As needed', 'Weekly', 'With meals'])
            ->inArray('route', ['Oral', 'Subcutaneous', 'Intramuscular', 'Intravenous', 'Topical', 'Inhalation', 'Sublingual'])
            ->date('start_date')
            ->date('end_date')
            ->inArray('status', ['active', 'discontinued', 'completed']);

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

        try {
            Database::execute(
                'INSERT INTO medications (clinic_id, patient_id, name, generic_name, dosage, frequency, route, start_date, end_date, prescribing_doctor, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $clinicId,
                    (int) $data['patient_id'],
                    $data['name'],
                    $data['generic_name'] ?? null,
                    $data['dosage'],
                    $data['frequency'],
                    $data['route'] ?? 'Oral',
                    $data['start_date'] ?? date('Y-m-d'),
                    $data['end_date'] ?? null,
                    $data['prescribing_doctor'] ?? null,
                    $data['status'] ?? 'active',
                    $data['notes'] ?? null,
                ]
            );

            $medicationId = (int) Database::lastInsertId();

            $medication = Database::queryOne(
                "SELECT m.*, p.patient_code, p.first_name as patient_first_name, 
                        p.last_name as patient_last_name
                 FROM medications m
                 JOIN patients p ON p.id = m.patient_id
                 WHERE m.id = ?",
                [$medicationId]
            );

            return Response::created($this->transformMedication($medication));

        } catch (\Throwable $e) {
            error_log("Error creating medication: " . $e->getMessage());
            return Response::error('CREATE_FAILED', 'Failed to create medication.', [], 500);
        }
    }

    /**
     * PUT /api/medications/{id}
     */
    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;
        $data = $request->all();

        $existing = Database::queryOne(
            'SELECT id FROM medications WHERE id = ? AND clinic_id = ?',
            [$id, $clinicId]
        );

        if (!$existing) {
            return Response::notFound('Medication not found.');
        }

        $validator = new Validator($data);
        $validator
            ->required('patient_id')
            ->integer('patient_id')
            ->required('name')
            ->minLength('name', 2)
            ->maxLength('name', 100)
            ->required('dosage')
            ->required('frequency')
            ->inArray('frequency', ['Once daily', 'Twice daily', 'Three times daily', 'Four times daily', 'Every 12 hours', 'Every 8 hours', 'Every 6 hours', 'As needed', 'Weekly', 'With meals'])
            ->inArray('route', ['Oral', 'Subcutaneous', 'Intramuscular', 'Intravenous', 'Topical', 'Inhalation', 'Sublingual'])
            ->date('start_date')
            ->date('end_date')
            ->inArray('status', ['active', 'discontinued', 'completed']);

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        try {
            Database::execute(
                'UPDATE medications SET 
                    patient_id = ?, name = ?, generic_name = ?, dosage = ?,
                    frequency = ?, route = ?, start_date = ?, end_date = ?,
                    prescribing_doctor = ?, status = ?, notes = ?, updated_at = NOW()
                 WHERE id = ? AND clinic_id = ?',
                [
                    (int) $data['patient_id'],
                    $data['name'],
                    $data['generic_name'] ?? null,
                    $data['dosage'],
                    $data['frequency'],
                    $data['route'] ?? 'Oral',
                    $data['start_date'] ?? date('Y-m-d'),
                    $data['end_date'] ?? null,
                    $data['prescribing_doctor'] ?? null,
                    $data['status'] ?? 'active',
                    $data['notes'] ?? null,
                    $id,
                    $clinicId,
                ]
            );

            $medication = Database::queryOne(
                "SELECT m.*, p.patient_code, p.first_name as patient_first_name, 
                        p.last_name as patient_last_name
                 FROM medications m
                 JOIN patients p ON p.id = m.patient_id
                 WHERE m.id = ?",
                [$id]
            );

            return Response::json($this->transformMedication($medication));

        } catch (\Throwable $e) {
            error_log("Error updating medication: " . $e->getMessage());
            return Response::error('UPDATE_FAILED', 'Failed to update medication.', [], 500);
        }
    }

    /**
     * DELETE /api/medications/{id}
     */
    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;

        $existing = Database::queryOne(
            'SELECT id FROM medications WHERE id = ? AND clinic_id = ?',
            [$id, $clinicId]
        );

        if (!$existing) {
            return Response::notFound('Medication not found.');
        }

        // Soft delete: mark as discontinued
        Database::execute(
            'UPDATE medications SET status = ?, end_date = CURDATE(), updated_at = NOW() WHERE id = ?',
            ['discontinued', $id]
        );

        return Response::noContent();
    }

    /**
     * Transform medication for API response
     */
    private function transformMedication(array $med): array
    {
        return [
            'id' => (int) $med['id'],
            'patient_id' => (int) $med['patient_id'],
            'patient_code' => $med['patient_code'],
            'patient_name' => $med['patient_first_name'] . ' ' . $med['patient_last_name'],
            'name' => $med['name'],
            'generic_name' => $med['generic_name'],
            'dosage' => $med['dosage'],
            'frequency' => $med['frequency'],
            'route' => $med['route'] ?? 'Oral',
            'start_date' => $med['start_date'],
            'end_date' => $med['end_date'],
            'prescribing_doctor' => $med['prescribing_doctor'],
            'status' => $med['status'],
            'notes' => $med['notes'],
            'created_at' => $med['created_at'],
        ];
    }
}
