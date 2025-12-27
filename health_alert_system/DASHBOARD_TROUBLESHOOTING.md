# Doctor Dashboard Troubleshooting Guide

## Problem: "Unable to load alerts at this time" Error

This error occurs when the doctor dashboard cannot properly load data from the database. Here's how to fix it step by step.

## Quick Fix Steps

### Step 1: Run the Database Fix Script
1. Open your web browser
2. Navigate to: `http://localhost/health_care_system/health_alert_system/fix_dashboard_database.php`
3. This will automatically fix common database schema issues

### Step 2: Run Diagnostics
1. Navigate to: `http://localhost/health_care_system/health_alert_system/test_dashboard_queries.php`
2. This will show you exactly which queries are failing and why

### Step 3: Test the Dashboard
1. Login with these credentials:
   - **Email:** sarah.johnson@hospital.com
   - **Password:** doctor123
2. Go to the doctor dashboard and check if the error is resolved

## Common Issues and Solutions

### Issue 1: Database Connection Failed
**Symptoms:** "Service Temporarily Unavailable" page
**Solution:**
1. Make sure XAMPP is running
2. Start MySQL service in XAMPP Control Panel
3. Check if database `health_alert_system` exists in phpMyAdmin

### Issue 2: Tables Don't Exist
**Symptoms:** "Table 'xyz' doesn't exist" errors in diagnostics
**Solution:**
1. Open phpMyAdmin
2. Import the `database_setup.sql` file
3. This will create all required tables with sample data

### Issue 3: User Status Enum Mismatch
**Symptoms:** Status-related errors in diagnostics
**Solution:**
The fix script automatically updates the user status enum from `('pending', 'approved')` to `('active', 'pending', 'inactive')`.

### Issue 4: Plain Text Passwords
**Symptoms:** Login fails even with correct credentials
**Solution:**
The fix script automatically hashes all plain text passwords using PHP's `password_hash()` function.

### Issue 5: No Sample Data
**Symptoms:** Dashboard loads but shows all zeros
**Solution:**
1. Run the complete `database_setup.sql` script
2. This creates sample doctors, patients, assignments, and health data

## Manual Database Setup

If the automated scripts don't work, you can manually set up the database:

### 1. Create Database
```sql
CREATE DATABASE IF NOT EXISTS health_alert_system;
USE health_alert_system;
```

### 2. Import Schema
Run the complete `database_setup.sql` file in phpMyAdmin or MySQL command line.

### 3. Verify Sample Data
Check that you have:
- At least 3 doctors with role='doctor' and status='active'
- At least 8 patients with role='patient' and status='active'
- Doctor-patient assignments in the `doctor_patients` table
- Sample health data and alerts

## Test Login Credentials

After running the setup, use these credentials to test:

### Doctor Accounts
- **Dr. Sarah Johnson:** sarah.johnson@hospital.com / doctor123
- **Dr. Michael Chen:** michael.chen@clinic.com / doctor123
- **Dr. Emily Rodriguez:** emily.rodriguez@medical.com / doctor123

### Patient Accounts
- **John Smith:** john.smith@email.com / patient123
- **Mary Johnson:** mary.johnson@email.com / patient123

### Admin Account
- **System Admin:** admin@healthalert.com / admin123

## Debugging Steps

### 1. Check Error Logs
Look for PHP errors in:
- XAMPP logs folder
- Browser developer console
- PHP error_log files

### 2. Enable Debug Mode
Add this to the top of `dashboard.php` for detailed error reporting:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### 3. Test Individual Queries
Use the diagnostic script to test each dashboard query individually and identify the specific failing query.

### 4. Check Database Structure
Verify table structure matches the schema:
```sql
DESCRIBE users;
DESCRIBE doctor_patients;
DESCRIBE alerts;
DESCRIBE health_data;
```

## Expected Database Schema

### Users Table
- `status` should be ENUM('active', 'pending', 'inactive')
- Passwords should be hashed (60+ characters)
- Doctors should have status='active' to access dashboard

### Alerts Table
- `status` should be ENUM('sent', 'seen')
- Must have foreign keys to users table

### Doctor-Patients Table
- Must have proper foreign key relationships
- Should contain sample assignments for testing

## Still Having Issues?

If you're still experiencing problems:

1. **Check XAMPP Version:** Ensure you're using a recent version of XAMPP with PHP 7.4+ and MySQL 5.7+

2. **Verify File Paths:** Make sure all file paths in your setup match the actual directory structure

3. **Check Permissions:** Ensure PHP has read/write permissions to the database and log files

4. **Clear Browser Cache:** Sometimes cached files can cause issues

5. **Test with Different Browser:** Rule out browser-specific issues

## Success Indicators

You'll know everything is working when:
- ✅ Diagnostic script shows all green checkmarks
- ✅ Dashboard loads without errors
- ✅ Stats cards show actual numbers (not all zeros)
- ✅ Patient lists and alerts display properly
- ✅ No PHP errors in browser console or logs

## Contact Information

If you continue to have issues after following this guide, the problem may be related to your specific XAMPP configuration or system setup.