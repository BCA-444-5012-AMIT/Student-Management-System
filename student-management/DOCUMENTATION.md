# Student Management System - Complete Documentation

**Version:** 1.0.0  
**Author:** Student Management System Team  
**Project Type:** BCA Final Year Project  
**Technology Stack:** PHP 8.x, MySQL, Bootstrap 5  

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Database Design](#database-design)
4. [Core Modules](#core-modules)
5. [Authentication & Security](#authentication--security)
6. [API Documentation](#api-documentation)
7. [User Interface Design](#user-interface-design)
8. [Installation & Deployment](#installation--deployment)
9. [Testing & Quality Assurance](#testing--quality-assurance)
10. [Future Enhancements](#future-enhancements)
11. [Troubleshooting Guide](#troubleshooting-guide)
12. [Appendix](#appendix)

---

## Project Overview

### Introduction
The Student Management System is a comprehensive web-based application designed to streamline the management of educational institutions. It provides a centralized platform for managing students, teachers, courses, attendance, and academic records.

### Objectives
- Automate student and teacher management processes
- Provide role-based access control for different user types
- Enable efficient course management and enrollment tracking
- Implement robust attendance tracking and reporting
- Ensure data security and integrity

### Key Features
- Multi-user role system (Admin, Teacher, Student)
- Secure authentication and session management
- Comprehensive student and teacher profiles
- Course management with enrollment system
- Attendance tracking and reporting
- Responsive web design
- Real-time dashboard analytics

---

## System Architecture

### Architecture Overview
The system follows a three-tier architecture:

1. **Presentation Layer:** HTML5, CSS3, JavaScript, Bootstrap 5
2. **Business Logic Layer:** PHP 8.x with PDO
3. **Data Layer:** MySQL database with InnoDB engine

### Directory Structure
```
student-management/
├── config/                 # Configuration files
│   └── config.php         # Database and app configuration
├── auth/                  # Authentication modules
│   ├── login.php          # Login page
│   ├── logout.php         # Logout functionality
│   └── unauthorized.php   # Access denied page
├── admin/                 # Admin modules
│   ├── dashboard.php      # Admin dashboard
│   ├── students.php       # Student management
│   ├── teachers.php       # Teacher management
│   ├── courses.php        # Course management
│   ├── attendance.php     # Attendance overview
│   ├── enrollments.php    # Enrollment management
│   ├── reports.php        # Reports generation
│   └── student_report.php # Student reports
├── teacher/               # Teacher modules
│   ├── dashboard.php      # Teacher dashboard
│   ├── students.php       # Assigned students
│   ├── courses.php        # Assigned courses
│   ├── attendance.php     # Attendance marking
│   ├── profile.php        # Teacher profile
│   └── student_report.php # Student reports
├── student/               # Student modules
│   ├── dashboard.php      # Student dashboard
│   ├── profile.php        # Student profile
│   ├── courses.php        # Enrolled courses
│   ├── attendance.php     # Attendance view
│   └── grades.php         # Grades (placeholder)
├── includes/              # Common components
│   ├── header.php         # Page header
│   ├── footer.php         # Page footer
│   └── sidebar.php        # Navigation sidebar
├── assets/                # Static assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── images/           # Image files
├── database.sql          # Database schema
├── index.php             # Home page
└── README.md             # Project readme
```

### Design Patterns
- **MVC Pattern:** Separation of concerns between data, logic, and presentation
- **Singleton Pattern:** Database connection management
- **Factory Pattern:** User role management
- **Observer Pattern:** Event handling for attendance and notifications

---

## Database Design

### Database Schema Overview
The system uses MySQL with the following main tables:

#### 1. users Table
**Purpose:** Authentication and user management
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    password_reset_token VARCHAR(255) DEFAULT NULL,
    password_reset_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 2. students Table
**Purpose:** Student personal and academic information
```sql
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    roll_number VARCHAR(20) UNIQUE NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    phone VARCHAR(15) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    enrollment_date DATE NOT NULL,
    batch_year VARCHAR(10) DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    section VARCHAR(10) DEFAULT NULL,
    blood_group VARCHAR(5) DEFAULT NULL,
    nationality VARCHAR(50) DEFAULT NULL,
    religion VARCHAR(50) DEFAULT NULL,
    category ENUM('general', 'sc', 'st', 'obc', 'other') DEFAULT 'general',
    admission_type ENUM('regular', 'management', 'nri', 'other') DEFAULT 'regular',
    father_name VARCHAR(50) DEFAULT NULL,
    mother_name VARCHAR(50) DEFAULT NULL,
    guardian_name VARCHAR(50) DEFAULT NULL,
    parent_phone VARCHAR(15) DEFAULT NULL,
    parent_email VARCHAR(100) DEFAULT NULL,
    emergency_contact VARCHAR(15) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 3. teachers Table
**Purpose:** Teacher professional information
```sql
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    phone VARCHAR(15) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    qualification VARCHAR(100) DEFAULT NULL,
    specialization VARCHAR(100) DEFAULT NULL,
    hire_date DATE NOT NULL,
    employment_type ENUM('permanent', 'contract', 'visiting', 'other') DEFAULT 'permanent',
    department VARCHAR(100) DEFAULT NULL,
    experience_years DECIMAL(3,1) DEFAULT 0,
    salary DECIMAL(10,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 4. courses Table
**Purpose:** Course information and management
```sql
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    credits INT DEFAULT 3,
    department VARCHAR(100) DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    course_type ENUM('theory', 'practical', 'elective', 'core') DEFAULT 'core',
    max_students INT DEFAULT 50,
    teacher_id INT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);
```

#### 5. enrollments Table
**Purpose:** Student-course enrollment tracking
```sql
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'completed', 'dropped', 'failed') DEFAULT 'active',
    grade VARCHAR(5) DEFAULT NULL,
    attendance_percentage DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id)
);
```

#### 6. attendance Table
**Purpose:** Attendance records and tracking
```sql
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
    remarks TEXT DEFAULT NULL,
    marked_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, course_id, attendance_date)
);
```

### Entity Relationship Diagram
```
users (1) ──────── (1) students
users (1) ──────── (1) teachers
courses (1) ─────── (N) enrollments (N) ─────── (1) students
courses (1) ─────── (N) attendance (N) ─────── (1) students
teachers (1) ─────── (N) courses
teachers (1) ─────── (N) attendance (marked_by)
```

### Database Optimization
- **Indexing:** Proper indexes on frequently queried columns
- **Foreign Keys:** Referential integrity with cascading deletes
- **Normalization:** Third normal form to reduce redundancy
- **Constraints:** Unique constraints to prevent duplicate data

---

## Core Modules

### 1. Authentication Module

#### Login System
**File:** `auth/login.php`

**Features:**
- Username/email login support
- Password validation with bcrypt hashing
- Session management
- Role-based redirection
- Forgot password functionality
- Input validation and sanitization

**Code Implementation:**
```php
<?php
session_start();
require_once '../config/config.php';

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    // Validate credentials
    $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        // Redirect based on role
        switch ($user['role']) {
            case 'admin':
                redirect('admin/dashboard.php');
                break;
            case 'teacher':
                redirect('teacher/dashboard.php');
                break;
            case 'student':
                redirect('student/dashboard.php');
                break;
        }
    }
}
?>
```

#### Security Features
- **Password Hashing:** Uses PHP's `password_hash()` with bcrypt
- **Session Security:** HTTP-only cookies and secure session configuration
- **Input Validation:** All inputs are sanitized using `cleanInput()`
- **SQL Injection Prevention:** PDO prepared statements for all queries

### 2. Admin Module

#### Dashboard
**File:** `admin/dashboard.php`

**Features:**
- System statistics overview
- Quick action buttons
- Recent activities
- Charts and graphs

**Key Functions:**
```php
// Get system statistics
function getSystemStats($pdo) {
    $stats = [];
    
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $stats['total_students'] = $stmt->fetch()['count'];
    
    // Total teachers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM teachers");
    $stats['total_teachers'] = $stmt->fetch()['count'];
    
    // Total courses
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses WHERE is_active = 1");
    $stats['total_courses'] = $stmt->fetch()['count'];
    
    // Total enrollments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM enrollments WHERE status = 'active'");
    $stats['total_enrollments'] = $stmt->fetch()['count'];
    
    return $stats;
}
```

#### Student Management
**File:** `admin/students.php`

**Features:**
- Add new students
- View student list with search and pagination
- Edit student information
- Delete students with confirmation
- Bulk operations

**Add Student Function:**
```php
function addStudent($pdo, $studentData) {
    try {
        $pdo->beginTransaction();
        
        // Create user account
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')");
        $stmt->execute([
            $studentData['username'],
            $studentData['email'],
            password_hash($studentData['password'], PASSWORD_DEFAULT)
        ]);
        $userId = $pdo->lastInsertId();
        
        // Create student record
        $stmt = $pdo->prepare("INSERT INTO students (user_id, first_name, last_name, roll_number, date_of_birth, gender, phone, address, enrollment_date, batch_year, semester, section, blood_group, father_name, mother_name, parent_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $studentData['first_name'],
            $studentData['last_name'],
            $studentData['roll_number'],
            $studentData['date_of_birth'],
            $studentData['gender'],
            $studentData['phone'],
            $studentData['address'],
            $studentData['enrollment_date'],
            $studentData['batch_year'],
            $studentData['semester'],
            $studentData['section'],
            $studentData['blood_group'],
            $studentData['father_name'],
            $studentData['mother_name'],
            $studentData['parent_phone']
        ]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error adding student: " . $e->getMessage());
        return false;
    }
}
```

### 3. Teacher Module

#### Dashboard
**File:** `teacher/dashboard.php`

**Features:**
- Personal statistics
- Assigned courses overview
- Recent attendance activities
- Student count per course

#### Attendance Management
**File:** `teacher/attendance.php`

**Features:**
- Course selection
- Date-wise attendance marking
- Bulk attendance operations
- Attendance history view
- Attendance percentage calculation

**Mark Attendance Function:**
```php
function markAttendance($pdo, $attendanceData) {
    try {
        $pdo->beginTransaction();
        
        foreach ($attendanceData['students'] as $studentId => $status) {
            // Check if attendance already exists
            $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND course_id = ? AND attendance_date = ?");
            $stmt->execute([$studentId, $attendanceData['course_id'], $attendanceData['date']]);
            
            if ($stmt->fetch()) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET status = ?, remarks = ?, marked_by = ? WHERE student_id = ? AND course_id = ? AND attendance_date = ?");
                $stmt->execute([
                    $status,
                    $attendanceData['remarks'][$studentId] ?? null,
                    $_SESSION['user_id'],
                    $studentId,
                    $attendanceData['course_id'],
                    $attendanceData['date']
                ]);
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO attendance (student_id, course_id, attendance_date, status, remarks, marked_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $studentId,
                    $attendanceData['course_id'],
                    $attendanceData['date'],
                    $status,
                    $attendanceData['remarks'][$studentId] ?? null,
                    $_SESSION['user_id']
                ]);
            }
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error marking attendance: " . $e->getMessage());
        return false;
    }
}
```

### 4. Student Module

#### Dashboard
**File:** `student/dashboard.php`

**Features:**
- Personal information display
- Enrolled courses overview
- Attendance statistics
- Recent announcements

#### Profile Management
**File:** `student/profile.php`

**Features:**
- View personal information
- Edit profile details
- Change password
- Upload profile picture

#### Attendance View
**File:** `student/attendance.php`

**Features:**
- View attendance history
- Attendance percentage calculation
- Course-wise attendance summary
- Export attendance report

---

## Authentication & Security

### Security Implementation

#### 1. Password Security
```php
// Password hashing during registration
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Password verification during login
if (password_verify($inputPassword, $storedHash)) {
    // Login successful
}
```

#### 2. Session Management
```php
// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS

// Session validation
function validateSession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        destroySession();
        redirect('auth/login.php');
    }
}
```

#### 3. Input Validation
```php
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}
```

#### 4. SQL Injection Prevention
```php
// Always use prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt->execute([$username, $password]);
$user = $stmt->fetch();
```

#### 5. Role-Based Access Control
```php
function requireRole($requiredRole) {
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
    
    $userRole = getUserRole();
    if ($userRole !== $requiredRole) {
        redirect('auth/unauthorized.php');
    }
}

// Usage in protected pages
requireRole('admin'); // Only admin can access
requireRole('teacher'); // Teacher or admin can access
```

### Security Best Practices

1. **Password Policy:**
   - Minimum 6 characters
   - Hashed with bcrypt
   - Password reset functionality

2. **Session Security:**
   - HTTP-only cookies
   - Secure session configuration
   - Session timeout

3. **Input Validation:**
   - All user inputs sanitized
   - Email and phone validation
   - Date validation

4. **Database Security:**
   - Prepared statements
   - Parameterized queries
   - Error handling without exposing details

5. **File Upload Security:**
   - File type validation
   - Size restrictions
   - Secure file storage

---

## API Documentation

### Internal API Functions

#### User Management API

**Get User by ID**
```php
function getUserById($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT u.*, s.first_name, s.last_name, s.roll_number FROM users u LEFT JOIN students s ON u.id = s.user_id WHERE u.id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}
```

**Update User Profile**
```php
function updateUserProfile($pdo, $userId, $profileData) {
    $stmt = $pdo->prepare("UPDATE users SET email = ?, profile_image = ? WHERE id = ?");
    return $stmt->execute([$profileData['email'], $profileData['profile_image'], $userId]);
}
```

#### Course Management API

**Get Courses by Teacher**
```php
function getCoursesByTeacher($pdo, $teacherId) {
    $stmt = $pdo->prepare("SELECT c.*, COUNT(e.student_id) as enrolled_students FROM courses c LEFT JOIN enrollments e ON c.id = e.course_id WHERE c.teacher_id = ? AND c.is_active = 1 GROUP BY c.id");
    $stmt->execute([$teacherId]);
    return $stmt->fetchAll();
}
```

**Get Student Enrollment**
```php
function getStudentEnrollments($pdo, $studentId) {
    $stmt = $pdo->prepare("SELECT e.*, c.course_name, c.course_code, c.credits, t.first_name as teacher_first_name, t.last_name as teacher_last_name FROM enrollments e JOIN courses c ON e.course_id = c.id LEFT JOIN teachers t ON c.teacher_id = t.user_id WHERE e.student_id = ? AND e.status = 'active'");
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}
```

#### Attendance API

**Get Attendance Statistics**
```php
function getAttendanceStats($pdo, $studentId, $courseId = null) {
    $sql = "SELECT 
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                COUNT(*) as total_classes
            FROM attendance WHERE student_id = ?";
    
    $params = [$studentId];
    
    if ($courseId) {
        $sql .= " AND course_id = ?";
        $params[] = $courseId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}
```

**Get Attendance History**
```php
function getAttendanceHistory($pdo, $studentId, $limit = 50) {
    $stmt = $pdo->prepare("SELECT a.*, c.course_name, c.course_code FROM attendance a JOIN courses c ON a.course_id = c.id WHERE a.student_id = ? ORDER BY a.attendance_date DESC LIMIT ?");
    $stmt->execute([$studentId, $limit]);
    return $stmt->fetchAll();
}
```

### Response Format

All API responses follow a consistent format:

```php
function jsonResponse($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}
```

### Error Handling

```php
try {
    // Database operations
    $result = performOperation($pdo, $data);
    jsonResponse(true, $result, 'Operation successful');
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    jsonResponse(false, null, 'Database error occurred');
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    jsonResponse(false, null, 'An error occurred');
}
```

---

## User Interface Design

### Design Principles

1. **Responsive Design:** Mobile-first approach using Bootstrap 5
2. **Consistent UI:** Unified color scheme and component styling
3. **Accessibility:** WCAG 2.1 compliance with semantic HTML
4. **User Experience:** Intuitive navigation and clear visual hierarchy

### CSS Framework

**Bootstrap 5 Integration:**
```html
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

### Color Scheme

**Primary Colors:**
- Primary: #667eea (Purple)
- Secondary: #764ba2 (Deep Purple)
- Success: #28a745 (Green)
- Danger: #dc3545 (Red)
- Warning: #ffc107 (Yellow)
- Info: #17a2b8 (Cyan)

**Gradient Usage:**
```css
.gradient-bg {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gradient-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}
```

### Component Library

#### Cards
```html
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Student Management</h5>
    </div>
    <div class="card-body">
        <!-- Card content -->
    </div>
</div>
```

#### Forms
```html
<form method="POST" action="">
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-2"></i>Submit
    </button>
</form>
```

#### Tables
```html
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>Roll Number</th>
                <th>Name</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- Table rows -->
        </tbody>
    </table>
</div>
```

### Navigation Structure

**Sidebar Navigation:**
```php
<!-- Admin Sidebar -->
<nav class="sidebar">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="students.php">
                <i class="fas fa-users me-2"></i>Students
            </a>
        </li>
        <!-- More navigation items -->
    </ul>
</nav>
```

### Responsive Design

**Mobile Breakpoints:**
```css
/* Small devices (landscape phones, 576px and up) */
@media (min-width: 576px) {
    .sidebar {
        width: 250px;
    }
}

/* Medium devices (tablets, 768px and up) */
@media (min-width: 768px) {
    .main-content {
        margin-left: 250px;
    }
}

/* Large devices (desktops, 992px and up) */
@media (min-width: 992px) {
    .dashboard-card {
        min-height: 200px;
    }
}
```

### JavaScript Interactions

**Form Validation:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});
```

**Dynamic Content Loading:**
```javascript
function loadContent(url, targetElement) {
    fetch(url)
        .then(response => response.text())
        .then(html => {
            document.getElementById(targetElement).innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading content:', error);
        });
}
```

---

## Installation & Deployment

### System Requirements

**Server Requirements:**
- PHP 8.x or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- 2GB RAM minimum
- 10GB disk space minimum

**PHP Extensions Required:**
- PDO
- PDO_MySQL
- OpenSSL
- Mbstring
- JSON
- Session
- Fileinfo

### Installation Steps

#### 1. Environment Setup

**XAMPP Installation:**
```bash
# Download and install XAMPP
# Start Apache and MySQL services
# Verify installation at http://localhost
```

#### 2. Database Setup

**Create Database:**
```sql
-- Create database
CREATE DATABASE student_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u root -p student_management < database.sql
```

#### 3. Project Configuration

**Config File Setup:**
```php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', 'http://localhost/student-management/');
```

#### 4. File Permissions

```bash
# Set appropriate permissions
chmod 755 /path/to/student-management
chmod 644 /path/to/student-management/config/config.php
chmod 777 /path/to/student-management/assets/uploads
```

### Deployment Configuration

#### Production Server Setup

**Apache Configuration:**
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/student-management
    
    <Directory /var/www/student-management>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

**PHP Configuration:**
```ini
; php.ini settings for production
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
max_execution_time = 300
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
```

#### Security Configuration

**SSL Certificate Setup:**
```bash
# Generate SSL certificate
sudo certbot --apache -d yourdomain.com
```

**Firewall Configuration:**
```bash
# Allow HTTP and HTTPS
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

### Backup Strategy

**Database Backup:**
```bash
#!/bin/bash
# backup_database.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p student_management > backup_$DATE.sql
gzip backup_$DATE.sql
```

**File Backup:**
```bash
#!/bin/bash
# backup_files.sh
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf student_management_backup_$DATE.tar.gz /path/to/student-management
```

### Performance Optimization

**Database Optimization:**
```sql
-- Optimize tables
OPTIMIZE TABLE users, students, teachers, courses, enrollments, attendance;

-- Add indexes for performance
CREATE INDEX idx_student_course ON attendance(student_id, course_id);
CREATE INDEX idx_enrollment_status ON enrollments(status, student_id);
```

**Caching Configuration:**
```php
// Enable OPcache
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

---

## Testing & Quality Assurance

### Testing Strategy

#### 1. Unit Testing

**Test Framework:** PHPUnit

**Example Test:**
```php
<?php
// tests/UserTest.php
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase {
    private $pdo;
    
    protected function setUp(): void {
        $this->pdo = new PDO('sqlite::memory:');
        // Create test database schema
    }
    
    public function testUserCreation() {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        
        $userId = createUser($this->pdo, $userData);
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);
    }
    
    public function testPasswordVerification() {
        $password = 'testpassword';
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertTrue(password_verify($password, $hashed));
        $this->assertFalse(password_verify('wrongpassword', $hashed));
    }
}
```

#### 2. Integration Testing

**Database Integration Test:**
```php
<?php
// tests/DatabaseTest.php
class DatabaseTest extends TestCase {
    public function testDatabaseConnection() {
        $pdo = getDBConnection();
        $this->assertInstanceOf(PDO::class, $pdo);
        
        // Test basic query
        $stmt = $pdo->query("SELECT 1");
        $this->assertEquals(1, $stmt->fetchColumn());
    }
    
    public function testStudentEnrollment() {
        $pdo = getDBConnection();
        
        // Create test student
        $studentId = createTestStudent($pdo);
        
        // Create test course
        $courseId = createTestCourse($pdo);
        
        // Enroll student
        $enrollmentId = enrollStudent($pdo, $studentId, $courseId);
        $this->assertIsInt($enrollmentId);
        
        // Verify enrollment
        $enrollment = getEnrollment($pdo, $enrollmentId);
        $this->assertEquals($studentId, $enrollment['student_id']);
        $this->assertEquals($courseId, $enrollment['course_id']);
    }
}
```

#### 3. Functional Testing

**Selenium Test Example:**
```java
// tests/LoginTest.java
import org.openqa.selenium.*;
import org.openqa.selenium.chrome.ChromeDriver;

public class LoginTest {
    private WebDriver driver;
    
    @Before
    public void setUp() {
        driver = new ChromeDriver();
        driver.get("http://localhost/student-management/auth/login.php");
    }
    
    @Test
    public void testValidLogin() {
        driver.findElement(By.name("username")).sendKeys("admin");
        driver.findElement(By.name("password")).sendKeys("password");
        driver.findElement(By.cssSelector("button[type='submit']")).click();
        
        // Verify redirect to dashboard
        assertTrue(driver.getCurrentUrl().contains("admin/dashboard.php"));
    }
    
    @Test
    public void testInvalidLogin() {
        driver.findElement(By.name("username")).sendKeys("invalid");
        driver.findElement(By.name("password")).sendKeys("wrong");
        driver.findElement(By.cssSelector("button[type='submit']")).click();
        
        // Verify error message
        assertTrue(driver.getPageSource().contains("Invalid username or password"));
    }
    
    @After
    public void tearDown() {
        driver.quit();
    }
}
```

### Code Quality

#### 1. Code Standards

**PSR-12 Coding Standards:**
```php
<?php
// Class declaration
class StudentManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get student by ID
     * 
     * @param int $studentId Student ID
     * @return array|null Student data
     */
    public function getStudentById(int $studentId): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, u.username, u.email FROM students s 
             JOIN users u ON s.user_id = u.id WHERE s.id = ?"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetch() ?: null;
    }
}
```

#### 2. Static Analysis

**PHPStan Configuration:**
```neon
# phpstan.neon
parameters:
    level: 6
    paths:
        - src
        - config
    ignoreErrors:
        - '#Call to an undefined method#'
```

#### 3. Code Coverage

**PHPUnit Coverage:**
```xml
<!-- phpunit.xml -->
<phpunit>
    <filter>
        <whitelist>
            <directory suffix=".php">src</directory>
        </whitelist>
    </filter>
    <logging>
        <log type="coverage-html" target="coverage"/>
    </logging>
</phpunit>
```

### Performance Testing

#### 1. Load Testing

**Apache Bench:**
```bash
# Test login endpoint
ab -n 1000 -c 10 http://localhost/student-management/auth/login.php

# Test dashboard
ab -n 500 -c 5 http://localhost/student-management/admin/dashboard.php
```

#### 2. Database Performance

**Query Optimization:**
```sql
-- Analyze slow queries
SHOW PROCESSLIST;
SHOW FULL PROCESSLIST;

-- Explain query execution plan
EXPLAIN SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.id WHERE s.batch_year = '2023';

-- Optimize with indexes
CREATE INDEX idx_batch_year ON students(batch_year);
```

### Security Testing

#### 1. Vulnerability Scanning

**OWASP ZAP Configuration:**
```bash
# Run security scan
zap-baseline.py -t http://localhost/student-management/

# Generate report
zap-baseline.py -t http://localhost/student-management/ -J security-report.json
```

#### 2. Penetration Testing

**SQL Injection Test:**
```php
// Test SQL injection protection
$maliciousInput = "'; DROP TABLE users; --";
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$maliciousInput]); // Should be safe
```

**XSS Prevention Test:**
```php
// Test XSS protection
$xssInput = "<script>alert('XSS')</script>";
$cleanInput = cleanInput($xssInput);
$this->assertStringNotContainsString('<script>', $cleanInput);
```

---

## Future Enhancements

### Planned Features

#### 1. Grades Management System

**Module Overview:**
- Grade entry and management
- GPA calculation
- Transcript generation
- Grade analytics

**Database Schema:**
```sql
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    exam_type ENUM('midterm', 'final', 'assignment', 'quiz') NOT NULL,
    max_marks DECIMAL(5,2) NOT NULL,
    obtained_marks DECIMAL(5,2) NOT NULL,
    grade VARCHAR(2) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    graded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES teachers(id) ON DELETE CASCADE
);
```

**Implementation:**
```php
class GradeManager {
    public function calculateGPA(array $grades): float {
        $totalPoints = 0;
        $totalCredits = 0;
        
        foreach ($grades as $grade) {
            $points = $this->gradeToPoints($grade['grade']);
            $totalPoints += $points * $grade['credits'];
            $totalCredits += $grade['credits'];
        }
        
        return $totalCredits > 0 ? $totalPoints / $totalCredits : 0;
    }
    
    private function gradeToPoints(string $grade): float {
        $gradeMap = [
            'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
            'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
            'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
            'D+' => 1.3, 'D' => 1.0, 'F' => 0.0
        ];
        
        return $gradeMap[$grade] ?? 0.0;
    }
}
```

#### 2. Exam Management System

**Features:**
- Exam scheduling
- Question bank management
- Online exam platform
- Automated grading

**Database Schema:**
```sql
CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    exam_title VARCHAR(200) NOT NULL,
    exam_type ENUM('midterm', 'final', 'quiz', 'assignment') NOT NULL,
    exam_date DATETIME NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    total_marks DECIMAL(5,2) NOT NULL,
    instructions TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES teachers(id) ON DELETE CASCADE
);

CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer', 'essay') NOT NULL,
    options JSON DEFAULT NULL COMMENT 'For multiple choice questions',
    correct_answer TEXT NOT NULL,
    marks DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);
```

#### 3. Notification System

**Features:**
- Email notifications
- SMS alerts
- In-app notifications
- Push notifications

**Implementation:**
```php
class NotificationManager {
    private $emailService;
    private $smsService;
    
    public function sendEmailNotification(string $to, string $subject, string $message): bool {
        $headers = [
            'From' => 'noreply@school.edu',
            'Content-Type' => 'text/html; charset=UTF-8'
        ];
        
        return mail($to, $subject, $message, $headers);
    }
    
    public function sendSMSNotification(string $phone, string $message): bool {
        // Integrate with SMS service provider
        $smsApi = new SMSProvider();
        return $smsApi->send($phone, $message);
    }
    
    public function createInAppNotification(int $userId, string $title, string $message): int {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $title, $message]);
        return $pdo->lastInsertId();
    }
}
```

#### 4. File Management System

**Features:**
- Document upload
- File sharing
- Version control
- Cloud storage integration

**Implementation:**
```php
class FileManager {
    private $uploadPath = '/var/www/student-management/uploads/';
    
    public function uploadFile(array $file, string $category): string {
        // Validate file
        $this->validateFile($file);
        
        // Generate unique filename
        $filename = $this->generateUniqueFilename($file['name']);
        $filepath = $this->uploadPath . $category . '/' . $filename;
        
        // Move file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Save to database
            $this->saveFileRecord($filename, $category, $file['size']);
            return $filename;
        }
        
        throw new Exception('File upload failed');
    }
    
    private function validateFile(array $file): void {
        $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'png'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('File type not allowed');
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('File size too large');
        }
    }
}
```

#### 5. Advanced Reporting

**Features:**
- PDF report generation
- Data visualization
- Custom report builder
- Export functionality

**Implementation:**
```php
class ReportGenerator {
    public function generateStudentReport(int $studentId): string {
        $student = $this->getStudentData($studentId);
        $attendance = $this->getAttendanceData($studentId);
        $grades = $this->getGradesData($studentId);
        
        // Generate PDF
        $pdf = new PDFReport();
        $pdf->addPage();
        $pdf->setStudentInfo($student);
        $pdf->setAttendanceData($attendance);
        $pdf->setGradesData($grades);
        
        $filename = 'student_report_' . $studentId . '_' . date('Y-m-d') . '.pdf';
        $pdf->output($filename, 'D');
        
        return $filename;
    }
    
    public function generateAttendanceReport(int $courseId, string $startDate, string $endDate): array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT 
                s.id,
                s.first_name,
                s.last_name,
                s.roll_number,
                COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present,
                COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent,
                COUNT(*) as total_classes,
                ROUND(COUNT(CASE WHEN a.status = 'present' THEN 1 END) * 100.0 / COUNT(*), 2) as percentage
            FROM students s
            JOIN enrollments e ON s.id = e.student_id
            LEFT JOIN attendance a ON s.id = a.student_id AND a.course_id = ? AND a.attendance_date BETWEEN ? AND ?
            WHERE e.course_id = ?
            GROUP BY s.id
            ORDER BY s.roll_number
        ");
        
        $stmt->execute([$courseId, $startDate, $endDate, $courseId]);
        return $stmt->fetchAll();
    }
}
```

### Technology Roadmap

#### Phase 1 (3 Months)
- Grades management system
- Basic reporting enhancements
- Mobile responsive improvements

#### Phase 2 (6 Months)
- Exam management system
- Notification system
- File management system

#### Phase 3 (12 Months)
- Advanced analytics
- Parent portal
- Mobile application

#### Phase 4 (18 Months)
- AI-powered features
- Cloud deployment
- Multi-institution support

---

## Troubleshooting Guide

### Common Issues

#### 1. Database Connection Issues

**Problem:** "Database connection failed"

**Solutions:**
```php
// Check database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// Test connection manually
try {
    $pdo = new PDO("mysql:host=localhost;dbname=student_management", "root", "");
    echo "Connection successful!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

**Debugging Steps:**
1. Verify MySQL service is running
2. Check database credentials
3. Ensure database exists
4. Check PHP MySQL extension

#### 2. Session Issues

**Problem:** User logged out unexpectedly

**Solutions:**
```php
// Check session configuration
ini_set('session.save_path', '/tmp');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);

// Debug session data
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
```

**Debugging Steps:**
1. Check session save path permissions
2. Verify session cookie settings
3. Check session timeout values
4. Ensure session_start() is called

#### 3. File Upload Issues

**Problem:** File upload not working

**Solutions:**
```php
// Check upload configuration
phpinfo(); // Check upload_max_filesize, post_max_size

// Debug upload
if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo "Upload error: " . $_FILES['file']['error'];
}

// Check directory permissions
$uploadDir = '/path/to/uploads/';
if (!is_writable($uploadDir)) {
    echo "Upload directory is not writable";
}
```

**Debugging Steps:**
1. Check PHP upload settings
2. Verify directory permissions
3. Check file size limits
4. Ensure proper form encoding

#### 4. Permission Issues

**Problem:** "Access denied" errors

**Solutions:**
```php
// Check user role
function checkUserRole() {
    if (!isLoggedIn()) {
        echo "User not logged in";
        return;
    }
    
    $role = getUserRole();
    echo "Current role: " . $role;
    
    // Check specific role requirements
    if ($role !== 'admin') {
        echo "Admin access required";
    }
}

// Debug permissions
echo "Session data: ";
print_r($_SESSION);
```

**Debugging Steps:**
1. Verify user authentication
2. Check role assignment
3. Review access control logic
4. Test with different user accounts

#### 5. Performance Issues

**Problem:** Slow page loading

**Solutions:**
```php
// Enable query logging
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

// Profile queries
$start = microtime(true);
$stmt = $pdo->prepare("SELECT * FROM students WHERE batch_year = ?");
$stmt->execute(['2023']);
$students = $stmt->fetchAll();
$end = microtime(true);
echo "Query time: " . ($end - $start) . " seconds";
```

**Optimization Steps:**
1. Add database indexes
2. Optimize queries
3. Enable caching
4. Use pagination

### Error Handling

#### Custom Error Handler
```php
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("Error: [$errno] $errstr in $errfile on line $errline");
    
    if (ini_get('display_errors')) {
        echo "<div class='alert alert-danger'>";
        echo "<strong>Error:</strong> $errstr";
        echo "<br><small>File: $errfile Line: $errline</small>";
        echo "</div>";
    }
    
    return true;
}

set_error_handler('customErrorHandler');
```

#### Exception Handler
```php
function customExceptionHandler($exception) {
    error_log("Uncaught exception: " . $exception->getMessage());
    
    if (ini_get('display_errors')) {
        echo "<div class='alert alert-danger'>";
        echo "<strong>Fatal Error:</strong> " . $exception->getMessage();
        echo "<br><small>File: " . $exception->getFile() . " Line: " . $exception->getLine() . "</small>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>";
        echo "An error occurred. Please try again later.";
        echo "</div>";
    }
}

set_exception_handler('customExceptionHandler');
```

### Logging System

#### Error Logging
```php
function logError($message, $context = []) {
    $logFile = __DIR__ . '/logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logMessage = "[$timestamp] $message$contextStr" . PHP_EOL;
    
    error_log($logMessage, 3, $logFile);
}

// Usage
try {
    // Database operation
} catch (Exception $e) {
    logError("Database error", [
        'query' => $sql,
        'params' => $params,
        'error' => $e->getMessage()
    ]);
}
```

#### Access Logging
```php
function logAccess($userId, $action, $details = []) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO access_logs (user_id, action, details, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $userId,
        $action,
        json_encode($details),
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}
```

### Maintenance Tasks

#### Database Maintenance
```sql
-- Optimize tables
OPTIMIZE TABLE users, students, teachers, courses, enrollments, attendance;

-- Clear old logs
DELETE FROM access_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Update statistics
ANALYZE TABLE users, students, teachers, courses, enrollments, attendance;
```

#### File Cleanup
```php
function cleanupOldFiles($directory, $days = 30) {
    $files = glob($directory . '*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > ($days * 86400)) {
            unlink($file);
            echo "Deleted old file: $file\n";
        }
    }
}

// Run cleanup
cleanupOldFiles('/path/to/uploads/temp/');
```

---

## Appendix

### A. Database Schema Complete

```sql
-- Complete database schema for Student Management System
-- This includes all tables, indexes, and constraints

-- Create database
CREATE DATABASE IF NOT EXISTS student_management
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE student_management;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    password_reset_token VARCHAR(255) DEFAULT NULL,
    password_reset_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB COMMENT='User authentication and profile management';

-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    roll_number VARCHAR(20) UNIQUE NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    phone VARCHAR(15) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    enrollment_date DATE NOT NULL,
    batch_year VARCHAR(10) DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    section VARCHAR(10) DEFAULT NULL,
    blood_group VARCHAR(5) DEFAULT NULL,
    nationality VARCHAR(50) DEFAULT NULL,
    religion VARCHAR(50) DEFAULT NULL,
    category ENUM('general', 'sc', 'st', 'obc', 'other') DEFAULT 'general',
    admission_type ENUM('regular', 'management', 'nri', 'other') DEFAULT 'regular',
    father_name VARCHAR(50) DEFAULT NULL,
    mother_name VARCHAR(50) DEFAULT NULL,
    guardian_name VARCHAR(50) DEFAULT NULL,
    parent_phone VARCHAR(15) DEFAULT NULL,
    parent_email VARCHAR(100) DEFAULT NULL,
    emergency_contact VARCHAR(15) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_roll_number (roll_number),
    INDEX idx_batch_year (batch_year),
    INDEX idx_enrollment_date (enrollment_date),
    INDEX idx_first_name (first_name),
    INDEX idx_last_name (last_name)
) ENGINE=InnoDB COMMENT='Complete student profile and academic information';

-- Teachers table
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    phone VARCHAR(15) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    qualification VARCHAR(100) DEFAULT NULL,
    specialization VARCHAR(100) DEFAULT NULL,
    hire_date DATE NOT NULL,
    employment_type ENUM('permanent', 'contract', 'visiting', 'other') DEFAULT 'permanent',
    department VARCHAR(100) DEFAULT NULL,
    experience_years DECIMAL(3,1) DEFAULT 0,
    salary DECIMAL(10,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_employee_id (employee_id),
    INDEX idx_department (department),
    INDEX idx_hire_date (hire_date)
) ENGINE=InnoDB COMMENT='Teacher professional and academic information';

-- Courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    credits INT DEFAULT 3,
    department VARCHAR(100) DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    course_type ENUM('theory', 'practical', 'elective', 'core') DEFAULT 'core',
    max_students INT DEFAULT 50,
    teacher_id INT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    INDEX idx_course_code (course_code),
    INDEX idx_department (department),
    INDEX idx_semester (semester),
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB COMMENT='Course information and management';

-- Enrollments table
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'completed', 'dropped', 'failed') DEFAULT 'active',
    grade VARCHAR(5) DEFAULT NULL,
    attendance_percentage DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id),
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_status (status),
    INDEX idx_enrollment_date (enrollment_date)
) ENGINE=InnoDB COMMENT='Student-course enrollment tracking';

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
    remarks TEXT DEFAULT NULL,
    marked_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, course_id, attendance_date),
    INDEX idx_student_course (student_id, course_id),
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_status (status),
    INDEX idx_marked_by (marked_by)
) ENGINE=InnoDB COMMENT='Attendance records and tracking';

-- Access logs table
CREATE TABLE access_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB COMMENT='User access and activity logs';

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB COMMENT='User notifications system';

-- Insert default admin user
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample data
-- This would include sample students, teachers, courses, etc.
```

### B. Configuration Files

#### Apache .htaccess
```apache
# Enable URL rewriting
RewriteEngine On

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Hide .htaccess file
<Files .htaccess>
    Order allow,deny
    Deny from all
</Files>

# Prevent directory listing
Options -Indexes

# Custom error pages
ErrorDocument 404 /error_pages/404.php
ErrorDocument 500 /error_pages/500.php

# PHP settings
php_value display_errors Off
php_value log_errors On
php_value error_log /var/log/php_errors.log

# File upload restrictions
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

#### PHP Configuration
```php
<?php
// config/config.php - Complete configuration

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.gc_maxlifetime', 3600); // 1 hour
ini_set('session.cookie_lifetime', 3600); // 1 hour

// Application configuration
define('APP_NAME', 'Student Management System');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // Change to 'production' in production
define('BASE_URL', 'http://localhost/student-management/');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('LOG_PATH', __DIR__ . '/../logs/');

// Email configuration
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@school.edu');
define('SMTP_FROM_NAME', APP_NAME);

// Security configuration
define('ENCRYPTION_KEY', 'your-secret-key-here');
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Pagination
define('ITEMS_PER_PAGE', 20);

// Start session
session_start();

// Autoloader
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Helper functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Database connection
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please try again later.");
            }
        }
    }
    
    return $pdo;
}

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect(BASE_URL . 'auth/login.php');
    }
}

function requireRole($requiredRole) {
    requireLogin();
    
    $userRole = getUserRole();
    if ($userRole !== $requiredRole && $userRole !== 'admin') {
        redirect(BASE_URL . 'auth/unauthorized.php');
    }
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Message functions
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

function getSuccessMessage() {
    $message = $_SESSION['success_message'] ?? null;
    unset($_SESSION['success_message']);
    return $message;
}

function getErrorMessage() {
    $message = $_SESSION['error_message'] ?? null;
    unset($_SESSION['error_message']);
    return $message;
}

// Logging function
function logMessage($level, $message, $context = []) {
    $logFile = LOG_PATH . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
    $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
    
    error_log($logMessage, 3, $logFile);
}
?>
```

### C. API Endpoints Reference

#### Authentication Endpoints
```
POST /auth/login.php
POST /auth/logout.php
POST /auth/forgot_password.php
POST /auth/reset_password.php
```

#### Student Endpoints
```
GET /student/api/profile.php
PUT /student/api/profile.php
GET /student/api/courses.php
GET /student/api/attendance.php
GET /student/api/grades.php
```

#### Teacher Endpoints
```
GET /teacher/api/dashboard.php
GET /teacher/api/students.php
GET /teacher/api/courses.php
POST /teacher/api/attendance.php
GET /teacher/api/reports.php
```

#### Admin Endpoints
```
GET /admin/api/dashboard.php
GET /admin/api/students.php
POST /admin/api/students.php
PUT /admin/api/students.php
DELETE /admin/api/students.php
GET /admin/api/teachers.php
POST /admin/api/teachers.php
GET /admin/api/courses.php
POST /admin/api/courses.php
GET /admin/api/reports.php
```

### D. Testing Checklist

#### Functional Testing
- [ ] User registration and login
- [ ] Role-based access control
- [ ] Student management (CRUD)
- [ ] Teacher management (CRUD)
- [ ] Course management (CRUD)
- [ ] Enrollment system
- [ ] Attendance marking
- [ ] Report generation
- [ ] Profile management
- [ ] Password reset functionality

#### Security Testing
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] CSRF protection
- [ ] Session security
- [ ] Input validation
- [ ] File upload security
- [ ] Authentication bypass attempts
- [ ] Authorization testing

