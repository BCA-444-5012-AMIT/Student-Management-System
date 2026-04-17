-- =====================================================
-- Student Management System Database Schema
-- Version: 2.0 - Updated with Enhanced Features
-- Author: Student Management System Team
-- Description: Complete database schema for educational institution management
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS student_management
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE student_management;

-- =====================================================
-- TABLE: users (Authentication & User Management)
-- Purpose: Store user credentials and authentication data
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL COMMENT 'Login username - unique across system',
    email VARCHAR(100) UNIQUE NOT NULL COMMENT 'User email - unique across system',
    password VARCHAR(255) NOT NULL COMMENT 'Hashed password using bcrypt',
    role ENUM('admin', 'teacher', 'student') NOT NULL COMMENT 'User role for access control',
    profile_image VARCHAR(255) DEFAULT NULL COMMENT 'Profile picture path',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Account status (active/inactive)',
    last_login TIMESTAMP NULL COMMENT 'Last login timestamp',
    password_reset_token VARCHAR(255) DEFAULT NULL COMMENT 'Password reset token',
    password_reset_expires TIMESTAMP NULL COMMENT 'Password reset token expiry',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Account creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    
    -- Indexes for performance
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB COMMENT='User authentication and profile management';

-- =====================================================
-- TABLE: students (Student Information & Academic Records)
-- Purpose: Store student personal and academic information
-- =====================================================
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL COMMENT 'Link to users table',
    first_name VARCHAR(50) NOT NULL COMMENT 'Student first name',
    last_name VARCHAR(50) NOT NULL COMMENT 'Student last name',
    roll_number VARCHAR(20) UNIQUE NOT NULL COMMENT 'Unique roll number (e.g., BCA2021001)',
    date_of_birth DATE NOT NULL COMMENT 'Student date of birth',
    gender ENUM('male', 'female', 'other') NOT NULL COMMENT 'Student gender',
    phone VARCHAR(15) DEFAULT NULL COMMENT 'Contact phone number',
    address TEXT DEFAULT NULL COMMENT 'Residential address',
    enrollment_date DATE NOT NULL COMMENT 'Date of enrollment in institution',
    batch_year VARCHAR(10) DEFAULT NULL COMMENT 'Academic batch year',
    semester VARCHAR(20) DEFAULT NULL COMMENT 'Current semester',
    section VARCHAR(10) DEFAULT NULL COMMENT 'Class section',
    blood_group VARCHAR(5) DEFAULT NULL COMMENT 'Blood group for medical records',
    nationality VARCHAR(50) DEFAULT NULL COMMENT 'Student nationality',
    religion VARCHAR(50) DEFAULT NULL COMMENT 'Student religion',
    category ENUM('general', 'sc', 'st', 'obc', 'other') DEFAULT 'general' COMMENT 'Student category',
    admission_type ENUM('regular', 'management', 'nri', 'other') DEFAULT 'regular' COMMENT 'Type of admission',
    father_name VARCHAR(50) DEFAULT NULL COMMENT 'Father name',
    mother_name VARCHAR(50) DEFAULT NULL COMMENT 'Mother name',
    guardian_name VARCHAR(50) DEFAULT NULL COMMENT 'Guardian name',
    parent_phone VARCHAR(15) DEFAULT NULL COMMENT 'Parent/guardian contact',
    parent_email VARCHAR(100) DEFAULT NULL COMMENT 'Parent/guardian email',
    emergency_contact VARCHAR(15) DEFAULT NULL COMMENT 'Emergency contact number',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    
    -- Foreign key constraint
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_roll_number (roll_number),
    INDEX idx_batch_year (batch_year),
    INDEX idx_enrollment_date (enrollment_date),
    INDEX idx_first_name (first_name),
    INDEX idx_last_name (last_name)
) ENGINE=InnoDB COMMENT='Complete student profile and academic information';

