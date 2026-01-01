# DiabetaCare - Diabetic Patient Management System

## 📋 Project Overview

**DiabetaCare** is a streamlined web-based diabetic clinic management system designed specifically for healthcare providers managing diabetic patients. The system focuses on essential patient care features with simple CRUD operations for patient records, appointment scheduling, medication tracking, and lab results management.

### Current Status

- **UI/Frontend:** ✅ Complete - Professional, responsive design with 5 core modules
- **Backend/API:** ⚠️ Not Implemented - Requires database, authentication, and business logic
- **Data:** Mock/hardcoded data for demonstration purposes

### Key Purpose

Enable healthcare providers to efficiently manage diabetic patient care through a simple, user-friendly platform that focuses on essential clinic operations: patient management, appointments, medications, and lab results.

---

## 🛠️ Technology Stack

### Core Technologies

| Technology       | Version | Purpose                         |
| ---------------- | ------- | ------------------------------- |
| **Next.js**      | 16.0.7  | React framework with App Router |
| **React**        | 19      | UI library                      |
| **TypeScript**   | 5       | Type-safe JavaScript            |
| **Tailwind CSS** | 3.4.1   | Utility-first styling           |
| **Lucide React** | 0.378.0 | Icon library                    |
| **Inter Font**   | Latest  | Google Fonts typography         |

### Development Tools

- **ESLint** - Code linting
- **PostCSS** - CSS processing with Autoprefixer
- **clsx & tailwind-merge** - Dynamic class management

---

## 🏗️ System Architecture

### Application Structure

```
diabetic-patient-management-system/
├── frontend/                           # Next.js application
│   ├── src/
│   │   ├── app/                       # App Router pages
│   │   │   ├── (app)/                # Protected routes with sidebar
│   │   │   │   ├── page.tsx          # Dashboard (home)
│   │   │   │   ├── patients/         # Patient CRUD management
│   │   │   │   ├── appointments/     # Appointment CRUD scheduling
│   │   │   │   ├── medications/      # Medication CRUD management
│   │   │   │   ├── lab-results/      # Lab results CRUD management
│   │   │   │   └── layout.tsx        # Sidebar layout wrapper
│   │   │   ├── login/                # Authentication page
│   │   │   ├── register/             # Clinic registration
│   │   │   ├── layout.tsx            # Root layout
│   │   │   └── globals.css           # Global styles
│   │   ├── components/
│   │   │   └── Sidebar.tsx           # Main navigation (5 items)
│   │   └── lib/
│   │       └── types.ts              # TypeScript interfaces
│   └── [configs]                      # Config files
├── docs/                              # Documentation
└── LICENSE                            # MIT License
```

### Routing Architecture

- **Route Groups:** Next.js App Router with route groups for layout separation
  - (app) group: All authenticated pages with sidebar navigation
  - Public routes: /login, /register without sidebar
- **Client Components:** All interactive pages use "use client" directive
- **Layouts:** Nested layouts for different sections (root + app)

---

## 🎨 Design System

### Color Palette - "CarePoint Inspired"

Professional medical color scheme with teal accents:

| Color Name         | Hex Code | Usage                                         |
| ------------------ | -------- | --------------------------------------------- |
| **Primary**        | #1E292C  | Dark slate - headers, active states, branding |
| **Accent**         | #2DD4BF  | Teal - CTAs, highlights, interactive elements |
| **Background**     | #EBF1F5  | Light gray-blue - page background             |
| **Surface**        | #FFFFFF  | White - cards, containers, modals             |
| **Text Primary**   | #1F2937  | Main text color                               |
| **Text Secondary** | #6B7280  | Secondary text, labels                        |
| **Text Muted**     | #9CA3AF  | Disabled, placeholder text                    |
| **Danger**         | #EF4444  | Critical alerts, high risk indicators         |
| **Warning**        | #F59E0B  | Warnings, moderate risk                       |
| **Success**        | #10B981  | Success states, stable conditions             |
| **Info**           | #3B82F6  | Informational elements                        |

### Typography

- **Font Family:** Inter (Google Fonts) with ligatures enabled
- **Font Weights:** 400 (regular), 500 (medium), 600 (semibold), 700 (bold)
- **Responsive Scale:** Tailwind's default type scale

