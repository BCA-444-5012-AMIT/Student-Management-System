<?php
$pageTitle = 'Teacher Report';
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$teacher = null;
$reportData = [];
$courseData = [];
$studentCountData = [];
$errorMessage = '';

// Get teacher ID from URL parameter
$teacherId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($teacherId === 0) {
    $errorMessage = 'No teacher specified';
} else {
    try {
        $pdo = getDBConnection();
        
        // Get teacher basic information
        $stmt = $pdo->prepare("
            SELECT t.*, u.username, u.email, u.created_at as user_created_at
            FROM teachers t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.id = ?
        ");
        $stmt->execute([$teacherId]);
        $teacher = $stmt->fetch();
        
        if (!$teacher) {
            $errorMessage = 'Teacher not found';
        } else {
            // Get course statistics
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT c.id) as total_courses,
                    COUNT(DISTINCT CASE WHEN c.status = 'active' THEN c.id END) as active_courses,
                    COUNT(DISTINCT e.student_id) as total_students,
                    SUM(c.credits) as total_credits,
                    MAX(c.created_at) as latest_course_date,
                    MIN(c.created_at) as first_course_date
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
                WHERE c.teacher_id = ?
            ");
            $stmt->execute([$teacherId]);
            $reportData = $stmt->fetch();
            
            // Get detailed course information
            $stmt = $pdo->prepare("
                SELECT c.course_code, c.course_name, c.credits, c.status, c.created_at,
                       COUNT(DISTINCT e.student_id) as enrolled_students,
                       COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.student_id END) as active_students,
                       MAX(a.date) as last_attendance_date
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id
                LEFT JOIN attendance a ON c.id = a.course_id
                WHERE c.teacher_id = ?
                GROUP BY c.id, c.course_code, c.course_name, c.credits, c.status, c.created_at
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$teacherId]);
            $courseData = $stmt->fetchAll();
            
            // Get student count per course for charts
            $stmt = $pdo->prepare("
                SELECT c.course_code, c.course_name, COUNT(DISTINCT e.student_id) as student_count
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
                WHERE c.teacher_id = ? AND c.status = 'active'
                GROUP BY c.id, c.course_code, c.course_name
                ORDER BY student_count DESC
                LIMIT 10
            ");
            $stmt->execute([$teacherId]);
            $studentCountData = $stmt->fetchAll();
        }
        
    } catch (PDOException $e) {
        $errorMessage = 'Database error: ' . $e->getMessage();
    }
}

// Handle report generation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'generate_pdf' && $teacher) {
        // Generate PDF report (simplified version - would need PDF library)
        generatePDFReport($teacher, $reportData, $courseData, $studentCountData);
    } elseif ($_POST['action'] === 'generate_csv' && $teacher) {
        // Generate CSV report
        generateCSVReport($teacher, $reportData, $courseData, $studentCountData);
    }
}

function generatePDFReport($teacher, $reportData, $courseData, $studentCountData) {
    // This would require a PDF library like TCPDF or FPDF
    // For now, we'll create a simple HTML version that can be printed
    header('Content-Type: text/html');
    header('Content-Disposition: inline; filename="teacher_report_' . $teacher['id'] . '.html"');
    
    include __DIR__ . '/../includes/header_print.php';
    include __DIR__ . '/teacher_report_content.php';
    include __DIR__ . '/../includes/footer_print.php';
    exit;
}

