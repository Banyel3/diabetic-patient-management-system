<?php
/**
 * DiabetaCare - API Client
 * 
 * Handles all communication with the PHP backend API.
 */

declare(strict_types=1);

class ApiClient
{
    private string $baseUrl;
    private ?string $token;

    public function __construct()
    {
        $this->baseUrl = API_BASE_URL;
        $this->token = getToken();
    }

    /**
     * Make a GET request
     */
    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $this->request('GET', $url);
    }

    /**
     * Make a POST request
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $this->baseUrl . $endpoint, $data);
    }

    /**
     * Make a PUT request
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $this->baseUrl . $endpoint, $data);
    }

    /**
     * Make a DELETE request
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $this->baseUrl . $endpoint);
    }

    /**
     * Make HTTP request
     */
    private function request(string $method, string $url, ?array $data = null): array
    {
        $ch = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        if ($data !== null && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'NETWORK_ERROR',
                    'message' => 'Failed to connect to the server: ' . $error,
                ],
            ];
        }

        $decoded = json_decode($response, true) ?? [];

        // Handle token expiration
        if ($httpCode === 401) {
            $errorCode = $decoded['error']['code'] ?? 'UNAUTHORIZED';
            if ($errorCode === 'TOKEN_EXPIRED') {
                clearAuth();
                redirect('/login');
            }
        }

        if ($httpCode >= 400) {
            return [
                'success' => false,
                'error' => $decoded['error'] ?? [
                    'code' => 'API_ERROR',
                    'message' => 'An error occurred',
                ],
                'status' => $httpCode,
            ];
        }

        return array_merge(['success' => true], $decoded);
    }

    // =========================================================================
    // AUTH API
    // =========================================================================

    public function login(string $email, string $password): array
    {
        return $this->post('/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function register(array $data): array
    {
        return $this->post('/auth/register', $data);
    }

    public function logout(): array
    {
        return $this->post('/auth/logout');
    }

    public function me(): array
    {
        return $this->get('/auth/me');
    }

    public function forgotPassword(string $email): array
    {
        return $this->post('/auth/forgot-password', ['email' => $email]);
    }

    public function resetPassword(string $token, string $password): array
    {
        return $this->post('/auth/reset-password', [
            'token' => $token,
            'password' => $password,
        ]);
    }

    // =========================================================================
    // DASHBOARD API
    // =========================================================================

    public function getDashboardSummary(): array
    {
        return $this->get('/dashboard/summary');
    }

    public function getUpcomingAppointments(int $limit = 5): array
    {
        return $this->get('/dashboard/upcoming-appointments', ['limit' => $limit]);
    }

    public function getRecentPatients(int $limit = 5): array
    {
        return $this->get('/dashboard/recent-patients', ['limit' => $limit]);
    }

    public function getCriticalAlerts(): array
    {
        return $this->get('/dashboard/critical-alerts');
    }

    public function getHbA1cTrends(int $months = 6): array
    {
        return $this->get('/dashboard/hba1c-trends', ['months' => $months]);
    }

    public function getChartData(): array
    {
        return $this->get('/dashboard/chart-data');
    }

    // =========================================================================
    // PATIENTS API
    // =========================================================================

    /**
     * Get patient page statistics for dashboard summary cards
     */
    public function getPatientsStats(): array
    {
        return $this->get('/patients/stats');
    }

    /**
     * Get lightweight patient list for dropdowns
     * Returns only: id, patient_code, first_name, last_name, full_name
     */
    public function getPatientList(): array
    {
        return $this->get('/patients/list');
    }

    public function getPatients(array $params = []): array
    {
        return $this->get('/patients', $params);
    }

    public function getPatient(int $id): array
    {
        return $this->get("/patients/{$id}");
    }

    public function createPatient(array $data): array
    {
        return $this->post('/patients', $data);
    }

    public function updatePatient(int $id, array $data): array
    {
        return $this->put("/patients/{$id}", $data);
    }

    public function deletePatient(int $id): array
    {
        return $this->delete("/patients/{$id}");
    }

    // =========================================================================
    // APPOINTMENTS API
    // =========================================================================

    /**
     * Get appointment page statistics for dashboard summary cards
     */
    public function getAppointmentsStats(): array
    {
        return $this->get('/appointments/stats');
    }

    public function getAppointments(array $params = []): array
    {
        return $this->get('/appointments', $params);
    }

    public function getAppointment(int $id): array
    {
        return $this->get("/appointments/{$id}");
    }

    public function createAppointment(array $data): array
    {
        return $this->post('/appointments', $data);
    }

    public function updateAppointment(int $id, array $data): array
    {
        return $this->put("/appointments/{$id}", $data);
    }

    public function deleteAppointment(int $id): array
    {
        return $this->delete("/appointments/{$id}");
    }

    // =========================================================================
    // MEDICATIONS API
    // =========================================================================

    /**
     * Get medication page statistics for dashboard summary cards
     */
    public function getMedicationsStats(): array
    {
        return $this->get('/medications/stats');
    }

    public function getMedications(array $params = []): array
    {
        return $this->get('/medications', $params);
    }

    public function getMedication(int $id): array
    {
        return $this->get("/medications/{$id}");
    }

    public function createMedication(array $data): array
    {
        return $this->post('/medications', $data);
    }

    public function updateMedication(int $id, array $data): array
    {
        return $this->put("/medications/{$id}", $data);
    }

    public function deleteMedication(int $id): array
    {
        return $this->delete("/medications/{$id}");
    }

    // =========================================================================
    // LAB RESULTS API
    // =========================================================================

    /**
     * Get lab results page statistics for dashboard summary cards
     */
    public function getLabResultsStats(): array
    {
        return $this->get('/lab-results/stats');
    }

    public function getLabResults(array $params = []): array
    {
        return $this->get('/lab-results', $params);
    }

    public function getLabResult(int $id): array
    {
        return $this->get("/lab-results/{$id}");
    }

    public function createLabResult(array $data): array
    {
        return $this->post('/lab-results', $data);
    }

    public function updateLabResult(int $id, array $data): array
    {
        return $this->put("/lab-results/{$id}", $data);
    }

    public function deleteLabResult(int $id): array
    {
        return $this->delete("/lab-results/{$id}");
    }

    public function getTestTypes(): array
    {
        return $this->get('/lab-results/test-types');
    }

    // =========================================================================
    // USER API
    // =========================================================================

    public function updateProfile(array $data): array
    {
        return $this->put('/users/me', $data);
    }

    public function updatePassword(string $currentPassword, string $newPassword): array
    {
        return $this->put('/users/me/password', [
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
        ]);
    }

    public function changePassword(array $data): array
    {
        return $this->put('/users/me/password', $data);
    }
}
