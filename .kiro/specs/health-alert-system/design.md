# Design Document: Health Alert Smart Health Monitoring System

## Overview

The Health Alert Smart Health Monitoring System is a web-based application built using PHP (procedural), MySQL, and Tailwind CSS. The system implements a three-tier architecture with role-based access control for patients, doctors, and administrators. The application follows a simple MVC-like structure while maintaining college-project simplicity with readable, non-OOP PHP code.

## Architecture

### Technology Stack
- **Backend**: PHP 7.4+ (procedural programming)
- **Database**: MySQL 5.7+
- **Server**: XAMPP (Apache + MySQL + PHP)
- **Frontend**: HTML5 + Tailwind CSS + Vanilla JavaScript
- **Session Management**: PHP Sessions
- **Database Access**: MySQLi (procedural)

### Application Structure
```
/health_alert_system/
├── config/
│   └── db.php                 # Database connection
├── admin/
│   ├── dashboard.php
│   ├── doctor_approvals.php
│   ├── doctor_list.php
│   └── patient_list.php
├── doctor/
│   ├── dashboard.php
│   ├── patient_list.php
│   ├── patient_stats.php
│   ├── send_alert.php
│   └── sent_alerts.php
├── patient/
│   ├── dashboard.php
│   ├── add_health_data.php
│   ├── health_history.php
│   └── alerts.php
├── assets/
│   ├── css/
│   └── js/
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── auth_check.php
├── index.php                  # Login page
├── register.php
├── logout.php
└── README.md
```

## Components and Interfaces

### Database Layer (config/db.php)
- Establishes MySQLi connection to MySQL database
- Provides connection error handling
- Uses procedural MySQLi functions throughout application

### Authentication System
- **Session Management**: PHP sessions store user_id, role, and name
- **Role-based Access**: Each directory (admin/, doctor/, patient/) includes auth_check.php
- **Login Flow**: index.php validates credentials and creates sessions
- **Registration Flow**: register.php creates new users with appropriate roles and status

### User Roles and Access Control

#### Patient Module
- **Dashboard**: Overview of recent health data and alert count
- **Add Health Data**: Form for daily health metrics input
- **Health History**: Chronological display of all health records
- **Alerts**: View and mark alerts from doctors as seen

#### Doctor Module  
- **Dashboard**: Statistics on assigned patients and alerts
- **Patient List**: View all assigned patients
- **Patient Stats**: Detailed health data view for selected patient
- **Send Alert**: Form to send health alerts to patients
- **Sent Alerts**: History of all sent alerts

#### Admin Module
- **Dashboard**: System overview with user counts
- **Doctor Approvals**: Approve/reject pending doctor registrations
- **Doctor List**: View all doctors and their status
- **Patient List**: View all registered patients

## Data Models

### Database Schema

#### users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'admin') NOT NULL,
    status ENUM('pending', 'approved') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### health_data Table
```sql
CREATE TABLE health_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    systolic_bp INT NOT NULL,
    diastolic_bp INT NOT NULL,
    sugar_level DECIMAL(5,2) NOT NULL,
    heart_rate INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### alerts Table
```sql
CREATE TABLE alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'seen') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### doctor_patients Table
```sql
CREATE TABLE doctor_patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (doctor_id, patient_id)
);
```

### Data Validation Rules
- **Blood Pressure**: Systolic (80-200), Diastolic (50-120)
- **Sugar Level**: 70-400 mg/dL
- **Heart Rate**: 40-200 BPM
- **Email**: Valid email format, unique across users
- **Password**: Minimum 6 characters (college project - no hashing)
- **Names**: Non-empty, maximum 100 characters

## User Interface Design

