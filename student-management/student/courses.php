<?php
$pageTitle = 'My Courses';
require_once __DIR__ . '/../config/config.php';
requireRole('student');

$studentId = null;
$myCourses = [];
$selectedCourse = null;
$courseAttendance = [];

// Get student information
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([getUserId()]);
    $student = $stmt->fetch();
    
    if ($student) {
        $studentId = $student['id'];
        
        // Get student's enrolled courses
        $stmt = $pdo->prepare("
            SELECT c.*, e.enrollment_date, e.status as enrollment_status,
                   t.first_name as teacher_first_name, t.last_name as teacher_last_name
            FROM courses c 
            JOIN enrollments e ON c.id = e.course_id 
            LEFT JOIN teachers t ON c.teacher_id = t.id
            WHERE e.student_id = ? AND e.status = 'active'
            ORDER BY c.course_name
        ");
        $stmt->execute([$studentId]);
        $myCourses = $stmt->fetchAll();
        
        // Get details for selected course
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        if ($courseId > 0) {
            $stmt = $pdo->prepare("
                SELECT c.*, e.enrollment_date, e.status as enrollment_status,
                       t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM courses c 
                JOIN enrollments e ON c.id = e.course_id 
                LEFT JOIN teachers t ON c.teacher_id = t.id
                WHERE e.student_id = ? AND e.course_id = ? AND e.status = 'active'
            ");
            $stmt->execute([$studentId, $courseId]);
            $selectedCourse = $stmt->fetch();
            
            if ($selectedCourse) {
                // Get attendance for this course
                $stmt = $pdo->prepare("
                    SELECT status, date, remarks
                    FROM attendance 
                    WHERE student_id = ? AND course_id = ?
                    ORDER BY date DESC
                    LIMIT 10
                ");
                $stmt->execute([$studentId, $courseId]);
                $courseAttendance = $stmt->fetchAll();
                
                // Get attendance statistics for this course
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                        COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                        COUNT(*) as total
                    FROM attendance 
                    WHERE student_id = ? AND course_id = ?
                ");
                $stmt->execute([$studentId, $courseId]);
                $attendanceStats = $stmt->fetch();
            }
        }
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
                <h2><i class="fas fa-book me-2"></i>My Courses</h2>
                <div>
                    <span class="badge bg-primary">Total: <?php echo count($myCourses); ?></span>
                </div>
            </div>
            
            <!-- Course Selection -->
            <?php if (!empty($myCourses)): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label">Select Course to View Details</label>
                                <select class="form-select" onchange="window.location.href='courses.php?course_id=' + this.value">
                                    <option value="">Choose a course...</option>
                                    <?php foreach ($myCourses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" 
                                                <?php echo (isset($_GET['course_id']) && $_GET['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='courses.php'">
                                    <i class="fas fa-times me-2"></i>Clear Selection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Course Details -->
            <?php if ($selectedCourse): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo htmlspecialchars($selectedCourse['course_name']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Course Code:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars($selectedCourse['course_code']); ?></span></p>
                                <p><strong>Credits:</strong> <?php echo $selectedCourse['credits']; ?></p>
                                <p><strong>Enrollment Date:</strong> <?php echo formatDate($selectedCourse['enrollment_date']); ?></p>
                                <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Assigned Teacher:</strong></p>
                                <?php if ($selectedCourse['teacher_first_name']): ?>
                                    <p><?php echo htmlspecialchars($selectedCourse['teacher_first_name'] . ' ' . $selectedCourse['teacher_last_name']); ?></p>
                                <?php else: ?>
                                    <p class="text-muted">Not assigned yet</p>
                                <?php endif; ?>
                                <p><strong>Description:</strong></p>
                                <p><?php echo htmlspecialchars($selectedCourse['description'] ?: 'No description available'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Summary -->
                <?php if (isset($attendanceStats)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2"></i>
                                Attendance Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="text-success">
                                        <h3><?php echo $attendanceStats['present']; ?></h3>
                                        <small>Present</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-danger">
                                        <h3><?php echo $attendanceStats['absent']; ?></h3>
                                        <small>Absent</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-warning">
                                        <h3><?php echo $attendanceStats['late']; ?></h3>
                                        <small>Late</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-info">
                                        <h3>
                                            <?php 
                                            $attendancePercentage = $attendanceStats['total'] > 0 
                                                ? round(($attendanceStats['present'] / $attendanceStats['total']) * 100, 1) 
                                                : 0; 
                                            echo $attendancePercentage . '%';
                                            ?>
                                        </h3>
                                        <small>Attendance Rate</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Recent Attendance -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-check me-2"></i>
                            Recent Attendance
                        </h5>
                        <a href="attendance.php?course_id=<?php echo $selectedCourse['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-list me-2"></i>View All Attendance
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($courseAttendance)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                <h5>No attendance records</h5>
                                <p class="text-muted">No attendance has been marked for this course yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courseAttendance as $attendance): ?>
                                            <tr>
                                                <td><?php echo formatDate($attendance['date']); ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $attendance['status'] === 'present' ? 'bg-success' : 
                                                             ($attendance['status'] === 'absent' ? 'bg-danger' : 'bg-warning'); 
                                                    ?>">
                                                        <?php echo ucfirst($attendance['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($attendance['remarks']): ?>
                                                        <?php echo htmlspecialchars($attendance['remarks']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            
            <?php else: ?>
                <!-- All Courses List -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($myCourses)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <h5>No courses enrolled</h5>
                                <p class="text-muted">You haven't enrolled in any courses yet. Please contact the administrator.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($myCourses as $course): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h6>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                                </div>
                                                <p class="card-text text-muted small">
                                                    <?php echo htmlspecialchars(substr($course['description'], 0, 80)) . (strlen($course['description']) > 80 ? '...' : ''); ?>
                                                </p>
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?php if ($course['teacher_first_name']): ?>
                                                            <?php echo htmlspecialchars($course['teacher_first_name'] . ' ' . $course['teacher_last_name']); ?>
                                                        <?php else: ?>
                                                            No teacher assigned
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-star me-1"></i>
                                                        <?php echo $course['credits']; ?> credits
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo formatDate($course['enrollment_date']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <a href="courses.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm w-100">
                                                    <i class="fas fa-eye me-2"></i>View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