### UI Patterns

- **Cards:** Rounded-3xl corners (1.5rem), subtle shadows for elevation
- **Buttons:** Rounded-2xl (1rem), hover effects with smooth transitions
- **Forms:** Rounded-2xl inputs, icon prefixes, focus rings with accent color
- **Badges:** Small rounded pills with color-coded backgrounds
- **Sidebar:** Compact 64px width, icon-only with tooltips on hover
- **Shadows:** Custom shadow utilities (shadow-card, shadow-soft)

---

## ✨ System Features

### 1. 🔐 Authentication & Registration

#### Login Page (/login)

- Split-screen design with branding section
- Email and password authentication
- Show/hide password toggle
- "Remember me" checkbox
- Forgot password link
- Link to registration
- Fully responsive (mobile/desktop)

#### Registration Page (/register)

**Multi-step clinic registration process:**

- **Step 1: Clinic Information**
  - Clinic registered name
  - Business registration number
  - Medical license number
  - Clinic phone and email
- **Step 2: Address Details**
  - Street address
  - City, State/Province
  - ZIP/Postal code
- **Step 3: Admin Account**
  - Admin name (first/last)
  - Admin email and phone
  - Password with confirmation
  - Terms & conditions agreement
- Progress indicator with step validation
- Back/Continue navigation
- Split-screen branding panel

### 2. 📊 Dashboard (Home Page)

**Clean overview with essential statistics:**

**Statistical Summary Cards:**

1. **Total Patients**

   - Total count: 664
   - Growth indicator: +12 this month

2. **Appointments Today**

   - Count: 8 scheduled
   - Quick view of daily schedule

3. **Active Prescriptions**

   - Count: 127 active medications
   - Tracking current prescriptions

4. **Pending Lab Results**
   - Count: 23 pending review
   - Requires attention indicator

**Recent Patients Table:**

- Patient names with ID
- Diabetes type (Type 1, Type 2, Gestational)
- Last visit date
- Quick view of recent patient activity

**Today's Appointments List:**

- Time-based appointment schedule
- Patient names
- Appointment types (Check-up, Follow-up, Lab Review)
- Chronological display

### 3. 👥 Patient Management (/patients)

**Full CRUD functionality with:**

- Search by name, ID, or phone number
- Filter by diabetes type (Type 1, Type 2, Gestational, Pre-diabetic)
- Patient table displaying:
  - Avatar with initials
  - Patient ID and full name
  - Age calculation from date of birth
  - Gender and diabetes type
  - Last HbA1c with color coding (Good <7%, Warning 7-9%, High >9%)
  - Last visit date
  - Status badges (Active, Inactive)
- **Add Patient Modal:** Complete form for new patient registration
- **Edit Patient Modal:** Update existing patient information
- **View Patient Modal:** Detailed patient information display
- **Delete Confirmation:** Safe deletion with confirmation dialog
- Pagination (5 patients per page)
- "Add New Patient" button

### 4. 📅 Appointment Scheduling (/appointments)

**Full CRUD functionality with:**

- Search by patient name
- Filter by status (Scheduled, Completed, Cancelled, No-show)
- Card-based appointment display showing:
  - Patient name and ID
  - Date and time (formatted display)
  - Appointment type (Check-up, Follow-up, Lab Review, Consultation, New Patient)
  - Status badges with color coding
- **Schedule Appointment Modal:**
  - Patient selection dropdown
  - Date and time pickers
  - Appointment type selection
  - Status management
  - Optional notes field
- **Edit Appointment:** Update appointment details
- **Delete Confirmation:** Cancel appointments with confirmation
- Pagination (6 appointments per page)
- "Schedule Appointment" button

### 5. 💊 Medication Management (/medications)

**Full CRUD functionality with:**

- Search by patient name or medication name
- Filter by status (Active, Discontinued)
- Medication table displaying:
  - Medication name with icon
  - Patient name and ID
  - Dosage (e.g., 500mg, 20 units)
  - Frequency (Once daily, Twice daily, etc.)
  - Start date (formatted)
  - Status badges (Active/Discontinued)