function generateCSVReport($teacher, $reportData, $courseData, $studentCountData) {
    $filename = 'teacher_report_' . $teacher['id'] . '_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Teacher Information
    fputcsv($output, ['TEACHER REPORT']);
    fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    fputcsv($output, ['PERSONAL INFORMATION']);
    fputcsv($output, ['Name', $teacher['first_name'] . ' ' . $teacher['last_name']]);
    fputcsv($output, ['Employee ID', $teacher['employee_id']]);
    fputcsv($output, ['Username', $teacher['username']]);
    fputcsv($output, ['Email', $teacher['email']]);
    fputcsv($output, ['Phone', $teacher['phone'] ?? 'N/A']);
    fputcsv($output, ['Specialization', $teacher['specialization'] ?? 'N/A']);
    fputcsv($output, ['Qualification', $teacher['qualification'] ?? 'N/A']);
    fputcsv($output, ['Experience', $teacher['experience'] . ' years']);
    fputcsv($output, ['Joining Date', $teacher['joining_date']]);
    fputcsv($output, []);
    
    // Teaching Summary
    fputcsv($output, ['TEACHING SUMMARY']);
    fputcsv($output, ['Total Courses', $reportData['total_courses'] ?? 0]);
    fputcsv($output, ['Active Courses', $reportData['active_courses'] ?? 0]);
    fputcsv($output, ['Total Students', $reportData['total_students'] ?? 0]);
    fputcsv($output, ['Total Credits', $reportData['total_credits'] ?? 0]);
    fputcsv($output, ['First Course', $reportData['first_course_date'] ?? 'N/A']);
    fputcsv($output, ['Latest Course', $reportData['latest_course_date'] ?? 'N/A']);
    fputcsv($output, []);
    
    // Course Details
    fputcsv($output, ['COURSE DETAILS']);
    fputcsv($output, ['Course Code', 'Course Name', 'Credits', 'Status', 'Enrolled Students', 'Active Students', 'Last Attendance']);
    foreach ($courseData as $course) {
        fputcsv($output, [
            $course['course_code'],
            $course['course_name'],
            $course['credits'],
            ucfirst($course['status']),
            $course['enrolled_students'],
            $course['active_students'],
            $course['last_attendance_date'] ?? 'N/A'
        ]);
    }
    fputcsv($output, []);
    
    // Student Distribution
    fputcsv($output, ['STUDENT DISTRIBUTION']);
    fputcsv($output, ['Course Code', 'Course Name', 'Student Count']);
    foreach ($studentCountData as $data) {
        fputcsv($output, [
            $data['course_code'],
            $data['course_name'],
            $data['student_count']
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
                <h2><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Report</h2>
                <div class="d-flex gap-2">
                    <?php if ($teacher): ?>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Report
                        </button>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="action" value="generate_csv">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-csv me-2"></i>Export CSV
                            </button>
                        </form>
                        <a href="teachers.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Teachers
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $errorMessage; ?>
                </div>
            <?php elseif ($teacher): ?>
                <!-- Teacher Report Content -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chalkboard-teacher me-2"></i>
                            Teacher Report: <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>Personal Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Full Name:</strong></td>
                                        <td><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></td>
                                        <td><strong>Employee ID:</strong></td>
                                        <td><?php echo htmlspecialchars($teacher['employee_id']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Username:</strong></td>
                                        <td>@<?php echo htmlspecialchars($teacher['username']); ?></td>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phone:</strong></td>
                                        <td><?php echo htmlspecialchars($teacher['phone'] ?? 'N/A'); ?></td>
                                        <td><strong>Joining Date:</strong></td>
                                        <td><?php echo formatDate($teacher['joining_date'] ?? null); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Specialization:</strong></td>
                                        <td><?php echo htmlspecialchars($teacher['specialization'] ?? 'N/A'); ?></td>
                                        <td><strong>Qualification:</strong></td>
                                        <td><?php echo htmlspecialchars($teacher['qualification'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Experience:</strong></td>
                                        <td><?php echo $teacher['experience'] ?? 'N/A'; ?> years</td>
                                        <td><strong>Address:</strong></td>
                                        <td><?php echo htmlspecialchars($teacher['address'] ?? 'N/A'); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="avatar-circle mx-auto mb-3" style="width: 100px; height: 100px; font-size: 24px;">
                                        <?php echo strtoupper(substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'], 0, 1)); ?>
                                    </div>
                                    <h5><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h5>
                                    <p class="text-muted"><?php echo htmlspecialchars($teacher['employee_id']); ?></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($teacher['specialization'] ?? 'General'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Teaching Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Teaching Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body">
                                                <h3><?php echo $reportData['total_courses'] ?? 0; ?></h3>
                                                <p class="mb-0">Total Courses</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body">
                                                <h3><?php echo $reportData['active_courses'] ?? 0; ?></h3>
                                                <p class="mb-0">Active Courses</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-info text-white">
                                            <div class="card-body">
                                                <h3><?php echo $reportData['total_students'] ?? 0; ?></h3>
                                                <p class="mb-0">Total Students</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-warning text-white">
                                            <div class="card-body">
                                                <h3><?php echo $reportData['total_credits'] ?? 0; ?></h3>
                                                <p class="mb-0">Total Credits</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h6>Teaching Experience</h6>
                                    <div class="progress-circle mx-auto" style="width: 150px; height: 150px;">
                                        <svg width="150" height="150">
                                            <circle cx="75" cy="75" r="65" stroke="#e9ecef" stroke-width="10" fill="none"></circle>
                                            <circle cx="75" cy="75" r="65" stroke="#28a745" stroke-width="10" fill="none"
                                                    stroke-dasharray="<?php echo min((($teacher['experience'] ?? 0) / 20) * 408, 408) ?> 408"
                                                    transform="rotate(-90 75 75)"></circle>
                                        </svg>
                                        <div class="progress-circle-text">
                                            <h2><?php echo $teacher['experience'] ?? '0'; ?></h2>
                                            <small>Years</small>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        First: <?php echo $reportData['first_course_date'] ?? 'N/A'; ?><br>
                                        Latest: <?php echo $reportData['latest_course_date'] ?? 'N/A'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Course Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-book me-2"></i>Course Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($courseData)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Course Code</th>
                                            <th>Course Name</th>
                                            <th>Credits</th>
                                            <th>Status</th>
                                            <th>Enrolled Students</th>
                                            <th>Active Students</th>
                                            <th>Last Attendance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courseData as $course): ?>
                                            <tr>
                                                <td><span class="badge bg-info"><?php echo htmlspecialchars($course['course_code']); ?></span></td>
                                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                <td><?php echo $course['credits']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $course['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($course['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $course['enrolled_students']; ?></td>
                                                <td><?php echo $course['active_students']; ?></td>
                                                <td><?php echo $course['last_attendance_date'] ?? 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No courses found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Student Distribution -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>Student Distribution (Top Courses)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($studentCountData)): ?>
                            <div class="row">
                                <?php foreach ($studentCountData as $index => $data): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($data['course_code']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($data['course_name']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <h5 class="mb-0"><?php echo $data['student_count']; ?></h5>
                                                <small class="text-muted">students</small>
                                            </div>
                                        </div>
                                        <div class="progress mt-2" style="height: 8px;">
                                            <?php 
                                            $maxStudents = max(array_column($studentCountData, 'student_count'));
                                            $percentage = ($data['student_count'] / $maxStudents) * 100;
                                            ?>
                                            <div class="progress-bar bg-info" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No student distribution data available.</p>
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
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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

.progress-circle-text small {
    font-size: 12px;
    color: #6c757d;
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