-- =====================================================
-- TABLE: teachers (Teacher Information & Professional Details)
-- Purpose: Store teacher personal and professional information
-- =====================================================
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL COMMENT 'Link to users table',
    first_name VARCHAR(50) NOT NULL COMMENT 'Teacher first name',
    last_name VARCHAR(50) NOT NULL COMMENT 'Teacher last name',
    employee_id VARCHAR(20) UNIQUE NOT NULL COMMENT 'Auto-generated employee ID (EMP001, etc.)',
    date_of_birth DATE NOT NULL COMMENT 'Teacher date of birth',
    gender ENUM('male', 'female', 'other') NOT NULL COMMENT 'Teacher gender',
    phone VARCHAR(15) DEFAULT NULL COMMENT 'Contact phone number',
    address TEXT DEFAULT NULL COMMENT 'Residential address',
    qualification VARCHAR(100) DEFAULT NULL COMMENT 'Academic qualification',
    specialization VARCHAR(100) DEFAULT NULL COMMENT 'Subject specialization',
    hire_date DATE NOT NULL COMMENT 'Date of hiring',
    employment_type ENUM('permanent', 'contract', 'visiting', 'other') DEFAULT 'permanent' COMMENT 'Employment type',
    department VARCHAR(100) DEFAULT NULL COMMENT 'Department assignment',
    designation VARCHAR(100) DEFAULT NULL COMMENT 'Job designation',
    experience_years INT DEFAULT 0 COMMENT 'Years of teaching experience',
    salary DECIMAL(10,2) DEFAULT NULL COMMENT 'Monthly salary',
    bank_account VARCHAR(50) DEFAULT NULL COMMENT 'Bank account number',
    bank_name VARCHAR(100) DEFAULT NULL COMMENT 'Bank name',
    pan_number VARCHAR(20) DEFAULT NULL COMMENT 'PAN number for tax purposes',
    aadhaar_number VARCHAR(20) DEFAULT NULL COMMENT 'Aadhaar number (Indian ID)',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Employment status',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    
    -- Foreign key constraint
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_employee_id (employee_id),
    INDEX idx_specialization (specialization),
    INDEX idx_department (department),
    INDEX idx_hire_date (hire_date)
) ENGINE=InnoDB COMMENT='Complete teacher profile and professional information';

-- =====================================================
-- TABLE: departments (Department Management)
-- Purpose: Store department information for organization structure
-- =====================================================
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) UNIQUE NOT NULL COMMENT 'Department name',
    department_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Department code',
    description TEXT DEFAULT NULL COMMENT 'Department description',
    head_of_department INT DEFAULT NULL COMMENT 'Department head (teacher ID)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Department creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    
    -- Foreign key constraint
    FOREIGN KEY (head_of_department) REFERENCES teachers(id) ON DELETE SET NULL,
    
    -- Indexes for performance
    INDEX idx_department_code (department_code)
) ENGINE=InnoDB COMMENT='Department organization structure';

-- =====================================================
-- TABLE: courses (Course Management & Curriculum)
-- Purpose: Store course information and curriculum details
-- =====================================================
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Unique course identifier',
    course_name VARCHAR(100) NOT NULL COMMENT 'Course title',
    description TEXT DEFAULT NULL COMMENT 'Course description and objectives',
    credits INT NOT NULL DEFAULT 3 COMMENT 'Course credit hours',
    course_type ENUM('core', 'elective', 'optional') DEFAULT 'core' COMMENT 'Course type',
    department_id INT DEFAULT NULL COMMENT 'Department offering this course',
    semester VARCHAR(20) DEFAULT NULL COMMENT 'Semester offering',
    academic_year VARCHAR(10) DEFAULT NULL COMMENT 'Academic year',
    max_students INT DEFAULT 50 COMMENT 'Maximum students allowed',
    current_students INT DEFAULT 0 COMMENT 'Currently enrolled students',
    prerequisites TEXT DEFAULT NULL COMMENT 'Course prerequisites',
    learning_outcomes TEXT DEFAULT NULL COMMENT 'Learning outcomes',
    syllabus TEXT DEFAULT NULL COMMENT 'Course syllabus',
    teacher_id INT DEFAULT NULL COMMENT 'Assigned teacher',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Course status',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Course creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    
    -- Foreign key constraints
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    
    -- Indexes for performance
    INDEX idx_course_code (course_code),
    INDEX idx_course_name (course_name),
    INDEX idx_semester (semester),
    INDEX idx_academic_year (academic_year)
) ENGINE=InnoDB COMMENT='Course catalog and curriculum management';

