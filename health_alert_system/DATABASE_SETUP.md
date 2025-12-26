# Database Setup Instructions

## Overview

This document provides step-by-step instructions for setting up the Health Alert System database with sample data.

## Prerequisites

- XAMPP (Apache + MySQL + PHP) installed and running
- phpMyAdmin access or MySQL command line
- Web browser

## Setup Steps

### 1. Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service
4. Verify both services are running (green status)

### 2. Create Database

#### Option A: Using phpMyAdmin (Recommended)

1. Open your web browser and go to `http://localhost/phpmyadmin`
2. Click on "SQL" tab in the top navigation
3. Copy and paste the entire contents of `database_setup.sql` file
4. Click "Go" to execute the script
5. Wait for the script to complete (you should see success messages)

#### Option B: Using MySQL Command Line

1. Open Command Prompt/Terminal
2. Navigate to your project directory
3. Run the following command:
   ```bash
   mysql -u root -p < database_setup.sql
   ```
4. Enter your MySQL password when prompted (default is empty for XAMPP)

### 3. Verify Database Setup

After running the setup script, you should see:

1. **Database Created**: `health_alert_system`
2. **Tables Created**: 
   - `users` (13 sample users)
   - `health_data` (30+ sample health records)
   - `alerts` (12 sample alerts)
   - `doctor_patients` (8 sample assignments)

3. **Success Messages**: The script will display:
   - "Database setup completed successfully!"
   - Sample login credentials
   - Summary statistics

### 4. Test Database Connection

1. Open your web browser
2. Navigate to `http://localhost/health_alert_system`
3. Try logging in with the sample accounts (see below)

## Sample Login Credentials

### Admin Account
- **Email**: admin@healthalert.com
- **Password**: admin123
- **Role**: Administrator

### Doctor Accounts
- **Email**: sarah.johnson@hospital.com
- **Password**: doctor123
- **Role**: Doctor (Approved)

- **Email**: michael.chen@clinic.com
- **Password**: doctor123
- **Role**: Doctor (Approved)

### Patient Accounts
- **Email**: john.smith@email.com
- **Password**: patient123
- **Role**: Patient

- **Email**: mary.johnson@email.com
- **Password**: patient123
- **Role**: Patient

## Database Schema

### Users Table
- Stores all system users (patients, doctors, admins)
- Includes role-based access control
- Doctor approval status management

### Health Data Table
- Patient health metrics (BP, sugar, heart rate)
- Timestamped entries for tracking
- Linked to patient accounts

### Alerts Table
- Doctor-to-patient communications
- Read/unread status tracking
- Message content and timestamps

### Doctor-Patients Table
- Assignment relationships
- Many-to-many mapping
- Assignment date tracking

## Sample Data Overview

The setup script includes:

- **1 Admin**: System administrator
- **3 Approved Doctors**: Can access patient data
- **2 Pending Doctors**: Awaiting admin approval
- **8 Patients**: With various health data patterns
- **30+ Health Records**: Spanning recent dates
- **12 Alerts**: Mix of read/unread status
- **8 Assignments**: Doctors assigned to patients

## Troubleshooting

### Common Issues

1. **"Connection failed" error**
   - Ensure MySQL service is running in XAMPP
   - Check database credentials in `config/db.php`

2. **"Table already exists" error**
   - The script includes DROP TABLE statements
   - Safe to re-run the entire script

3. **"Access denied" error**
   - Check MySQL user permissions
   - Default XAMPP uses root with no password

4. **Script timeout**
   - Increase PHP execution time in php.ini
   - Or run script in smaller sections

### Verification Queries

Run these queries in phpMyAdmin to verify setup:

```sql
-- Check user counts by role
SELECT role, COUNT(*) as count FROM users GROUP BY role;

-- Check health data entries
SELECT COUNT(*) as total_health_records FROM health_data;

-- Check doctor-patient assignments
SELECT COUNT(*) as total_assignments FROM doctor_patients;

-- Check alert status distribution
SELECT status, COUNT(*) as count FROM alerts GROUP BY status;
```

## Next Steps

After successful database setup:

1. **Test Login**: Try all sample accounts
2. **Explore Features**: Navigate through different user roles
3. **Add Data**: Create new health records and alerts
4. **Admin Functions**: Test doctor approval workflow
5. **Assignment Management**: Use admin assignment interface

## Security Notes

- Sample passwords are for development only
- Change default credentials before production use
- Consider password hashing for production deployment
- Review database permissions for production environment

## Support

If you encounter issues:

1. Check XAMPP error logs
2. Verify MySQL service status
3. Review PHP error messages
4. Ensure proper file permissions
5. Confirm database connection settings

The database setup provides a complete working environment for testing and development of the Health Alert System.