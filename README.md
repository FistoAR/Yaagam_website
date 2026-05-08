# Sree Kanika Parameshwari Trust - Website & Admin Portal

This project consists of an Astro-based frontend and a PHP-based backend for managing Yaagam registrations and QR code verification.

## 🚀 Project Structure

- `/src`: Astro frontend source code.
  - `/pages/admin`: Admin portal pages (Login, Dashboard, QR Generator, Employees, Reports).
  - `/layouts/AdminLayout.astro`: Shared layout for admin pages.
  - `/styles/admin.css`: Premium dark theme styling.
- `/backend`: PHP API and Database setup.
  - `/config`: Database and Auth configurations.
  - `/api`: REST API endpoints.
  - `setup.sql`: Database schema.
  - `setup.php`: One-time setup script.

## 🔑 Emergency Login Details

In case of emergency or if you are locked out, use the following credentials:

| Type | Username | Password |
| :--- | :--- | :--- |
| **Emergency Admin** | `emergency_admin` | `KPT@Secure2026!` |
| **Default Admin** | `admin` | `admin123` |

> [!IMPORTANT]
> Please change these passwords after the first login for security.

## 🛠 Setup Instructions

### 1. Database Setup
1. Create a MySQL database (e.g., `fisto_yaagam`).
2. Update the credentials in `backend/config/database.php`.
3. Run the SQL schema in `backend/setup.sql` or execute `php backend/setup.php` to initialize the database and seed the admin accounts.

### 2. Frontend Setup
1. Install dependencies: `npm install`
2. Start the development server: `npm run dev`
3. The admin portal will be available at `http://localhost:4321/admin`.

## 📱 QR Code Verification API

The backend provides a specific endpoint for the mobile app to verify QR codes:

- **GET `/api/qr/verify.php?key=VERIFICATION_KEY`**: Checks if a QR code is valid and its current status.
- **POST `/api/qr/verify.php`**: Marks a QR code as verified.
  - Body: `{ "key": "VERIFICATION_KEY", "verified_by": "Scanner Name" }`

## ✨ Features
- **Dashboard**: Real-time statistics and activity logs.
- **QR Generator**: Create unique, trackable QR codes for customers.
- **Reports**: Comprehensive daily and yaagam-wise reporting with CSV export.
- **Employee Management**: Admin-only tool for managing staff access.
- **Premium UI**: Modern dark theme with gold accents, fully responsive.
