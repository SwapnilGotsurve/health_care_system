# Requirements Document

## Introduction

The Health Alert Smart Health Monitoring System is a web-based application designed for college-level demonstration of a multi-role healthcare management system. The system enables patients to track their daily health metrics, doctors to monitor patient data and send alerts, and administrators to manage system users.

## Glossary

- **System**: The Health Alert Smart Health Monitoring System
- **Patient**: A registered user who can input and track health data
- **Doctor**: A medical professional who monitors patients and sends health alerts
- **Admin**: System administrator who manages user approvals and system oversight
- **Health_Data**: Daily health metrics including blood pressure, sugar level, and heart rate
- **Alert**: A message sent from doctor to patient regarding health concerns
- **Dashboard**: Main interface showing relevant information for each user role

## Requirements

### Requirement 1: Patient Registration and Authentication

**User Story:** As a patient, I want to register and login to the system, so that I can access my personal health monitoring features.

#### Acceptance Criteria

1. WHEN a patient visits the registration page, THE System SHALL display a form with name, email, and password fields
2. WHEN a patient submits valid registration data, THE System SHALL create a new user account with role "patient" and status "approved"
3. WHEN a patient attempts to register with an existing email, THE System SHALL prevent registration and display an error message
4. WHEN a patient enters valid login credentials, THE System SHALL authenticate them and redirect to the patient dashboard
5. WHEN a patient enters invalid credentials, THE System SHALL display an error message and remain on login page

### Requirement 2: Patient Health Data Management

**User Story:** As a patient, I want to add and view my daily health data, so that I can track my health metrics over time.

#### Acceptance Criteria

1. WHEN a patient accesses the add health data form, THE System SHALL display fields for systolic BP, diastolic BP, sugar level, and heart rate
2. WHEN a patient submits valid health data, THE System SHALL store the data with current timestamp and patient ID
3. WHEN a patient views their health history, THE System SHALL display all their health records in chronological order
4. WHEN a patient submits health data with missing required fields, THE System SHALL prevent submission and show validation errors
5. WHEN a patient submits health data with invalid numeric values, THE System SHALL reject the data and display appropriate error messages

### Requirement 3: Patient Alert Management

**User Story:** As a patient, I want to view alerts sent by my doctor, so that I can stay informed about my health status.

#### Acceptance Criteria

1. WHEN a patient accesses the alerts page, THE System SHALL display all alerts sent to them by doctors
2. WHEN displaying alerts, THE System SHALL show the message content, sender doctor, and timestamp
3. WHEN a patient views an alert, THE System SHALL mark it as "seen" in the database
4. WHEN no alerts exist for a patient, THE System SHALL display an appropriate "no alerts" message

### Requirement 4: Doctor Registration and Approval

**User Story:** As a doctor, I want to register for the system, so that I can monitor patients after admin approval.

#### Acceptance Criteria

1. WHEN a doctor visits the registration page, THE System SHALL display the same registration form as patients
2. WHEN a doctor submits registration data, THE System SHALL create a user account with role "doctor" and status "pending"
3. WHEN a doctor with pending status attempts to login, THE System SHALL deny access and display "awaiting approval" message
4. WHEN a doctor with approved status logs in, THE System SHALL authenticate them and redirect to doctor dashboard

### Requirement 5: Doctor Patient Management

**User Story:** As a doctor, I want to view and monitor my assigned patients, so that I can provide appropriate healthcare guidance.

#### Acceptance Criteria

1. WHEN a doctor accesses the patient list, THE System SHALL display all patients assigned to them
2. WHEN a doctor selects a patient, THE System SHALL display that patient's complete health history in table format
3. WHEN displaying patient health data, THE System SHALL show systolic BP, diastolic BP, sugar level, heart rate, and date for each entry
4. WHEN a patient has no health data, THE System SHALL display "no data available" message

### Requirement 6: Doctor Alert System

**User Story:** As a doctor, I want to send health alerts to patients, so that I can communicate important health information.

#### Acceptance Criteria

1. WHEN a doctor accesses the send alert page, THE System SHALL display a form with patient selection and message fields
2. WHEN a doctor sends an alert, THE System SHALL store the alert with doctor ID, patient ID, message, and timestamp
3. WHEN a doctor views sent alerts, THE System SHALL display all alerts they have sent with patient names and timestamps
4. WHEN a doctor attempts to send an empty alert message, THE System SHALL prevent submission and show validation error

### Requirement 7: Admin User Management

**User Story:** As an admin, I want to manage doctor approvals and view all system users, so that I can maintain system security and oversight.

#### Acceptance Criteria

1. WHEN an admin logs in, THE System SHALL authenticate using pre-existing admin credentials
2. WHEN an admin accesses doctor approvals, THE System SHALL display all doctors with "pending" status
3. WHEN an admin approves a doctor, THE System SHALL update the doctor's status to "approved"
4. WHEN an admin rejects a doctor, THE System SHALL delete the doctor's account from the system
5. WHEN an admin views all doctors, THE System SHALL display all doctors with their approval status
6. WHEN an admin views all patients, THE System SHALL display all registered patients

### Requirement 8: Database Structure and Data Persistence

**User Story:** As a system architect, I want a well-structured database, so that all user data and relationships are properly stored and maintained.

#### Acceptance Criteria

1. THE System SHALL use a users table with id, name, email, password, role, status, and created_at fields
2. THE System SHALL use a health_data table with id, patient_id, systolic_bp, diastolic_bp, sugar_level, heart_rate, and created_at fields
3. THE System SHALL use an alerts table with id, doctor_id, patient_id, message, status, and created_at fields
4. THE System SHALL use a doctor_patients table with id, doctor_id, and patient_id fields for patient assignments
5. WHEN any data is stored, THE System SHALL maintain referential integrity between related tables

### Requirement 9: User Interface and Experience

**User Story:** As any system user, I want a clean and modern interface, so that I can easily navigate and use the system features.

#### Acceptance Criteria

1. THE System SHALL use Tailwind CSS for all styling and layout
2. WHEN users interact with buttons and cards, THE System SHALL provide hover effects and smooth transitions
3. THE System SHALL display appropriate status badges for health data (Healthy/Alert based on values)
4. THE System SHALL provide responsive design that works on different screen sizes
5. WHEN users navigate between pages, THE System SHALL maintain consistent layout and styling

### Requirement 10: Session Management and Security

**User Story:** As a system user, I want secure session management, so that my account remains protected during use.

#### Acceptance Criteria

1. WHEN a user logs in successfully, THE System SHALL create a session with user ID and role
2. WHEN a user accesses role-specific pages, THE System SHALL verify their role matches the required access level
3. WHEN a user logs out, THE System SHALL destroy their session and redirect to login page
4. WHEN an unauthorized user attempts to access protected pages, THE System SHALL redirect them to the login page
5. THE System SHALL maintain session state across page navigation within the same browser session