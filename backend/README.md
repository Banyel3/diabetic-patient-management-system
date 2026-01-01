# DiabetaCare Backend

PHP 8.1+ REST API for the DiabetaCare diabetic patient management system.

## Requirements

- PHP 8.1 or higher
- MySQL 8.0 or higher
- PDO extension with MySQL driver
- Apache/Nginx with mod_rewrite or equivalent

## Quick Start

### 1. Configure Environment

```bash
cd backend
cp .env.example .env
```

Edit `.env` with your database credentials:

```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=diabetacare
DB_USERNAME=root
DB_PASSWORD=your_password

JWT_SECRET=your-secure-secret-key-change-in-production
JWT_EXPIRY=86400
```

### 2. Create Database

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p diabetacare < database/seed.sql
```

### 3. Start Development Server

```bash
php -S localhost:8080 -t public
```

The API will be available at `http://localhost:8080/api`.

### 4. Test the API

```bash
# Health check
curl http://localhost:8080/api/health

# Login with demo credentials
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@diabetacare.test","password":"password"}'
```

## Project Structure

```
backend/
├── public/
│   └── index.php          # Entry point
├── config/
│   └── env.php            # Environment loader
├── routes/
│   └── api.php            # Route definitions
├── src/
│   ├── Core/
│   │   ├── Database.php   # PDO wrapper
│   │   ├── Request.php    # HTTP request handler
│   │   ├── Response.php   # JSON response builder
│   │   └── Router.php     # URL routing
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── PatientsController.php
│   │   ├── AppointmentsController.php
│   │   ├── MedicationsController.php
│   │   └── LabResultsController.php
│   ├── Middleware/
│   │   └── AuthMiddleware.php
│   └── Services/
│       ├── JwtService.php
│       └── Validator.php
├── database/
│   ├── schema.sql         # Database structure
│   └── seed.sql           # Sample data
└── .env.example
```

## API Endpoints

### Authentication

- `POST /api/auth/register` - Register clinic + admin
- `POST /api/auth/login` - Get JWT token
- `POST /api/auth/logout` - Invalidate token
- `GET /api/auth/me` - Current user info

### Patients

- `GET /api/patients` - List with pagination/filters
- `GET /api/patients/{id}` - Single patient
- `POST /api/patients` - Create
- `PUT /api/patients/{id}` - Update
- `DELETE /api/patients/{id}` - Soft delete

### Appointments

- `GET /api/appointments` - List
- `GET /api/appointments/{id}` - Single
- `POST /api/appointments` - Create
- `PUT /api/appointments/{id}` - Update
- `DELETE /api/appointments/{id}` - Delete

### Medications

- `GET /api/medications` - List
- `GET /api/medications/{id}` - Single
- `POST /api/medications` - Create
- `PUT /api/medications/{id}` - Update
- `DELETE /api/medications/{id}` - Discontinue

### Lab Results

- `GET /api/lab-results` - List
- `GET /api/lab-results/test-types` - Available tests
- `GET /api/lab-results/{id}` - Single
- `POST /api/lab-results` - Create
- `PUT /api/lab-results/{id}` - Update
- `DELETE /api/lab-results/{id}` - Delete

### Dashboard

- `GET /api/dashboard/summary` - Clinic statistics
- `GET /api/dashboard/upcoming-appointments` - Next appointments
- `GET /api/dashboard/recent-patients` - Recently updated
- `GET /api/dashboard/critical-alerts` - Patients needing attention
- `GET /api/dashboard/hba1c-trends` - Monthly HbA1c trends

## Authentication

The API uses JWT (JSON Web Tokens) for authentication.

1. Login to get a token
2. Include the token in subsequent requests:
   ```
   Authorization: Bearer <token>
   ```

Tokens expire after 24 hours by default (configurable via `JWT_EXPIRY`).

## Demo Credentials

After running `seed.sql`:

- **Email:** admin@diabetacare.test
- **Password:** password

## Database Indexes

The schema includes optimized indexes for common query patterns:

- `idx_patients_clinic_status` - Patient listing and filtering
- `idx_appointments_clinic_scheduled` - Today's appointments
- `idx_medications_patient` - Patient medication history
- `idx_lab_results_patient_test` - Lab result trends
- `idx_lab_results_hba1c` - HbA1c reporting

## Production Deployment

### Apache (.htaccess)

The `public/.htaccess` file handles URL rewriting:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

### Nginx

```nginx
location /api {
    try_files $uri $uri/ /api/public/index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### Security Checklist

- [ ] Change `JWT_SECRET` to a strong random value
- [ ] Set `APP_DEBUG=false` in production
- [ ] Configure CORS_ORIGIN for your domain
- [ ] Use HTTPS
- [ ] Set secure database credentials
- [ ] Enable PHP OPcache

## License

MIT
