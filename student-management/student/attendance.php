<?php
$pageTitle = 'My Attendance';
require_once __DIR__ . '/../config/config.php';
requireRole('student');

$studentInfo = [];
$attendanceRecords = [];
$attendanceStats = [];
$courseFilter = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$monthFilter = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Get student information
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([getUserId()]);
    $student = $stmt->fetch();
    
    if ($student) {
        $studentId = $student['id'];
        
        // Get student's courses for filter
        $stmt = $pdo->prepare("
            SELECT c.id, c.course_code, c.course_name
            FROM courses c 
            JOIN enrollments e ON c.id = e.course_id 
            WHERE e.student_id = ? AND e.status = 'active'
            ORDER BY c.course_name
        ");
        $stmt->execute([$studentId]);
        $myCourses = $stmt->fetchAll();
        
        // Build attendance query with filters
        $sql = "
            SELECT a.*, c.course_code, c.course_name, c.credits
            FROM attendance a
            JOIN courses c ON a.course_id = c.id
            WHERE a.student_id = ?
        ";
        $params = [$studentId];
        
        if ($courseFilter > 0) {
            $sql .= " AND a.course_id = ?";
            $params[] = $courseFilter;
        }
        
        if ($monthFilter) {
            $sql .= " AND DATE_FORMAT(a.date, '%Y-%m') = ?";
            $params[] = $monthFilter;
        }
        
        $sql .= " ORDER BY a.date DESC, c.course_name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $attendanceRecords = $stmt->fetchAll();
        
        // Get attendance statistics
        $statsSql = "
            SELECT 
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                COUNT(*) as total
            FROM attendance 
            WHERE student_id = ?
        ";
        $statsParams = [$studentId];
        
        if ($courseFilter > 0) {
            $statsSql .= " AND course_id = ?";
            $statsParams[] = $courseFilter;
        }
        
        if ($monthFilter) {
            $statsSql .= " AND DATE_FORMAT(date, '%Y-%m') = ?";
            $statsParams[] = $monthFilter;
        }
        
        $stmt = $pdo->prepare($statsSql);
        $stmt->execute($statsParams);
        $attendanceStats = $stmt->fetch();
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
                <h2><i class="fas fa-calendar-check me-2"></i>My Attendance</h2>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Filter by Course</label>
                                <select class="form-select" name="course_id" onchange="this.form.submit()">
                                    <option value="">All Courses</option>
                                    <?php foreach ($myCourses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" 
                                                <?php echo ($courseFilter == $course['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Filter by Month</label>
                                <input type="month" class="form-control" name="month" 
                                       value="<?php echo $monthFilter; ?>" onchange="this.form.submit()">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='attendance.php'">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Attendance Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success"><?php echo $attendanceStats['present']; ?></h3>
                            <p class="mb-0">Present</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-danger"><?php echo $attendanceStats['absent']; ?></h3>
                            <p class="mb-0">Absent</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-warning"><?php echo $attendanceStats['late']; ?></h3>
                            <p class="mb-0">Late</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info">
                                <?php 
                                $attendancePercentage = $attendanceStats['total'] > 0 
                                    ? round(($attendanceStats['present'] / $attendanceStats['total']) * 100, 1) 
                                    : 0; 
                                echo $attendancePercentage . '%';
                                ?>
                            </h3>
                            <p class="mb-0">Attendance Rate</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Records -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Attendance Records
                        <?php if ($courseFilter > 0): ?>
                            <?php 
                            $selectedCourse = array_filter($myCourses, function($c) use ($courseFilter) {
                                return $c['id'] == $courseFilter;
                            });
                            $courseName = !empty($selectedCourse) ? reset($selectedCourse)['course_name'] : '';
                            ?>
                            - <?php echo htmlspecialchars($courseName); ?>
                        <?php endif; ?>
                        <?php if ($monthFilter): ?>
                            - <?php echo date('F Y', strtotime($monthFilter . '-01')); ?>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($attendanceRecords)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                            <h5>No attendance records found</h5>
                            <p class="text-muted">
                                <?php if ($courseFilter > 0 || $monthFilter): ?>
                                    No records found for the selected filters.
                                <?php else: ?>
                                    No attendance records have been marked yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Course</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                        <th>Marked By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendanceRecords as $record): ?>
                                        <tr>
                                            <td><?php echo formatDate($record['date']); ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($record['course_code']); ?></span>
                                                <br>
                                                <small><?php echo htmlspecialchars($record['course_name']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $record['status'] === 'present' ? 'bg-success' : 
                                                         ($record['status'] === 'absent' ? 'bg-danger' : 'bg-warning'); 
                                                ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($record['remarks']): ?>
                                                    <?php echo htmlspecialchars($record['remarks']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                // Get teacher name who marked attendance
                                                try {
                                                    $stmt = $pdo->prepare("
                                                        SELECT t.first_name, t.last_name 
                                                        FROM teachers t 
                                                        WHERE t.id = ?
                                                    ");
                                                    $stmt->execute([$record['marked_by']]);
                                                    $teacher = $stmt->fetch();
                                                    if ($teacher) {
                                                        echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']);
                                                    } else {
                                                        echo 'Teacher';
                                                    }
                                                } catch (PDOException $e) {
                                                    echo 'Teacher';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Summary -->
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Summary:</strong> 
                            Showing <?php echo count($attendanceRecords); ?> record(s)
                            <?php if ($attendanceStats['total'] > 0): ?>
                                • Attendance rate: <?php echo $attendancePercentage; ?>%
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
