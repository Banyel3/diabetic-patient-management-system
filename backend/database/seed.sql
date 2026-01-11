-- ============================================================================
-- DiabetaCare - Sample Data for Development
-- 
-- Creates a sample clinic with users, patients, appointments, medications,
-- and lab results for testing the application.
-- ============================================================================

-- Insert sample clinic
INSERT INTO clinics (id, name, business_registration_number, medical_license_number, phone, email) VALUES
(1, 'DiabetaCare Clinic - Downtown', 'BRN-2024-001234', 'MLC-DC-56789', '(555) 123-4567', 'admin@diabetacare-downtown.com');

-- Insert clinic address
INSERT INTO clinic_addresses (clinic_id, street_address, city, state_province, zip_postal_code, country) VALUES
(1, '123 Medical Center Drive, Suite 400', 'New York', 'NY', '10001', 'United States');

-- Insert admin user (password: Password123!)
INSERT INTO users (id, clinic_id, first_name, last_name, email, phone, password_hash, role, is_active, terms_accepted_at) VALUES
(1, 1, 'John', 'Smith', 'dr.smith@diabetacare-downtown.com', '(555) 123-4568', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
 'admin', TRUE, NOW());

-- Insert sample patients
INSERT INTO patients (id, clinic_id, patient_code, first_name, last_name, date_of_birth, gender, phone, email, address, diabetes_type, diagnosis_date, last_hba1c, last_hba1c_date, last_visit_date, status, notes) VALUES
(1, 1, 'P001', 'Sarah', 'Connor', '1985-03-15', 'female', '(555) 234-5678', 'sarah.connor@email.com', '456 Oak Street, Apt 12, New York, NY 10002', 'Type 2', '2020-06-15', 7.2, '2025-12-28', '2025-12-28', 'Active', 'Regular follow-up patient. Good medication compliance.'),
(2, 1, 'P002', 'John', 'Smith', '1972-07-22', 'male', '(555) 345-6789', 'john.smith@email.com', '789 Pine Avenue, New York, NY 10003', 'Type 1', '2015-03-20', 8.5, '2025-12-20', '2025-12-20', 'Active', 'On insulin therapy. Requires regular monitoring.'),
(3, 1, 'P003', 'Emily', 'Chen', '1990-11-08', 'female', '(555) 456-7890', 'emily.chen@email.com', '321 Maple Lane, Brooklyn, NY 11201', 'Gestational', '2025-08-10', 6.4, '2025-12-15', '2025-12-15', 'Active', 'Currently pregnant. Gestational diabetes diagnosed at 24 weeks.'),
(4, 1, 'P004', 'Michael', 'Brown', '1968-05-30', 'male', '(555) 567-8901', 'michael.brown@email.com', '654 Elm Street, Queens, NY 11375', 'Type 2', '2018-09-05', 6.8, '2025-12-10', '2025-12-10', 'Active', 'Well-controlled with oral medication.'),
(5, 1, 'P005', 'Lisa', 'Park', '1995-02-14', 'female', '(555) 678-9012', 'lisa.park@email.com', '987 Cedar Road, Bronx, NY 10451', 'Pre-diabetic', '2025-06-20', 5.9, '2025-12-20', '2025-12-20', 'Active', 'Lifestyle modification program. Weight loss in progress.'),
(6, 1, 'P006', 'David', 'Wilson', '1980-09-18', 'male', '(555) 789-0123', 'david.wilson@email.com', '147 Birch Drive, Staten Island, NY 10301', 'Type 2', '2019-04-12', 7.8, '2025-11-15', '2025-11-15', 'Active', 'Recently started on combination therapy.'),
(7, 1, 'P007', 'Jennifer', 'Garcia', '1988-12-03', 'female', '(555) 890-1234', 'jennifer.garcia@email.com', '258 Walnut Street, Manhattan, NY 10016', 'Type 1', '2010-08-25', 7.0, '2025-12-05', '2025-12-05', 'Active', 'Uses insulin pump. Excellent self-management skills.'),
(8, 1, 'P008', 'Robert', 'Martinez', '1955-06-28', 'male', '(555) 901-2345', 'robert.martinez@email.com', '369 Spruce Lane, Brooklyn, NY 11215', 'Type 2', '2012-11-30', 9.2, '2025-12-01', '2025-12-01', 'Active', 'Struggling with adherence. Consider diabetes education.'),
(9, 1, 'P009', 'Amanda', 'Taylor', '1978-04-09', 'female', '(555) 012-3456', 'amanda.taylor@email.com', '741 Ash Court, Queens, NY 11432', 'Type 2', '2021-02-14', 6.5, '2025-11-20', '2025-11-20', 'Active', 'Diet-controlled. Making good progress.'),
(10, 1, 'P010', 'James', 'Anderson', '1962-08-17', 'male', '(555) 123-4560', 'james.anderson@email.com', '852 Hickory Road, Bronx, NY 10467', 'Type 2', '2016-07-08', 8.1, '2025-10-25', '2025-10-25', 'Inactive', 'Transferred to another clinic.');