-- =====================================================
-- TABLE: enrollments (Student-Course Relationship)
-- Purpose: Manage student enrollment in courses
-- =====================================================
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL COMMENT 'Enrolled student',
    course_id INT NOT NULL COMMENT 'Course enrolled in',
    enrollment_date DATE NOT NULL COMMENT 'Date of enrollment',
    status ENUM('active', 'completed', 'dropped', 'suspended') DEFAULT 'active' COMMENT 'Enrollment status',
    enrollment_type ENUM('regular', 'late', 'transfer') DEFAULT 'regular' COMMENT 'Type of enrollment',
    fees_paid DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Fees paid amount',
    fees_total DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total fees amount',
    payment_status ENUM('pending', 'partial', 'completed', 'waived') DEFAULT 'pending' COMMENT 'Payment status',
    grade_obtained VARCHAR(10) DEFAULT NULL COMMENT 'Final grade obtained',
    grade_points DECIMAL(5,2) DEFAULT NULL COMMENT 'Grade points earned',
    attendance_percentage DECIMAL(5,2) DEFAULT NULL COMMENT 'Overall attendance percentage',
    completion_date DATE DEFAULT NULL COMMENT 'Course completion date',
    dropout_date DATE DEFAULT NULL COMMENT 'Date of dropout if applicable',
    dropout_reason TEXT DEFAULT NULL COMMENT 'Reason for dropping',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Enrollment creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    
    -- Foreign key constraints
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    
    -- Unique constraint to prevent duplicate enrollments
    UNIQUE KEY unique_enrollment (student_id, course_id),
    
    -- Indexes for performance
    INDEX idx_enrollment_date (enrollment_date),
    INDEX idx_status (status),
    INDEX idx_student_course (student_id, course_id)
) ENGINE=InnoDB COMMENT='Student enrollment management and tracking';

-- =====================================================
-- TABLE: attendance (Attendance Management & Tracking)
-- Purpose: Track student attendance for courses
-- =====================================================
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL COMMENT 'Student whose attendance is being marked',
    course_id INT NOT NULL COMMENT 'Course for which attendance is marked',
    date DATE NOT NULL COMMENT 'Attendance date',
    status ENUM('present', 'absent', 'late', 'excused', 'on_leave') DEFAULT 'present' COMMENT 'Attendance status',
    check_in_time TIME DEFAULT NULL COMMENT 'Student check-in time',
    check_out_time TIME DEFAULT NULL COMMENT 'Student check-out time',
    total_hours DECIMAL(4,2) DEFAULT NULL COMMENT 'Total hours attended',
    marked_by INT NOT NULL COMMENT 'Teacher who marked attendance',
    remarks TEXT DEFAULT NULL COMMENT 'Additional remarks about attendance',
    attendance_type ENUM('regular', 'practical', 'exam', 'extra_class') DEFAULT 'regular' COMMENT 'Type of attendance session',
    location VARCHAR(100) DEFAULT NULL COMMENT 'Classroom/location',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Attendance record creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    
    -- Foreign key constraints
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES teachers(id) ON DELETE CASCADE,
    
    -- Unique constraint to prevent duplicate attendance records
    UNIQUE KEY unique_attendance (student_id, course_id, date),
    
    -- Indexes for performance
    INDEX idx_attendance_date (date),
    INDEX idx_student_attendance (student_id, date),
    INDEX idx_course_attendance (course_id, date),
    INDEX idx_status (status)
) ENGINE=InnoDB COMMENT='Student attendance tracking and management';

