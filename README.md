# DiabetaCare

A comprehensive diabetic patient management system with a PHP frontend and PHP REST API backend.

## Version 2.1.0

This version features:

- ✅ Full-stack PHP architecture with real database persistence
- ✅ JWT-based authentication
- ✅ Multi-tenant clinic support
- ✅ 5 core modules: Dashboard, Patients, Appointments, Medications, Lab Results
- ✅ Responsive CarePoint-inspired medical UI
- ✅ Pure PHP frontend (no Node.js required)

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Frontend (PHP 8.1+)                     │
│  Vanilla PHP • CSS • Lucide Icons • Responsive Design       │
├─────────────────────────────────────────────────────────────┤
│                         API Layer                            │
│              RESTful JSON over HTTP/HTTPS                    │
├─────────────────────────────────────────────────────────────┤
│                    Backend (PHP 8.1+)                        │
│  Vanilla PHP • PDO • JWT Authentication • Middleware        │
├─────────────────────────────────────────────────────────────┤
│                      Database (MySQL 8.0)                    │
│  Indexed queries • CTEs • Triggers • Soft deletes           │
└─────────────────────────────────────────────────────────────┘
```

## Quick Start

### Prerequisites

- PHP 8.1+
- MySQL 8.0+
- Apache with mod_rewrite (or XAMPP)

### 1. Clone and Setup

```bash
git clone <repository-url>
cd diabetic-patient-management-system
```

### 2. Backend Setup

```bash
cd backend

# Configure environment
cp .env.example .env
# Edit .env with your database credentials

# Create database
mysql -u root -p < database/schema.sql
mysql -u root -p diabetacare < database/seed.sql

# Start PHP development server
php -S localhost:8080 -t public
```

### 3. Frontend Setup

The PHP frontend runs on Apache (via XAMPP) or any web server with mod_rewrite enabled.

1. Place the project in your web server's document root (e.g., `htdocs` for XAMPP)
2. Ensure Apache/mod_rewrite is enabled
3. Access the frontend via your web server

```bash
# Example with XAMPP on Windows:
# Place project in C:\xampp\htdocs\diabetic-patient-management-system
# Frontend URL: http://localhost/diabetic-patient-management-system/frontend
```

### 4. Access the Application

- **Frontend:** http://localhost/diabetic-patient-management-system/frontend
- **Backend API:** http://localhost:8080/api

**Demo Credentials:**

- Email: admin@diabetacare.test
- Password: password

## Project Structure

```
diabetic-patient-management-system/
├── frontend/                    # PHP Frontend Application
│   ├── assets/
│   │   └── css/style.css       # Main stylesheet
│   ├── includes/
│   │   ├── functions.php       # Helper functions
│   │   ├── ApiClient.php       # Backend API client
│   │   └── layout/             # Header/footer templates
│   ├── pages/
│   │   ├── auth/               # Login, register, etc.
│   │   ├── patients/           # Patient CRUD pages
│   │   ├── appointments/       # Appointment pages
│   │   ├── medications/        # Medication pages
│   │   ├── lab-results/        # Lab results pages
│   │   ├── dashboard.php       # Main dashboard
│   │   └── settings.php        # User settings
│   ├── index.php               # Router entry point
│   └── .htaccess               # URL rewriting rules
│
├── backend/                     # PHP REST API
│   ├── public/
│   │   └── index.php           # Entry point
│   ├── routes/
│   │   └── api.php             # Route definitions
│   ├── src/
│   │   ├── Controllers/        # CRUD controllers
│   │   ├── Core/               # Router, Database, Request, Response
│   │   ├── Middleware/         # Auth middleware
│   │   └── Services/           # JWT, Validator
│   └── database/
│       ├── schema.sql          # Database structure
│       └── seed.sql            # Sample data
│
└── docs/
    └── API.md                   # API documentation
```

## API Endpoints

| Module       | Endpoints                                                                               |
| ------------ | --------------------------------------------------------------------------------------- |
| Auth         | POST /register, /login, /logout, GET /me                                                |
| Patients     | GET, POST /patients, GET, PUT, DELETE /patients/{id}                                    |
| Appointments | GET, POST /appointments, GET, PUT, DELETE /appointments/{id}                            |
| Medications  | GET, POST /medications, GET, PUT, DELETE /medications/{id}                              |
| Lab Results  | GET, POST /lab-results, GET /lab-results/test-types, GET, PUT, DELETE /lab-results/{id} |
| Dashboard    | GET /summary, /upcoming-appointments, /recent-patients, /critical-alerts, /hba1c-trends |

See [docs/API.md](docs/API.md) for complete API documentation.

## Features

### Dashboard

- Patient statistics with diabetes type breakdown
- Today's appointments overview
- HbA1c control distribution
- Critical alerts (high HbA1c, missed visits, critical labs)
- Monthly HbA1c trend charts

### Patients

- Full CRUD with auto-generated patient codes
- Search by name, code, or phone
- Filter by diabetes type and status
- Soft delete for data retention
- Denormalized last HbA1c for quick access

### Appointments

- Schedule with patient selection
- Multiple appointment types
- Status tracking (Scheduled, Completed, Cancelled, No-show)
- Date range filtering
- Updates patient last visit date

### Medications

- Track active prescriptions
- Multiple frequency and route options
- Prescribing doctor tracking
- Soft discontinuation (not hard delete)

### Lab Results

- Standard diabetes test types with auto-populated units
- Auto-calculated status based on reference ranges
- HbA1c triggers patient record update
- Test trend analysis

## Database Schema

Key tables:

- `clinics` - Multi-tenant support
- `users` - Staff with roles (admin, doctor, nurse, receptionist)
- `patients` - Core patient records with denormalized last_hba1c
- `appointments` - Scheduling with status tracking
- `medications` - Prescriptions with soft delete
- `lab_results` - Test results with reference ranges
- `auth_tokens` - JWT token storage for logout

Indexes are documented in [backend/database/schema.sql](backend/database/schema.sql) with optimization rationale.

## Technology Stack

### Frontend

- **Next.js 16** - React framework with App Router
- **React 19** - UI library
- **TypeScript 5** - Type safety
- **Tailwind CSS 3.4** - Utility-first styling
- **Lucide React** - Icon library

### Backend

- **PHP 8.1+** - Server-side language (vanilla, no framework)
- **MySQL 8.0** - Relational database
- **PDO** - Database abstraction with prepared statements
- **JWT** - Stateless authentication

## Development

### Frontend Commands

```bash
npm run dev      # Development server
npm run build    # Production build
npm run start    # Production server
npm run lint     # ESLint
```

### Backend Testing

```bash
# Health check
curl http://localhost:8080/api/health

# Login
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@diabetacare.test","password":"password"}'

# Get patients (with token)
curl http://localhost:8080/api/patients \
  -H "Authorization: Bearer <token>"
```

## Production Deployment

1. **Backend:**

   - Set `APP_DEBUG=false`
   - Use strong `JWT_SECRET`
   - Configure `CORS_ORIGIN` for your domain
   - Use Apache with mod_rewrite or Nginx

2. **Frontend:**
   - Set `NEXT_PUBLIC_API_URL` to production API
   - Run `npm run build`
   - Deploy to Vercel, Netlify, or Node.js server

## License

MIT