-- Insert sample appointments
INSERT INTO appointments (id, clinic_id, patient_id, scheduled_at, duration_minutes, type, status, notes) VALUES
-- Today's appointments (2026-01-01)
(1, 1, 1, '2026-01-01 09:00:00', 30, 'Follow-up', 'Scheduled', 'Quarterly check-up'),
(2, 1, 2, '2026-01-01 09:30:00', 30, 'Lab Review', 'Scheduled', 'Review recent HbA1c results'),
(3, 1, 3, '2026-01-01 10:00:00', 45, 'Check-up', 'Scheduled', 'Gestational diabetes monitoring'),
(4, 1, 4, '2026-01-01 11:00:00', 30, 'Consultation', 'Scheduled', 'Discuss medication adjustment'),
(5, 1, 5, '2026-01-01 14:00:00', 30, 'Follow-up', 'Scheduled', 'Weight loss progress review'),
(6, 1, 6, '2026-01-01 14:30:00', 30, 'Lab Review', 'Scheduled', NULL),
(7, 1, 7, '2026-01-01 15:00:00', 30, 'Check-up', 'Scheduled', 'Insulin pump check'),
(8, 1, 8, '2026-01-01 16:00:00', 45, 'Consultation', 'Scheduled', 'Diabetes education session'),
-- Past appointments
(9, 1, 1, '2025-12-28 09:00:00', 30, 'Check-up', 'Completed', 'Regular check-up completed'),
(10, 1, 2, '2025-12-20 10:30:00', 30, 'Follow-up', 'Completed', NULL),
(11, 1, 5, '2025-12-20 11:00:00', 30, 'New Patient', 'Completed', 'Initial consultation'),
-- Future appointments
(12, 1, 1, '2026-01-15 09:00:00', 30, 'Follow-up', 'Scheduled', NULL),
(13, 1, 3, '2026-01-08 10:00:00', 30, 'Check-up', 'Scheduled', NULL),
(14, 1, 9, '2026-01-05 14:00:00', 30, 'Follow-up', 'Scheduled', 'Diet review');

-- ============================================================================
-- BULK APPOINTMENTS DATA (2000 records)
-- Generates appointments connected to existing patients
-- ============================================================================

-- Generate 2000 appointments for existing patients
SET NOCOUNT ON;

DECLARE @i INT = 15;  -- Start after manual inserts
DECLARE @patient_id INT;
DECLARE @scheduled_at DATETIME2;
DECLARE @type NVARCHAR(20);
DECLARE @status NVARCHAR(20);
DECLARE @duration INT;
DECLARE @notes NVARCHAR(MAX);

WHILE @i <= 2014
BEGIN
    -- Random patient (1 to 2011)
    SET @patient_id = ABS(CHECKSUM(NEWID())) % 2011 + 1;
    
    -- Random date between 6 months ago and 3 months from now
    SET @scheduled_at = DATEADD(DAY, ABS(CHECKSUM(NEWID())) % 270 - 180, GETDATE());
    SET @scheduled_at = DATEADD(HOUR, 8 + (ABS(CHECKSUM(NEWID())) % 10), CAST(CAST(@scheduled_at AS DATE) AS DATETIME2));
    SET @scheduled_at = DATEADD(MINUTE, (ABS(CHECKSUM(NEWID())) % 4) * 15, @scheduled_at);
    
    -- Random type
    SET @type = CASE ABS(CHECKSUM(NEWID())) % 5
        WHEN 0 THEN 'Check-up'
        WHEN 1 THEN 'Follow-up'
        WHEN 2 THEN 'Lab Review'
        WHEN 3 THEN 'Consultation'
        ELSE 'New Patient'
    END;
    
    -- Status based on date
    IF @scheduled_at < GETDATE()
        SET @status = CASE ABS(CHECKSUM(NEWID())) % 3
            WHEN 0 THEN 'Completed'
            WHEN 1 THEN 'Cancelled'
            ELSE 'No-show'
        END;
    ELSE
        SET @status = 'Scheduled';
    
    -- Random duration (15, 30, 45, 60)
    SET @duration = (ABS(CHECKSUM(NEWID())) % 4 + 1) * 15;
    
    -- Random notes
    SET @notes = CASE ABS(CHECKSUM(NEWID())) % 5
        WHEN 0 THEN 'Regular diabetes management check'
        WHEN 1 THEN 'HbA1c review and medication adjustment'
        WHEN 2 THEN 'Blood pressure and glucose monitoring'
        WHEN 3 THEN 'Diet and exercise counseling'
        ELSE NULL
    END;
    
    INSERT INTO appointments (clinic_id, patient_id, scheduled_at, duration_minutes, type, status, notes, created_at, updated_at)
    VALUES (1, @patient_id, @scheduled_at, @duration, @type, @status, @notes, DATEADD(DAY, -ABS(CHECKSUM(NEWID())) % 30, @scheduled_at), GETDATE());
    
    SET @i = @i + 1;