-- =====================================================
-- TABLE: grades (Grade Management System)
-- Purpose: Store student grades and academic performance
-- =====================================================
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL COMMENT 'Link to enrollment table',
    assessment_type ENUM('assignment', 'quiz', 'midterm', 'final', 'practical') NOT NULL COMMENT 'Type of assessment',
    assessment_name VARCHAR(100) NOT NULL COMMENT 'Name of assessment',
    max_marks DECIMAL(5,2) NOT NULL DEFAULT 100.00 COMMENT 'Maximum possible marks',
    obtained_marks DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Marks obtained by student',
    grade VARCHAR(5) DEFAULT NULL COMMENT 'Grade assigned (A, B, C, etc.)',
    grade_points DECIMAL(5,2) DEFAULT NULL COMMENT 'Grade points for GPA calculation',
    percentage DECIMAL(5,2) GENERATED ALWAYS AS (obtained_marks / max_marks * 100) STORED COMMENT 'Percentage obtained',
    weightage DECIMAL(5,2) DEFAULT 1.00 COMMENT 'Weightage in total grade',
    assessment_date DATE NOT NULL COMMENT 'Date of assessment',
    remarks TEXT DEFAULT NULL COMMENT 'Teacher remarks about performance',
    graded_by INT NOT NULL COMMENT 'Teacher who graded the assessment',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Grade record creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    
    -- Foreign key constraints
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES teachers(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_enrollment_assessment (enrollment_id, assessment_type, assessment_date),
    INDEX idx_assessment_date (assessment_date),
    INDEX idx_student_grades (enrollment_id)
) ENGINE=InnoDB COMMENT='Student grade management and academic performance tracking';

-- =====================================================
-- TABLE: assignments (Assignment Management System)
-- Purpose: Manage course assignments and submissions
-- =====================================================
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL COMMENT 'Course for this assignment',
    title VARCHAR(200) NOT NULL COMMENT 'Assignment title',
    description TEXT DEFAULT NULL COMMENT 'Assignment description and requirements',
    assignment_type ENUM('homework', 'project', 'lab', 'presentation') DEFAULT 'homework' COMMENT 'Type of assignment',
    total_marks DECIMAL(5,2) NOT NULL DEFAULT 100.00 COMMENT 'Total marks for assignment',
    due_date DATE NOT NULL COMMENT 'Assignment due date',
    assigned_date DATE NOT NULL DEFAULT (CURRENT_DATE) COMMENT 'Date assignment was given',
    submission_type ENUM('online', 'offline', 'both') DEFAULT 'both' COMMENT 'Submission method',
    max_file_size INT DEFAULT 10485760 COMMENT 'Maximum file size in bytes (10MB)',
    allowed_extensions VARCHAR(200) DEFAULT 'pdf,doc,docx,ppt,pptx' COMMENT 'Allowed file extensions',
    is_published BOOLEAN DEFAULT TRUE COMMENT 'Assignment visibility to students',
    created_by INT NOT NULL COMMENT 'Teacher who created assignment',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Assignment creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    
    -- Foreign key constraint
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES teachers(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_course_assignments (course_id),
    INDEX idx_due_date (due_date),
    INDEX idx_assignment_type (assignment_type)
) ENGINE=InnoDB COMMENT='Course assignment management';

-- =====================================================
-- TABLE: assignment_submissions (Student Assignment Submissions)
-- Purpose: Track student assignment submissions
-- =====================================================
CREATE TABLE assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL COMMENT 'Related assignment',
    student_id INT NOT NULL COMMENT 'Student who submitted',
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Submission timestamp',
    file_path VARCHAR(500) DEFAULT NULL COMMENT 'Submitted file path',
    file_name VARCHAR(255) DEFAULT NULL COMMENT 'Original file name',
    file_size INT DEFAULT NULL COMMENT 'File size in bytes',
    plagiarism_score DECIMAL(5,2) DEFAULT NULL COMMENT 'Plagiarism detection score',
    status ENUM('submitted', 'graded', 'returned', 'late') DEFAULT 'submitted' COMMENT 'Submission status',
    marks_obtained DECIMAL(5,2) DEFAULT NULL COMMENT 'Marks obtained after grading',
    teacher_feedback TEXT DEFAULT NULL COMMENT 'Teacher feedback on submission',
    graded_by INT DEFAULT NULL COMMENT 'Teacher who graded submission',
    graded_date TIMESTAMP DEFAULT NULL COMMENT 'Date when submission was graded',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Submission record creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    
    -- Foreign key constraints
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES teachers(id) ON DELETE SET NULL,
    
    -- Unique constraint to prevent duplicate submissions
    UNIQUE KEY unique_submission (assignment_id, student_id),
    
    -- Indexes for performance
    INDEX idx_assignment_submissions (assignment_id, student_id),
    INDEX idx_submission_date (submission_date),
    INDEX idx_status (status)
) ENGINE=InnoDB COMMENT='Student assignment submissions tracking';

