<?php
$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/../config/config.php';
requireRole('student');

$studentInfo = [];
$myCourses = [];
$attendanceStats = [];
$recentAttendance = [];

// Get student information
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT s.* FROM students s WHERE s.user_id = ?");
    $stmt->execute([getUserId()]);
    $studentInfo = $stmt->fetch();
    
    if ($studentInfo) {
        $studentId = $studentInfo['id'];
        
        // Get student's courses
        $stmt = $pdo->prepare("
            SELECT c.*, e.enrollment_date, e.status as enrollment_status
            FROM courses c 
            JOIN enrollments e ON c.id = e.course_id 
            WHERE e.student_id = ? AND e.status = 'active'
            ORDER BY c.course_name
        ");
        $stmt->execute([$studentId]);
        $myCourses = $stmt->fetchAll();
        
        // Get attendance statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                COUNT(*) as total
            FROM attendance 
            WHERE student_id = ?
        ");
        $stmt->execute([$studentId]);
        $attendanceStats = $stmt->fetch();
        
        // Get recent attendance
        $stmt = $pdo->prepare("
            SELECT a.*, c.course_name, c.course_code
            FROM attendance a
            JOIN courses c ON a.course_id = c.id
            WHERE a.student_id = ?
            ORDER BY a.date DESC
            LIMIT 10
        ");
        $stmt->execute([$studentId]);
        $recentAttendance = $stmt->fetchAll();
    }
    
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
                <h2><i class="fas fa-tachometer-alt me-2"></i>Student Dashboard</h2>
                <div>
                    <span class="badge bg-primary">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
            
            <!-- Student Info Card -->
            <?php if ($studentInfo): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($studentInfo['first_name'] . ' ' . $studentInfo['last_name']); ?>
                                </h5>
                                <p class="text-muted mb-2">Roll Number: <?php echo htmlspecialchars($studentInfo['roll_number']); ?></p>
                                <p class="mb-1"><strong>Date of Birth:</strong> <?php echo formatDate($studentInfo['date_of_birth']); ?></p>
                                <p class="mb-1"><strong>Gender:</strong> <?php echo ucfirst($studentInfo['gender']); ?></p>
                                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($studentInfo['phone'] ?: 'Not provided'); ?></p>
                                <p class="mb-0"><strong>Enrollment Date:</strong> <?php echo formatDate($studentInfo['enrollment_date']); ?></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-outline-primary" onclick="editProfile()">
                                    <i class="fas fa-edit me-2"></i>Edit Profile
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($myCourses); ?></div>
                        <div class="stats-label">My Courses</div>
                        <i class="fas fa-book stats-icon"></i>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $attendanceStats['present']; ?></div>
                        <div class="stats-label">Total Present</div>
                        <i class="fas fa-check-circle stats-icon"></i>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $attendanceStats['absent']; ?></div>
                        <div class="stats-label">Total Absent</div>
                        <i class="fas fa-times-circle stats-icon"></i>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number">
                            <?php 
                            $attendancePercentage = $attendanceStats['total'] > 0 
                                ? round(($attendanceStats['present'] / $attendanceStats['total']) * 100, 1) 
                                : 0; 
                            echo $attendancePercentage . '%';
                            ?>
                        </div>
                        <div class="stats-label">Attendance Rate</div>
                        <i class="fas fa-percentage stats-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- My Courses -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-book me-2"></i>My Courses</h5>
                            <a href="courses.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($myCourses)): ?>
                                <p class="text-muted">No courses enrolled yet.</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($myCourses, 0, 5) as $course): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($course['course_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($course['course_code']); ?> • <?php echo $course['credits']; ?> credits</small>
                                            </div>
                                            <span class="badge bg-success rounded-pill">Active</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Attendance -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Recent Attendance</h5>
                            <a href="attendance.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentAttendance)): ?>
                                <p class="text-muted">No attendance records found.</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($recentAttendance, 0, 5) as $attendance): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($attendance['course_name']); ?></h6>
                                                    <small class="text-muted"><?php echo formatDate($attendance['date']); ?></small>
                                                </div>
                                                <span class="badge <?php 
                                                    echo $attendance['status'] === 'present' ? 'bg-success' : 
                                                         ($attendance['status'] === 'absent' ? 'bg-danger' : 'bg-warning'); 
                                                ?> rounded-pill">
                                                    <?php echo ucfirst($attendance['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
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
                                    <a href="profile.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-user me-2"></i>My Profile
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="courses.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-book me-2"></i>My Courses
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="attendance.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-calendar-check me-2"></i>Attendance
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="grades.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-chart-line me-2"></i>Grades
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

<script>
function editProfile() {
    window.location.href = 'profile.php';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
