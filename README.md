# Dentaris — Dental Clinic SaaS

A full-stack SaaS MVP for dental clinics built with Laravel 11, Inertia.js + Vue 3, and Tailwind CSS.

## Tech Stack

- **Backend**: Laravel 11, Sanctum, DomPDF, SQLite (dev) / MySQL (prod)
- **Frontend**: Inertia.js + Vue 3, Tailwind CSS v3, Alpine.js, Chart.js, FullCalendar.js
- **Design**: "Medical Luxury" — Navy (#0F1F3D) + Teal (#00BFA6), Playfair Display + DM Sans

## Features

- Multi-role authentication (Admin, Dentist, Receptionist)
- Patient management with full medical profiles
- Interactive appointment calendar (FullCalendar)
- SVG Odontogram (32-tooth dental chart)
- Clinical notes (SOAP format)
- Invoice generation with PDF export
- Revenue dashboard with Chart.js
- Dark/Light mode toggle
- Fully responsive (mobile + tablet + desktop)

## Quick Start

### Requirements

- PHP 8.2+
- Composer 2.x
- Node.js 22+ (required for Vite 7)
- SQLite (default) or MySQL

### Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Set up environment
cp .env.example .env
php artisan key:generate

# 3. Create SQLite database (or configure MySQL in .env)
touch database/database.sqlite

# 4. Run migrations + seed demo data
php artisan migrate --seed

# 5. Create storage symlink
php artisan storage:link

# 6. Install & build frontend (Node 22 required)
npm install
npm run build
# OR for development:
npm run dev
```

### Running the App

```bash
# Terminal 1 — Laravel server
php artisan serve

# Terminal 2 — Vite dev server (for development)
npm run dev
```

Visit: http://localhost:8000

### Demo Credentials

| Role          | Email                      | Password   |
|---------------|----------------------------|------------|
| Admin         | admin@dentaris.app         | password   |
| Dentist       | james@dentaris.app         | password   |
| Dentist       | emily@dentaris.app         | password   |
| Receptionist  | receptionist@dentaris.app  | password   |

## Project Structure

```
app/
├── Http/Controllers/
│   ├── Admin/          # ClinicController, StaffController
│   ├── Appointment/    # AppointmentController, DentalRecordController
│   ├── Billing/        # InvoiceController
│   ├── Patient/        # PatientController, PatientFileController
│   └── DashboardController.php
├── Models/             # Clinic, User, Patient, Appointment, Treatment,
│                       # DentalRecord, Invoice, InvoiceItem, Payment, PatientFile
resources/js/
├── Layouts/            # AppLayout.vue (sidebar + header)
├── Components/         # Modal, ConfirmModal, StatCard, StatusBadge, Pagination
└── Pages/
    ├── Dashboard.vue
    ├── Patients/       # Index, Show, Create, Edit, Odontogram
    ├── Appointments/   # Index (FullCalendar)
    ├── Billing/        # Index, Show, Create
    └── Admin/          # Staff/Index, Clinic/Settings
database/
├── migrations/         # 9 migration files
└── seeders/            # 1 clinic, 4 users, 24 treatments, 20 patients, ~50 appointments
```

## Environment Variables

Key settings in `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

# For MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=getsmile
# DB_USERNAME=root
# DB_PASSWORD=secret
```

## Notes

- Node.js 22+ is required (Vite 7 requires Node >= 20.19)
- Run `nvm use 22` if using nvm
- The app uses SQLite by default for zero-config development
- Files/uploads are stored in `storage/app/public` (symlinked to `public/storage`)
