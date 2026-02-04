<?php
/**
 * DiabetaCare - Appointments Controller
 * 
 * CRUD operations for appointment scheduling.
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

class AppointmentsController
{
    //VIEW - Uses: vw_AppointmentCalendar
    /**
     * GET /api/appointments/stats
     * 
     * Get summary statistics for appointments page dashboard.
     * Uses database views for optimized aggregation.
     */
    public function stats(Request $request): Response
    {
        $clinicId = $request->clinicId;
        $today = date('Y-m-d');

        if (Database::isSqlServer()) {
            $stats = Database::queryOne("
                SELECT 
                    COUNT(*) AS total_appointments,
                    SUM(CASE WHEN CAST(scheduled_at AS DATE) = ? THEN 1 ELSE 0 END) AS today_total,
                    SUM(CASE WHEN CAST(scheduled_at AS DATE) = ? AND status = 'Scheduled' THEN 1 ELSE 0 END) AS today_scheduled,
                    SUM(CASE WHEN CAST(scheduled_at AS DATE) = ? AND status = 'Completed' THEN 1 ELSE 0 END) AS today_completed,
                    SUM(CASE WHEN status = 'Scheduled' AND scheduled_at >= GETDATE() THEN 1 ELSE 0 END) AS upcoming,
                    SUM(CASE WHEN scheduled_at >= DATEADD(DAY, -DATEPART(WEEKDAY, GETDATE()) + 1, CAST(GETDATE() AS DATE))
                              AND scheduled_at < DATEADD(DAY, 7 - DATEPART(WEEKDAY, GETDATE()) + 1, CAST(GETDATE() AS DATE)) THEN 1 ELSE 0 END) AS this_week,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled,
                    SUM(CASE WHEN status = 'No-show' THEN 1 ELSE 0 END) AS no_show
                FROM vw_AppointmentCalendar 
                WHERE clinic_id = ?
            ", [$today, $today, $today, $clinicId]);
        } else {
            $stats = Database::queryOne("
                SELECT 
                    COUNT(*) AS total_appointments,
                    SUM(CASE WHEN DATE(scheduled_at) = ? THEN 1 ELSE 0 END) AS today_total,
                    SUM(CASE WHEN DATE(scheduled_at) = ? AND status = 'Scheduled' THEN 1 ELSE 0 END) AS today_scheduled,
                    SUM(CASE WHEN DATE(scheduled_at) = ? AND status = 'Completed' THEN 1 ELSE 0 END) AS today_completed,
                    SUM(CASE WHEN status = 'Scheduled' AND scheduled_at >= NOW() THEN 1 ELSE 0 END) AS upcoming,
                    SUM(CASE WHEN YEARWEEK(scheduled_at, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) AS this_week,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled,
                    SUM(CASE WHEN status = 'No-show' THEN 1 ELSE 0 END) AS no_show
                FROM vw_AppointmentCalendar 
                WHERE clinic_id = ?
            ", [$today, $today, $today, $clinicId]);
        }

        return Response::json([
            'today' => [
                'total' => (int) ($stats['today_total'] ?? 0),
                'scheduled' => (int) ($stats['today_scheduled'] ?? 0),
                'completed' => (int) ($stats['today_completed'] ?? 0),
            ],
            'this_week' => (int) ($stats['this_week'] ?? 0),
            'upcoming' => (int) ($stats['upcoming'] ?? 0),
            'by_status' => [
                'completed' => (int) ($stats['completed'] ?? 0),
                'cancelled' => (int) ($stats['cancelled'] ?? 0),
                'no_show' => (int) ($stats['no_show'] ?? 0),
            ],
        ]);
    }

    //INDEX - Uses: idx_appointments_clinic_scheduled, idx_appointments_clinic_status, idx_appointments_patient
    //VIEW - Related: vw_AppointmentCalendar
    //FUNCTION - Related: fn_GetDaysUntilNextAppointment
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
        $date = $request->query('date'); // Single date filter
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $patientId = $request->query('patient_id');

        // If single date is provided, use it for both from and to
        if ($date && !$dateFrom && !$dateTo) {
            $dateFrom = $date;
            $dateTo = $date;
        }

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
            $searchConcat = Database::isSqlServer()
                ? "(p.first_name + ' ' + p.last_name LIKE ?)"
                : "(CONCAT(p.first_name, ' ', p.last_name) LIKE ?)";
            $conditions[] = $searchConcat;
            $params[] = "%{$search}%";
        }

        if ($status) {
            $conditions[] = 'a.status = ?';
            $params[] = $status;
        }

        if ($dateFrom) {
            $dateFromCond = Database::isSqlServer()
                ? 'CAST(a.scheduled_at AS DATE) >= ?'
                : 'DATE(a.scheduled_at) >= ?';
            $conditions[] = $dateFromCond;
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $dateToCond = Database::isSqlServer()
                ? 'CAST(a.scheduled_at AS DATE) <= ?'
                : 'DATE(a.scheduled_at) <= ?';
            $conditions[] = $dateToCond;
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
        $paginationClause = SqlHelper::paginate($pagination['page_size'], $pagination['offset']);
        $appointments = Database::query(
            "SELECT a.id, a.patient_id, a.scheduled_at, a.duration_minutes, 
                    a.type, a.status, a.notes, a.created_at, a.updated_at,
                    p.patient_code, p.first_name as patient_first_name, 
                    p.last_name as patient_last_name, p.last_visit_date
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             WHERE {$whereClause}
             ORDER BY a.scheduled_at DESC
             {$paginationClause}",
            $params
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

    //TRIGGER - Fires: trg_Appointments_SetUpdatedAt (sets created_at/updated_at)
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
            $appointmentId = Database::insertAndGetId(
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

            // Update patient's last visit date if completed
            if (($data['status'] ?? 'Scheduled') === 'Completed') {
                $this->updatePatientLastVisit((int) $data['patient_id']);
            }

            // Fetch and return created appointment
            $appointment = Database::queryOne(
                "SELECT a.*, p.patient_code, p.first_name as patient_first_name, 
                        p.last_name as patient_last_name, p.last_visit_date
                 FROM appointments a
                 JOIN patients p ON p.id = a.patient_id
                 WHERE a.id = ?",
                [$appointmentId]
            );

            if (!$appointment) {
                error_log("Failed to fetch newly created appointment. ID: {$appointmentId}");
                return Response::error('CREATE_FAILED', 'Appointment was created but could not be retrieved.', [], 500);
            }

            return Response::created($this->transformAppointment($appointment));

        } catch (\Throwable $e) {
            error_log("Error creating appointment: " . $e->getMessage());
            return Response::error('CREATE_FAILED', 'Failed to create appointment.', [], 500);
        }
    }

    //TRIGGER - Fires: trg_Appointments_SetUpdatedAt, trg_Appointments_AfterUpdate (updates patient.last_visit_date when status='Completed')
    //STOREDPROCEDURE - Calls: sp_UpdatePatientLastVisit (via trigger)
    /**
     * PUT /api/appointments/{id}
     * 
     * Supports partial updates - only provided fields are validated and updated.
     */
    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $clinicId = $request->clinicId;
        $data = $request->all();

        $existing = Database::queryOne(
            'SELECT * FROM appointments WHERE id = ? AND clinic_id = ?',
            [$id, $clinicId]
        );

        if (!$existing) {
            return Response::notFound('Appointment not found.');
        }

        // Validate only provided fields
        $validator = new Validator($data);
        
        if (isset($data['patient_id'])) {
            $validator->integer('patient_id');
        }
        if (isset($data['scheduled_at'])) {
            $validator->datetime('scheduled_at', 'Y-m-d H:i:s');
        }
        if (isset($data['type'])) {
            $validator->inArray('type', ['Check-up', 'Follow-up', 'Lab Review', 'Consultation', 'New Patient']);
        }
        if (isset($data['status'])) {
            $validator->inArray('status', ['Scheduled', 'Completed', 'Cancelled', 'No-show']);
        }

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        // Merge existing data with provided updates
        $patientId = isset($data['patient_id']) ? (int) $data['patient_id'] : (int) $existing['patient_id'];
        $scheduledAt = $data['scheduled_at'] ?? $existing['scheduled_at'];
        $durationMinutes = $data['duration_minutes'] ?? $existing['duration_minutes'] ?? 30;
        $type = $data['type'] ?? $existing['type'];
        $status = $data['status'] ?? $existing['status'];
        $notes = array_key_exists('notes', $data) ? $data['notes'] : $existing['notes'];

        try {
            $nowFunc = SqlHelper::now();
            Database::execute(
                "UPDATE appointments SET 
                    patient_id = ?, scheduled_at = ?, duration_minutes = ?,
                    type = ?, status = ?, notes = ?, updated_at = {$nowFunc}
                 WHERE id = ? AND clinic_id = ?",
                [
                    $patientId,
                    $scheduledAt,
                    $durationMinutes,
                    $type,
                    $status,
                    $notes,
                    $id,
                    $clinicId,
                ]
            );

            // Update patient's last visit date if status changed to Completed
            if ($status === 'Completed' && $existing['status'] !== 'Completed') {
                $this->updatePatientLastVisit($patientId);
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
        $currentDate = SqlHelper::currentDate();
        Database::execute(
            "UPDATE patients SET last_visit_date = {$currentDate} WHERE id = ?",
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
            'updated_at' => $apt['updated_at'] ?? null,
            'patient_last_visit_date' => $apt['last_visit_date'] ?? null,
        ];
    }
}