-- =====================================================
-- TABLE: notifications (System Notifications)
-- Purpose: Store system notifications for users
-- =====================================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'User who will receive notification',
    title VARCHAR(200) NOT NULL COMMENT 'Notification title',
    message TEXT NOT NULL COMMENT 'Notification message content',
    type ENUM('info', 'success', 'warning', 'error', 'announcement') DEFAULT 'info' COMMENT 'Notification type',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium' COMMENT 'Notification priority',
    is_read BOOLEAN DEFAULT FALSE COMMENT 'Read status',
    action_url VARCHAR(500) DEFAULT NULL COMMENT 'Action URL for notification',
    expires_at TIMESTAMP DEFAULT NULL COMMENT 'Notification expiry time',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Notification creation time',
    
    -- Foreign key constraint
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_user_notifications (user_id, is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_priority (priority)
) ENGINE=InnoDB COMMENT='System notifications and alerts';

-- =====================================================
-- TABLE: audit_logs (System Audit Trail)
-- Purpose: Track system actions for security and auditing
-- =====================================================
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT 'User who performed action',
    action VARCHAR(100) NOT NULL COMMENT 'Action performed',
    table_name VARCHAR(100) DEFAULT NULL COMMENT 'Table affected',
    record_id INT DEFAULT NULL COMMENT 'Record ID affected',
    old_values TEXT DEFAULT NULL COMMENT 'Previous values before change',
    new_values TEXT DEFAULT NULL COMMENT 'New values after change',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP address of user',
    user_agent TEXT DEFAULT NULL COMMENT 'Browser user agent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Log creation time',
    
    -- Foreign key constraint
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes for performance
    INDEX idx_user_action (user_id, action),
    INDEX idx_created_at (created_at),
    INDEX idx_table_record (table_name, record_id)
) ENGINE=InnoDB COMMENT='System audit trail and security logging';

-- =====================================================
-- TABLE: system_settings (System Configuration)
-- Purpose: Store system-wide configuration settings
-- =====================================================
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL COMMENT 'Setting identifier',
    setting_value TEXT DEFAULT NULL COMMENT 'Setting value',
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string' COMMENT 'Data type of setting',
    description TEXT DEFAULT NULL COMMENT 'Setting description',
    category VARCHAR(50) DEFAULT 'general' COMMENT 'Setting category',
    is_public BOOLEAN DEFAULT FALSE COMMENT 'Whether setting is publicly accessible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Setting creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    
    -- Indexes for performance
    INDEX idx_setting_key (setting_key),
    INDEX idx_category (category)
) ENGINE=InnoDB COMMENT='System configuration and settings management';

-- =====================================================
-- TABLE: academic_sessions (Academic Session Management)
-- Purpose: Manage academic sessions and terms
-- =====================================================
CREATE TABLE academic_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(100) NOT NULL COMMENT 'Session name (e.g., 2024-2025)',
    start_date DATE NOT NULL COMMENT 'Session start date',
    end_date DATE NOT NULL COMMENT 'Session end date',
    is_current BOOLEAN DEFAULT FALSE COMMENT 'Current active session',
    description TEXT DEFAULT NULL COMMENT 'Session description',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Session creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    
    -- Indexes for performance
    INDEX idx_session_dates (start_date, end_date),
    INDEX idx_is_current (is_current)
) ENGINE=InnoDB COMMENT='Academic session and term management';