- **Add Medication Modal:**
  - Patient selection dropdown
  - Medication name with autocomplete suggestions
  - Dosage and frequency inputs
  - Start date picker
  - Status selection (Active/Discontinued)
  - End date (for discontinued medications)
  - Optional notes field
- **Common medication suggestions:** Metformin, Insulin Glargine, Glipizide, Lisinopril, etc.
- **Edit Medication:** Update prescription details
- **Delete Confirmation:** Remove medications with confirmation
- Pagination (6 medications per page)
- "Add Medication" button

### 6. 🧪 Lab Results (/lab-results)

**Full CRUD functionality with:**

- Search by patient name
- Filter by test type and status (Pending Review, Reviewed)
- Lab results table displaying:
  - Patient name
  - Medication name
  - Type (Oral, Injectable, etc.)
  - Dosage amount
  - Frequency (daily, BID, TID)
  - Adherence status (Good, Poor, Missed)
- "Add Prescription" button
- Medication history

  - Patient name and ID
  - Test type (HbA1c, Fasting Glucose, Lipid Panel, Creatinine, eGFR, etc.)
  - Test result value with unit
  - Reference range for comparison
  - Result interpretation with visual indicators:
    - **Normal:** Green with down arrow
    - **Elevated:** Amber with up arrow
    - **High:** Red with up arrow
  - Test date (formatted)
  - Status badges (Pending Review/Reviewed)

- **Add Lab Result Modal:**
  - Patient selection dropdown
  - Test type selection (auto-populates unit and reference range)
  - Numeric value input
  - Date picker
  - Status selection
  - Optional clinical notes
- **Focus on diabetes-relevant tests:** HbA1c, Fasting Glucose, Random Glucose, Lipid Panel, Creatinine, eGFR, Urine Albumin
- **Edit Lab Result:** Update test values and status
- **Delete Confirmation:** Remove lab results with confirmation
- Pagination (6 results per page)
- "Add Lab Result" button

### 7. 🧭 Navigation Sidebar

**Compact vertical sidebar (64px width):**

- DiabetaCare logo at top
- **Icon-based navigation** with 5 main items:
  1. Dashboard (LayoutDashboard icon)
  2. Patients (Users icon)
  3. Appointments (Calendar icon)
  4. Medications (Pill icon)
  5. Lab Results (Beaker icon)
- **Bottom navigation:**
  - Settings (Settings icon)
  - Log out (LogOut icon) → /login
- Active state highlighting with accent color
- Hover tooltips for icon descriptions
- Smooth transitions and animations

---

## 📱 Responsive Design

**Mobile-First Approach:**

- **Mobile (< 640px):** Single column layouts, stacked cards, collapsed navigation
- **Tablet (640px - 1024px):** Two-column layouts, compact tables, visible sidebar
- **Desktop (> 1024px):** Multi-column layouts, full data tables, expanded sidebar

**Responsive Features:**

- Flexible grid system using Tailwind
- Breakpoint-specific layouts
- Mobile logo vs desktop logo
- Collapsible tables and cards
- Touch-friendly interactive elements

---

## 🔒 Security & Compliance

### Current Implementation

⚠️ **Authentication is UI-only** - No actual security implemented yet

### Recommended Security Measures

1. **Authentication:**

   - Implement NextAuth.js or JWT-based authentication
   - Secure password hashing (bcrypt, argon2)
   - Session management with httpOnly cookies
   - Two-factor authentication (2FA)

2. **Authorization:**

   - Role-based access control (RBAC)
   - Route protection middleware
   - API endpoint authorization

3. **Data Protection:**

   - Encrypt sensitive patient data at rest
   - HTTPS/TLS for data in transit
   - Input validation and sanitization
   - SQL injection prevention
   - XSS protection

4. **HIPAA Compliance:**

   - Audit logging for all data access
   - Data backup and disaster recovery
   - Business associate agreements (BAA)
   - Patient consent management
   - Data retention policies

5. **Additional Security:**
   - Rate limiting on API endpoints
   - CSRF protection
   - Content Security Policy (CSP)
   - Regular security audits

---

## 🚀 Getting Started

### Prerequisites

- Node.js 20.x or higher
- npm or yarn package manager

### Installation Steps