### Design System
- **Framework**: Tailwind CSS via CDN
- **Color Scheme**: Blue primary (#3B82F6), green success (#10B981), red danger (#EF4444)
- **Typography**: Default Tailwind font stack
- **Components**: Cards, badges, buttons, tables, forms
- **Responsive**: Mobile-first approach with Tailwind responsive classes

### Component Library
- **Status Badges**: Health status indicators (Normal/Alert based on values)
- **Data Cards**: Dashboard statistics display
- **Data Tables**: Health records and user lists
- **Forms**: Consistent styling for all input forms
- **Navigation**: Role-based menu system
- **Alerts**: Success/error message display

### Micro-interactions
- **Hover Effects**: `hover:scale-105` on cards and buttons
- **Transitions**: `transition-all duration-200` for smooth animations
- **Focus States**: Tailwind focus rings on form inputs
- **Loading States**: Simple text feedback on form submissions

## Error Handling

### Database Errors
- Connection failures display generic error message
- Query failures log to PHP error log
- Graceful degradation for non-critical features

### Validation Errors
- Client-side: Basic HTML5 validation
- Server-side: PHP validation with error message display
- Form data persistence on validation failure

### Authentication Errors
- Invalid credentials show generic "Invalid login" message
- Unauthorized access redirects to login page
- Session timeout handling with redirect

### User Experience Errors
- 404 pages for missing resources
- Friendly error messages for user actions
- Form validation feedback with specific field errors

## Testing Strategy

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property-Based Testing Overview

Property-based testing validates software correctness by testing universal properties across many generated inputs. Each property is a formal specification that should hold for all valid inputs.

Now I need to analyze the acceptance criteria to determine which ones can be tested as properties.

## Correctness Properties

Based on the prework analysis, I've identified the following testable properties that ensure system correctness:

### Property 1: User Registration Creates Correct Account Type
*For any* valid registration data with role "patient", the system should create a user account with role "patient" and status "approved"
**Validates: Requirements 1.2**

### Property 2: Valid Login Authentication
*For any* user with valid credentials, the login system should authenticate them and redirect to their role-appropriate dashboard
**Validates: Requirements 1.4, 4.4**

### Property 3: Invalid Credentials Rejection
*For any* invalid credential combination, the login system should reject authentication and display an error message
**Validates: Requirements 1.5**

### Property 4: Health Data Storage Integrity
*For any* valid health data submission, the system should store the data with correct patient ID and current timestamp
**Validates: Requirements 2.2**

### Property 5: Health History Chronological Ordering
*For any* patient with multiple health records, the health history display should show all records in chronological order (newest first)
**Validates: Requirements 2.3**

### Property 6: Health Data Validation
*For any* health data with missing required fields or invalid numeric values, the system should reject submission and display appropriate validation errors
**Validates: Requirements 2.4, 2.5**

### Property 7: Alert Display Completeness
*For any* patient with alerts, the alerts page should display all alerts sent to them with complete information (message, doctor, timestamp)
**Validates: Requirements 3.1, 3.2**

### Property 8: Alert Status Update
*For any* alert viewed by a patient, the system should mark that alert as "seen" in the database
**Validates: Requirements 3.3**

### Property 9: Doctor Registration Status
*For any* doctor registration submission, the system should create a user account with role "doctor" and status "pending"
**Validates: Requirements 4.2**

### Property 10: Pending Doctor Access Control
*For any* doctor with "pending" status, the system should deny login access and display "awaiting approval" message
**Validates: Requirements 4.3**

### Property 11: Doctor Patient Assignment Display
*For any* doctor with assigned patients, the patient list should display all and only the patients assigned to that doctor
**Validates: Requirements 5.1**

### Property 12: Patient Health Data Display
*For any* selected patient, the system should display their complete health history with all required fields (systolic BP, diastolic BP, sugar level, heart rate, date)
**Validates: Requirements 5.2, 5.3**

### Property 13: Alert Storage Integrity
*For any* alert sent by a doctor, the system should store the alert with correct doctor ID, patient ID, message, and timestamp
**Validates: Requirements 6.2**

### Property 14: Sent Alerts Display
*For any* doctor with sent alerts, the sent alerts page should display all alerts they have sent with patient names and timestamps
**Validates: Requirements 6.3**

### Property 15: Admin Doctor Approval
*For any* pending doctor, when an admin approves them, the system should update their status to "approved"
**Validates: Requirements 7.3**

### Property 16: Admin Doctor Rejection
*For any* pending doctor, when an admin rejects them, the system should delete their account from the system
**Validates: Requirements 7.4**

### Property 17: Admin User Lists
*For any* admin viewing user lists, the system should display all doctors with their status and all registered patients
**Validates: Requirements 7.2, 7.5, 7.6**

### Property 18: Database Referential Integrity
*For any* data storage operation involving related tables, the system should maintain referential integrity between foreign key relationships
**Validates: Requirements 8.5**

### Property 19: Health Status Badge Logic
*For any* health data entry, the system should display appropriate status badges based on health value ranges (Normal/Alert)
**Validates: Requirements 9.3**

### Property 20: Session Management
*For any* successful login, the system should create a session with correct user ID and role, and maintain it across page navigation
**Validates: Requirements 10.1, 10.5**

### Property 21: Role-Based Access Control
*For any* user attempting to access role-specific pages, the system should verify their role matches the required access level
**Validates: Requirements 10.2**

### Property 22: Logout Session Destruction
*For any* logged-in user, the logout function should destroy their session and redirect to the login page
**Validates: Requirements 10.3**

### Property 23: Unauthorized Access Protection
*For any* unauthorized user attempting to access protected pages, the system should redirect them to the login page
**Validates: Requirements 10.4**

## Testing Strategy

### Dual Testing Approach
The system will use both unit testing and property-based testing to ensure comprehensive coverage:

**Unit Tests**: Verify specific examples, edge cases, and error conditions
- Test specific user registration scenarios
- Test empty form submissions
- Test database connection failures
- Test specific health value ranges
- Test admin login with pre-existing credentials

**Property Tests**: Verify universal properties across all inputs
- Use PHPUnit with property-based testing extensions
- Minimum 100 iterations per property test
- Each property test references its design document property
- Tag format: **Feature: health-alert-system, Property {number}: {property_text}**

### Testing Configuration
- **Framework**: PHPUnit for PHP testing
- **Database**: Use test database with same schema
- **Property Testing**: Generate random valid data for comprehensive testing
- **Coverage**: Both functional behavior and data integrity
- **Integration**: Test complete user workflows end-to-end

### Test Data Generation
- **Users**: Random names, emails, passwords with different roles
- **Health Data**: Random values within valid ranges
- **Alerts**: Random messages between doctors and patients
- **Edge Cases**: Boundary values, empty inputs, invalid data types

The testing strategy ensures that both specific examples work correctly (unit tests) and that the system behaves correctly across all possible inputs (property tests), providing confidence in system reliability and correctness.