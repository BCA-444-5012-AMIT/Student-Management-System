<?php
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$role = getUserRole();
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="p-3">
        <h5 class="text-white mb-3">
            <i class="fas fa-user-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['username']); ?>
        </h5>
        <p class="text-white-50 small mb-3"><?php echo ucfirst($role); ?> Panel</p>
    </div>
    
    <nav class="nav flex-column">
        <?php if ($role === 'admin'): ?>
            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a class="nav-link <?php echo $current_page === 'students.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/students.php">
                <i class="fas fa-user-graduate me-2"></i>Students
            </a>
            <a class="nav-link <?php echo $current_page === 'teachers.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/teachers.php">
                <i class="fas fa-chalkboard-teacher me-2"></i>Teachers
            </a>
            <a class="nav-link <?php echo $current_page === 'courses.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/courses.php">
                <i class="fas fa-book me-2"></i>Courses
            </a>
            <a class="nav-link <?php echo $current_page === 'enrollments.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/enrollments.php">
                <i class="fas fa-list-alt me-2"></i>Enrollments
            </a>
            <a class="nav-link <?php echo $current_page === 'attendance.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/attendance.php">
                <i class="fas fa-calendar-check me-2"></i>Attendance
            </a>
            <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/reports.php">
                <i class="fas fa-chart-bar me-2"></i>Reports
            </a>
            <a class="nav-link <?php echo $current_page === 'student_search.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/student_search.php">
                <i class="fas fa-search me-2"></i>Student Search
            </a>
            
        <?php elseif ($role === 'teacher'): ?>
            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>teacher/dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a class="nav-link <?php echo $current_page === 'students.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>teacher/students.php">
                <i class="fas fa-user-graduate me-2"></i>My Students
            </a>
            <a class="nav-link <?php echo $current_page === 'courses.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>teacher/courses.php">
                <i class="fas fa-book me-2"></i>My Courses
            </a>
            <a class="nav-link <?php echo $current_page === 'attendance.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>teacher/attendance.php">
                <i class="fas fa-calendar-check me-2"></i>Mark Attendance
            </a>
            <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>teacher/profile.php">
                <i class="fas fa-user me-2"></i>Profile
            </a>
            
        <?php elseif ($role === 'student'): ?>
            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>student/dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>student/profile.php">
                <i class="fas fa-user me-2"></i>Profile
            </a>
            <a class="nav-link <?php echo $current_page === 'courses.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>student/courses.php">
                <i class="fas fa-book me-2"></i>My Courses
            </a>
            <a class="nav-link <?php echo $current_page === 'attendance.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>student/attendance.php">
                <i class="fas fa-calendar-check me-2"></i>Attendance
            </a>
        <?php endif; ?>
        
        <hr class="text-white-50">
        
        <a class="nav-link" href="<?php echo BASE_URL; ?>auth/logout.php">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </nav>
</div>
