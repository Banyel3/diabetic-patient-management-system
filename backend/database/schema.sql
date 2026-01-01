-- ============================================================================
-- DiabetaCare Database Schema
-- MySQL 8.0+ Compatible
-- 
-- This schema defines the complete data model for the DiabetaCare
-- diabetic clinic management system with optimized indexes for common queries.
-- ============================================================================

-- ============================================================================
-- CLINICS AND ADDRESSES
-- Stores clinic registration information from the multi-step registration flow.
-- ============================================================================

CREATE TABLE IF NOT EXISTS clinics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Clinic Information (Step 1)
    name VARCHAR(255) NOT NULL,
    business_registration_number VARCHAR(100),
    medical_license_number VARCHAR(100),
    phone VARCHAR(50),
    email VARCHAR(255) NOT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    UNIQUE KEY uk_clinics_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index on email for login lookups (already unique, serves as index)


CREATE TABLE IF NOT EXISTS clinic_addresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT UNSIGNED NOT NULL,
    
    -- Address Details (Step 2)
    street_address VARCHAR(500),
    city VARCHAR(100),
    state_province VARCHAR(100),
    zip_postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'United States',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_clinic_addresses_clinic 
        FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    
    -- One address per clinic
    UNIQUE KEY uk_clinic_addresses_clinic (clinic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- USERS
-- Stores user accounts with role-based access control.
-- Users belong to a clinic (multi-tenant isolation).
-- ============================================================================

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT UNSIGNED NOT NULL,
    
    -- User Information
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    password_hash VARCHAR(255) NOT NULL,
    
    -- Role: admin, doctor, nurse, staff
    role ENUM('admin', 'doctor', 'nurse', 'staff') DEFAULT 'staff',
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    email_verified_at TIMESTAMP NULL,
    terms_accepted_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_users_clinic 
        FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    
    -- Unique email globally
    UNIQUE KEY uk_users_email (email),
    
    -- Index for clinic-scoped queries
    INDEX idx_users_clinic_active (clinic_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- AUTH TOKENS
-- Stores JWT refresh tokens or session tokens for token invalidation.
-- ============================================================================

CREATE TABLE IF NOT EXISTS auth_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    
    -- Token identifier (hashed)
    token_hash VARCHAR(255) NOT NULL,
    
    -- Expiration
    expires_at TIMESTAMP NOT NULL,
    
    -- Revocation
    revoked_at TIMESTAMP NULL,
    
    -- Metadata
    user_agent VARCHAR(500),
    ip_address VARCHAR(45),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_auth_tokens_user 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Index for token lookup
    INDEX idx_auth_tokens_hash (token_hash),
    
    -- Index for cleanup of expired tokens
    INDEX idx_auth_tokens_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- PATIENTS
-- Core patient records with diabetes-specific information.
-- All queries are scoped by clinic_id for multi-tenant isolation.
-- ============================================================================

CREATE TABLE IF NOT EXISTS patients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT UNSIGNED NOT NULL,
    
    -- Patient Identifier (clinic-specific, e.g., P001)
    patient_code VARCHAR(20) NOT NULL,
    
    -- Demographics
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    
    -- Contact Information
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    
    -- Diabetes Information
    diabetes_type ENUM('Type 1', 'Type 2', 'Gestational', 'Pre-diabetic') NOT NULL,
    diagnosis_date DATE,
    
    -- Latest Clinical Values (denormalized for dashboard performance)
    -- Updated via triggers or application logic when lab results are added
    last_hba1c DECIMAL(4,2),
    last_hba1c_date DATE,
    last_visit_date DATE,
    
    -- Status
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    
    -- Notes
    notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL, -- Soft delete support
    
    -- Foreign Keys
    CONSTRAINT fk_patients_clinic 
        FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    
    -- Unique patient code per clinic
    UNIQUE KEY uk_patients_clinic_code (clinic_id, patient_code),
    
    -- =========================================================================
    -- INDEXES FOR PATIENTS
    -- 
    -- idx_patients_clinic_type: Supports filtering patients by diabetes type
    -- within a clinic. Used in patient list with type filter dropdown.
    -- 
    -- idx_patients_clinic_status: Supports filtering by active/inactive status.
    -- Commonly used to show only active patients in listings.
    -- 
    -- idx_patients_clinic_last_visit: Supports sorting and filtering by
    -- last visit date for identifying patients overdue for appointments.
    -- 
    -- idx_patients_search: Composite index for text search operations.
    -- Optimizes queries that search by first_name, last_name, or phone.
    -- =========================================================================
    INDEX idx_patients_clinic_type (clinic_id, diabetes_type),
    INDEX idx_patients_clinic_status (clinic_id, status),
    INDEX idx_patients_clinic_last_visit (clinic_id, last_visit_date),
    INDEX idx_patients_clinic_deleted (clinic_id, deleted_at),
    INDEX idx_patients_search (clinic_id, first_name, last_name, phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- APPOINTMENTS
-- Appointment scheduling with status tracking.
-- ============================================================================

CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    
    -- Scheduling
    scheduled_at DATETIME NOT NULL,
    duration_minutes INT UNSIGNED DEFAULT 30,
    
    -- Appointment Type
    type ENUM('Check-up', 'Follow-up', 'Lab Review', 'Consultation', 'New Patient') 
        DEFAULT 'Check-up',
    
    -- Status
    status ENUM('Scheduled', 'Completed', 'Cancelled', 'No-show') 
        DEFAULT 'Scheduled',
    
    -- Notes
    notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_appointments_clinic 
        FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_patient 
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    -- =========================================================================
    -- INDEXES FOR APPOINTMENTS
    -- 
    -- idx_appointments_clinic_scheduled: Primary index for fetching appointments
    -- by date. Used for dashboard "today's appointments" and calendar views.
    -- Covers: WHERE clinic_id = ? AND scheduled_at BETWEEN ? AND ?
    -- 
    -- idx_appointments_clinic_status: Supports filtering by status in the
    -- appointments list page. Used with the status filter dropdown.
    -- 
    -- idx_appointments_patient: Supports viewing all appointments for a
    -- specific patient in patient detail views.
    -- =========================================================================
    INDEX idx_appointments_clinic_scheduled (clinic_id, scheduled_at),
    INDEX idx_appointments_clinic_status (clinic_id, status),
    INDEX idx_appointments_patient (patient_id, scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- MEDICATIONS
-- Patient medication prescriptions with active/discontinued status.
-- ============================================================================

CREATE TABLE IF NOT EXISTS medications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    
    -- Medication Details
    name VARCHAR(255) NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    
    -- Date Range
    start_date DATE NOT NULL,
    end_date DATE,
    
    -- Status
    status ENUM('Active', 'Discontinued') DEFAULT 'Active',
    
    -- Notes
    notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_medications_clinic 
        FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    CONSTRAINT fk_medications_patient 
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    -- =========================================================================
    -- INDEXES FOR MEDICATIONS
    -- 
    -- idx_medications_clinic_status: Supports filtering medications by status.
    -- Used to show only active prescriptions or include discontinued ones.
    -- 
    -- idx_medications_patient_status: Supports viewing active medications
    -- for a specific patient. Used in patient detail views.
    -- 
    -- idx_medications_search: Supports searching medications by name.
    -- =========================================================================
    INDEX idx_medications_clinic_status (clinic_id, status),
    INDEX idx_medications_clinic_patient (clinic_id, patient_id, status),
    INDEX idx_medications_search (clinic_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- LAB RESULTS
-- Patient lab test results with diabetes-specific test types.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_results (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    
    -- Test Information
    test_type VARCHAR(100) NOT NULL,
    test_value DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    reference_range VARCHAR(100),
    
    -- Test Date
    test_date DATE NOT NULL,
    
    -- Review Status
    status ENUM('Pending Review', 'Reviewed') DEFAULT 'Pending Review',
    
    -- Notes
    notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_lab_results_clinic 
        FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    CONSTRAINT fk_lab_results_patient 
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    -- =========================================================================
    -- INDEXES FOR LAB RESULTS
    -- 
    -- idx_lab_results_clinic_date: Primary index for date-based queries.
    -- Supports listing recent lab results in date order.
    -- 
    -- idx_lab_results_clinic_status: Supports filtering by pending/reviewed.
    -- Critical for the "Pending Lab Results" dashboard count.
    -- 
    -- idx_lab_results_clinic_type: Supports filtering by test type.
    -- Used in lab results page type dropdown.
    -- 
    -- idx_lab_results_patient_date: Supports viewing lab history for a patient.
    -- =========================================================================
    INDEX idx_lab_results_clinic_date (clinic_id, test_date),
    INDEX idx_lab_results_clinic_status (clinic_id, status),
    INDEX idx_lab_results_clinic_type (clinic_id, test_type),
    INDEX idx_lab_results_patient_date (patient_id, test_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- AUDIT LOG (Optional - for HIPAA compliance)
-- Tracks all data access and modifications for compliance.
-- ============================================================================

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT UNSIGNED,
    user_id INT UNSIGNED,
    
    -- Action Details
    action VARCHAR(50) NOT NULL, -- create, read, update, delete
    entity_type VARCHAR(50) NOT NULL, -- patient, appointment, etc.
    entity_id INT UNSIGNED,
    
    -- Change Data (JSON)
    old_values JSON,
    new_values JSON,
    
    -- Request Context
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    
    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes for audit queries
    INDEX idx_audit_clinic_date (clinic_id, created_at),
    INDEX idx_audit_user_date (user_id, created_at),
    INDEX idx_audit_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- STORED PROCEDURE: Update Patient Last HbA1c
-- Called after inserting/updating HbA1c lab results to denormalize the value.
-- ============================================================================

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS update_patient_last_hba1c(IN p_patient_id INT UNSIGNED)
BEGIN
    UPDATE patients p
    SET 
        last_hba1c = (
            SELECT lr.test_value 
            FROM lab_results lr 
            WHERE lr.patient_id = p_patient_id 
              AND lr.test_type = 'HbA1c'
            ORDER BY lr.test_date DESC 
            LIMIT 1
        ),
        last_hba1c_date = (
            SELECT lr.test_date 
            FROM lab_results lr 
            WHERE lr.patient_id = p_patient_id 
              AND lr.test_type = 'HbA1c'
            ORDER BY lr.test_date DESC 
            LIMIT 1
        )
    WHERE p.id = p_patient_id;
END //

DELIMITER ;


-- ============================================================================
-- TRIGGER: Auto-update patient's last HbA1c when lab result is added
-- ============================================================================

DELIMITER //

CREATE TRIGGER IF NOT EXISTS trg_lab_results_after_insert
AFTER INSERT ON lab_results
FOR EACH ROW
BEGIN
    IF NEW.test_type = 'HbA1c' THEN
        CALL update_patient_last_hba1c(NEW.patient_id);
    END IF;
END //

CREATE TRIGGER IF NOT EXISTS trg_lab_results_after_update
AFTER UPDATE ON lab_results
FOR EACH ROW
BEGIN
    IF NEW.test_type = 'HbA1c' OR OLD.test_type = 'HbA1c' THEN
        CALL update_patient_last_hba1c(NEW.patient_id);
    END IF;
END //

DELIMITER ;