```bash
# Clone the repository
git clone https://github.com/Banyel3/diabetic-patient-management-system.git

# Navigate to frontend directory
cd diabetic-patient-management-system/frontend

# Install dependencies
npm install

# Run development server
npm run dev
```

Visit http://localhost:3000 to view the application.

### Available Scripts

| Command | Description |
| ------- | ----------- |

|
pm run dev | Start development server (port 3000) |
|
pm run build | Build production bundle |
|
pm run start | Start production server |
|
pm run lint | Run ESLint for code quality |

---

## 📊 Project Statistics

- **Total Pages/Routes:** 7 (5 app routes + 2 auth routes)
- **Reusable Components:** 1 (Sidebar)
- **TypeScript Interfaces:** 4 (Patient, Appointment, Medication, LabResult)
- **Lines of Code:** ~3,800+ (frontend only)
- **Dependencies:** 14 (8 production, 6 dev)
- **Custom Colors:** 24 in Tailwind config
- **Icons Used:** 25+ different Lucide icons
- **Current State:** Fully functional CRUD UI with mock data

---

## ⚠️ Current Limitations

### Not Implemented (Requires Development)

- ❌ **No Backend/API:** No server, database, or API endpoints
- ❌ **No Real Authentication:** Login/register are UI only
- ❌ **No Data Persistence:** All data is hardcoded/mock (localStorage not implemented)
- ❌ **No State Management:** No Redux, Context API, or Zustand
- ❌ **No Form Validation:** No Zod or React Hook Form (basic HTML5 validation only)
- ✅ **TypeScript Interfaces:** Basic data models defined in types.ts
- ❌ **No API Integration:** No fetch calls or data fetching
- ❌ **No File Upload:** Lab results file upload not implemented
- ❌ **No Calendar Library:** Appointment calendar is basic
- ❌ **No Charts/Analytics:** No data visualization library
- ❌ **No Tests:** No unit, integration, or E2E tests

---

## 🗺️ Development Roadmap

### Phase 1: Backend Foundation (Critical)

**Goal:** Build functional backend infrastructure

- [ ] Choose backend stack (Next.js API routes vs separate Node.js server)
- [ ] Design database schema (PostgreSQL/MySQL recommended)
- [ ] Set up ORM (Prisma, TypeORM, or Drizzle)
- [ ] Create TypeScript data models and interfaces
- [ ] Implement RESTful API endpoints for all modules
- [ ] Set up authentication system (NextAuth.js or JWT)
- [ ] Implement authorization and role-based access
- [ ] Create database migrations

**Estimated Time:** 4-6 weeks

### Phase 2: Data Integration (Essential)

**Goal:** Connect frontend to backend

- [ ] Install React Query or SWR for data fetching
- [ ] Replace mock data with API calls
- [ ] Implement loading states and skeletons
- [ ] Add error handling and retry logic
- [ ] Set up global state management if needed
- [ ] Add form validation (React Hook Form + Zod)
- [ ] Implement optimistic updates
- [ ] Add real-time updates (WebSocket or polling)

**Estimated Time:** 3-4 weeks

### Phase 3: Feature Enhancement (Important)

**Goal:** Complete core functionality

- [ ] Integrate calendar library (FullCalendar, react-big-calendar)
- [ ] Add charting library (Recharts, Chart.js, or Tremor)
- [ ] Implement PDF generation (jsPDF, react-pdf, or Puppeteer)
- [ ] Add file upload functionality (for lab results)
- [ ] Implement advanced search and filtering
- [ ] Add pagination for large datasets
- [ ] Create patient profile pages with full history
- [ ] Build appointment scheduling logic
- [ ] Implement risk calculation algorithms

**Estimated Time:** 4-5 weeks

### Phase 4: Advanced Features (Nice to Have)

**Goal:** Enhance user experience

- [ ] Real-time notifications system
- [ ] Email/SMS alerts for appointments and critical values
- [ ] Data export functionality (CSV, Excel)
- [ ] Advanced analytics dashboard with trends
- [ ] Patient portal (separate interface for patients)
- [ ] Mobile app using React Native
- [ ] Telemedicine integration
- [ ] Prescription management with e-prescribing
- [ ] Insurance and billing module

**Estimated Time:** 6-8 weeks