END;

SET NOCOUNT OFF;

-- ============================================================================
-- SAMPLE MEDICATIONS DATA
-- ============================================================================
INSERT INTO medications (id, clinic_id, patient_id, name, dosage, frequency, start_date, end_date, status, notes) VALUES
(1, 1, 1, 'Metformin', '500mg', 'Twice daily', '2020-06-15', NULL, 'Active', 'Take with meals'),
(2, 1, 1, 'Lisinopril', '10mg', 'Once daily', '2021-08-01', NULL, 'Active', 'Blood pressure management'),
(3, 1, 2, 'Insulin Glargine', '20 units', 'Once daily at bedtime', '2015-03-20', NULL, 'Active', 'Inject subcutaneously'),
(4, 1, 2, 'Insulin Lispro', '5 units', 'Before meals', '2015-03-20', NULL, 'Active', 'Adjust based on carb intake'),
(5, 1, 3, 'Metformin', '500mg', 'Once daily', '2025-08-10', NULL, 'Active', 'Gestational diabetes management'),
(6, 1, 4, 'Glipizide', '5mg', 'Once daily before breakfast', '2018-09-05', NULL, 'Active', NULL),
(7, 1, 4, 'Metformin', '1000mg', 'Twice daily', '2018-09-05', NULL, 'Active', NULL),
(8, 1, 5, 'Metformin', '500mg', 'Once daily', '2025-06-20', NULL, 'Active', 'Low dose for pre-diabetes'),
(9, 1, 6, 'Metformin', '1000mg', 'Twice daily', '2019-04-12', NULL, 'Active', NULL),
(10, 1, 6, 'Empagliflozin', '10mg', 'Once daily', '2025-01-15', NULL, 'Active', 'Added for better control'),
(11, 1, 7, 'Insulin Pump', 'Variable', 'Continuous', '2018-05-01', NULL, 'Active', 'Medtronic 780G'),
(12, 1, 8, 'Metformin', '1000mg', 'Twice daily', '2012-11-30', NULL, 'Active', NULL),
(13, 1, 8, 'Sitagliptin', '100mg', 'Once daily', '2023-06-01', NULL, 'Active', 'Added due to poor control'),
(14, 1, 2, 'Aspirin', '81mg', 'Once daily', '2015-05-12', '2024-11-01', 'Discontinued', 'Stopped due to GI issues');

-- Insert sample lab results
INSERT INTO lab_results (id, clinic_id, patient_id, test_type, test_value, unit, reference_range, test_date, status, notes) VALUES
-- Recent lab results
(1, 1, 1, 'HbA1c', 7.2, '%', '4.0-5.6%', '2025-12-28', 'Reviewed', 'Slight improvement from previous test'),
(2, 1, 1, 'Fasting Glucose', 142, 'mg/dL', '70-99 mg/dL', '2025-12-28', 'Reviewed', NULL),
(3, 1, 2, 'HbA1c', 8.5, '%', '4.0-5.6%', '2025-12-20', 'Reviewed', 'Needs insulin dose adjustment'),
(4, 1, 3, 'Fasting Glucose', 126, 'mg/dL', '70-99 mg/dL', '2026-01-01', 'Pending Review', 'Routine gestational monitoring'),
(5, 1, 3, 'HbA1c', 6.4, '%', '4.0-5.6%', '2025-12-15', 'Reviewed', 'Good control for gestational diabetes'),
(6, 1, 4, 'HbA1c', 6.8, '%', '4.0-5.6%', '2025-12-10', 'Reviewed', 'Well controlled'),
(7, 1, 5, 'HbA1c', 5.9, '%', '4.0-5.6%', '2025-12-20', 'Reviewed', 'Pre-diabetic range'),
(8, 1, 5, 'Lipid Panel', 210, 'mg/dL', '<200 mg/dL', '2026-01-01', 'Pending Review', NULL),
(9, 1, 6, 'HbA1c', 7.8, '%', '4.0-5.6%', '2025-11-15', 'Reviewed', NULL),
(10, 1, 7, 'HbA1c', 7.0, '%', '4.0-5.6%', '2025-12-05', 'Reviewed', 'Target range for Type 1'),
(11, 1, 8, 'HbA1c', 9.2, '%', '4.0-5.6%', '2025-12-01', 'Reviewed', 'Poor control - needs intervention'),
(12, 1, 9, 'HbA1c', 6.5, '%', '4.0-5.6%', '2025-11-20', 'Reviewed', 'Excellent progress with diet'),
-- More pending results
(13, 1, 2, 'Creatinine', 1.1, 'mg/dL', '0.7-1.3 mg/dL', '2026-01-01', 'Pending Review', 'Kidney function check'),
(14, 1, 4, 'eGFR', 85, 'mL/min', '>60 mL/min', '2026-01-01', 'Pending Review', NULL),
(15, 1, 6, 'Urine Albumin', 45, 'mg/L', '<30 mg/L', '2026-01-01', 'Pending Review', 'Slightly elevated');