-- =====================================================
-- INSERT SAMPLE DATA
-- Purpose: Provide initial data for testing and demonstration
-- =====================================================

-- Insert sample departments
INSERT INTO departments (department_name, department_code, description) VALUES 
('Computer Science', 'CS', 'Department of Computer Science and Applications'),
('Information Technology', 'IT', 'Department of Information Technology'),
('Mathematics', 'MATH', 'Department of Mathematics'),
('Physics', 'PHY', 'Department of Physics'),
('Chemistry', 'CHEM', 'Department of Chemistry');

-- Admin user (Super Admin)
-- Username: amiitsoft07@gmail.com
-- Password: 99055
INSERT INTO users (username, email, password, role) VALUES 
('amiitsoft07@gmail.com', 'amiitsoft07@gmail.com', '$2y$10$YourCorrectHashFor99055Here', 'admin');

-- Teachers
INSERT INTO users (username, email, password, role) VALUES 
('teacher1', 'john.smith@sms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher'),
('teacher2', 'sarah.jones@sms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher');

INSERT INTO teachers (user_id, first_name, last_name, employee_id, date_of_birth, gender, phone, address, qualification, specialization, hire_date, department, designation) VALUES 
(2, 'John', 'Smith', 'EMP001', '1985-05-15', 'male', '9876543210', '123 Main St, City', 'M.Sc Computer Science', 'Web Development', '2020-01-15', 1, 'Senior Lecturer'),
(3, 'Sarah', 'Jones', 'EMP002', '1988-08-22', 'female', '9876543211', '456 Oak Ave, City', 'M.Tech IT', 'Database Systems', '2020-06-10', 1, 'Assistant Professor');

