# Health Alert System

A comprehensive health monitoring system built with PHP, MySQL, and Tailwind CSS for managing patient health data, doctor-patient relationships, and health alerts.

## Features

### Patient Module
- **Dashboard**: Overview of health status and recent alerts
- **Health Data Entry**: Record blood pressure, sugar levels, and heart rate
- **Health History**: View chronological health records with status indicators
- **Alerts Management**: Receive and manage alerts from doctors

### Doctor Module
- **Dashboard**: Patient statistics and activity overview
- **Patient Management**: View assigned patients and their health data
- **Alert System**: Send customized health alerts to patients
- **Sent Alerts History**: Track all sent alerts with filtering options

### Admin Module
- **System Dashboard**: Overview of users and system statistics
- **Doctor Approvals**: Approve or reject doctor registration requests
- **User Management**: Manage doctors and patients with search functionality
- **Patient Assignment**: Assign patients to doctors for monitoring

## System Requirements

- **Web Server**: Apache (XAMPP recommended)
- **PHP**: Version 7.4 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Browser**: Modern web browser with JavaScript enabled

## Installation & Setup

### 1. XAMPP Installation
1. Download and install [XAMPP](https://www.apachefriends.org/download.html)
2. Start Apache and MySQL services from XAMPP Control Panel

### 2. Project Setup
1. Clone or download the project files
2. Copy the `health_alert_system` folder to your XAMPP `htdocs` directory
   ```
   C:\xampp\htdocs\health_alert_system\
   ```

### 3. Database Configuration
1. Open phpMyAdmin in your browser: `http://localhost/phpmyadmin`
2. Create a new database named `health_alert_system`
3. Import the database schema and sample data:
   - Click on the `health_alert_system` database
   - Go to the "Import" tab
   - Select the `database_setup.sql` file from the project root
   - Click "Go" to execute the import

### 4. Database Connection Setup
The database connection is already configured in `config/db.php` with default XAMPP settings:
- **Host**: localhost
- **Username**: root
- **Password**: (empty)
- **Database**: health_alert_system

If your XAMPP MySQL has different credentials, update the `config/db.php` file accordingly.

### 5. Access the Application
Open your web browser and navigate to:
```
http://localhost/health_alert_system/
```

## Default Login Credentials

The system comes with pre-configured demo accounts:

### Admin Account
- **Email**: admin@hospital.com
- **Password**: admin123

### Doctor Account
- **Email**: dr.smith@hospital.com
- **Password**: doctor123

### Patient Account
- **Email**: john.doe@email.com
- **Password**: patient123

## User Registration

### Patient Registration
- Patients can register directly through the registration form
- Account is activated immediately upon registration
- Patients need to be assigned to a doctor by an admin

### Doctor Registration
- Doctors can register through the registration form
- Account status is set to "pending" and requires admin approval
- Once approved, doctors can access the system and manage patients

## System Architecture

### File Structure
```
health_alert_system/
├── admin/              # Admin module pages
├── doctor/             # Doctor module pages  
├── patient/            # Patient module pages
├── assets/             # CSS and JavaScript files
├── config/             # Database configuration
├── includes/           # Shared components and authentication
├── tests/              # PHPUnit test files
├── database_setup.sql  # Database schema and sample data
└── index.php          # Login page
```

### Database Schema
- **users**: User accounts with role-based access
- **health_data**: Patient health records
- **alerts**: Doctor-to-patient alert messages
- **doctor_patients**: Doctor-patient assignments

## Security Features

- **Role-based Access Control**: Separate access levels for patients, doctors, and admins
- **Session Management**: Secure session handling with automatic timeouts
- **Input Validation**: Server-side validation for all user inputs
- **SQL Injection Protection**: Prepared statements and input sanitization
- **Authentication Middleware**: Protected routes with automatic redirects

## Testing

The system includes comprehensive PHPUnit tests covering:
- Authentication and authorization
- Data validation and storage
- Role-based functionality
- Integration workflows

To run tests (requires PHPUnit):
```bash
cd health_alert_system
phpunit --configuration phpunit.xml
```

## Troubleshooting

### Common Issues

**Database Connection Error**
- Ensure MySQL service is running in XAMPP
- Verify database credentials in `config/db.php`
- Check if `health_alert_system` database exists

**Page Not Found (404)**
- Ensure the project is in the correct XAMPP htdocs directory
- Check that Apache service is running
- Verify the URL path is correct

**Login Issues**
- Use the default credentials provided above
- Ensure the database has been properly imported
- Check that the user account exists in the database

**Permission Denied**
- Ensure proper file permissions on the project directory
- Check XAMPP directory permissions

### Support
For technical issues or questions about the system, refer to the code comments and database schema documentation in the respective files.

## Development Notes

- Built with procedural PHP for educational purposes
- Uses Tailwind CSS via CDN for responsive styling
- Implements property-based testing for robust validation
- Follows college-level coding standards and practices