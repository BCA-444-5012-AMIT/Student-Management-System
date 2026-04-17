<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$students = [];
$teachers = [];
$courses = [];
$attendanceStats = [];
$enrollmentStats = [];
$courseStats = [];

// Get attendance statistics
try {
    $pdo = getDBConnection();
    
    // Overall attendance statistics
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count,
            ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance), 2) as percentage
        FROM attendance 
        GROUP BY status
    ");
    $attendanceStats = $stmt->fetchAll();
    
    // Course-wise attendance
    $stmt = $pdo->query("
        SELECT 
            c.course_code,
            c.course_name,
            COUNT(a.id) as total_attendance,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
            ROUND(
                (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) * 100.0) / COUNT(a.id), 
                2
            ) as attendance_rate
        FROM courses c
        LEFT JOIN attendance a ON c.id = a.course_id
        GROUP BY c.id, c.course_code, c.course_name
        HAVING total_attendance > 0
        ORDER BY attendance_rate DESC
    ");
    $courseStats = $stmt->fetchAll();
    
    // Enrollment statistics
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count,
            ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM enrollments), 2) as percentage
        FROM enrollments 
        GROUP BY status
    ");
    $enrollmentStats = $stmt->fetchAll();
    
    // Student attendance summary
    $stmt = $pdo->query("
        SELECT 
            s.id,
            s.first_name,
            s.last_name,
            s.roll_number,
            u.email,
            u.username,
            COUNT(a.id) as total_classes,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
            ROUND(
                CASE WHEN COUNT(a.id) > 0 THEN 
                    (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) * 100.0) / COUNT(a.id)
                ELSE 0 END, 
                2
            ) as attendance_percentage,
            GROUP_CONCAT(DISTINCT c.course_code ORDER BY c.course_code SEPARATOR ', ') as enrolled_courses
        FROM students s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN attendance a ON s.id = a.student_id
        LEFT JOIN courses c ON a.course_id = c.id
        GROUP BY s.id, s.first_name, s.last_name, s.roll_number, u.email, u.username
        ORDER BY attendance_percentage DESC, s.first_name, s.last_name
    ");
    $students = $stmt->fetchAll();
    
    // Teacher course load
    $stmt = $pdo->query("
        SELECT 
            t.id,
            t.first_name,
            t.last_name,
            COUNT(c.id) as courses_assigned,
            COUNT(DISTINCT a.student_id) as total_students,
            COUNT(a.id) as total_attendance_marked
        FROM teachers t
        LEFT JOIN courses c ON t.id = c.teacher_id
        LEFT JOIN attendance a ON c.id = a.course_id
        GROUP BY t.id, t.first_name, t.last_name
        ORDER BY courses_assigned DESC
    ");
    $teachers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setErrorMessage('Database error: ' . $e->getMessage());
}

