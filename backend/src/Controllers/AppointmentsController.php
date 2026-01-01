<?php
/**
 * DiabetaCare - Appointments Controller
 * 
 * CRUD operations for appointment scheduling.
 */

declare(strict_types=1);

namespace DiabetaCare\Controllers;

use DiabetaCare\Core\Request;
use DiabetaCare\Core\Response;
use DiabetaCare\Core\Database;
use DiabetaCare\Services\Validator;

class AppointmentsController
{
    /**
     * GET /api/appointments
     * 
     * List appointments with filters.
     * 
     * Query Parameters:
     *   - page, page_size: Pagination
     *   - search: Search by patient name
     *   - status: Filter by status
     *   - date_from, date_to: Date range filter
     *   - patient_id: Filter by specific patient
     */
    public function index(Request $request): Response
    {
        $clinicId = $request->clinicId;
        $pagination = $request->pagination();
        
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $patientId = $request->query('patient_id');

        // =========================================================================
        // APPOINTMENTS QUERY OPTIMIZATION
        // 
        // Primary index used: idx_appointments_clinic_scheduled (clinic_id, scheduled_at)
        // This index efficiently supports date-range queries which are the most common
        // access pattern (viewing appointments for a specific day or date range).
        // 
        // Status filter uses: idx_appointments_clinic_status (clinic_id, status)
        // Patient filter uses: idx_appointments_patient (patient_id, scheduled_at)
        // =========================================================================

        $conditions = ['a.clinic_id = ?'];
        $params = [$clinicId];

        if ($search !== '') {
            $conditions[] = "(CONCAT(p.first_name, ' ', p.last_name) LIKE ?)";
            $params[] = "%{$search}%";
        }

        if ($status) {
            $conditions[] = 'a.status = ?';
            $params[] = $status;
        }

        if ($dateFrom) {
            $conditions[] = 'DATE(a.scheduled_at) >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $conditions[] = 'DATE(a.scheduled_at) <= ?';
            $params[] = $dateTo;
        }

        if ($patientId) {
            $conditions[] = 'a.patient_id = ?';
            $params[] = (int) $patientId;
        }

        $whereClause = implode(' AND ', $conditions);

        // Get total count
        $totalItems = (int) Database::queryValue(
            "SELECT COUNT(*) 
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             WHERE {$whereClause}",
            $params
        );

        // Get paginated results with patient info
        $appointments = Database::query(
            "SELECT a.id, a.patient_id, a.scheduled_at, a.duration_minutes, 
                    a.type, a.status, a.notes, a.created_at,
                    p.patient_code, p.first_name as patient_first_name, 
                    p.last_name as patient_last_name
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             WHERE {$whereClause}
             ORDER BY a.scheduled_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$pagination['page_size'], $pagination['offset']])
        );

        $items = array_map([$this, 'transformAppointment'], $appointments);

        return Response::paginated(
            $items,
            $pagination['page'],
            $pagination['page_size'],
            $totalItems
        );
    }

    /**
     * GET /api/appointments/{id}
     */
    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;

        $appointment = Database::queryOne(
            "SELECT a.*, p.patient_code, p.first_name as patient_first_name, 
                    p.last_name as patient_last_name
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             WHERE a.id = ? AND a.clinic_id = ?",
            [$id, $clinicId]
        );

        if (!$appointment) {
            return Response::notFound('Appointment not found.');
        }

        return Response::json($this->transformAppointment($appointment));
    }

    /**
     * POST /api/appointments
     */
    public function store(Request $request): Response
    {
        $data = $request->all();
        $clinicId = $request->clinicId;

        $validator = new Validator($data);
        $validator
            ->required('patient_id')
            ->integer('patient_id')
            ->required('scheduled_at')
            ->datetime('scheduled_at', 'Y-m-d H:i:s')
            ->required('type')
            ->inArray('type', ['Check-up', 'Follow-up', 'Lab Review', 'Consultation', 'New Patient'])
            ->inArray('status', ['Scheduled', 'Completed', 'Cancelled', 'No-show']);

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
                'INSERT INTO appointments (clinic_id, patient_id, scheduled_at, duration_minutes, type, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $clinicId,
                    (int) $data['patient_id'],
                    $data['scheduled_at'],
                    $data['duration_minutes'] ?? 30,
                    $data['type'],
                    $data['status'] ?? 'Scheduled',
                    $data['notes'] ?? null,
                ]
            );

            $appointmentId = (int) Database::lastInsertId();

            // Update patient's last visit date if completed
            if (($data['status'] ?? 'Scheduled') === 'Completed') {
                $this->updatePatientLastVisit((int) $data['patient_id']);
            }

            // Fetch and return created appointment
            $appointment = Database::queryOne(
                "SELECT a.*, p.patient_code, p.first_name as patient_first_name, 
                        p.last_name as patient_last_name
                 FROM appointments a
                 JOIN patients p ON p.id = a.patient_id
                 WHERE a.id = ?",
                [$appointmentId]
            );

            return Response::created($this->transformAppointment($appointment));

        } catch (\Throwable $e) {
            error_log("Error creating appointment: " . $e->getMessage());
            return Response::error('CREATE_FAILED', 'Failed to create appointment.', [], 500);
        }
    }

    /**
     * PUT /api/appointments/{id}
     */
    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;
        $data = $request->all();

        $existing = Database::queryOne(
            'SELECT id, patient_id, status as old_status FROM appointments WHERE id = ? AND clinic_id = ?',
            [$id, $clinicId]
        );

        if (!$existing) {
            return Response::notFound('Appointment not found.');
        }

        $validator = new Validator($data);
        $validator
            ->required('patient_id')
            ->integer('patient_id')
            ->required('scheduled_at')
            ->datetime('scheduled_at', 'Y-m-d H:i:s')
            ->required('type')
            ->inArray('type', ['Check-up', 'Follow-up', 'Lab Review', 'Consultation', 'New Patient'])
            ->inArray('status', ['Scheduled', 'Completed', 'Cancelled', 'No-show']);

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        try {
            Database::execute(
                'UPDATE appointments SET 
                    patient_id = ?, scheduled_at = ?, duration_minutes = ?,
                    type = ?, status = ?, notes = ?, updated_at = NOW()
                 WHERE id = ? AND clinic_id = ?',
                [
                    (int) $data['patient_id'],
                    $data['scheduled_at'],
                    $data['duration_minutes'] ?? 30,
                    $data['type'],
                    $data['status'] ?? 'Scheduled',
                    $data['notes'] ?? null,
                    $id,
                    $clinicId,
                ]
            );

            // Update patient's last visit date if status changed to Completed
            $newStatus = $data['status'] ?? 'Scheduled';
            if ($newStatus === 'Completed' && $existing['old_status'] !== 'Completed') {
                $this->updatePatientLastVisit((int) $data['patient_id']);
            }

            $appointment = Database::queryOne(
                "SELECT a.*, p.patient_code, p.first_name as patient_first_name, 
                        p.last_name as patient_last_name
                 FROM appointments a
                 JOIN patients p ON p.id = a.patient_id
                 WHERE a.id = ?",
                [$id]
            );

            return Response::json($this->transformAppointment($appointment));

        } catch (\Throwable $e) {
            error_log("Error updating appointment: " . $e->getMessage());
            return Response::error('UPDATE_FAILED', 'Failed to update appointment.', [], 500);
        }
    }

    /**
     * DELETE /api/appointments/{id}
     */
    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;

        $existing = Database::queryOne(
            'SELECT id FROM appointments WHERE id = ? AND clinic_id = ?',
            [$id, $clinicId]
        );

        if (!$existing) {
            return Response::notFound('Appointment not found.');
        }

        Database::execute('DELETE FROM appointments WHERE id = ?', [$id]);

        return Response::noContent();
    }

    /**
     * Update patient's last visit date
     */
    private function updatePatientLastVisit(int $patientId): void
    {
        Database::execute(
            'UPDATE patients SET last_visit_date = CURDATE() WHERE id = ?',
            [$patientId]
        );
    }

    /**
     * Transform appointment for API response
     */
    private function transformAppointment(array $apt): array
    {
        $scheduledAt = new \DateTime($apt['scheduled_at']);
        
        return [
            'id' => (int) $apt['id'],
            'patient_id' => (int) $apt['patient_id'],
            'patient_code' => $apt['patient_code'],
            'patient_name' => $apt['patient_first_name'] . ' ' . $apt['patient_last_name'],
            'date' => $scheduledAt->format('Y-m-d'),
            'time' => $scheduledAt->format('H:i'),
            'scheduled_at' => $apt['scheduled_at'],
            'duration_minutes' => (int) ($apt['duration_minutes'] ?? 30),
            'type' => $apt['type'],
            'status' => $apt['status'],
            'notes' => $apt['notes'],
            'created_at' => $apt['created_at'],
        ];
    }
}
