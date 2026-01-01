<?php
/**
 * DiabetaCare - Dashboard Controller
 * 
 * Aggregated statistics and KPIs for the clinic dashboard.
 * Uses Common Table Expressions (CTEs) for optimized complex queries.
 */

declare(strict_types=1);

namespace DiabetaCare\Controllers;

use DiabetaCare\Core\Request;
use DiabetaCare\Core\Response;
use DiabetaCare\Core\Database;

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
        
        // =========================================================================
        // DASHBOARD QUERY OPTIMIZATION
        // 
        // This query uses CTEs (WITH clause) to efficiently calculate multiple
        // statistics in a single database round-trip. Each CTE is optimized with
        // appropriate indexes as documented below.
        // 
        // Performance Note: CTEs in MySQL 8.0+ are materialized by default,
        // meaning each subquery runs once and results are cached.
        // =========================================================================

        $stats = Database::queryOne("
            WITH 
            -- Patient Statistics (uses: idx_patients_clinic_status)
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
            
            -- Today's Appointments (uses: idx_appointments_clinic_scheduled)
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
            
            -- This Week's Appointments
            week_appointments AS (
                SELECT COUNT(*) as total_week
                FROM appointments 
                WHERE clinic_id = ? 
                  AND scheduled_at >= DATE_SUB(?, INTERVAL WEEKDAY(?) DAY)
                  AND scheduled_at < DATE_ADD(DATE_SUB(?, INTERVAL WEEKDAY(?) DAY), INTERVAL 7 DAY)
            ),
            
            -- HbA1c Control Analysis (uses: idx_lab_results_hba1c via patients.last_hba1c)
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
            
            -- Active Medications (uses: idx_medications_patient)
            medication_stats AS (
                SELECT COUNT(DISTINCT m.id) as active_medications,
                       COUNT(DISTINCT m.patient_id) as patients_on_meds
                FROM medications m
                JOIN patients p ON p.id = m.patient_id
                WHERE m.clinic_id = ? AND m.status = 'active' AND p.deleted_at IS NULL
            ),
            
            -- Recent Lab Results (last 30 days)
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

        // Transform to frontend-friendly format
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
        $clinicId = $request->clinicId;
        $now = date('Y-m-d H:i:s');

        $appointments = Database::query("
            SELECT a.id, a.scheduled_at, a.type, a.status, a.duration_minutes,
                   p.patient_code, p.first_name, p.last_name
            FROM appointments a
            JOIN patients p ON p.id = a.patient_id
            WHERE a.clinic_id = ? 
              AND a.scheduled_at >= ?
              AND a.status = 'Scheduled'
              AND p.deleted_at IS NULL
            ORDER BY a.scheduled_at ASC
            LIMIT 10
        ", [$clinicId, $now]);

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
    }

    /**
     * GET /api/dashboard/recent-patients
     * 
     * Get recently added or updated patients.
     */
    public function recentPatients(Request $request): Response
    {
        $clinicId = $request->clinicId;

        $patients = Database::query("
            SELECT id, patient_code, first_name, last_name, diabetes_type, 
                   status, last_hba1c, created_at, updated_at
            FROM patients
            WHERE clinic_id = ? AND deleted_at IS NULL
            ORDER BY COALESCE(updated_at, created_at) DESC
            LIMIT 5
        ", [$clinicId]);

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
    }

    /**
     * GET /api/dashboard/critical-alerts
     * 
     * Get patients with critical lab values or other alerts.
     */
    public function criticalAlerts(Request $request): Response
    {
        $clinicId = $request->clinicId;

        // Find patients with high HbA1c (>= 9.0%)
        $highHba1c = Database::query("
            SELECT p.id, p.patient_code, p.first_name, p.last_name, p.last_hba1c
            FROM patients p
            WHERE p.clinic_id = ? 
              AND p.deleted_at IS NULL 
              AND p.last_hba1c >= 9.0
            ORDER BY p.last_hba1c DESC
            LIMIT 5
        ", [$clinicId]);

        // Find patients with no recent appointments (>90 days)
        $noRecentVisit = Database::query("
            SELECT p.id, p.patient_code, p.first_name, p.last_name, 
                   p.last_visit_date
            FROM patients p
            WHERE p.clinic_id = ?
              AND p.deleted_at IS NULL
              AND p.status = 'Active'
              AND (p.last_visit_date IS NULL OR p.last_visit_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY))
            ORDER BY p.last_visit_date ASC
            LIMIT 5
        ", [$clinicId]);

        // Find critical lab results from last 7 days
        $criticalLabs = Database::query("
            SELECT l.id, l.test_name, l.result_value, l.unit, l.test_date,
                   p.patient_code, p.first_name, p.last_name
            FROM lab_results l
            JOIN patients p ON p.id = l.patient_id
            WHERE l.clinic_id = ?
              AND l.status = 'Critical'
              AND l.test_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              AND p.deleted_at IS NULL
            ORDER BY l.test_date DESC
            LIMIT 5
        ", [$clinicId]);

        return Response::json([
            'alerts' => [
                'high_hba1c' => array_map(function($p) {
                    return [
                        'patient_id' => (int) $p['id'],
                        'patient_code' => $p['patient_code'],
                        'patient_name' => $p['first_name'] . ' ' . $p['last_name'],
                        'hba1c' => (float) $p['last_hba1c'],
                        'severity' => $p['last_hba1c'] >= 10.0 ? 'critical' : 'warning',
                        'message' => sprintf('HbA1c at %.1f%% - Immediate attention needed', $p['last_hba1c']),
                    ];
                }, $highHba1c),
                
                'no_recent_visit' => array_map(function($p) {
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
                }, $noRecentVisit),
                
                'critical_labs' => array_map(function($l) {
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
                }, $criticalLabs),
            ],
            'total_alerts' => count($highHba1c) + count($noRecentVisit) + count($criticalLabs),
        ]);
    }

    /**
     * GET /api/dashboard/hba1c-trends
     * 
     * Get HbA1c trend data for charts.
     */
    public function hba1cTrends(Request $request): Response
    {
        $clinicId = $request->clinicId;
        $months = (int) $request->query('months', 6);
        $months = max(1, min(24, $months)); // Limit to 1-24 months

        // Monthly average HbA1c for the clinic
        $trends = Database::query("
            SELECT 
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
            ORDER BY month ASC
        ", [$clinicId, $months]);

        return Response::json([
            'trends' => array_map(function($t) {
                return [
                    'month' => $t['month'],
                    'label' => $t['month_label'],
                    'average_hba1c' => round((float) $t['avg_hba1c'], 1),
                    'test_count' => (int) $t['test_count'],
                ];
            }, $trends),
            'period_months' => $months,
        ]);
    }
}
