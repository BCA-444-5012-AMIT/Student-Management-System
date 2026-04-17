<?php
$pageTitle = 'Student Report';
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$student = null;
$reportData = [];
$attendanceData = [];
$enrollmentData = [];
$errorMessage = '';

// Get student ID from URL parameter
$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($studentId === 0) {
    $errorMessage = 'No student specified';
} else {
    try {
        $pdo = getDBConnection();
        
        // Get student basic information
        $stmt = $pdo->prepare("
            SELECT s.*, u.username, u.email, u.created_at as user_created_at
            FROM students s
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        
        if (!$student) {
            $errorMessage = 'Student not found';
        } else {
            // Get attendance statistics
            $stmt = $pdo->prepare("
                SELECT 
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
                    MAX(a.date) as last_attendance_date,
                    MIN(a.date) as first_attendance_date
                FROM attendance a
                WHERE a.student_id = ?
            ");
            $stmt->execute([$studentId]);
            $reportData = $stmt->fetch();
            
            // Get detailed attendance records
            $stmt = $pdo->prepare("
                SELECT a.date, a.status, a.time_in, a.time_out, c.course_code, c.course_name
                FROM attendance a
                LEFT JOIN courses c ON a.course_id = c.id
                WHERE a.student_id = ?
                ORDER BY a.date DESC
                LIMIT 50
            ");
            $stmt->execute([$studentId]);
            $attendanceData = $stmt->fetchAll();
            
            // Get enrollment information
            $stmt = $pdo->prepare("
                SELECT e.enrollment_date, e.status, c.course_code, c.course_name, c.credits, t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM enrollments e
                LEFT JOIN courses c ON e.course_id = c.id
                LEFT JOIN teachers t ON c.teacher_id = t.id
                WHERE e.student_id = ?
                ORDER BY e.enrollment_date DESC
            ");
            $stmt->execute([$studentId]);
            $enrollmentData = $stmt->fetchAll();
        }
        
    } catch (PDOException $e) {
        $errorMessage = 'Database error: ' . $e->getMessage();
    }
}

// Handle report generation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'generate_pdf' && $student) {
        // Generate PDF report (simplified version - would need PDF library)
        generatePDFReport($student, $reportData, $attendanceData, $enrollmentData);
    } elseif ($_POST['action'] === 'generate_csv' && $student) {
        // Generate CSV report
        generateCSVReport($student, $reportData, $attendanceData, $enrollmentData);
    }
}

function generatePDFReport($student, $reportData, $attendanceData, $enrollmentData) {
    // This would require a PDF library like TCPDF or FPDF
    // For now, we'll create a simple HTML version that can be printed
    header('Content-Type: text/html');
    header('Content-Disposition: inline; filename="student_report_' . $student['roll_number'] . '.html"');
    
    include __DIR__ . '/../includes/header_print.php';
    include __DIR__ . '/student_report_content.php';
    include __DIR__ . '/../includes/footer_print.php';
    exit;
}

function generateCSVReport($student, $reportData, $attendanceData, $enrollmentData) {
    $filename = 'student_report_' . $student['roll_number'] . '_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Student Information
    fputcsv($output, ['STUDENT REPORT']);
    fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    fputcsv($output, ['PERSONAL INFORMATION']);
    fputcsv($output, ['Name', $student['first_name'] . ' ' . $student['last_name']]);
    fputcsv($output, ['Roll Number', $student['roll_number']]);
    fputcsv($output, ['Username', $student['username']]);
    fputcsv($output, ['Email', $student['email']]);
    fputcsv($output, ['Date of Birth', $student['date_of_birth']]);
    fputcsv($output, ['Gender', ucfirst($student['gender'])]);
    fputcsv($output, ['Phone', $student['phone'] ?? 'N/A']);
    fputcsv($output, ['Address', $student['address'] ?? 'N/A']);
    fputcsv($output, ['Enrollment Date', $student['enrollment_date']]);
    fputcsv($output, []);
    
    // Attendance Summary
    fputcsv($output, ['ATTENDANCE SUMMARY']);
    fputcsv($output, ['Total Classes', $reportData['total_classes'] ?? 0]);
    fputcsv($output, ['Present', $reportData['present'] ?? 0]);
    fputcsv($output, ['Absent', $reportData['absent'] ?? 0]);
    fputcsv($output, ['Late', $reportData['late'] ?? 0]);
    fputcsv($output, ['Attendance Percentage', ($reportData['attendance_percentage'] ?? 0) . '%']);
    fputcsv($output, ['First Attendance', $reportData['first_attendance_date'] ?? 'N/A']);
    fputcsv($output, ['Last Attendance', $reportData['last_attendance_date'] ?? 'N/A']);
    fputcsv($output, []);
    
    // Course Enrollments
    fputcsv($output, ['COURSE ENROLLMENTS']);
    fputcsv($output, ['Course Code', 'Course Name', 'Credits', 'Teacher', 'Enrollment Date', 'Status']);
    foreach ($enrollmentData as $enrollment) {
        fputcsv($output, [
            $enrollment['course_code'],
            $enrollment['course_name'],
            $enrollment['credits'],
            ($enrollment['teacher_first_name'] ?? '') . ' ' . ($enrollment['teacher_last_name'] ?? ''),
            $enrollment['enrollment_date'],
            ucfirst($enrollment['status'])
        ]);
    }
    fputcsv($output, []);
    
    // Recent Attendance
    fputcsv($output, ['RECENT ATTENDANCE (Last 50 Records)']);
    fputcsv($output, ['Date', 'Course', 'Status', 'Time In', 'Time Out']);
    foreach ($attendanceData as $attendance) {
        fputcsv($output, [
            $attendance['date'],
            $attendance['course_code'] ?? 'N/A',
            ucfirst($attendance['status']),
            $attendance['time_in'] ?? 'N/A',
            $attendance['time_out'] ?? 'N/A'
        ]);
    }
    
    fclose($output);
    exit;
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
                <h2><i class="fas fa-file-alt me-2"></i>Student Report</h2>
                <div class="d-flex gap-2">
                    <?php if ($student): ?>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Report
                        </button>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="action" value="generate_csv">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-csv me-2"></i>Export CSV
                            </button>
                        </form>
                        <a href="students.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Students
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $errorMessage; ?>
                </div>
            <?php elseif ($student): ?>
                <!-- Student Report Content -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user-graduate me-2"></i>
                            Student Report: <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>Personal Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Full Name:</strong></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><strong>Roll Number:</strong></td>
                                        <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Username:</strong></td>
                                        <td>@<?php echo htmlspecialchars($student['username']); ?></td>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Date of Birth:</strong></td>
                                        <td><?php echo formatDate($student['date_of_birth']); ?></td>
                                        <td><strong>Gender:</strong></td>
                                        <td><?php echo ucfirst($student['gender']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phone:</strong></td>
                                        <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                                        <td><strong>Enrollment Date:</strong></td>
                                        <td><?php echo formatDate($student['enrollment_date']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Address:</strong></td>
                                        <td colspan="3"><?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="avatar-circle mx-auto mb-3" style="width: 100px; height: 100px; font-size: 24px;">
                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                    </div>
                                    <h5><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                                    <p class="text-muted"><?php echo htmlspecialchars($student['roll_number']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Attendance Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body">
                                                <h3><?php echo $reportData['total_classes'] ?? 0; ?></h3>
                                                <p class="mb-0">Total Classes</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body">
                                                <h3><?php echo $reportData['present'] ?? 0; ?></h3>
                                                <p class="mb-0">Present</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-danger text-white">
                                            <div class="card-body">
                                                <h3><?php echo $reportData['absent'] ?? 0; ?></h3>
                                                <p class="mb-0">Absent</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-warning text-white">
                                            <div class="card-body">
                                                <h3><?php echo $reportData['late'] ?? 0; ?></h3>
                                                <p class="mb-0">Late</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h6>Overall Attendance</h6>
                                    <div class="progress-circle mx-auto" style="width: 150px; height: 150px;">
                                        <svg width="150" height="150">
                                            <circle cx="75" cy="75" r="65" stroke="#e9ecef" stroke-width="10" fill="none"></circle>
                                            <circle cx="75" cy="75" r="65" stroke="<?php echo ($reportData['attendance_percentage'] ?? 0) >= 75 ? '#28a745' : (($reportData['attendance_percentage'] ?? 0) >= 60 ? '#ffc107' : '#dc3545'); ?>" stroke-width="10" fill="none"
                                                    stroke-dasharray="<?php echo ($reportData['attendance_percentage'] ?? 0) * 4.08 ?> 408"
                                                    transform="rotate(-90 75 75)"></circle>
                                        </svg>
                                        <div class="progress-circle-text">
                                            <h2><?php echo ($reportData['attendance_percentage'] ?? 0); ?>%</h2>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        First: <?php echo $reportData['first_attendance_date'] ?? 'N/A'; ?><br>
                                        Last: <?php echo $reportData['last_attendance_date'] ?? 'N/A'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Course Enrollments -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-book me-2"></i>Course Enrollments
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($enrollmentData)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Course Code</th>
                                            <th>Course Name</th>
                                            <th>Credits</th>
                                            <th>Teacher</th>
                                            <th>Enrollment Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($enrollmentData as $enrollment): ?>
                                            <tr>
                                                <td><span class="badge bg-info"><?php echo htmlspecialchars($enrollment['course_code']); ?></span></td>
                                                <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                                <td><?php echo $enrollment['credits']; ?></td>
                                                <td><?php echo htmlspecialchars(($enrollment['teacher_first_name'] ?? '') . ' ' . ($enrollment['teacher_last_name'] ?? '')); ?></td>
                                                <td><?php echo formatDate($enrollment['enrollment_date']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $enrollment['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($enrollment['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No course enrollments found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Attendance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Recent Attendance Records
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($attendanceData)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Course</th>
                                            <th>Status</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendanceData as $attendance): ?>
                                            <tr>
                                                <td><?php echo formatDate($attendance['date']); ?></td>
                                                <td>
                                                    <?php if ($attendance['course_code']): ?>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($attendance['course_code']); ?></span>
                                                        <small class="text-muted ms-1"><?php echo htmlspecialchars($attendance['course_name']); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">General</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $attendance['status'] === 'present' ? 'success' : 
                                                            ($attendance['status'] === 'absent' ? 'danger' : 'warning'); 
                                                    ?>">
                                                        <?php echo ucfirst($attendance['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $attendance['time_in'] ?? 'N/A'; ?></td>
                                                <td><?php echo $attendance['time_out'] ?? 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No attendance records found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.progress-circle {
    position: relative;
    display: inline-block;
}

.progress-circle-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.progress-circle-text h2 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}

@media print {
    .sidebar, .btn, .no-print {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
    }
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
        border-bottom: 1px solid #000 !important;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
