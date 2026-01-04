# DiabetaCare PHP Frontend

A server-rendered PHP frontend for the DiabetaCare diabetic patient management system. This replaces the previous Next.js React frontend with a simpler, more maintainable PHP solution.

## Overview

DiabetaCare is a comprehensive diabetes patient management system designed for healthcare providers to:
- Track and manage diabetic patients
- Record lab results (HbA1c, glucose, lipid panels, etc.)
- Manage medications and prescriptions
- Schedule and track appointments
- Monitor critical alerts and patient health trends

## Architecture

### Technology Stack
- **PHP 8.0+**: Server-rendered templates
- **CSS3**: Custom styling (Tailwind-inspired design system)
- **Lucide Icons**: Consistent iconography
- **Session-based Auth**: Secure authentication using PHP sessions

### Folder Structure
```
php-frontend/
├── index.php                    # Main router/entry point
├── assets/
│   └── css/
│       └── style.css           # Global styles
├── includes/
│   ├── functions.php           # Helper functions
│   ├── ApiClient.php           # Backend API client
│   └── layout/
│       ├── header.php          # Common header/sidebar
│       └── footer.php          # Common footer/scripts
└── pages/
    ├── dashboard.php           # Main dashboard
    ├── settings.php            # User settings
    ├── quick-start.php         # Quick start guide
    ├── auth/
    │   ├── login.php
    │   ├── logout.php
    │   ├── register.php
    │   ├── forgot-password.php
    │   └── reset-password.php
    ├── patients/
    │   ├── index.php           # Patient list
    │   ├── view.php            # Patient detail
    │   ├── create.php          # Add patient
    │   └── edit.php            # Edit patient
    ├── appointments/
    │   ├── index.php
    │   ├── create.php
    │   └── edit.php
    ├── medications/
    │   ├── index.php
    │   ├── create.php
    │   └── edit.php
    ├── lab-results/
    │   ├── index.php
    │   ├── create.php
    │   └── edit.php
    └── errors/
        └── 404.php
```

## Installation

### Prerequisites
- XAMPP (or Apache + PHP 8.0+ + MySQL)
- Backend API running on port 8080

### Setup
1. Clone the repository to your XAMPP htdocs folder:
   ```
   C:\xampp\htdocs\diabetic-patient-management-system\
   ```

2. Start Apache and MySQL in XAMPP

3. Access the frontend at:
   ```
   http://localhost/diabetic-patient-management-system/php-frontend/
   ```

## Configuration

### API Connection
The API base URL is configured in `index.php`:
```php
define('API_BASE_URL', 'http://localhost:8080/api');
```

### Base Path
The base path for routing is set in `index.php`:
```php
$basePath = '/diabetic-patient-management-system/php-frontend';
```

## Features

### Authentication
- **Login**: Email and password authentication
- **Registration**: Multi-step registration form
- **Password Reset**: Email-based password recovery
- **Session Management**: Secure PHP sessions with token expiration

### Dashboard
- Patient statistics overview
- Recent patients list
- Today's appointments
- Critical health alerts
- Quick start banner for new users

### Patients Module
- Patient list with search and filtering by diabetes type
- Patient detail view with tabs (Overview, Lab Results, Medications, Appointments)
- Add/Edit patient forms with validation
- Patient deletion with confirmation

### Appointments Module
- Appointment list with date and status filters
- Quick status updates (mark complete)
- Add/Edit appointment forms
- Patient dropdown for scheduling

### Medications Module
- Medication list with patient and status filters
- Active/Inactive toggle
- Categorized medications (Insulin, Oral Hypoglycemic, etc.)
- Dosage and frequency tracking

### Lab Results Module
- Lab results list with test type filters
- HbA1c visual indicators (color-coded)
- Comprehensive lab result entry (glucose, lipids, kidney function)
- Blood pressure and weight tracking

### Settings
- Profile management
- Password change
- Application information

## Design System

### Colors
```css
--accent: #2DD4BF       /* Teal accent */
--primary: #1E292C      /* Dark primary text */
--background: #EBF1F5   /* Light gray background */
--surface: #FFFFFF      /* White surfaces */
--text-primary: #1E292C
--text-secondary: #64748B
--text-muted: #94A3B8
```

### Components
- **Cards**: Elevated containers with shadows
- **Tables**: Responsive tables with actions
- **Forms**: Consistent input styling with validation
- **Badges**: Status and type indicators
- **Modals**: Confirmation dialogs
- **Alerts**: Success/error notifications

## Security

### CSRF Protection
All forms include CSRF tokens:
```php
<?php echo csrfField(); ?>
```

### Input Sanitization
All output is escaped:
```php
<?php echo e($userInput); ?>
```

### Authentication
- Session-based authentication
- Token expiration handling
- Protected routes

## API Client

The `ApiClient` class handles all backend communication:

```php
// Get patients
$patients = api()->getPatients(['page' => 1, 'search' => 'John']);

// Create appointment
$result = api()->createAppointment([
    'patient_id' => 1,
    'appointment_date' => '2025-01-15',
    'appointment_time' => '10:00',
]);
```

## Migration from Next.js

This PHP frontend replaces the original Next.js implementation:

| Next.js | PHP |
|---------|-----|
| React components | PHP templates |
| App Router | Simple URL routing |
| Client-side state | Session storage |
| Tailwind CSS | Custom CSS |
| TypeScript | PHP type hints |

### Benefits of PHP Frontend
- **Simpler deployment**: No Node.js or build step required
- **Lower overhead**: No JavaScript runtime
- **Unified stack**: Single PHP environment
- **Easier maintenance**: Traditional PHP patterns

## Contributing

1. Follow PSR-12 coding standards
2. Use type hints for function parameters
3. Add comments for complex logic
4. Test all forms and validation

## License

MIT License - See LICENSE file for details.
