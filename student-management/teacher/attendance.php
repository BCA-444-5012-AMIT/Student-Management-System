<?php
$pageTitle = 'Mark Attendance';
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');

$teacherId = null;
$myCourses = [];
$selectedCourse = null;
$students = [];
$attendanceData = [];
$errors = [];
$successMessage = '';

// Get teacher information
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([getUserId()]);
    $teacher = $stmt->fetch();
    
    if ($teacher) {
        $teacherId = $teacher['id'];
        
        // Get teacher's courses
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE teacher_id = ? ORDER BY course_name");
        $stmt->execute([$teacherId]);
        $myCourses = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    setErrorMessage('Database error: ' . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'select_course') {
            $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
            $attendanceDate = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d');
            
            // Validate attendance date is not in the future
            if ($attendanceDate > date('Y-m-d')) {
                setErrorMessage('Attendance date cannot be in the future.');
                $attendanceDate = date('Y-m-d');
            }
            
            if ($courseId > 0) {
                try {
                    $pdo = getDBConnection();
                    
                    // Get course details
                    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
                    $stmt->execute([$courseId, $teacherId]);
                    $selectedCourse = $stmt->fetch();
                    
                    if ($selectedCourse) {
                        // Get enrolled students with attendance statistics
                        $stmt = $pdo->prepare("
                            SELECT s.*, u.username,
                                   (SELECT COUNT(*) FROM attendance a 
                                    WHERE a.student_id = s.id AND a.course_id = ?) as total_classes,
                                   (SELECT COUNT(*) FROM attendance a 
                                    WHERE a.student_id = s.id AND a.course_id = ? AND a.status = 'present') as present_count,
                                   (SELECT COUNT(*) FROM attendance a 
                                    WHERE a.student_id = s.id AND a.course_id = ? AND a.status = 'late') as late_count
                            FROM students s 
                            JOIN users u ON s.user_id = u.id 
                            JOIN enrollments e ON s.id = e.student_id 
                            WHERE e.course_id = ? AND e.status = 'active'
                            ORDER BY s.first_name, s.last_name
                        ");
                        $stmt->execute([$courseId, $courseId, $courseId, $courseId]);
                        $students = $stmt->fetchAll();
                        
                        // Calculate attendance percentage for each student
                        foreach ($students as &$student) {
                            $totalClasses = $student['total_classes'];
                            $presentCount = $student['present_count'];
                            $lateCount = $student['late_count'];
                            
                            if ($totalClasses > 0) {
                                // Consider both present and late as attended (late counts as 0.5 attendance)
                                $attendedCount = $presentCount + ($lateCount * 0.5);
                                $student['attendance_percentage'] = round(($attendedCount / $totalClasses) * 100, 1);
                                $student['attendance_status'] = $student['attendance_percentage'] >= 75 ? 'good' : 
                                                              ($student['attendance_percentage'] >= 60 ? 'average' : 'poor');
                            } else {
                                $student['attendance_percentage'] = 0;
                                $student['attendance_status'] = 'no-data';
                            }
                        }
                        
                        // Get existing attendance for this date
                        $stmt = $pdo->prepare("
                            SELECT student_id, status, remarks 
                            FROM attendance 
                            WHERE course_id = ? AND date = ?
                        ");
                        $stmt->execute([$courseId, $attendanceDate]);
                        $attendanceRecords = $stmt->fetchAll();
                        
                        // Create attendance data array
                        $attendanceData = [];
                        foreach ($attendanceRecords as $record) {
                            $attendanceData[$record['student_id']] = [
                                'status' => $record['status'],
                                'remarks' => $record['remarks']
                            ];
                        }
                    }
                } catch (PDOException $e) {
                    setErrorMessage('Database error: ' . $e->getMessage());
                }
            }
        }
        
        elseif ($_POST['action'] === 'save_attendance') {
            $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
            $attendanceDate = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : '';
            $attendance = isset($_POST['attendance']) ? $_POST['attendance'] : [];
            $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : [];
            
            // Validate attendance date is not in the future
            if ($attendanceDate > date('Y-m-d')) {
                setErrorMessage('Attendance date cannot be in the future.');
            } elseif ($courseId > 0 && !empty($attendanceDate)) {
                try {
                    $pdo = getDBConnection();
                    $pdo->beginTransaction();
                    
                    foreach ($attendance as $studentId => $status) {
                        $remark = isset($remarks[$studentId]) ? cleanInput($remarks[$studentId]) : '';
                        
                        // Check if attendance already exists
                        $stmt = $pdo->prepare("
                            SELECT id FROM attendance 
                            WHERE student_id = ? AND course_id = ? AND date = ?
                        ");
                        $stmt->execute([$studentId, $courseId, $attendanceDate]);
                        $existing = $stmt->fetch();
                        
                        if ($existing) {
                            // Update existing record
                            $stmt = $pdo->prepare("
                                UPDATE attendance 
                                SET status = ?, remarks = ?, marked_by = ? 
                                WHERE student_id = ? AND course_id = ? AND date = ?
                            ");
                            $stmt->execute([$status, $remark, $teacherId, $studentId, $courseId, $attendanceDate]);
                        } else {
                            // Insert new record
                            $stmt = $pdo->prepare("
                                INSERT INTO attendance (student_id, course_id, date, status, remarks, marked_by) 
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$studentId, $courseId, $attendanceDate, $status, $remark, $teacherId]);
                        }
                    }
                    
                    $pdo->commit();
                    setSuccessMessage('Attendance saved successfully!');
                    
                    // Reload the data
                    $_POST['action'] = 'select_course';
                    $_POST['course_id'] = $courseId;
                    $_POST['attendance_date'] = $attendanceDate;
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    setErrorMessage('Database error: ' . $e->getMessage());
                }
            }
        }
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
                <h2><i class="fas fa-calendar-check me-2"></i>Mark Attendance</h2>
            </div>
            
            <?php if (empty($myCourses)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No courses assigned to you yet. Please contact the administrator.
                </div>
            <?php else: ?>
                <!-- Course Selection Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="select_course">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Select Course *</label>
                                    <select class="form-select" name="course_id" required onchange="this.form.submit()">
                                        <option value="">Select a course</option>
                                        <?php foreach ($myCourses as $course): ?>
                                            <option value="<?php echo $course['id']; ?>" 
                                                    <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Attendance Date</label>
                                    <input type="date" class="form-control" name="attendance_date" 
                                           value="<?php echo isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d'); ?>"
                                           max="<?php echo date('Y-m-d'); ?>"
                                           onchange="this.form.submit()">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-2"></i>Load Students
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Attendance Form -->
                <?php if ($selectedCourse && !empty($students)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>
                                <?php echo htmlspecialchars($selectedCourse['course_name']); ?> - 
                                <?php echo formatDate(isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d')); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Attendance Summary -->
                            <?php
                            $totalStudents = count($students);
                            $goodAttendance = 0;
                            $averageAttendance = 0;
                            $poorAttendance = 0;
                            $noDataStudents = 0;
                            
                            foreach ($students as $student) {
                                switch ($student['attendance_status']) {
                                    case 'good':
                                        $goodAttendance++;
                                        break;
                                    case 'average':
                                        $averageAttendance++;
                                        break;
                                    case 'poor':
                                        $poorAttendance++;
                                        break;
                                    case 'no-data':
                                        $noDataStudents++;
                                        break;
                                }
                            }
                            ?>
                            
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="fas fa-chart-pie me-2"></i>Course Attendance Summary
                                            </h6>
                                            <div class="row text-center">
                                                <div class="col-md-2">
                                                    <div class="stat-item">
                                                        <h4 class="text-primary"><?php echo $totalStudents; ?></h4>
                                                        <small class="text-muted">Total Students</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="stat-item">
                                                        <h4 class="text-success"><?php echo $goodAttendance; ?></h4>
                                                        <small class="text-muted">Good (≥75%)</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="stat-item">
                                                        <h4 class="text-warning"><?php echo $averageAttendance; ?></h4>
                                                        <small class="text-muted">Average (60-74%)</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="stat-item">
                                                        <h4 class="text-danger"><?php echo $poorAttendance; ?></h4>
                                                        <small class="text-muted">Poor (<60%)</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="stat-item">
                                                        <h4 class="text-secondary"><?php echo $noDataStudents; ?></h4>
                                                        <small class="text-muted">No Data</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="stat-item">
                                                        <?php 
                                                        $classAttendanceRate = $totalStudents > 0 ? round(($goodAttendance / $totalStudents) * 100, 1) : 0;
                                                        ?>
                                                        <h4 class="text-info"><?php echo $classAttendanceRate; ?>%</h4>
                                                        <small class="text-muted">Class Rate</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="save_attendance">
                                <input type="hidden" name="course_id" value="<?php echo $selectedCourse['id']; ?>">
                                <input type="hidden" name="attendance_date" value="<?php echo isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d'); ?>">
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Roll Number</th>
                                                <th>Student Name</th>
                                                <th>Attendance %</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $percentage = $student['attendance_percentage'];
                                                        $status = $student['attendance_status'];
                                                        $totalClasses = $student['total_classes'];
                                                        $presentCount = $student['present_count'];
                                                        $lateCount = $student['late_count'];
                                                        $absentCount = $totalClasses - $presentCount - $lateCount;
                                                        
                                                        if ($totalClasses > 0) {
                                                            $badgeClass = $status === 'good' ? 'bg-success' : 
                                                                         ($status === 'average' ? 'bg-warning' : 'bg-danger');
                                                            $statusText = $status === 'good' ? 'Good' : 
                                                                         ($status === 'average' ? 'Average' : 'Poor');
                                                            
                                                            // Create tooltip text
                                                            $tooltipText = "Present: $presentCount, Late: $lateCount, Absent: $absentCount, Total: $totalClasses";
                                                            
                                                            echo '<span class="badge attendance-badge ' . $badgeClass . ' percentage-tooltip" ';
                                                            echo 'data-tooltip="' . htmlspecialchars($tooltipText) . '" ';
                                                            echo 'title="' . htmlspecialchars($statusText . ' Attendance: ' . $percentage . '%') . '">';
                                                            echo $percentage . '%';
                                                            echo '</span>';
                                                            echo '<br>';
                                                            echo '<small class="attendance-details text-muted" style="cursor: pointer;">';
                                                            echo '<i class="fas fa-chart-line me-1"></i>';
                                                            echo $presentCount . 'P/' . $lateCount . 'L/' . $absentCount . 'A';
                                                            echo '</small>';
                                                        } else {
                                                            echo '<span class="badge attendance-badge bg-secondary" title="No attendance data available">';
                                                            echo 'No Data';
                                                            echo '</span>';
                                                            echo '<br>';
                                                            echo '<small class="attendance-details text-muted">';
                                                            echo '<i class="fas fa-info-circle me-1"></i>';
                                                            echo '0 classes held';
                                                            echo '</small>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" 
                                                                   value="present" id="present_<?php echo $student['id']; ?>"
                                                                   <?php echo (isset($attendanceData[$student['id']]) && $attendanceData[$student['id']]['status'] === 'present') ? 'checked' : ''; ?>>
                                                            <label class="btn btn-outline-success" for="present_<?php echo $student['id']; ?>">
                                                                <i class="fas fa-check"></i> Present
                                                            </label>
                                                            
                                                            <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" 
                                                                   value="absent" id="absent_<?php echo $student['id']; ?>"
                                                                   <?php echo (isset($attendanceData[$student['id']]) && $attendanceData[$student['id']]['status'] === 'absent') ? 'checked' : ''; ?>>
                                                            <label class="btn btn-outline-danger" for="absent_<?php echo $student['id']; ?>">
                                                                <i class="fas fa-times"></i> Absent
                                                            </label>
                                                            
                                                            <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" 
                                                                   value="late" id="late_<?php echo $student['id']; ?>"
                                                                   <?php echo (isset($attendanceData[$student['id']]) && $attendanceData[$student['id']]['status'] === 'late') ? 'checked' : ''; ?>>
                                                            <label class="btn btn-outline-warning" for="late_<?php echo $student['id']; ?>">
                                                                <i class="fas fa-clock"></i> Late
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" 
                                                               name="remarks[<?php echo $student['id']; ?>]" 
                                                               placeholder="Optional remarks"
                                                               value="<?php echo isset($attendanceData[$student['id']]) ? htmlspecialchars($attendanceData[$student['id']]['remarks']) : ''; ?>">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-3">
                                    <div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Total students: <?php echo count($students); ?>
                                        </small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Attendance
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                
                <?php elseif ($selectedCourse && empty($students)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No students enrolled in this course yet.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.stat-item {
    padding: 10px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.stat-item:hover {
    background-color: rgba(0,0,0,0.05);
    transform: translateY(-2px);
}

.stat-item h4 {
    margin: 0;
    font-weight: bold;
    font-size: 1.5rem;
}

.stat-item small {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.attendance-badge {
    font-size: 0.9rem;
    font-weight: bold;
    padding: 6px 12px;
    border-radius: 20px;
}

.attendance-details {
    font-size: 0.8rem;
    color: #6c757d;
}

.percentage-tooltip {
    cursor: help;
    position: relative;
}

.percentage-tooltip:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background-color: #333;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    white-space: nowrap;
    z-index: 1000;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-top: none;
}

.attendance-summary-card {
    border-left: 4px solid #007bff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .stat-item h4 {
        font-size: 1.2rem;
    }
    
    .stat-item small {
        font-size: 0.7rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add tooltips to attendance percentages
    const percentageBadges = document.querySelectorAll('.attendance-badge');
    percentageBadges.forEach(badge => {
        const tooltip = badge.getAttribute('data-tooltip');
        if (tooltip) {
            badge.classList.add('percentage-tooltip');
            badge.setAttribute('data-tooltip', tooltip);
        }
    });
    
    // Add click handler to show detailed attendance breakdown
    const attendanceDetails = document.querySelectorAll('.attendance-details');
    attendanceDetails.forEach(detail => {
        detail.addEventListener('click', function() {
            const studentId = this.closest('tr').querySelector('input[type="radio"]').name.match(/\d+/)[0];
            showAttendanceDetails(studentId);
        });
    });
    
    // Function to show detailed attendance breakdown (placeholder)
    function showAttendanceDetails(studentId) {
        // This could be expanded to show a modal with detailed attendance history
        console.log('Show attendance details for student:', studentId);
    }
    
    // Add real-time calculation preview when attendance is marked
    const attendanceRadios = document.querySelectorAll('input[name^="attendance"]');
    attendanceRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            updateAttendancePreview(this);
        });
    });
    
    function updateAttendancePreview(radio) {
        const row = radio.closest('tr');
        const studentId = radio.name.match(/\d+/)[0];
        const newStatus = radio.value;
        
        // Get current stats
        const percentageCell = row.querySelector('td:nth-child(3)');
        const currentText = percentageCell.textContent;
        
        // Show a temporary indicator that attendance will be updated
        const indicator = document.createElement('span');
        indicator.className = 'badge bg-info ms-2';
        indicator.textContent = 'Will Update';
        percentageCell.appendChild(indicator);
        
        // Remove indicator after 2 seconds
        setTimeout(() => {
            if (indicator.parentNode) {
                indicator.parentNode.removeChild(indicator);
            }
        }, 2000);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
