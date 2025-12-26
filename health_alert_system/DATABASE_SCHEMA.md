# Database Schema Documentation

## Overview

The Health Alert System uses a MySQL database with four main tables that handle user management, health data storage, alert messaging, and doctor-patient relationships. The schema is designed for a college-level health monitoring application with role-based access control.

## Database: `health_alert_system`

### Table Structure

#### 1. `users` Table
**Purpose**: Stores all user accounts (patients, doctors, administrators)

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'admin') NOT NULL,
    status ENUM('active', 'pending', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Fields Description**:
- `id`: Unique identifier for each user (Primary Key)
- `name`: Full name of the user
- `email`: Unique email address used for login
- `password`: Hashed password (using PHP's `password_hash()`)
- `role`: User type - determines access permissions
- `status`: Account status - doctors start as 'pending' until admin approval
- `created_at`: Account creation timestamp

**Indexes**:
- Primary Key: `id`
- Unique Index: `email`
- Index: `role` (for role-based queries)
- Index: `status` (for filtering by account status)

#### 2. `health_data` Table
**Purpose**: Stores patient health measurements and vital signs

```sql
CREATE TABLE health_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    systolic_bp INT NOT NULL,
    diastolic_bp INT NOT NULL,
    sugar_level DECIMAL(5,2) NOT NULL,
    heart_rate INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Fields Description**:
- `id`: Unique identifier for each health record
- `patient_id`: Foreign key linking to users table (patient only)
- `systolic_bp`: Systolic blood pressure (mmHg)
- `diastolic_bp`: Diastolic blood pressure (mmHg)
- `sugar_level`: Blood glucose level (mg/dL) - supports decimal values
- `heart_rate`: Heart rate (beats per minute)
- `created_at`: Timestamp when measurement was recorded

**Constraints**:
- Foreign Key: `patient_id` → `users(id)` with CASCADE delete
- Check constraints (application level):
  - Systolic BP: 70-250 mmHg
  - Diastolic BP: 40-150 mmHg
  - Sugar Level: 50-500 mg/dL
  - Heart Rate: 30-220 bpm

**Indexes**:
- Primary Key: `id`
- Foreign Key Index: `patient_id`
- Composite Index: `patient_id, created_at` (for patient history queries)

#### 3. `alerts` Table
**Purpose**: Stores alert messages sent from doctors to patients

```sql
CREATE TABLE alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'seen') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Fields Description**:
- `id`: Unique identifier for each alert
- `doctor_id`: Foreign key to users table (doctor who sent the alert)
- `patient_id`: Foreign key to users table (patient receiving the alert)
- `message`: Alert message content (supports up to 65,535 characters)
- `status`: Alert status - 'sent' (unread) or 'seen' (read by patient)
- `created_at`: Timestamp when alert was sent

**Constraints**:
- Foreign Key: `doctor_id` → `users(id)` with CASCADE delete
- Foreign Key: `patient_id` → `users(id)` with CASCADE delete
- Message length: 1-1000 characters (application validation)

**Indexes**:
- Primary Key: `id`
- Foreign Key Index: `doctor_id`
- Foreign Key Index: `patient_id`
- Composite Index: `patient_id, status` (for patient alert queries)
- Composite Index: `doctor_id, created_at` (for doctor sent alerts)

#### 4. `doctor_patients` Table
**Purpose**: Many-to-many relationship between doctors and patients

```sql
CREATE TABLE doctor_patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (doctor_id, patient_id)
);
```

**Fields Description**:
- `id`: Unique identifier for each assignment
- `doctor_id`: Foreign key to users table (doctor)
- `patient_id`: Foreign key to users table (patient)
- `assigned_at`: Timestamp when assignment was created

**Constraints**:
- Foreign Key: `doctor_id` → `users(id)` with CASCADE delete
- Foreign Key: `patient_id` → `users(id)` with CASCADE delete
- Unique Constraint: `(doctor_id, patient_id)` - prevents duplicate assignments

**Indexes**:
- Primary Key: `id`
- Unique Index: `doctor_id, patient_id`
- Index: `patient_id` (for reverse lookups)

## Relationships

### Entity Relationship Diagram (ERD)

```
users (1) ←→ (M) health_data
  ↑                    ↑
  |                    |
  | (doctor_id)        | (patient_id)
  |                    |
  ↓                    ↓
alerts (M) ←→ (1) users (1) ←→ (M) doctor_patients
  ↑                              ↓
  |                              |
  | (patient_id)                 | (patient_id)
  |                              |
  ↓                              ↓
users (1) ←→ (M) doctor_patients
```

### Relationship Details

1. **Users → Health Data** (One-to-Many)
   - One patient can have multiple health records
   - Cascade delete: Deleting a patient removes all their health data

2. **Users → Alerts** (One-to-Many, bidirectional)
   - One doctor can send multiple alerts
   - One patient can receive multiple alerts
   - Cascade delete: Deleting a user removes related alerts

3. **Users → Doctor-Patients** (Many-to-Many)
   - One doctor can be assigned to multiple patients
   - One patient can be assigned to multiple doctors
   - Junction table manages the relationship

## Sample Data

The database includes sample data for testing and demonstration:

### Default Admin Account
- **Email**: admin@hospital.com
- **Password**: admin123
- **Role**: admin

### Sample Doctor Account
- **Email**: dr.smith@hospital.com
- **Password**: doctor123
- **Role**: doctor
- **Status**: active

### Sample Patient Account
- **Email**: john.doe@email.com
- **Password**: patient123
- **Role**: patient

## Query Patterns

### Common Query Examples

#### 1. Get Patient's Recent Health Data
```sql
SELECT * FROM health_data 
WHERE patient_id = ? 
ORDER BY created_at DESC 
LIMIT 10;
```

#### 2. Get Doctor's Assigned Patients
```sql
SELECT u.id, u.name, u.email 
FROM users u
JOIN doctor_patients dp ON u.id = dp.patient_id
WHERE dp.doctor_id = ? AND u.role = 'patient';
```

#### 3. Get Unread Alerts for Patient
```sql
SELECT a.*, u.name as doctor_name 
FROM alerts a
JOIN users u ON a.doctor_id = u.id
WHERE a.patient_id = ? AND a.status = 'sent'
ORDER BY a.created_at DESC;
```

#### 4. Get Patient Activity Statistics
```sql
SELECT 
    COUNT(*) as total_records,
    MAX(created_at) as last_entry,
    AVG(systolic_bp) as avg_systolic,
    AVG(diastolic_bp) as avg_diastolic
FROM health_data 
WHERE patient_id = ?
AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## Performance Considerations

### Indexing Strategy
- All foreign keys are indexed for join performance
- Composite indexes on frequently queried column combinations
- Unique constraints prevent data duplication

### Query Optimization
- Use LIMIT clauses for pagination
- Filter by date ranges using indexed timestamp columns
- Use prepared statements to prevent SQL injection

### Maintenance
- Regular cleanup of old health data (if required)
- Monitor index usage and query performance
- Consider partitioning for large datasets in production

## Security Features

### Data Protection
- Password hashing using PHP's `password_hash()`
- Foreign key constraints maintain referential integrity
- Cascade deletes prevent orphaned records

### Access Control
- Role-based permissions enforced at application level
- Session-based authentication
- Input validation and sanitization

### Audit Trail
- All tables include `created_at` timestamps
- User actions can be logged for compliance
- Data modification history can be tracked if needed

## Backup and Recovery

### Backup Strategy
```sql
-- Full database backup
mysqldump -u root -p health_alert_system > backup.sql

-- Table-specific backup
mysqldump -u root -p health_alert_system users > users_backup.sql
```

### Recovery
```sql
-- Restore full database
mysql -u root -p health_alert_system < backup.sql

-- Restore specific table
mysql -u root -p health_alert_system < users_backup.sql
```

## Migration Notes

### Version Control
- Use versioned migration scripts for schema changes
- Test migrations on development data before production
- Maintain rollback scripts for each migration

### Data Migration
- Export/import procedures for data transfer
- Validation scripts to verify data integrity
- Performance testing for large datasets