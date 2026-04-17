<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

// Get statistics
try {
    $pdo = getDBConnection();
    
    // Total counts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $totalStudents = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM teachers");
    $totalTeachers = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
    $totalCourses = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM enrollments WHERE status = 'active'");
    $activeEnrollments = $stmt->fetch()['count'];
    
    // Recent enrollments
    $stmt = $pdo->query("
        SELECT e.id, s.first_name, s.last_name, c.course_name, e.enrollment_date 
        FROM enrollments e 
        JOIN students s ON e.student_id = s.id 
        JOIN courses c ON e.course_id = c.id 
        ORDER BY e.enrollment_date DESC 
        LIMIT 5
    ");
    $recentEnrollments = $stmt->fetchAll();
    
    // Attendance summary
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
            COUNT(CASE WHEN status = 'late' THEN 1 END) as late
        FROM attendance 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $attendanceSummary = $stmt->fetch();
    
} catch (PDOException $e) {
    setErrorMessage('Database error: ' . $e->getMessage());
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 d-none d-md-block">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>
                <div>
                    <span class="badge bg-primary">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $totalStudents; ?></div>
                        <div class="stats-label">Total Students</div>
                        <i class="fas fa-user-graduate stats-icon"></i>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $totalTeachers; ?></div>
                        <div class="stats-label">Total Teachers</div>
                        <i class="fas fa-chalkboard-teacher stats-icon"></i>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $totalCourses; ?></div>
                        <div class="stats-label">Total Courses</div>
                        <i class="fas fa-book stats-icon"></i>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $activeEnrollments; ?></div>
                        <div class="stats-label">Active Enrollments</div>
                        <i class="fas fa-list-alt stats-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Recent Enrollments -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Enrollments</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentEnrollments)): ?>
                                <p class="text-muted">No recent enrollments found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Course</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentEnrollments as $enrollment): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                                    <td><?php echo formatDate($enrollment['enrollment_date']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Weekly Attendance Summary -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Weekly Attendance Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="text-success">
                                        <h3><?php echo $attendanceSummary['present']; ?></h3>
                                        <small>Present</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-danger">
                                        <h3><?php echo $attendanceSummary['absent']; ?></h3>
                                        <small>Absent</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-warning">
                                        <h3><?php echo $attendanceSummary['late']; ?></h3>
                                        <small>Late</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <a href="students.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-user-plus me-2"></i>Add Student
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="teachers.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>Add Teacher
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="courses.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-plus-circle me-2"></i>Create Course
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="reports.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-file-alt me-2"></i>View Reports
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
