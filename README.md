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
- ✅ SQL Server & MySQL support

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Frontend (PHP 8.2+)                     │
│  Vanilla PHP • CSS • Lucide Icons • Responsive Design       │
├─────────────────────────────────────────────────────────────┤
│                         API Layer                            │
│              RESTful JSON over HTTP/HTTPS                    │
├─────────────────────────────────────────────────────────────┤
│                    Backend (PHP 8.2+)                        │
│  Vanilla PHP • PDO • JWT Authentication • Middleware        │
├─────────────────────────────────────────────────────────────┤
│                Database (SQL Server / MySQL)                 │
│  Indexed queries • Triggers • Soft deletes • HIPAA auditing │
└─────────────────────────────────────────────────────────────┘
```

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation Guide](#installation-guide)
   - [Windows (XAMPP + SQL Server)](#windows-xampp--sql-server)
   - [Windows (XAMPP + MySQL)](#windows-xampp--mysql)
   - [Linux/Mac (Apache + MySQL)](#linuxmac-apache--mysql)
3. [Database Setup](#database-setup)
4. [Configuration](#configuration)
5. [Running the Application](#running-the-application)
6. [Demo Credentials](#demo-credentials)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Required Software

#### Option 1: Windows with SQL Server (Recommended for Windows)
- **XAMPP 8.2.12** or higher (includes PHP 8.2.12 and Apache)
  - Download: https://www.apachefriends.org/
- **Microsoft SQL Server 2019 Express** or higher
  - Download: https://www.microsoft.com/en-us/sql-server/sql-server-downloads
- **SQL Server Management Studio (SSMS)** - Optional but recommended
  - Download: https://aka.ms/ssmsfullsetup

#### Option 2: Windows/Linux/Mac with MySQL
- **PHP 8.1+** with extensions:
  - PDO
  - pdo_mysql (or pdo_sqlsrv for SQL Server)
  - mbstring
  - json
  - curl
- **MySQL 8.0+** or **MariaDB 10.5+**
- **Apache 2.4+** with mod_rewrite enabled
- **Composer** (optional, not required for this project)

### Verify Prerequisites

```bash
# Check PHP version
php -v
# Should show PHP 8.1.0 or higher

# Check PHP extensions
php -m | grep -E 'pdo|mbstring|json|curl'

# Check MySQL
mysql --version

# Check Apache (on Linux/Mac)
apache2 -v
# or
httpd -v
```

---

## Installation Guide

### Windows (XAMPP + SQL Server)

#### Step 1: Install XAMPP

1. Download and install XAMPP 8.2.12 from https://www.apachefriends.org/
2. Install to `C:\xampp` (default location)
3. During installation, select:
   - ✅ Apache
   - ✅ PHP
   - ❌ MySQL (we'll use SQL Server instead)
   - ❌ Other components (optional)

#### Step 2: Install SQL Server

1. Download **SQL Server 2019 Express** or newer
2. During installation:
   - Select "Basic" installation type
   - Choose installation directory
   - Instance name: Use `SQLEXPRESS` (default)
   - Authentication mode: **Mixed Mode** (enable SQL Server authentication)
   - Set a strong SA password
3. Install **SQL Server Management Studio (SSMS)** for database management

#### Step 3: Enable SQL Server PDO Extension in PHP

1. Open `C:\xampp\php\php.ini` in a text editor (as Administrator)
2. Find and uncomment (remove `;` from) these lines:
   ```ini
   extension=pdo_sqlsrv
   extension=sqlsrv
   ```
   If they don't exist, add them under the `; Dynamic Extensions` section.

3. Download Microsoft Drivers for PHP for SQL Server:
   - Visit: https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server
   - Download the version matching your PHP (8.2 thread-safe x64)
   - Extract `php_pdo_sqlsrv_82_ts_x64.dll` and `php_sqlsrv_82_ts_x64.dll`
   - Copy them to `C:\xampp\php\ext\`

4. Restart Apache from XAMPP Control Panel

#### Step 4: Clone the Project

1. Open Command Prompt or PowerShell
2. Navigate to XAMPP's htdocs directory:
   ```bash
   cd C:\xampp\htdocs
   ```
3. Clone or extract the project:
   ```bash
   # If using Git
   git clone <repository-url> diabetic-patient-management-system
   
   # Or extract the ZIP file to:
   # C:\xampp\htdocs\diabetic-patient-management-system
   ```

#### Step 5: Create the Database

1. Open **SQL Server Management Studio (SSMS)**
2. Connect to `(local)\SQLEXPRESS` using Windows Authentication or SA account
3. Open the schema file:
   - File → Open → File
   - Navigate to: `C:\xampp\htdocs\diabetic-patient-management-system\backend\database\schema.sql`
4. Execute the script (F5 or click Execute)
5. Verify the database `DiabetaCare` was created

**Alternative: Using Command Line**
```bash
# Using sqlcmd (comes with SQL Server)
sqlcmd -S .\SQLEXPRESS -E -i "C:\xampp\htdocs\diabetic-patient-management-system\backend\database\schema.sql"