-- Students
INSERT INTO users (username, email, password, role) VALUES 
('student1', 'alice.wilson@sms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('student2', 'bob.brown@sms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('student3', 'charlie.davis@sms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

INSERT INTO students (user_id, first_name, last_name, roll_number, date_of_birth, gender, phone, address, enrollment_date, batch_year, category, father_name, mother_name, parent_phone) VALUES 
(4, 'Alice', 'Wilson', 'BCA2021001', '2002-03-10', 'female', '9876543212', '789 Pine Rd, City', '2021-07-01', '2021-2025', 'general', 'Robert Wilson', 'Mary Wilson', '9876543210'),
(5, 'Bob', 'Brown', 'BCA2021002', '2002-07-25', 'male', '9876543213', '321 Elm St, City', '2021-07-01', '2021-2025', 'general', 'James Brown', 'Patricia Brown', '9876543211'),
(6, 'Charlie', 'Davis', 'BCA2021003', '2002-11-15', 'male', '9876543214', '654 Maple Dr, City', '2021-07-01', '2021-2025', 'general', 'Michael Davis', 'Jennifer Davis', '9876543212');

-- Courses
INSERT INTO courses (course_code, course_name, description, credits, department_id, semester, academic_year, max_students, teacher_id) VALUES 
('BCA101', 'Fundamentals of Computers', 'Introduction to computer basics and concepts', 4, 1, '1st Semester', '2024-2025', 60, 1),
('BCA102', 'Programming in C', 'Learn C programming language from basics to advanced concepts', 4, 1, '1st Semester', '2024-2025', 50, 1),
('BCA103', 'Database Management Systems', 'Introduction to database concepts and SQL programming', 4, 2, '2nd Semester', '2024-2025', 40, 2),
('BCA104', 'Web Technologies', 'HTML, CSS, JavaScript basics for modern web development', 3, 1, '2nd Semester', '2024-2025', 45, 1);

-- Enrollments
INSERT INTO enrollments (student_id, course_id, enrollment_date, status, fees_total, payment_status) VALUES 
(1, 1, '2021-07-01', 'active', 25000.00, 'completed'),
(1, 2, '2021-07-01', 'active', 4000.00, 'completed'),
(1, 3, '2021-07-01', 'active', 3000.00, 'completed'),
(2, 1, '2021-07-01', 'active', 25000.00, 'completed'),
(2, 2, '2021-07-01', 'active', 4000.00, 'completed'),
(2, 4, '2021-07-01', 'active', 3000.00, 'completed'),
(3, 1, '2021-07-01', 'active', 25000.00, 'completed'),
(3, 3, '2021-07-01', 'active', 3000.00, 'completed'),
(3, 4, '2021-07-01', 'active', 3000.00, 'completed');

-- Sample attendance data
INSERT INTO attendance (student_id, course_id, date, status, marked_by, remarks, check_in_time, check_out_time, total_hours) VALUES 
(1, 1, '2024-01-15', 'present', 1, 'On time', '09:00:00', '17:00:00', '8.00'),
(2, 1, '2024-01-15', 'present', 1, 'On time', '09:00:00', '17:00:00', '8.00'),
(3, 1, '2024-01-15', 'late', 1, '5 minutes late', '09:05:00', '17:00:00', '7.75'),
(1, 2, '2024-01-16', 'present', 1, 'On time', '09:00:00', '17:00:00', '8.00'),
(2, 2, '2024-01-16', 'absent', 1, 'Sick leave', NULL, NULL, NULL),
(3, 2, '2024-01-16', 'present', 2, 'On time', '09:00:00', '17:00:00', '8.00'),
(3, 3, '2024-01-17', 'present', 2, 'On time', '09:00:00', '17:00:00', '8.00');

-- Sample grades
INSERT INTO grades (enrollment_id, assessment_type, assessment_name, max_marks, obtained_marks, grade, grade_points, assessment_date, graded_by) VALUES 
(1, 'assignment', 'Assignment 1', 100.00, 85.00, 'A', 4.0, '2024-09-15', 1),
(1, 'assignment', 'Assignment 2', 100.00, 92.00, 'A-', 3.7, '2024-10-01', 1),
(1, 'quiz', 'Midterm Quiz', 50.00, 43.00, 'B+', 3.5, '2024-10-15', 1),
(2, 'assignment', 'Assignment 1', 100.00, 78.00, 'B+', 3.7, '2024-09-15', 2),
(2, 'quiz', 'Midterm Quiz', 50.00, 38.00, 'B', 3.0, '2024-10-15', 2);

-- Sample assignments
INSERT INTO assignments (course_id, title, description, assignment_type, total_marks, due_date, created_by) VALUES 
(1, 'Web Development Project', 'Create a responsive website using HTML, CSS, and JavaScript', 'project', 100.00, '2024-11-15', 1),
(2, 'Database Assignment', 'Design and implement a student management database', 'assignment', 100.00, '2024-11-01', 2);

-- Sample assignment submissions
INSERT INTO assignment_submissions (assignment_id, student_id, submission_date, file_name, file_size, status, marks_obtained, teacher_feedback) VALUES 
(1, 1, '2024-11-10', 'project_submission.pdf', 2048576, 'submitted', 85.00, 'Excellent work on responsive design!'),
(1, 2, '2024-11-08', 'database_assignment.sql', 1024, 'submitted', 92.00, 'Good database design with proper normalization');

-- Sample system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, category) VALUES 
('system_name', 'Student Management System', 'string', 'Name of the system', 'general'),
('institution_name', 'ABC College of Technology', 'string', 'Name of the educational institution', 'general'),
('academic_year', '2024-2025', 'string', 'Current academic year', 'academic'),
('max_file_size', '10485760', 'number', 'Maximum file upload size in bytes', 'uploads'),
('attendance_threshold', '75', 'number', 'Minimum attendance percentage required', 'academic'),
('grading_scale', 'A+,A,A-,B+,B,B-,C+,C,C-,D', 'string', 'Grading scale for assessments', 'academic');

-- Sample academic session
INSERT INTO academic_sessions (session_name, start_date, end_date, is_current, description) VALUES 
('2024-2025', '2024-07-01', '2025-06-30', TRUE, 'Current academic session with multiple terms');

-- =====================================================
-- DATABASE SETUP COMPLETE
-- =====================================================

-- Success message for database setup
SELECT 'Database setup completed successfully!' AS status;
