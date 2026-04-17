# Student Management System

A comprehensive web-based Student Management System built with PHP 8.x, MySQL, and Bootstrap 5. This system is designed as a Final Year BCA Project and provides complete functionality for managing students, teachers, courses, and attendance in educational institutions.

## 🚀 Features

### Authentication & Security
- Secure login/logout system with session management
- Role-based access control (Admin, Teacher, Student)
- Password hashing using PHP's `password_hash()` and `password_verify()`
- Session-based authentication with security measures

### Admin Features
- **Dashboard**: Overview statistics and quick actions
- **Student Management**: Add, view, and manage students
- **Teacher Management**: Add, view, and manage teachers
- **Course Management**: Create and manage courses with teacher assignments
- **Enrollment Management**: Track student enrollments
- **Attendance Overview**: View attendance statistics
- **Reports**: Generate and view various reports

### Teacher Features
- **Dashboard**: Personal statistics and assigned courses
- **Student Management**: View assigned students
- **Course Management**: Manage assigned courses
- **Attendance Marking**: Mark and update student attendance
- **Profile Management**: Update personal information

### Student Features
- **Dashboard**: Personal information and statistics
- **Profile Management**: View and update profile
- **Course Management**: View enrolled courses
- **Attendance Tracking**: View attendance history and statistics
- **Grades**: View academic performance (placeholder for future implementation)

## 🛠 Technology Stack

- **Backend**: PHP 8.x
- **Database**: MySQL with InnoDB engine
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5
- **Icons**: Font Awesome 6
- **Database Access**: PDO with prepared statements

## 📋 Requirements

- PHP 8.x or higher
- MySQL 5.7 or higher
- XAMPP/WAMP/MAMP (for local development)
- Modern web browser

## 🚀 Installation

### 1. Database Setup

1. Start your XAMPP control panel
2. Start Apache and MySQL services
3. Open phpMyAdmin (http://localhost/phpmyadmin)
4. Import the `database.sql` file provided in the project
5. Verify that the database `student_management` is created with all tables

### 2. Project Setup

1. Copy the project files to your XAMPP htdocs directory
2. Ensure the folder structure is maintained:
   ```
   student-management/
   ├── config/
   ├── auth/
   ├── admin/
   ├── teacher/
   ├── student/
   ├── includes/
   ├── assets/
   └── index.php
   ```

### 3. Access the Application

1. Open your web browser
2. Navigate to: `http://localhost/student-management/`
3. Use the demo credentials to login

## 🔐 Demo Credentials

| Role      | Username  | Password |
|-----------|-----------|----------|
| Admin     | admin     | password |
| Teacher   | teacher1  | password |
| Student   | student1  | password |

## 📁 Project Structure

```
student-management/
├── config/
│   └── config.php              # Database configuration and helper functions
├── auth/
│   ├── login.php               # Login page
│   ├── logout.php              # Logout functionality
│   └── unauthorized.php         # Unauthorized access page
├── admin/
│   ├── dashboard.php           # Admin dashboard
│   ├── students.php            # Student management
│   ├── teachers.php            # Teacher management
│   └── courses.php             # Course management
├── teacher/
│   ├── dashboard.php           # Teacher dashboard
│   ├── students.php            # View assigned students
│   ├── courses.php             # Manage assigned courses
│   ├── attendance.php          # Mark attendance
│   └── profile.php             # Teacher profile
├── student/
│   ├── dashboard.php           # Student dashboard
│   ├── profile.php             # Student profile
│   ├── courses.php             # View enrolled courses
│   ├── attendance.php          # View attendance
│   └── grades.php              # View grades (placeholder)
├── includes/
│   ├── header.php              # Common header
│   ├── footer.php              # Common footer
│   └── sidebar.php             # Navigation sidebar
├── assets/                     # Static assets (CSS, JS, images)
├── database.sql                # Database setup file
├── index.php                   # Home page
└── README.md                   # This file
```

## 🗄 Database Schema

The system uses the following main tables:

- **users**: Authentication and user information
- **students**: Student details and profiles
- **teachers**: Teacher details and profiles
- **courses**: Course information and assignments
- **enrollments**: Student-course relationships
- **attendance**: Attendance records and tracking

## 🔒 Security Features

- **Password Hashing**: All passwords are securely hashed using PHP's built-in functions
- **SQL Injection Prevention**: All database queries use PDO prepared statements
- **Session Security**: Secure session configuration with HTTP-only cookies
- **Input Validation**: All user inputs are properly sanitized
- **Role-Based Access**: Strict access control based on user roles
- **Error Handling**: Comprehensive error handling with try-catch blocks

## 🎯 Key Features Implementation

### Authentication System
- Secure login with username/email and password
- Session management with automatic logout
- Role-based redirection to appropriate dashboards

### User Management
- Admin can add/manage students and teachers
- Automatic user account creation for students and teachers
- Profile management for all user types

### Course Management
- Create courses with credits and descriptions
- Assign teachers to courses
- Student enrollment system

### Attendance System
- Teachers can mark attendance for their courses
- Students can view their attendance history
- Attendance statistics and reporting

### Responsive Design
- Mobile-friendly interface using Bootstrap 5
- Consistent UI/UX across all pages
- Modern and professional appearance

## 🔄 Future Enhancements

The following features are planned for future versions:

1. **Grades Module**: Complete grade management system
2. **Exam Management**: Schedule and manage examinations
3. **Assignment System**: Upload and manage assignments
4. **Notification System**: Email and in-app notifications
5. **File Management**: Document upload and management
6. **Advanced Reporting**: PDF reports and analytics
7. **Parent Portal**: Access for parents/guardians
8. **Mobile App**: Native mobile application

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL service is running in XAMPP
   - Check database credentials in `config/config.php`
   - Verify database is imported correctly

2. **Session Issues**
   - Ensure PHP session path is writable
   - Check session configuration in `config.php`

3. **Permission Errors**
   - Ensure proper file permissions on the project directory
   - Check XAMPP Apache error logs

4. **Blank Pages**
   - Enable error reporting in `config/config.php`
   - Check PHP error logs
   - Verify all required files exist

## 📞 Support

For support and queries:
- Check the troubleshooting section above
- Review the code comments for understanding
- Test with the provided demo credentials

## 📄 License

This project is developed as an educational project for BCA Final Year. Feel free to use, modify, and enhance according to your requirements.

## 🙏 Acknowledgments

- Bootstrap 5 for responsive UI framework
- Font Awesome for icons
- PHP community for excellent documentation
- MySQL for reliable database management

---

**Note**: This is a demonstration project. For production use, additional security measures and testing are recommended.