#### Performance Testing
- [ ] Database query optimization
- [ ] Page load time testing
- [ ] Concurrent user testing
- [ ] Large dataset handling
- [ ] Memory usage optimization

#### Compatibility Testing
- [ ] Cross-browser testing
- [ ] Mobile responsiveness
- [ ] Different screen sizes
- [ ] Different PHP versions
- [ ] Different MySQL versions

### E. Deployment Checklist

#### Pre-deployment
- [ ] Code review completed
- [ ] Security audit performed
- [ ] Performance testing completed
- [ ] Database backup created
- [ ] Configuration updated for production
- [ ] Error reporting disabled
- [ ] Logging configured

#### Post-deployment
- [ ] Functionality testing
- [ ] Performance monitoring
- [ ] Error log monitoring
- [ ] User acceptance testing
- [ ] Documentation updated
- [ ] Backup schedule configured
- [ ] Monitoring setup

---

## Conclusion

This comprehensive documentation provides a complete overview of the Student Management System, including its architecture, implementation details, and maintenance procedures. The system is designed with scalability, security, and maintainability in mind, making it suitable for educational institutions of various sizes.

The modular architecture allows for easy extension and customization, while the robust security measures ensure data protection and user privacy. The detailed testing procedures and troubleshooting guides help ensure smooth operation and quick resolution of issues.

For any questions or support needs, please refer to the troubleshooting section or contact the development team.

---

**Document Version:** 1.0.0  
**Last Updated:** December 2024  
**Next Review:** March 2025