// Handle date range filtering
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['filter'])) {
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    
    try {
        $pdo = getDBConnection();
        
        // Filtered attendance statistics
        $stmt = $pdo->prepare("
            SELECT 
                status,
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance WHERE date BETWEEN ? AND ?), 2) as percentage
            FROM attendance 
            WHERE date BETWEEN ? AND ?
            GROUP BY status
        ");
        $stmt->execute([$startDate, $endDate, $startDate, $endDate]);
        $attendanceStats = $stmt->fetchAll();
        
        // Filtered course-wise attendance
        $stmt = $pdo->prepare("
            SELECT 
                c.course_code,
                c.course_name,
                COUNT(a.id) as total_attendance,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
                ROUND(
                    (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) * 100.0) / COUNT(a.id), 
                    2
                ) as attendance_rate
            FROM courses c
            LEFT JOIN attendance a ON c.id = a.course_id AND a.date BETWEEN ? AND ?
            GROUP BY c.id, c.course_code, c.course_name
            HAVING total_attendance > 0
            ORDER BY attendance_rate DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $courseStats = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        setErrorMessage('Database error: ' . $e->getMessage());
    }
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
                <h2><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h2>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Report
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="reportSearch" placeholder="Search reports by student, course, or date...">
                        <button class="btn btn-outline-secondary" type="button" id="clearReportSearch">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                    <small class="text-muted">Type to search reports in real-time</small>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-end">
                        <span class="me-2">Total Records:</span>
                        <span class="badge bg-primary" id="reportCount">0</span>
                    </div>
                </div>
            </div>
            
            <!-- Search Results Message -->
            <div id="reportSearchResults" class="alert alert-info" style="display: none;">
                <i class="fas fa-info-circle me-2"></i>
                <span id="reportSearchMessage"></span>
            </div>
            
            <!-- Date Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="filter" value="1">
                        <div class="col-md-4">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : date('Y-m-01'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                            <a href="reports.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div id="noReportsMessage" style="display: none;">
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>No reports found</h5>
                            <p class="text-muted">Try adjusting your search criteria.</p>
                                <div>
                                    <h4 class="card-title">
                                        <?php 
                                        $totalStudents = 0;
                                        try {
                                            $pdo = getDBConnection();
                                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
                                            $result = $stmt->fetch();
                                            $totalStudents = $result['count'];
                                        } catch (PDOException $e) {}
                                        echo $totalStudents;
                                        ?>
                                    </h4>
                                    <p class="card-text">Total Students</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-user-graduate fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">
                                        <?php 
                                        $totalTeachers = 0;
                                        try {
                                            $pdo = getDBConnection();
                                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM teachers");
                                            $result = $stmt->fetch();
                                            $totalTeachers = $result['count'];
                                        } catch (PDOException $e) {}
                                        echo $totalTeachers;
                                        ?>
                                    </h4>
                                    <p class="card-text">Total Teachers</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-chalkboard-teacher fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">
                                        <?php 
                                        $totalCourses = 0;
                                        try {
                                            $pdo = getDBConnection();
                                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
                                            $result = $stmt->fetch();
                                            $totalCourses = $result['count'];
                                        } catch (PDOException $e) {}
                                        echo $totalCourses;
                                        ?>
                                    </h4>
                                    <p class="card-text">Total Courses</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-book fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">
                                        <?php 
                                        $totalEnrollments = 0;
                                        try {
                                            $pdo = getDBConnection();
                                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM enrollments");
                                            $result = $stmt->fetch();
                                            $totalEnrollments = $result['count'];
                                        } catch (PDOException $e) {}
                                        echo $totalEnrollments;
                                        ?>
                                    </h4>
                                    <p class="card-text">Total Enrollments</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-list-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Statistics -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-pie me-2"></i>Attendance Overview</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($attendanceStats)): ?>
                                <?php foreach ($attendanceStats as $stat): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>
                                            <?php
                                            $badgeClass = '';
                                            switch($stat['status']) {
                                                case 'present':
                                                    $badgeClass = 'bg-success';
                                                    break;
                                                case 'absent':
                                                    $badgeClass = 'bg-danger';
                                                    break;
                                                case 'late':
                                                    $badgeClass = 'bg-warning';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($stat['status']); ?></span>
                                        </span>
                                        <div class="d-flex align-items-center">
                                            <span class="me-3"><?php echo $stat['count']; ?></span>
                                            <div class="progress" style="width: 100px;">
                                                <div class="progress-bar" style="width: <?php echo $stat['percentage']; ?>%"></div>
                                            </div>
                                            <span class="ms-2"><?php echo $stat['percentage']; ?>%</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No attendance data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-pie me-2"></i>Enrollment Status</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($enrollmentStats)): ?>
                                <?php foreach ($enrollmentStats as $stat): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>
                                            <?php
                                            $badgeClass = '';
                                            switch($stat['status']) {
                                                case 'active':
                                                    $badgeClass = 'bg-success';
                                                    break;
                                                case 'completed':
                                                    $badgeClass = 'bg-primary';
                                                    break;
                                                case 'dropped':
                                                    $badgeClass = 'bg-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($stat['status']); ?></span>
                                        </span>
                                        <div class="d-flex align-items-center">
                                            <span class="me-3"><?php echo $stat['count']; ?></span>
                                            <div class="progress" style="width: 100px;">
                                                <div class="progress-bar" style="width: <?php echo $stat['percentage']; ?>%"></div>
                                            </div>
                                            <span class="ms-2"><?php echo $stat['percentage']; ?>%</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No enrollment data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Course-wise Attendance -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar me-2"></i>Course-wise Attendance</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($courseStats)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Total Classes</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>Late</th>
                                        <th>Attendance Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courseStats as $course): ?>
                                        <tr class="report-row" 
                                            data-course="<?php echo htmlspecialchars(strtolower($course['course_code'] . ' ' . $course['course_name'])); ?>"
                                            data-total="<?php echo $course['total_attendance']; ?>"
                                            data-present="<?php echo $course['present']; ?>"
                                            data-absent="<?php echo $course['absent']; ?>"
                                            data-late="<?php echo $course['late']; ?>"
                                            data-rate="<?php echo $course['attendance_rate']; ?>"
                                            data-search-text="<?php echo htmlspecialchars(strtolower($course['course_code'] . ' ' . $course['course_name'] . ' ' . $course['total_attendance'] . ' ' . $course['present'] . ' ' . $course['absent'] . ' ' . $course['late'] . ' ' . $course['attendance_rate'])); ?>">
                                            <td>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                                <?php echo htmlspecialchars($course['course_name']); ?>
                                            </td>
                                            <td><?php echo $course['total_attendance']; ?></td>
                                            <td><span class="badge bg-success"><?php echo $course['present']; ?></span></td>
                                            <td><span class="badge bg-danger"><?php echo $course['absent']; ?></span></td>
                                            <td><span class="badge bg-warning"><?php echo $course['late']; ?></span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress me-2" style="width: 100px;">
                                                        <div class="progress-bar bg-success" style="width: <?php echo $course['attendance_rate']; ?>%"></div>
                                                    </div>
                                                    <span><?php echo $course['attendance_rate']; ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No course attendance data available.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Top Students by Attendance -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-trophy me-2"></i>Top Students by Attendance</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($students)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="studentsTable">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Student Details</th>
                                                <th>Contact Info</th>
                                                <th>Enrolled Courses</th>
                                                <th>Attendance Summary</th>
                                                <th>Attendance %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $rank = 1; ?>
                                            <?php foreach ($students as $student): ?>
                                                <tr class="student-row" 
                                                    data-id="<?php echo $student['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars(strtolower($student['first_name'] . ' ' . $student['last_name'])); ?>"
                                                    data-roll="<?php echo htmlspecialchars(strtolower($student['roll_number'])); ?>"
                                                    data-username="<?php echo htmlspecialchars(strtolower($student['username'])); ?>"
                                                    data-email="<?php echo htmlspecialchars(strtolower($student['email'])); ?>"
                                                    data-courses="<?php echo htmlspecialchars(strtolower($student['enrolled_courses'] ?? '')); ?>"
                                                    data-total="<?php echo $student['total_classes']; ?>"
                                                    data-present="<?php echo $student['present']; ?>"
                                                    data-absent="<?php echo $student['absent']; ?>"
                                                    data-late="<?php echo $student['late']; ?>"
                                                    data-percentage="<?php echo $student['attendance_percentage']; ?>"
                                                    data-search-text="<?php echo htmlspecialchars(strtolower($student['first_name'] . ' ' . $student['last_name'] . ' ' . $student['roll_number'] . ' ' . $student['username'] . ' ' . $student['email'] . ' ' . ($student['enrolled_courses'] ?? '') . ' ' . $student['total_classes'] . ' ' . $student['present'] . ' ' . $student['absent'] . ' ' . $student['late'] . ' ' . $student['attendance_percentage'])); ?>">
                                                    <td>
                                                        <?php if ($rank <= 3): ?>
                                                            <i class="fas fa-medal text-<?php echo $rank == 1 ? 'warning' : ($rank == 2 ? 'secondary' : 'danger'); ?> me-1"></i>
                                                        <?php endif; ?>
                                                        <span class="badge bg-primary">#<?php echo $rank++; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="student-info">
                                                            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-id-card me-1"></i><?php echo htmlspecialchars($student['roll_number']); ?>
                                                                <br>
                                                                <i class="fas fa-user me-1"></i>@<?php echo htmlspecialchars($student['username']); ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="contact-info">
                                                            <?php if (!empty($student['email'])): ?>
                                                                <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>" class="d-block mb-1">
                                                                    <i class="fas fa-envelope text-primary me-1"></i>
                                                                    <small><?php echo htmlspecialchars($student['email']); ?></small>
                                                                </a>
                                                            <?php endif; ?>
                                                            <div class="d-block">
                                                                <i class="fas fa-info-circle text-muted me-1"></i>
                                                                <small class="text-muted">Phone not available</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="courses-info">
                                                            <?php 
                                                            $courses = $student['enrolled_courses'] ?? '';
                                                            if (!empty($courses)) {
                                                                $courseArray = explode(', ', $courses);
                                                                foreach ($courseArray as $course) {
                                                                    echo '<span class="badge bg-info me-1 mb-1">' . htmlspecialchars($course) . '</span>';
                                                                }
                                                            } else {
                                                                echo '<span class="text-muted">No courses</span>';
                                                            }
                                                            ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="attendance-summary">
                                                            <?php if ($student['total_classes'] > 0): ?>
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <small><i class="fas fa-check text-success me-1"></i>Present: <strong><?php echo $student['present']; ?></strong></small>
                                                                </div>
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <small><i class="fas fa-times text-danger me-1"></i>Absent: <strong><?php echo $student['absent']; ?></strong></small>
                                                                </div>
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <small><i class="fas fa-clock text-warning me-1"></i>Late: <strong><?php echo $student['late']; ?></strong></small>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <small><i class="fas fa-calendar text-primary me-1"></i>Total: <strong><?php echo $student['total_classes']; ?></strong></small>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="text-center">
                                                                    <i class="fas fa-info-circle text-muted fa-2x mb-2"></i>
                                                                    <small class="text-muted">No attendance data available</small>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="attendance-percentage">
                                                            <?php 
                                                            $percentage = $student['attendance_percentage'];
                                                            $totalClasses = $student['total_classes'];
                                                            
                                                            if ($totalClasses > 0) {
                                                                $badgeClass = $percentage >= 75 ? 'bg-success' : 
                                                                             ($percentage >= 60 ? 'bg-warning' : 'bg-danger');
                                                                echo '<span class="badge ' . $badgeClass . ' fs-6">' . $percentage . '%</span>';
                                                                echo '<div class="progress mt-2" style="height: 8px;">';
                                                                echo '<div class="progress-bar ' . $badgeClass . '" style="width: ' . $percentage . '%"></div>';
                                                                echo '</div>';
                                                            } else {
                                                                echo '<span class="badge bg-secondary fs-6">No Data</span>';
                                                                echo '<div class="progress mt-2" style="height: 8px;">';
                                                                echo '<div class="progress-bar bg-secondary" style="width: 0%"></div>';
                                                                echo '</div>';
                                                            }
                                                            ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No students found in the system.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Teacher Statistics -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Statistics</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($teachers)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Teacher Name</th>
                                        <th>Courses Assigned</th>
                                        <th>Total Students</th>
                                        <th>Attendance Marked</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></td>
                                            <td><span class="badge bg-primary"><?php echo $teacher['courses_assigned']; ?></span></td>
                                            <td><span class="badge bg-info"><?php echo $teacher['total_students']; ?></span></td>
                                            <td><span class="badge bg-success"><?php echo $teacher['total_attendance_marked']; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No teacher data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .btn, .no-print {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
    }
}

/* Enhanced Student Table Styles */
.student-info {
    line-height: 1.4;
}

.contact-info a {
    text-decoration: none;
    color: inherit;
}

.contact-info a:hover {
    text-decoration: underline;
}

.courses-info {
    max-width: 200px;
}

.courses-info .badge {
    font-size: 0.7rem;
    margin-bottom: 2px;
}

.attendance-summary {
    font-size: 0.85rem;
    line-height: 1.3;
}

.attendance-percentage {
    min-width: 100px;
}

.attendance-percentage .badge {
    font-size: 0.9rem;
    font-weight: bold;
}

.student-row {
    transition: all 0.2s ease;
}

.student-row:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.student-row td {
    vertical-align: top;
    padding: 12px 8px;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .courses-info {
        max-width: 150px;
    }
    
    .attendance-summary {
        font-size: 0.8rem;
    }
}

@media (max-width: 992px) {
    .contact-info {
        font-size: 0.8rem;
    }
    
    .courses-info .badge {
        font-size: 0.65rem;
    }
}

@media (max-width: 768px) {
    .student-row td {
        padding: 8px 4px;
    }
    
    .attendance-summary {
        font-size: 0.75rem;
    }
    
    .attendance-percentage {
        min-width: 80px;
    }
}

/* Medal styling */
.fa-medal {
    font-size: 1.2rem;
}

/* Progress bar styling */
.progress {
    background-color: #e9ecef;
    border-radius: 4px;
}

.progress-bar {
    transition: width 0.6s ease;
}

/* Badge enhancements */
.badge {
    font-weight: 500;
}

/* Table header styling */
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-top: none;
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Search highlight */
.student-row.search-highlight {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}
</style>

<script>
// Report Search Functionality
document.addEventListener('DOMContentLoaded', function() {
    const reportSearchInput = document.getElementById('reportSearch');
    const clearReportButton = document.getElementById('clearReportSearch');
    const reportRows = document.querySelectorAll('.report-row');
    const studentRows = document.querySelectorAll('.student-row');
    const reportCount = document.getElementById('reportCount');
    const reportSearchResults = document.getElementById('reportSearchResults');
    const reportSearchMessage = document.getElementById('reportSearchMessage');
    const noReportsMessage = document.getElementById('noReportsMessage');
    const noReportDataMessage = document.getElementById('noReportDataMessage');
    
    // Debug initialization
    console.log('=== Search Initialization ===');
    console.log('Report search input found:', !!reportSearchInput);
    console.log('Report rows found:', reportRows.length);
    console.log('Student rows found:', studentRows.length);
    console.log('Report count element found:', !!reportCount);
    
    // Combine all searchable rows
    const allRows = Array.from(reportRows).concat(Array.from(studentRows));
    console.log('Total searchable rows:', allRows.length);
    
    // Initialize report count
    if (reportCount) {
        reportCount.textContent = allRows.length;
        console.log('Initial count set to:', allRows.length);
    }
    
    // Show/hide appropriate messages based on data availability
    if (allRows.length === 0) {
        if (noReportDataMessage) noReportDataMessage.style.display = 'block';
        console.log('No data message shown');
    }
    
    // Search functionality
    if (reportSearchInput) {
        reportSearchInput.addEventListener('input', function() {
            performReportSearch();
        });
        
        reportSearchInput.addEventListener('keyup', function(e) {
            // Clear search on Escape key
            if (e.key === 'Escape') {
                clearReportSearch();
            }
        });
    }
    
    // Clear search functionality
    if (clearReportButton) {
        clearReportButton.addEventListener('click', clearReportSearch);
    }
    
    function performReportSearch() {
        const searchTerm = reportSearchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        let totalRecords = allRows.length;
        
        console.log('Search term:', searchTerm);
        console.log('Total rows:', totalRecords);
        console.log('Student rows found:', studentRows.length);
        
        allRows.forEach(function(row, index) {
            const searchText = row.getAttribute('data-search-text');
            
            // Additional student-specific fields
            const name = row.getAttribute('data-name');
            const roll = row.getAttribute('data-roll');
            const username = row.getAttribute('data-username');
            const email = row.getAttribute('data-email');
            const courses = row.getAttribute('data-courses');
            const total = row.getAttribute('data-total');
            const present = row.getAttribute('data-present');
            const absent = row.getAttribute('data-absent');
            const late = row.getAttribute('data-late');
            const percentage = row.getAttribute('data-percentage');
            
            // Debug logging for first few rows
            if (index < 3) {
                console.log(`Row ${index}:`, {
                    name: name,
                    roll: roll,
                    searchText: searchText,
                    matches: searchText.includes(searchTerm)
                });
            }
            
            // Check if search term matches any field
            const matches = searchTerm === '' || 
                           searchText.includes(searchTerm) ||
                           name.includes(searchTerm) ||
                           roll.includes(searchTerm) ||
                           username.includes(searchTerm) ||
                           email.includes(searchTerm) ||
                           courses.includes(searchTerm) ||
                           total.includes(searchTerm) ||
                           present.includes(searchTerm) ||
                           absent.includes(searchTerm) ||
                           late.includes(searchTerm) ||
                           percentage.includes(searchTerm);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        console.log('Visible count:', visibleCount);
        
        // Update UI based on search results
        updateReportSearchUI(searchTerm, visibleCount, totalRecords);
    }
    
    function updateReportSearchUI(searchTerm, visibleCount, totalRecords) {
        // Update report count
        if (reportCount) {
            reportCount.textContent = visibleCount;
        }
        
        // Show/hide search results message
        if (reportSearchResults && reportSearchMessage) {
            if (searchTerm !== '') {
                reportSearchResults.style.display = 'block';
                if (visibleCount > 0) {
                    reportSearchResults.className = 'alert alert-success';
                    reportSearchMessage.innerHTML = `<i class="fas fa-check-circle me-2"></i>Found ${visibleCount} record${visibleCount !== 1 ? 's' : ''} matching "${searchTerm}"`;
                } else {
                    reportSearchResults.className = 'alert alert-warning';
                    reportSearchMessage.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>No records found matching "${searchTerm}"`;
                }
            } else {
                reportSearchResults.style.display = 'none';
            }
        }
        
        // Show/hide appropriate messages
        if (visibleCount === 0) {
            if (searchTerm !== '') {
                // Search returned no results
                if (noReportsMessage) noReportsMessage.style.display = 'block';
                if (noReportDataMessage) noReportDataMessage.style.display = 'none';
            } else {
                // No records at all
                if (noReportDataMessage) noReportDataMessage.style.display = 'block';
                if (noReportsMessage) noReportsMessage.style.display = 'none';
            }
        } else {
            // Records found
            if (noReportsMessage) noReportsMessage.style.display = 'none';
            if (noReportDataMessage) noReportDataMessage.style.display = 'none';
        }
    }
    
    function clearReportSearch() {
        if (reportSearchInput) {
            reportSearchInput.value = '';
            performReportSearch();
            reportSearchInput.focus();
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