# Or restore from export file
sqlcmd -S .\SQLEXPRESS -E -i "C:\xampp\htdocs\diabetic-patient-management-system\DiabetaCare_Export.sql"
```

#### Step 6: Configure the Backend

1. Navigate to the backend directory:
   ```bash
   cd C:\xampp\htdocs\diabetic-patient-management-system\backend
   ```

2. Copy the environment template:
   ```bash
   copy .env.example .env
   ```
   (On PowerShell: `Copy-Item .env.example .env`)

3. Edit `.env` with your database credentials:
   ```env
   # Application
   APP_ENV=development
   APP_DEBUG=true

   # Database Driver: 'sqlsrv' for SQL Server, 'mysql' for MySQL
   DB_DRIVER=sqlsrv

   # Database Connection (SQL Server)
   DB_HOST=.\SQLEXPRESS
   DB_PORT=1433
   DB_NAME=DiabetaCare
   DB_USER=
   DB_PASSWORD=

   # For Windows Authentication (recommended), leave DB_USER and DB_PASSWORD empty
   # For SQL Authentication, provide credentials:
   # DB_USER=sa
   # DB_PASSWORD=your_password

   # JWT Authentication
   JWT_SECRET=your-super-secret-jwt-key-change-this-in-production
   JWT_EXPIRY=86400

   # Frontend URL (for CORS)
   FRONTEND_URL=http://localhost
   ```

4. Save the file

#### Step 7: Start the Backend API

Open Command Prompt or PowerShell in the backend directory:

```bash
cd C:\xampp\htdocs\diabetic-patient-management-system\backend
C:\xampp\php\php.exe -S localhost:8080 -t public
```

You should see: `PHP 8.2.12 Development Server (http://localhost:8080) started`

**Keep this terminal window open** - the API server is running.

#### Step 8: Start Apache for Frontend

1. Open **XAMPP Control Panel**
2. Click **Start** next to Apache
3. Verify Apache is running (green highlight)

#### Step 9: Access the Application

Open your web browser and navigate to:
- **Frontend:** http://localhost/diabetic-patient-management-system/frontend
- **Backend API Health Check:** http://localhost:8080/api/health

---

### Windows (XAMPP + MySQL)

#### Steps 1-4: Follow "Windows (XAMPP + SQL Server)" Steps 1-4

But in Step 1, select MySQL during XAMPP installation.

#### Step 5: Create the Database (MySQL)

1. Start MySQL from XAMPP Control Panel
2. Open **phpMyAdmin**: http://localhost/phpmyadmin
3. Click "SQL" tab
4. Copy and paste the contents of `backend/database/schema.sql` (MySQL version)
5. Click "Go" to execute

**Alternative: Using Command Line**
```bash
# Navigate to XAMPP MySQL bin
cd C:\xampp\mysql\bin

# Create database and import schema
mysql -u root -p < C:\xampp\htdocs\diabetic-patient-management-system\backend\database\schema.sql
```

#### Step 6: Configure Backend (.env)

```env
# Database Driver
DB_DRIVER=mysql

# Database Connection (MySQL)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=DiabetaCare
DB_USER=root
DB_PASSWORD=

# JWT Authentication
JWT_SECRET=your-super-secret-jwt-key-change-this
JWT_EXPIRY=86400
```

Continue with Steps 7-9 from the SQL Server guide.

---

### Linux/Mac (Apache + MySQL)

#### Step 1: Install Prerequisites

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-mysql php8.2-mbstring php8.2-curl php8.2-xml
sudo apt install -y apache2 libapache2-mod-php8.2
sudo apt install -y mysql-server
```

**macOS (using Homebrew):**
```bash
brew install php@8.2
brew install mysql
brew install httpd
```

#### Step 2: Clone the Project

```bash
# Linux - Apache document root
cd /var/www/html
sudo git clone <repository-url> diabetic-patient-management-system
sudo chown -R www-data:www-data diabetic-patient-management-system

# macOS - Apache document root
cd /usr/local/var/www
git clone <repository-url> diabetic-patient-management-system
```

#### Step 3: Enable Apache mod_rewrite

**Ubuntu/Debian:**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**macOS:**
Edit `/usr/local/etc/httpd/httpd.conf` and uncomment:
```apache
LoadModule rewrite_module lib/httpd/modules/mod_rewrite.so
```

#### Step 4: Configure Apache VirtualHost (Optional)

Create `/etc/apache2/sites-available/diabetacare.conf`:

```apache
<VirtualHost *:80>
    ServerName diabetacare.local
    DocumentRoot /var/www/html/diabetic-patient-management-system/frontend

    <Directory /var/www/html/diabetic-patient-management-system/frontend>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/diabetacare_error.log
    CustomLog ${APACHE_LOG_DIR}/diabetacare_access.log combined
</VirtualHost>
```

Enable the site:
```bash
sudo a2ensite diabetacare
sudo systemctl reload apache2
```

Add to `/etc/hosts`:
```
127.0.0.1   diabetacare.local
```

#### Step 5: Create MySQL Database

```bash
# Secure MySQL installation (first time only)
sudo mysql_secure_installation

# Create database
sudo mysql -u root -p

# In MySQL prompt:
CREATE DATABASE DiabetaCare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'diabetacare'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON DiabetaCare.* TO 'diabetacare'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u diabetacare -p DiabetaCare < backend/database/schema.sql
```

#### Step 6: Configure Backend

```bash
cd backend
cp .env.example .env
nano .env  # or use your preferred editor
```

Edit `.env`:
```env
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=DiabetaCare
DB_USER=diabetacare
DB_PASSWORD=secure_password

JWT_SECRET=your-random-secret-key-here
JWT_EXPIRY=86400
```

#### Step 7: Start the Backend API

```bash
cd backend
php -S localhost:8080 -t public
```

Or use a process manager like `supervisor` or `systemd` for production.

#### Step 8: Access the Application

- Frontend: http://localhost/diabetic-patient-management-system/frontend
- Or if using VirtualHost: http://diabetacare.local
- Backend API: http://localhost:8080/api/health

---

## Database Setup

### Import Full Database Export

If you have the `DiabetaCare_Export.sql` file (includes schema + sample data):

**SQL Server:**
```bash
sqlcmd -S .\SQLEXPRESS -E -i DiabetaCare_Export.sql
```

**MySQL:**
```bash
mysql -u root -p < DiabetaCare_Export.sql
```

### Manual Schema Setup

If you only have the schema file:

1. Import `backend/database/schema.sql` (creates tables, indexes, triggers)
2. Optionally import `backend/database/seed.sql` (sample data)

---

## Configuration

### Backend Environment Variables (.env)

Located at: `backend/.env`

```env
# Application Settings
APP_ENV=development          # development | production
APP_DEBUG=true              # true | false (disable in production)

# Database Configuration
DB_DRIVER=sqlsrv            # sqlsrv (SQL Server) | mysql
DB_HOST=.\SQLEXPRESS        # .\SQLEXPRESS | localhost
DB_PORT=1433                # 1433 (SQL Server) | 3306 (MySQL)
DB_NAME=DiabetaCare
DB_USER=                    # Leave empty for Windows Auth (SQL Server)
DB_PASSWORD=                # Leave empty for Windows Auth

# Security
JWT_SECRET=change-this-to-a-random-64-char-string
JWT_EXPIRY=86400            # Token expiration in seconds (24 hours)

# CORS
FRONTEND_URL=http://localhost

# Pagination
PAGINATION_DEFAULT_SIZE=10
PAGINATION_MAX_SIZE=100
```

### Frontend Configuration

Located at: `frontend/includes/config.php`

```php
<?php
// API Base URL - Update if backend runs on different port/host
define('API_BASE_URL', 'http://localhost:8080/api');

// Frontend Base URL
define('BASE_URL', '/diabetic-patient-management-system/frontend');
```

**Important:** If you change the project directory or use a VirtualHost, update `BASE_URL`.

---

## Running the Application

### Development Mode

**Terminal 1 - Backend API:**
```bash
cd backend
php -S localhost:8080 -t public
```

**Terminal 2 - Frontend (if not using Apache):**
```bash
cd frontend
php -S localhost:3000
```

**Or use Apache/XAMPP:**
- Start Apache from XAMPP Control Panel
- Access: http://localhost/diabetic-patient-management-system/frontend

### Production Deployment

1. **Backend:**
   - Set `APP_DEBUG=false` in `.env`
   - Use strong `JWT_SECRET` (64+ random characters)
   - Deploy to Apache/Nginx with PHP-FPM
   - Enable HTTPS with SSL certificate
   - Configure proper CORS headers

2. **Frontend:**
   - Update `BASE_URL` in `config.php`
   - Ensure `.htaccess` is working for clean URLs
   - Enable HTTPS

3. **Database:**
   - Use production credentials
   - Regular backups
   - Enable audit logging
   - Restrict network access

---

## Demo Credentials

After importing the database, use these credentials to login:

**Admin Account:**
- **Email:** `admin@diabetacare-downtown.com`
- **Password:** `Admin@2026`

**Alternative Accounts** (if seed data is imported):
- Doctor: `doctor@diabetacare-downtown.com` / `Doctor@2026`
- Nurse: `nurse@diabetacare-downtown.com` / `Nurse@2026`

### Creating a New Account

Visit: http://localhost/diabetic-patient-management-system/frontend/register

Fill in clinic details and admin user information. The system supports multi-tenant clinics.

---

## Troubleshooting

### Common Issues

#### 1. "Cannot connect to database"

**SQL Server:**
- Verify SQL Server service is running: `services.msc` → SQL Server (SQLEXPRESS)
- Check Windows Firewall allows port 1433
- Verify connection string in `.env` (use `.\SQLEXPRESS` for local instance)
- Test connection: `sqlcmd -S .\SQLEXPRESS -E -Q "SELECT @@VERSION"`

**MySQL:**
- Verify MySQL is running in XAMPP Control Panel
- Check credentials in `.env`
- Test connection: `mysql -u root -p -e "SELECT VERSION();"`

#### 2. "SQL Server PDO driver not found"

- Verify driver files are in `C:\xampp\php\ext\`:
  - `php_pdo_sqlsrv_82_ts_x64.dll`
  - `php_sqlsrv_82_ts_x64.dll`
- Check `php.ini` has extensions enabled (uncommented)
- Restart Apache
- Verify with: `php -m | findstr sqlsrv`

#### 3. "Frontend 404 / Pages not found"

- Verify `.htaccess` exists in `frontend/` directory
- Check Apache mod_rewrite is enabled:
  - XAMPP: Edit `httpd.conf`, uncomment `LoadModule rewrite_module modules/mod_rewrite.so`
- Ensure `AllowOverride All` in Apache configuration
- Restart Apache

#### 4. "Backend API returns 500 error"

- Check PHP error logs: `C:\xampp\apache\logs\error.log`
- Enable debugging: Set `APP_DEBUG=true` in backend `.env`
- Verify database connection works
- Check file permissions (Linux/Mac): `chmod -R 755 backend`

#### 5. "CORS errors in browser console"

- Verify `FRONTEND_URL` in backend `.env` matches your frontend URL
- Backend should add CORS headers for `http://localhost`
- Check browser DevTools → Network tab for response headers

#### 6. "Token expired / Invalid token"

- JWT tokens expire after `JWT_EXPIRY` seconds (default 24 hours)
- Clear browser localStorage and login again
- Check system time is correct (JWT relies on timestamps)

#### 7. "SQL syntax errors"

- Verify you're using the correct schema file for your database:
  - SQL Server: Uses `[brackets]`, `GETDATE()`, `IDENTITY`
  - MySQL: Uses `backticks`, `NOW()`, `AUTO_INCREMENT`
- Check `DB_DRIVER` in `.env` matches your database

### Logs and Debugging

**Backend PHP Errors:**
- XAMPP: `C:\xampp\apache\logs\error.log`
- Linux: `/var/log/apache2/error.log`

**Enable PHP Error Display** (development only):
Edit `backend/public/index.php`, add at top:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

**Database Query Logging:**
Enable in `backend/src/Core/Database.php` by uncommenting debug statements.

### Getting Help

1. Check the [API Documentation](docs/API.md)
2. Review [Database Schema](backend/database/schema.sql)
3. Check browser DevTools Console and Network tabs
4. Review backend logs for detailed error messages
5. Verify all prerequisites are installed and versions match

---

## Quick Reference

### Important URLs

| Component | URL |
|-----------|-----|
| Frontend | http://localhost/diabetic-patient-management-system/frontend |
| Backend API | http://localhost:8080/api |
| Health Check | http://localhost:8080/api/health |
| phpMyAdmin (MySQL) | http://localhost/phpmyadmin |

### Important Files

| File | Purpose |
|------|---------|
| `backend/.env` | Database and API configuration |
| `frontend/includes/config.php` | Frontend configuration |
| `backend/database/schema.sql` | Database structure |
| `DiabetaCare_Export.sql` | Full database export (schema + data) |
| `frontend/.htaccess` | URL rewriting rules |
| `backend/routes/api.php` | API endpoint definitions |

### Quick Commands

```bash
# Start Backend API
cd backend && php -S localhost:8080 -t public

# Test Backend Health
curl http://localhost:8080/api/health

# Test Login
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@diabetacare-downtown.com","password":"Admin@2026"}'

# Check PHP Version
php -v

# Check PHP Extensions
php -m

# View Apache Error Log (XAMPP)
tail -f C:\xampp\apache\logs\error.log

# Restart Apache (Linux)
sudo systemctl restart apache2
```

---

## Project Structure

```
diabetic-patient-management-system/
├── frontend/                    # PHP Frontend Application
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css       # Main stylesheet
│   │   └── images/             # Images and icons
│   ├── includes/
│   │   ├── functions.php       # Helper functions
│   │   ├── ApiClient.php       # Backend API client
│   │   ├── config.php          # Frontend configuration
│   │   └── layout/             # Header/footer templates
│   │       ├── header.php
│   │       └── footer.php
│   ├── pages/
│   │   ├── auth/               # Authentication pages
│   │   │   ├── login.php
│   │   │   └── register.php
│   │   ├── patients/           # Patient module
│   │   │   ├── index.php       # List patients
│   │   │   ├── create.php      # Add patient
│   │   │   ├── edit.php        # Edit patient
│   │   │   └── view.php        # Patient details
│   │   ├── appointments/       # Appointments module
│   │   ├── medications/        # Medications module
│   │   ├── lab-results/        # Lab results module
│   │   ├── dashboard.php       # Main dashboard
│   │   └── settings.php        # User settings
│   ├── index.php               # Router entry point
│   └── .htaccess               # URL rewriting rules
│
├── backend/                     # PHP REST API
│   ├── public/
│   │   └── index.php           # API entry point
│   ├── config/
│   │   └── env.php             # Environment loader
│   ├── routes/
│   │   └── api.php             # Route definitions
│   ├── src/
│   │   ├── Autoloader.php      # PSR-4 autoloader
│   │   ├── Controllers/        # API controllers
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── PatientsController.php
│   │   │   ├── AppointmentsController.php
│   │   │   ├── MedicationsController.php
│   │   │   ├── LabResultsController.php
│   │   │   └── UsersController.php
│   │   ├── Core/               # Core framework
│   │   │   ├── Database.php    # PDO wrapper (SQL Server/MySQL)
│   │   │   ├── Request.php     # HTTP request handler
│   │   │   ├── Response.php    # JSON response builder
│   │   │   └── Router.php      # URL routing with middleware
│   │   ├── Middleware/         # HTTP middleware
│   │   │   ├── AuthMiddleware.php
│   │   │   └── RoleMiddleware.php
│   │   └── Services/           # Business services
│   │       ├── JwtService.php  # JWT token generation/validation
│   │       └── Validator.php   # Input validation
│   ├── database/
│   │   ├── schema.sql          # Database structure (SQL Server/MySQL)
│   │   └── seed.sql            # Sample data
│   ├── .env.example            # Environment template
│   └── .env                    # Environment config (gitignored)
│
├── docs/                        # Documentation
│   ├── API.md                  # API documentation
│   ├── README.md               # Documentation index
│   └── PATIENT_DATA_MODEL.md   # Data model reference
│
├── DiabetaCare_Export.sql      # Full database export (schema + data)
├── README.md                   # This file
└── LICENSE                     # License information
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