### Phase 5: Production Ready (Critical for Launch)

**Goal:** Prepare for deployment

- [ ] Write comprehensive test suite:
  - Unit tests with Jest
  - Integration tests
  - E2E tests with Playwright
- [ ] Performance optimization:
  - Code splitting
  - Image optimization
  - Caching strategies
- [ ] Security audit and penetration testing
- [ ] HIPAA compliance verification
- [ ] Accessibility audit (WCAG 2.1 AA)
- [ ] Complete documentation
- [ ] Set up CI/CD pipeline
- [ ] Configure production environment
- [ ] Create deployment strategy
- [ ] Set up monitoring and logging

**Estimated Time:** 4-6 weeks

**Total Estimated Development Time:** 21-29 weeks (5-7 months)

---

## 🧪 Testing Strategy (Recommended)

### Unit Tests

- **Framework:** Jest + React Testing Library
- **Coverage:** All components, utilities, and business logic
- **Target:** 80%+ code coverage

### Integration Tests

- Test API endpoint interactions
- Test form submissions and data flow
- Test authentication flows

### End-to-End Tests

- **Framework:** Playwright (folder already exists)
- Test complete user workflows
- Test across different browsers
- Test responsive design on various devices

### Accessibility Tests

- **Tool:** axe-core or Pa11y
- WCAG 2.1 AA compliance
- Screen reader compatibility

---

## 🤝 Contributing

### How to Contribute

1. Fork the repository
2. Create a feature branch: git checkout -b feature/AmazingFeature
3. Commit your changes: git commit -m 'Add some AmazingFeature'
4. Push to the branch: git push origin feature/AmazingFeature
5. Open a Pull Request

### Code Standards

- Follow ESLint configuration
- Use TypeScript for all new code
- Follow existing naming conventions
- Write meaningful commit messages
- Add JSDoc comments for functions
- Update documentation for new features

### Pull Request Guidelines

- Provide clear description of changes
- Include screenshots for UI changes
- Ensure all tests pass
- Update documentation if needed

---

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](../LICENSE) file for details.

Copyright © 2025 DiabetaCare

---

## 👥 Credits & Acknowledgments

- **Project Maintainer:** [Banyel3](https://github.com/Banyel3)
- **Design Inspiration:** CarePoint Dribbble design by healthcare UI designers
- **Icons:** Lucide React icon library
- **Typography:** Inter font by Rasmus Andersson (Google Fonts)
- **Framework:** Next.js by Vercel
- **Styling:** Tailwind CSS

---

## 📞 Support & Contact

- **Repository:** [diabetic-patient-management-system](https://github.com/Banyel3/diabetic-patient-management-system)
- **Issues:** [GitHub Issues](https://github.com/Banyel3/diabetic-patient-management-system/issues)
- **Maintainer:** [@Banyel3](https://github.com/Banyel3)

---

## 🎯 Summary

**DiabetaCare** is a professionally designed, streamlined diabetic clinic management system with a complete UI implementation. The frontend showcases modern web development best practices with Next.js 16, React 19, TypeScript, and Tailwind CSS, focusing on essential clinic operations.

**Current State:** The system has 5 core modules fully designed and implemented as functional CRUD interfaces with mock data. It demonstrates strong frontend architecture, clean code organization, responsive design, and professional medical UI/UX with modal-based workflows.

**Key Features:** Full create, read, update, and delete operations for Patients, Appointments, Medications, and Lab Results. Each module includes search, filtering, pagination, and user-friendly modal forms.

**Next Steps:** To become a production-ready application, the system requires backend development, database integration, authentication implementation, and proper data persistence. The clean, well-organized codebase provides a solid foundation for backend integration.

**Target Users:** Small to medium-sized diabetes clinics, endocrinology practices, and healthcare providers specializing in diabetes care who need a simple, focused patient management system.

**Vision:** To provide a simple, secure, HIPAA-compliant platform that streamlines essential diabetic clinic operations through efficient patient, appointment, medication, and lab result management.

---

**Last Updated:** January 1, 2026  
**Version:** 2.0.0 (Simplified & Refactored - 5 Core Modules)  
**Status:** 🟡 In Development (Frontend Complete with CRUD, Backend Required)
