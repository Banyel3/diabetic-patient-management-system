<?php
/**
 * DiabetaCare - Dashboard Controller
 * 
 * Aggregated statistics and KPIs for the clinic dashboard.
 * Uses Common Table Expressions (CTEs) for optimized complex queries.
 * Compatible with both MySQL and SQL Server.
 */

declare(strict_types=1);

namespace DiabetaCare\Controllers;

use DiabetaCare\Core\Request;
use DiabetaCare\Core\Response;
use DiabetaCare\Core\Database;
use DiabetaCare\Core\SqlHelper;

class DashboardController
{
    /**
     * GET /api/dashboard/summary
     * 
     * Get comprehensive dashboard statistics.
     * Uses CTEs for efficient data aggregation.
     */
    public function summary(Request $request): Response
    {
        $clinicId = $request->clinicId;
        $today = date('Y-m-d');
        
        // Use database-specific query
        if (Database::isSqlServer()) {
            return $this->summaryForSqlServer($clinicId, $today);
        }
        return $this->summaryForMySql($clinicId, $today);
    }
    
    /**
     * SQL Server version of dashboard summary
     */
    private function summaryForSqlServer(int $clinicId, string $today): Response
    {
        $stats = Database::queryOne("
            WITH 
            patient_stats AS (
                SELECT 
                    COUNT(*) as total_patients,
                    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_patients,
                    SUM(CASE WHEN diabetes_type = 'Type 1' THEN 1 ELSE 0 END) as type1_count,
                    SUM(CASE WHEN diabetes_type = 'Type 2' THEN 1 ELSE 0 END) as type2_count,
                    SUM(CASE WHEN diabetes_type = 'Pre-diabetic' THEN 1 ELSE 0 END) as prediabetes_count,
                    SUM(CASE WHEN diabetes_type = 'Gestational' THEN 1 ELSE 0 END) as gestational_count
                FROM patients 
                WHERE clinic_id = ? AND deleted_at IS NULL
            ),
            todays_appointments AS (
                SELECT 
                    COUNT(*) as total_today,
                    SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN status = 'No-show' THEN 1 ELSE 0 END) as no_show
                FROM appointments 
                WHERE clinic_id = ? AND CAST(scheduled_at AS DATE) = ?
            ),
            week_appointments AS (
                SELECT COUNT(*) as total_week
                FROM appointments 
                WHERE clinic_id = ? 
                  AND scheduled_at >= DATEADD(DAY, -DATEPART(WEEKDAY, GETDATE()) + 1, CAST(GETDATE() AS DATE))
                  AND scheduled_at < DATEADD(DAY, 7 - DATEPART(WEEKDAY, GETDATE()) + 1, CAST(GETDATE() AS DATE))
            ),
            hba1c_control AS (
                SELECT 
                    COUNT(*) as patients_with_hba1c,
                    SUM(CASE WHEN last_hba1c < 7.0 THEN 1 ELSE 0 END) as well_controlled,
                    SUM(CASE WHEN last_hba1c >= 7.0 AND last_hba1c < 8.0 THEN 1 ELSE 0 END) as moderate_control,
                    SUM(CASE WHEN last_hba1c >= 8.0 AND last_hba1c < 9.0 THEN 1 ELSE 0 END) as poor_control,
                    SUM(CASE WHEN last_hba1c >= 9.0 THEN 1 ELSE 0 END) as very_poor_control,
                    AVG(last_hba1c) as avg_hba1c
                FROM patients 
                WHERE clinic_id = ? AND deleted_at IS NULL AND last_hba1c IS NOT NULL
            ),
            medication_stats AS (
                SELECT COUNT(DISTINCT m.id) as active_medications,
                       COUNT(DISTINCT m.patient_id) as patients_on_meds
                FROM medications m
                JOIN patients p ON p.id = m.patient_id
                WHERE m.clinic_id = ? AND m.status = 'Active' AND p.deleted_at IS NULL
            ),
            recent_labs AS (
                SELECT COUNT(*) as labs_last_30_days
                FROM lab_results 
                WHERE clinic_id = ? AND test_date >= DATEADD(DAY, -30, CAST(GETDATE() AS DATE))
            )
            SELECT 
                ps.*,
                ta.total_today as appointments_today,
                ta.scheduled as appointments_scheduled,
                ta.completed as appointments_completed,
                ta.cancelled as appointments_cancelled,
                ta.no_show as appointments_no_show,
                wa.total_week as appointments_this_week,
                hc.patients_with_hba1c,
                hc.well_controlled,
                hc.moderate_control,
                hc.poor_control,
                hc.very_poor_control,
                hc.avg_hba1c,
                ms.active_medications,
                ms.patients_on_meds,
                rl.labs_last_30_days
            FROM patient_stats ps
            CROSS JOIN todays_appointments ta
            CROSS JOIN week_appointments wa
            CROSS JOIN hba1c_control hc
            CROSS JOIN medication_stats ms
            CROSS JOIN recent_labs rl
        ", [
            $clinicId,                  // patient_stats
            $clinicId, $today,          // todays_appointments
            $clinicId,                  // week_appointments
            $clinicId,                  // hba1c_control
            $clinicId,                  // medication_stats
            $clinicId,                  // recent_labs
        ]);

        return $this->formatSummaryResponse($stats);
    }
    
    /**
     * MySQL version of dashboard summary
     */
    private function summaryForMySql(int $clinicId, string $today): Response
    {
        $stats = Database::queryOne("
            WITH 
            patient_stats AS (
                SELECT 
                    COUNT(*) as total_patients,
                    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_patients,
                    SUM(CASE WHEN diabetes_type = 'Type 1' THEN 1 ELSE 0 END) as type1_count,
                    SUM(CASE WHEN diabetes_type = 'Type 2' THEN 1 ELSE 0 END) as type2_count,
                    SUM(CASE WHEN diabetes_type = 'Pre-diabetes' THEN 1 ELSE 0 END) as prediabetes_count,
                    SUM(CASE WHEN diabetes_type = 'Gestational' THEN 1 ELSE 0 END) as gestational_count
                FROM patients 
                WHERE clinic_id = ? AND deleted_at IS NULL
            ),
            todays_appointments AS (
                SELECT 
                    COUNT(*) as total_today,
                    SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN status = 'No-show' THEN 1 ELSE 0 END) as no_show
                FROM appointments 
                WHERE clinic_id = ? AND DATE(scheduled_at) = ?
            ),
            week_appointments AS (
                SELECT COUNT(*) as total_week
                FROM appointments 
                WHERE clinic_id = ? 
                  AND scheduled_at >= DATE_SUB(?, INTERVAL WEEKDAY(?) DAY)
                  AND scheduled_at < DATE_ADD(DATE_SUB(?, INTERVAL WEEKDAY(?) DAY), INTERVAL 7 DAY)
            ),
            hba1c_control AS (
                SELECT 
                    COUNT(*) as patients_with_hba1c,
                    SUM(CASE WHEN last_hba1c < 7.0 THEN 1 ELSE 0 END) as well_controlled,
                    SUM(CASE WHEN last_hba1c >= 7.0 AND last_hba1c < 8.0 THEN 1 ELSE 0 END) as moderate_control,
                    SUM(CASE WHEN last_hba1c >= 8.0 AND last_hba1c < 9.0 THEN 1 ELSE 0 END) as poor_control,
                    SUM(CASE WHEN last_hba1c >= 9.0 THEN 1 ELSE 0 END) as very_poor_control,
                    AVG(last_hba1c) as avg_hba1c
                FROM patients 
                WHERE clinic_id = ? AND deleted_at IS NULL AND last_hba1c IS NOT NULL
            ),
            medication_stats AS (
                SELECT COUNT(DISTINCT m.id) as active_medications,
                       COUNT(DISTINCT m.patient_id) as patients_on_meds
                FROM medications m
                JOIN patients p ON p.id = m.patient_id
                WHERE m.clinic_id = ? AND m.status = 'active' AND p.deleted_at IS NULL
            ),
            recent_labs AS (
                SELECT COUNT(*) as labs_last_30_days
                FROM lab_results 
                WHERE clinic_id = ? AND test_date >= DATE_SUB(?, INTERVAL 30 DAY)
            )
            SELECT 
                ps.*,
                ta.total_today as appointments_today,
                ta.scheduled as appointments_scheduled,
                ta.completed as appointments_completed,
                ta.cancelled as appointments_cancelled,
                ta.no_show as appointments_no_show,
                wa.total_week as appointments_this_week,
                hc.patients_with_hba1c,
                hc.well_controlled,
                hc.moderate_control,
                hc.poor_control,
                hc.very_poor_control,
                hc.avg_hba1c,
                ms.active_medications,
                ms.patients_on_meds,
                rl.labs_last_30_days
            FROM patient_stats ps
            CROSS JOIN todays_appointments ta
            CROSS JOIN week_appointments wa
            CROSS JOIN hba1c_control hc
            CROSS JOIN medication_stats ms
            CROSS JOIN recent_labs rl
        ", [
            $clinicId,                          // patient_stats
            $clinicId, $today,                  // todays_appointments
            $clinicId, $today, $today, $today, $today,  // week_appointments
            $clinicId,                          // hba1c_control
            $clinicId,                          // medication_stats
            $clinicId, $today,                  // recent_labs
        ]);

        return $this->formatSummaryResponse($stats);
    }
    
    /**
     * Format summary response (shared between MySQL and SQL Server)
     */
    private function formatSummaryResponse(?array $stats): Response
    {
        return Response::json([
            'patients' => [
                'total' => (int) ($stats['total_patients'] ?? 0),
                'active' => (int) ($stats['active_patients'] ?? 0),
                'by_type' => [
                    'Type 1' => (int) ($stats['type1_count'] ?? 0),
                    'Type 2' => (int) ($stats['type2_count'] ?? 0),
                    'Pre-diabetes' => (int) ($stats['prediabetes_count'] ?? 0),
                    'Gestational' => (int) ($stats['gestational_count'] ?? 0),
                ],
            ],
            'appointments' => [
                'today' => [
                    'total' => (int) ($stats['appointments_today'] ?? 0),
                    'scheduled' => (int) ($stats['appointments_scheduled'] ?? 0),
                    'completed' => (int) ($stats['appointments_completed'] ?? 0),
                    'cancelled' => (int) ($stats['appointments_cancelled'] ?? 0),
                    'no_show' => (int) ($stats['appointments_no_show'] ?? 0),
                ],
                'this_week' => (int) ($stats['appointments_this_week'] ?? 0),
            ],
            'hba1c_control' => [
                'patients_tracked' => (int) ($stats['patients_with_hba1c'] ?? 0),
                'average' => round((float) ($stats['avg_hba1c'] ?? 0), 1),
                'distribution' => [
                    'well_controlled' => (int) ($stats['well_controlled'] ?? 0),
                    'moderate' => (int) ($stats['moderate_control'] ?? 0),
                    'poor' => (int) ($stats['poor_control'] ?? 0),
                    'very_poor' => (int) ($stats['very_poor_control'] ?? 0),
                ],
            ],
            'medications' => [
                'active_prescriptions' => (int) ($stats['active_medications'] ?? 0),
                'patients_on_medications' => (int) ($stats['patients_on_meds'] ?? 0),
            ],
            'lab_results' => [
                'last_30_days' => (int) ($stats['labs_last_30_days'] ?? 0),
            ],
            'generated_at' => date('c'),
        ]);
    }

    /**
     * GET /api/dashboard/upcoming-appointments
     * 
     * Get next 10 upcoming appointments.
     */
    public function upcomingAppointments(Request $request): Response
    {
        try {
            $clinicId = $request->clinicId;
            $now = date('Y-m-d H:i:s');

            $query = Database::isSqlServer()
                ? "SELECT TOP 10 a.id, a.scheduled_at, a.type, a.status, a.duration_minutes,
                       p.patient_code, p.first_name, p.last_name
                   FROM appointments a
                   JOIN patients p ON p.id = a.patient_id
                   WHERE a.clinic_id = ? 
                     AND a.scheduled_at >= ?
                     AND a.status = 'Scheduled'
                     AND p.deleted_at IS NULL
                   ORDER BY a.scheduled_at ASC"
                : "SELECT a.id, a.scheduled_at, a.type, a.status, a.duration_minutes,
                       p.patient_code, p.first_name, p.last_name
                   FROM appointments a
                   JOIN patients p ON p.id = a.patient_id
                   WHERE a.clinic_id = ? 
                     AND a.scheduled_at >= ?
                     AND a.status = 'Scheduled'
                     AND p.deleted_at IS NULL
                   ORDER BY a.scheduled_at ASC
                   LIMIT 10";

            $appointments = Database::query($query, [$clinicId, $now]);

            return Response::json([
                'appointments' => array_map(function($apt) {
                    $scheduledAt = new \DateTime($apt['scheduled_at']);
                    return [
                        'id' => (int) $apt['id'],
                        'patient_code' => $apt['patient_code'],
                        'patient_name' => $apt['first_name'] . ' ' . $apt['last_name'],
                        'date' => $scheduledAt->format('Y-m-d'),
                        'time' => $scheduledAt->format('H:i'),
                        'type' => $apt['type'],
                        'duration_minutes' => (int) $apt['duration_minutes'],
                    ];
                }, $appointments),
            ]);
        } catch (\Exception $e) {
            error_log("Dashboard upcoming-appointments error: " . $e->getMessage());
            return Response::json(['appointments' => []]);
        }
    }

    /**
     * GET /api/dashboard/recent-patients
     * 
     * Get recently added or updated patients.
     */
    public function recentPatients(Request $request): Response
    {
        try {
            $clinicId = $request->clinicId;

            $query = Database::isSqlServer()
                ? "SELECT TOP 5 id, patient_code, first_name, last_name, diabetes_type, 
                       status, last_hba1c, created_at, updated_at
                   FROM patients
                   WHERE clinic_id = ? AND deleted_at IS NULL
                   ORDER BY COALESCE(updated_at, created_at) DESC"
                : "SELECT id, patient_code, first_name, last_name, diabetes_type, 
                       status, last_hba1c, created_at, updated_at
                   FROM patients
                   WHERE clinic_id = ? AND deleted_at IS NULL
                   ORDER BY COALESCE(updated_at, created_at) DESC
                   LIMIT 5";

            $patients = Database::query($query, [$clinicId]);

            return Response::json([
                'patients' => array_map(function($p) {
                    return [
                        'id' => (int) $p['id'],
                        'patient_code' => $p['patient_code'],
                        'name' => $p['first_name'] . ' ' . $p['last_name'],
                        'diabetes_type' => $p['diabetes_type'],
                        'status' => $p['status'],
                        'last_hba1c' => $p['last_hba1c'] ? (float) $p['last_hba1c'] : null,
                    ];
                }, $patients),
            ]);
        } catch (\Exception $e) {
            error_log("Dashboard recent-patients error: " . $e->getMessage());
            return Response::json(['patients' => []]);
        }
    }

    /**
     * GET /api/dashboard/critical-alerts
     * 
     * Get patients with critical lab values or other alerts.
     */
    public function criticalAlerts(Request $request): Response
    {
        try {
            $clinicId = $request->clinicId;

            // Find patients with high HbA1c (>= 9.0%)
            $highHba1cQuery = Database::isSqlServer()
                ? "SELECT TOP 5 p.id, p.patient_code, p.first_name, p.last_name, p.last_hba1c
                   FROM patients p
                   WHERE p.clinic_id = ? 
                     AND p.deleted_at IS NULL 
                     AND p.last_hba1c >= 9.0
                   ORDER BY p.last_hba1c DESC"
                : "SELECT p.id, p.patient_code, p.first_name, p.last_name, p.last_hba1c
                   FROM patients p
                   WHERE p.clinic_id = ? 
                     AND p.deleted_at IS NULL 
                     AND p.last_hba1c >= 9.0
                   ORDER BY p.last_hba1c DESC
                   LIMIT 5";
            
            $highHba1c = Database::query($highHba1cQuery, [$clinicId]);

            // Find patients with no recent appointments (>90 days)
            $noRecentVisitQuery = Database::isSqlServer()
                ? "SELECT TOP 5 p.id, p.patient_code, p.first_name, p.last_name, 
                       p.last_visit_date
                   FROM patients p
                   WHERE p.clinic_id = ?
                     AND p.deleted_at IS NULL
                     AND p.status = 'Active'
                     AND (p.last_visit_date IS NULL OR p.last_visit_date < DATEADD(DAY, -90, CAST(GETDATE() AS DATE)))
                   ORDER BY p.last_visit_date ASC"
                : "SELECT p.id, p.patient_code, p.first_name, p.last_name, 
                       p.last_visit_date
                   FROM patients p
                   WHERE p.clinic_id = ?
                     AND p.deleted_at IS NULL
                     AND p.status = 'Active'
                     AND (p.last_visit_date IS NULL OR p.last_visit_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY))
                   ORDER BY p.last_visit_date ASC
                   LIMIT 5";
            
            $noRecentVisit = Database::query($noRecentVisitQuery, [$clinicId]);

            // Find critical lab results from last 7 days
            $criticalLabsQuery = Database::isSqlServer()
                ? "SELECT TOP 5 l.id, l.test_name, l.result_value, l.unit, l.test_date,
                       p.patient_code, p.first_name, p.last_name
                   FROM lab_results l
                   JOIN patients p ON p.id = l.patient_id
                   WHERE l.clinic_id = ?
                     AND l.status IN ('Critical', 'Abnormal', 'Pending')
                     AND l.test_date >= DATEADD(DAY, -7, CAST(GETDATE() AS DATE))
                     AND p.deleted_at IS NULL
                     AND (l.test_name = 'HbA1c' OR l.test_name LIKE '%Glucose%')
                   ORDER BY l.test_date DESC"
                : "SELECT l.id, l.test_name, l.result_value, l.unit, l.test_date,
                       p.patient_code, p.first_name, p.last_name
                   FROM lab_results l
                   JOIN patients p ON p.id = l.patient_id
                   WHERE l.clinic_id = ?
                     AND l.status IN ('Critical', 'Abnormal', 'Pending')
                     AND l.test_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                     AND p.deleted_at IS NULL
                     AND (l.test_name = 'HbA1c' OR l.test_name LIKE '%Glucose%')
                   ORDER BY l.test_date DESC
                   LIMIT 5";
            
            $criticalLabs = Database::query($criticalLabsQuery, [$clinicId]);

            return Response::json([
                'alerts' => [
                    'high_hba1c' => !empty($highHba1c) ? array_map(function($p) {
                        return [
                            'patient_id' => (int) $p['id'],
                            'patient_code' => $p['patient_code'],
                            'patient_name' => $p['first_name'] . ' ' . $p['last_name'],
                            'hba1c' => (float) $p['last_hba1c'],
                            'severity' => $p['last_hba1c'] >= 10.0 ? 'critical' : 'warning',
                            'message' => sprintf('HbA1c at %.1f%% - Immediate attention needed', $p['last_hba1c']),
                        ];
                    }, $highHba1c) : [],
                    
                    'no_recent_visit' => !empty($noRecentVisit) ? array_map(function($p) {
                        $days = $p['last_visit_date'] 
                            ? (new \DateTime())->diff(new \DateTime($p['last_visit_date']))->days
                            : null;
                        return [
                            'patient_id' => (int) $p['id'],
                            'patient_code' => $p['patient_code'],
                            'patient_name' => $p['first_name'] . ' ' . $p['last_name'],
                            'last_visit' => $p['last_visit_date'],
                            'days_since_visit' => $days,
                            'severity' => 'warning',
                            'message' => $p['last_visit_date'] 
                                ? sprintf('No visit in %d days - Follow-up recommended', $days)
                                : 'No recorded visits - Schedule appointment',
                        ];
                    }, $noRecentVisit) : [],
                    
                    'critical_labs' => !empty($criticalLabs) ? array_map(function($l) {
                        return [
                            'lab_result_id' => (int) $l['id'],
                            'patient_code' => $l['patient_code'],
                            'patient_name' => $l['first_name'] . ' ' . $l['last_name'],
                            'test_name' => $l['test_name'],
                            'result' => $l['result_value'] . ' ' . $l['unit'],
                            'test_date' => $l['test_date'],
                            'severity' => 'critical',
                            'message' => sprintf('Critical %s result: %s %s', 
                                $l['test_name'], $l['result_value'], $l['unit']),
                        ];
                    }, $criticalLabs) : [],
                ],
                'total_alerts' => count($highHba1c) + count($noRecentVisit) + count($criticalLabs),
            ]);
        } catch (\Exception $e) {
            error_log("Dashboard critical-alerts error: " . $e->getMessage());
            return Response::json([
                'alerts' => [
                    'high_hba1c' => [],
                    'no_recent_visit' => [],
                    'critical_labs' => [],
                ],
                'total_alerts' => 0,
            ]);
        }
    }

    /**
     * GET /api/dashboard/hba1c-trends
     * 
     * Get HbA1c trend data for charts.
     */
    public function hba1cTrends(Request $request): Response
    {
        try {
            $clinicId = $request->clinicId;
            $months = (int) $request->query('months', 6);
            $months = max(1, min(24, $months)); // Limit to 1-24 months

            // Monthly average HbA1c for the clinic
            $query = Database::isSqlServer()
                ? "SELECT 
                        FORMAT(l.test_date, 'yyyy-MM') as month,
                        FORMAT(l.test_date, 'MMM yyyy') as month_label,
                        AVG(CAST(l.result_value AS DECIMAL(4,1))) as avg_hba1c,
                        COUNT(*) as test_count
                   FROM lab_results l
                   JOIN patients p ON p.id = l.patient_id
                   WHERE l.clinic_id = ?
                     AND l.test_name = 'HbA1c'
                     AND l.test_date >= DATEADD(MONTH, -?, CAST(GETDATE() AS DATE))
                     AND p.deleted_at IS NULL
                   GROUP BY FORMAT(l.test_date, 'yyyy-MM'), FORMAT(l.test_date, 'MMM yyyy')
                   ORDER BY month ASC"
                : "SELECT 
                        DATE_FORMAT(l.test_date, '%Y-%m') as month,
                        DATE_FORMAT(l.test_date, '%b %Y') as month_label,
                        AVG(CAST(l.result_value AS DECIMAL(4,1))) as avg_hba1c,
                        COUNT(*) as test_count
                   FROM lab_results l
                   JOIN patients p ON p.id = l.patient_id
                   WHERE l.clinic_id = ?
                     AND l.test_name = 'HbA1c'
                     AND l.test_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                     AND p.deleted_at IS NULL
                   GROUP BY DATE_FORMAT(l.test_date, '%Y-%m'), DATE_FORMAT(l.test_date, '%b %Y')
                   ORDER BY month ASC";

            $trends = Database::query($query, [$clinicId, $months]);

            return Response::json([
                'trends' => !empty($trends) ? array_map(function($t) {
                    return [
                        'month' => $t['month'],
                        'label' => $t['month_label'],
                        'average_hba1c' => round((float) $t['avg_hba1c'], 1),
                        'test_count' => (int) $t['test_count'],
                    ];
                }, $trends) : [],
                'period_months' => $months,
            ]);
        } catch (\Exception $e) {
            error_log("Dashboard hba1c-trends error: " . $e->getMessage());
            return Response::json([
                'trends' => [],
                'period_months' => $months ?? 6,
            ]);
        }
    }
    
    /**
     * GET /api/dashboard/chart-data
     * 
     * Get all chart data for dashboard visualizations.
     * Returns data formatted for Chart.js integration.
     */
    public function chartData(Request $request): Response
    {
        try {
            $clinicId = $request->clinicId;
            
            // HbA1c Distribution (for pie/doughnut chart)
            $hba1cDistribution = Database::queryOne("
                SELECT 
                    SUM(CASE WHEN last_hba1c < 7.0 THEN 1 ELSE 0 END) as well_controlled,
                    SUM(CASE WHEN last_hba1c >= 7.0 AND last_hba1c < 8.0 THEN 1 ELSE 0 END) as moderate,
                    SUM(CASE WHEN last_hba1c >= 8.0 AND last_hba1c < 9.0 THEN 1 ELSE 0 END) as poor,
                    SUM(CASE WHEN last_hba1c >= 9.0 THEN 1 ELSE 0 END) as very_poor
                FROM patients 
                WHERE clinic_id = ? AND deleted_at IS NULL AND last_hba1c IS NOT NULL
            ", [$clinicId]);
            
            // Diabetes Type Distribution (for pie chart)
            $diabetesTypes = Database::queryOne("
                SELECT 
                    SUM(CASE WHEN diabetes_type = 'Type 1' THEN 1 ELSE 0 END) as type1,
                    SUM(CASE WHEN diabetes_type = 'Type 2' THEN 1 ELSE 0 END) as type2,
                    SUM(CASE WHEN diabetes_type IN ('Pre-diabetic', 'Pre-diabetes') THEN 1 ELSE 0 END) as prediabetic,
                    SUM(CASE WHEN diabetes_type = 'Gestational' THEN 1 ELSE 0 END) as gestational
                FROM patients 
                WHERE clinic_id = ? AND deleted_at IS NULL
            ", [$clinicId]);
            
            // Appointments by status this week (for bar chart)
            $appointmentStatusQuery = Database::isSqlServer()
                ? "SELECT status, COUNT(*) as count
                   FROM appointments 
                   WHERE clinic_id = ?
                     AND scheduled_at >= DATEADD(DAY, -7, CAST(GETDATE() AS DATE))
                   GROUP BY status"
                : "SELECT status, COUNT(*) as count
                   FROM appointments 
                   WHERE clinic_id = ?
                     AND scheduled_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                   GROUP BY status";
            
            $appointmentStatus = Database::query($appointmentStatusQuery, [$clinicId]);
            
            // Monthly appointments trend (for line chart)
            $appointmentTrendQuery = Database::isSqlServer()
                ? "SELECT 
                        FORMAT(scheduled_at, 'yyyy-MM') as month,
                        FORMAT(scheduled_at, 'MMM') as month_label,
                        COUNT(*) as total
                   FROM appointments 
                   WHERE clinic_id = ?
                     AND scheduled_at >= DATEADD(MONTH, -6, CAST(GETDATE() AS DATE))
                   GROUP BY FORMAT(scheduled_at, 'yyyy-MM'), FORMAT(scheduled_at, 'MMM')
                   ORDER BY month ASC"
                : "SELECT 
                        DATE_FORMAT(scheduled_at, '%Y-%m') as month,
                        DATE_FORMAT(scheduled_at, '%b') as month_label,
                        COUNT(*) as total
                   FROM appointments 
                   WHERE clinic_id = ?
                     AND scheduled_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                   GROUP BY DATE_FORMAT(scheduled_at, '%Y-%m'), DATE_FORMAT(scheduled_at, '%b')
                   ORDER BY month ASC";
            
            $appointmentTrend = Database::query($appointmentTrendQuery, [$clinicId]);
            
            // Format for Chart.js
            return Response::json([
                'hba1c_distribution' => [
                    'labels' => ['Well Controlled (<7%)', 'Moderate (7-8%)', 'Poor (8-9%)', 'Very Poor (≥9%)'],
                    'data' => [
                        (int) ($hba1cDistribution['well_controlled'] ?? 0),
                        (int) ($hba1cDistribution['moderate'] ?? 0),
                        (int) ($hba1cDistribution['poor'] ?? 0),
                        (int) ($hba1cDistribution['very_poor'] ?? 0),
                    ],
                    'backgroundColor' => ['#22c55e', '#f59e0b', '#f97316', '#ef4444'],
                ],
                'diabetes_types' => [
                    'labels' => ['Type 1', 'Type 2', 'Pre-diabetic', 'Gestational'],
                    'data' => [
                        (int) ($diabetesTypes['type1'] ?? 0),
                        (int) ($diabetesTypes['type2'] ?? 0),
                        (int) ($diabetesTypes['prediabetic'] ?? 0),
                        (int) ($diabetesTypes['gestational'] ?? 0),
                    ],
                    'backgroundColor' => ['#3b82f6', '#8b5cf6', '#ec4899', '#06b6d4'],
                ],
                'appointment_status' => [
                    'labels' => array_column($appointmentStatus, 'status'),
                    'data' => array_map('intval', array_column($appointmentStatus, 'count')),
                ],
                'appointment_trend' => [
                    'labels' => array_column($appointmentTrend, 'month_label'),
                    'data' => array_map('intval', array_column($appointmentTrend, 'total')),
                ],
            ]);
        } catch (\Exception $e) {
            error_log("Dashboard chart-data error: " . $e->getMessage());
            return Response::json([
                'hba1c_distribution' => ['labels' => [], 'data' => []],
                'diabetes_types' => ['labels' => [], 'data' => []],
                'appointment_status' => ['labels' => [], 'data' => []],
                'appointment_trend' => ['labels' => [], 'data' => []],
            ]);
        }
    }
}
