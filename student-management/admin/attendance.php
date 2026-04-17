<?php
$pageTitle = 'Manage Attendance';
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$attendance = [];
$students = [];
$courses = [];
$teachers = [];
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Add new attendance record
            $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
            $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
            $date = isset($_POST['date']) ? $_POST['date'] : '';
            $status = isset($_POST['status']) ? $_POST['status'] : '';
            $markedBy = isset($_POST['marked_by']) ? (int)$_POST['marked_by'] : 0;
            $remarks = isset($_POST['remarks']) ? cleanInput($_POST['remarks']) : '';
            
            // Validation
            if ($studentId <= 0) $errors['student_id'] = 'Student is required';
            if ($courseId <= 0) $errors['course_id'] = 'Course is required';
            if (empty($date)) $errors['date'] = 'Date is required';
            if (empty($status)) $errors['status'] = 'Status is required';
            if ($markedBy <= 0) $errors['marked_by'] = 'Teacher is required';
            
            // Date validation (cannot be future date)
            if (!empty($date)) {
                $dateTimestamp = strtotime($date);
                $todayTimestamp = strtotime(date('Y-m-d'));
                
                if ($dateTimestamp > $todayTimestamp) {
                    $errors['date'] = 'Attendance date cannot be in the future';
                }
            }
            
            if (empty($errors)) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("
                        INSERT INTO attendance (student_id, course_id, date, status, marked_by, remarks) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$studentId, $courseId, $date, $status, $markedBy, $remarks]);
                    
                    setSuccessMessage('Attendance marked successfully!');
                    header('Location: attendance.php');
                    exit();
                    
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $errors['duplicate'] = 'Attendance for this student, course, and date already exists';
                    } else {
                        $errors['database'] = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }
        
        elseif ($_POST['action'] === 'delete') {
            // Delete attendance record
            $attendanceId = isset($_POST['attendance_id']) ? (int)$_POST['attendance_id'] : 0;
            
            if ($attendanceId > 0) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("DELETE FROM attendance WHERE id = ?");
                    $stmt->execute([$attendanceId]);
                    setSuccessMessage('Attendance record deleted successfully!');
                    header('Location: attendance.php');
                    exit();
                } catch (PDOException $e) {
                    setErrorMessage('Cannot delete attendance record.');
                }
            }
        }
        
        elseif ($_POST['action'] === 'edit') {
            // Edit attendance record
            $attendanceId = isset($_POST['attendance_id']) ? (int)$_POST['attendance_id'] : 0;
            $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
            $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
            $date = isset($_POST['date']) ? $_POST['date'] : '';
            $status = isset($_POST['status']) ? $_POST['status'] : '';
            $markedBy = isset($_POST['marked_by']) ? (int)$_POST['marked_by'] : 0;
            $remarks = isset($_POST['remarks']) ? cleanInput($_POST['remarks']) : '';
            
            // Validation
            if ($studentId <= 0) $errors['student_id'] = 'Student is required';
            if ($courseId <= 0) $errors['course_id'] = 'Course is required';
            if (empty($date)) $errors['date'] = 'Date is required';
            if (empty($status)) $errors['status'] = 'Status is required';
            if ($markedBy <= 0) $errors['marked_by'] = 'Teacher is required';
            
            if (empty($errors)) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("
                        UPDATE attendance SET student_id = ?, course_id = ?, date = ?, 
                        status = ?, marked_by = ?, remarks = ? WHERE id = ?
                    ");
                    $stmt->execute([$studentId, $courseId, $date, $status, $markedBy, $remarks, $attendanceId]);
                    
                    setSuccessMessage('Attendance record updated successfully!');
                    header('Location: attendance.php');
                    exit();
                    
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $errors['duplicate'] = 'Attendance for this student, course, and date already exists';
                    } else {
                        $errors['database'] = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Fetch all attendance records with details
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT a.*, s.first_name, s.last_name, s.roll_number, s.id as student_id,
               c.course_code, c.course_name,
               t.first_name as teacher_first_name, t.last_name as teacher_last_name
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        JOIN courses c ON a.course_id = c.id
        JOIN teachers t ON a.marked_by = t.id
        ORDER BY a.date ASC, c.course_code, s.id
    ");
    $attendance = $stmt->fetchAll();
} catch (PDOException $e) {
    setErrorMessage('Database error: ' . $e->getMessage());
}

// Fetch all students for dropdown
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT s.id, s.first_name, s.last_name, s.roll_number 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        ORDER BY s.id
    ");
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    setErrorMessage('Database error: ' . $e->getMessage());
}

// Fetch all courses for dropdown
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT c.id, c.course_code, c.course_name 
        FROM courses c 
        ORDER BY c.course_code
    ");
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    setErrorMessage('Database error: ' . $e->getMessage());
}

// Fetch all teachers for dropdown
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT t.id, t.first_name, t.last_name 
        FROM teachers t 
        JOIN users u ON t.user_id = u.id 
        ORDER BY t.last_name, t.first_name
    ");
    $teachers = $stmt->fetchAll();
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
                <h2><i class="fas fa-calendar-check me-2"></i>Manage Attendance</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
                    <i class="fas fa-plus me-2"></i>Mark Attendance
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="attendanceSearch" placeholder="Search attendance by student name, course, date, or status...">
                        <button class="btn btn-outline-secondary" type="button" id="clearAttendanceSearch">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                    <small class="text-muted">Type to search attendance records in real-time</small>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-end">
                        <span class="me-2">Total Records:</span>
                        <span class="badge bg-primary" id="attendanceCount">0</span>
                    </div>
                </div>
            </div>
            
            <?php if (isset($errors['duplicate'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $errors['duplicate']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors['database'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $errors['database']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Search Results Message -->
            <div id="attendanceSearchResults" class="alert alert-info" style="display: none;">
                <i class="fas fa-info-circle me-2"></i>
                <span id="attendanceSearchMessage"></span>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div id="noAttendanceMessage" style="display: none;">
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>No attendance records found</h5>
                            <p class="text-muted">Try adjusting your search criteria.</p>
                        </div>
                    </div>
                    
                    <div id="noAttendanceDataMessage" style="display: none;">
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                            <h5>No attendance records found</h5>
                            <p class="text-muted">Start by marking attendance for students.</p>
                        </div>
                    </div>
                    
                    <?php if (!empty($attendance)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="attendanceTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Student</th>
                                        <th>Roll Number</th>
                                        <th>Course</th>
                                        <th>Status</th>
                                        <th>Marked By</th>
                                        <th>Remarks</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance as $record): ?>
                                        <tr class="attendance-row" 
                                            data-id="<?php echo $record['id']; ?>"
                                            data-date="<?php echo formatDate($record['date']); ?>"
                                            data-student="<?php echo htmlspecialchars(strtolower($record['first_name'] . ' ' . $record['last_name'])); ?>"
                                            data-roll="<?php echo htmlspecialchars(strtolower($record['roll_number'])); ?>"
                                            data-course="<?php echo htmlspecialchars(strtolower($record['course_code'] . ' ' . $record['course_name'])); ?>"
                                            data-status="<?php echo htmlspecialchars(strtolower($record['status'])); ?>"
                                            data-marked-by="<?php echo htmlspecialchars(strtolower($record['marked_by'])); ?>"
                                            data-remarks="<?php echo htmlspecialchars(strtolower($record['remarks'])); ?>"
                                            data-search-text="<?php echo htmlspecialchars(strtolower(formatDate($record['date']) . ' ' . $record['first_name'] . ' ' . $record['last_name'] . ' ' . $record['roll_number'] . ' ' . $record['course_code'] . ' ' . $record['course_name'] . ' ' . $record['status'] . ' ' . $record['marked_by'] . ' ' . $record['remarks'])); ?>">
                                            <td><?php echo formatDate($record['date']); ?></td>
                                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['roll_number']); ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($record['course_code']); ?></span>
                                                <?php echo htmlspecialchars($record['course_name']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                switch($record['status']) {
                                                    case 'present':
                                                        $statusClass = 'bg-success';
                                                        break;
                                                    case 'absent':
                                                        $statusClass = 'bg-danger';
                                                        break;
                                                    case 'late':
                                                        $statusClass = 'bg-warning';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['teacher_first_name'] . ' ' . $record['teacher_last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['remarks'] ?: '-'); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewAttendance(<?php echo $record['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="editAttendance(<?php echo $record['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this attendance record?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="attendance_id" value="<?php echo $record['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Attendance Modal -->
<div class="modal fade" id="addAttendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student *</label>
                        <select class="form-select" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['roll_number'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['student_id'])): ?>
                            <div class="text-danger small"><?php echo $errors['student_id']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Course *</label>
                        <select class="form-select" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['course_id'])): ?>
                            <div class="text-danger small"><?php echo $errors['course_id']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date *</label>
                        <input type="date" class="form-control" name="date" 
                               value="<?php echo date('Y-m-d'); ?>" 
                               max="<?php echo date('Y-m-d'); ?>" required>
                        <small class="text-muted">Cannot be a future date</small>
                        <?php if (isset($errors['date'])): ?>
                            <div class="text-danger small"><?php echo $errors['date']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="status" required>
                            <option value="">Select Status</option>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <div class="text-danger small"><?php echo $errors['status']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Marked By *</label>
                        <select class="form-select" name="marked_by" required>
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['marked_by'])): ?>
                            <div class="text-danger small"><?php echo $errors['marked_by']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="2" placeholder="Optional remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Mark Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Attendance Modal -->
<div class="modal fade" id="viewAttendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attendance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="attendanceDetails">
                    <!-- Attendance details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Attendance Modal -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="editAttendanceForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="attendance_id" id="editAttendanceId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student *</label>
                        <select class="form-select" name="student_id" id="editStudentId" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['roll_number'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['student_id'])): ?>
                            <div class="text-danger small"><?php echo $errors['student_id']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Course *</label>
                        <select class="form-select" name="course_id" id="editCourseId" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['course_id'])): ?>
                            <div class="text-danger small"><?php echo $errors['course_id']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date *</label>
                        <input type="date" class="form-control" name="date" id="editDate" 
                               max="<?php echo date('Y-m-d'); ?>" required>
                        <small class="text-muted">Cannot be a future date</small>
                        <?php if (isset($errors['date'])): ?>
                            <div class="text-danger small"><?php echo $errors['date']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="status" id="editStatus" required>
                            <option value="">Select Status</option>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <div class="text-danger small"><?php echo $errors['status']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Marked By *</label>
                        <select class="form-select" name="marked_by" id="editMarkedBy" required>
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['marked_by'])): ?>
                            <div class="text-danger small"><?php echo $errors['marked_by']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" id="editRemarks" rows="2" placeholder="Optional remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store attendance data for view and edit functions
const attendanceData = <?php echo json_encode($attendance); ?>;
const studentsData = <?php echo json_encode($students); ?>;
const coursesData = <?php echo json_encode($courses); ?>;
const teachersData = <?php echo json_encode($teachers); ?>;

// JavaScript date format function
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric' 
    });
}

function viewAttendance(id) {
    const record = attendanceData.find(a => a.id == id);
    if (record) {
        const statusClass = {
            'present': 'bg-success',
            'absent': 'bg-danger',
            'late': 'bg-warning'
        }[record.status] || 'bg-secondary';
        
        const detailsHtml = `
            <div class="row">
                <div class="col-12">
                    <h6 class="text-primary mb-3">Attendance Information</h6>
                    <p><strong>Date:</strong> ${formatDate(record.date)}</p>
                    <p><strong>Student:</strong> ${record.first_name} ${record.last_name}</p>
                    <p><strong>Roll Number:</strong> ${record.roll_number}</p>
                    <p><strong>Course:</strong> <span class="badge bg-primary">${record.course_code}</span> ${record.course_name}</p>
                    <p><strong>Status:</strong> <span class="badge ${statusClass}">${record.status.charAt(0).toUpperCase() + record.status.slice(1)}</span></p>
                    <p><strong>Marked By:</strong> ${record.teacher_first_name} ${record.teacher_last_name}</p>
                    <p><strong>Remarks:</strong> ${record.remarks || 'No remarks'}</p>
                </div>
            </div>
        `;
        document.getElementById('attendanceDetails').innerHTML = detailsHtml;
        new bootstrap.Modal(document.getElementById('viewAttendanceModal')).show();
    }
}

function editAttendance(id) {
    const record = attendanceData.find(a => a.id == id);
    if (record) {
        // Populate form fields
        document.getElementById('editAttendanceId').value = record.id;
        document.getElementById('editStudentId').value = record.student_id;
        document.getElementById('editCourseId').value = record.course_id;
        document.getElementById('editDate').value = record.date;
        document.getElementById('editStatus').value = record.status;
        document.getElementById('editMarkedBy').value = record.marked_by;
        document.getElementById('editRemarks').value = record.remarks || '';
        
        new bootstrap.Modal(document.getElementById('editAttendanceModal')).show();
    }
}

function confirmDelete(message) {
    return confirm(message);
}

// Attendance Search Functionality
document.addEventListener('DOMContentLoaded', function() {
    const attendanceSearchInput = document.getElementById('attendanceSearch');
    const clearAttendanceButton = document.getElementById('clearAttendanceSearch');
    const attendanceRows = document.querySelectorAll('.attendance-row');
    const attendanceCount = document.getElementById('attendanceCount');
    const attendanceSearchResults = document.getElementById('attendanceSearchResults');
    const attendanceSearchMessage = document.getElementById('attendanceSearchMessage');
    const noAttendanceMessage = document.getElementById('noAttendanceMessage');
    const noAttendanceDataMessage = document.getElementById('noAttendanceDataMessage');
    const attendanceTable = document.getElementById('attendanceTable');
    
    // Initialize attendance count
    if (attendanceCount) {
        attendanceCount.textContent = attendanceRows.length;
    }
    
    // Show/hide appropriate messages based on data availability
    if (attendanceRows.length === 0) {
        if (noAttendanceDataMessage) noAttendanceDataMessage.style.display = 'block';
        if (attendanceTable) attendanceTable.style.display = 'none';
    }
    
    // Search functionality
    if (attendanceSearchInput) {
        attendanceSearchInput.addEventListener('input', function() {
            performAttendanceSearch();
        });
        
        attendanceSearchInput.addEventListener('keyup', function(e) {
            // Clear search on Escape key
            if (e.key === 'Escape') {
                clearAttendanceSearch();
            }
        });
    }
    
    // Clear search functionality
    if (clearAttendanceButton) {
        clearAttendanceButton.addEventListener('click', clearAttendanceSearch);
    }
    
    function performAttendanceSearch() {
        const searchTerm = attendanceSearchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        let totalAttendance = attendanceRows.length;
        
        attendanceRows.forEach(function(row) {
            const searchText = row.getAttribute('data-search-text');
            const date = row.getAttribute('data-date');
            const student = row.getAttribute('data-student');
            const roll = row.getAttribute('data-roll');
            const course = row.getAttribute('data-course');
            const status = row.getAttribute('data-status');
            const markedBy = row.getAttribute('data-marked-by');
            const remarks = row.getAttribute('data-remarks');
            
            // Check if search term matches any field
            const matches = searchTerm === '' || 
                           searchText.includes(searchTerm) ||
                           date.includes(searchTerm) ||
                           student.includes(searchTerm) ||
                           roll.includes(searchTerm) ||
                           course.includes(searchTerm) ||
                           status.includes(searchTerm) ||
                           markedBy.includes(searchTerm) ||
                           remarks.includes(searchTerm);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update UI based on search results
        updateAttendanceSearchUI(searchTerm, visibleCount, totalAttendance);
    }
    
    function updateAttendanceSearchUI(searchTerm, visibleCount, totalAttendance) {
        // Update attendance count
        if (attendanceCount) {
            attendanceCount.textContent = visibleCount;
        }
        
        // Show/hide search results message
        if (attendanceSearchResults && attendanceSearchMessage) {
            if (searchTerm !== '') {
                attendanceSearchResults.style.display = 'block';
                if (visibleCount > 0) {
                    attendanceSearchResults.className = 'alert alert-success';
                    attendanceSearchMessage.innerHTML = `<i class="fas fa-check-circle me-2"></i>Found ${visibleCount} record${visibleCount !== 1 ? 's' : ''} matching "${searchTerm}"`;
                } else {
                    attendanceSearchResults.className = 'alert alert-warning';
                    attendanceSearchMessage.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>No attendance records found matching "${searchTerm}"`;
                }
            } else {
                attendanceSearchResults.style.display = 'none';
            }
        }
        
        // Show/hide appropriate messages
        if (visibleCount === 0) {
            if (searchTerm !== '') {
                // Search returned no results
                if (noAttendanceMessage) noAttendanceMessage.style.display = 'block';
                if (noAttendanceDataMessage) noAttendanceDataMessage.style.display = 'none';
                if (attendanceTable) attendanceTable.style.display = 'none';
            } else {
                // No attendance at all
                if (noAttendanceDataMessage) noAttendanceDataMessage.style.display = 'block';
                if (noAttendanceMessage) noAttendanceMessage.style.display = 'none';
                if (attendanceTable) attendanceTable.style.display = 'none';
            }
        } else {
            // Attendance records found
            if (noAttendanceMessage) noAttendanceMessage.style.display = 'none';
            if (noAttendanceDataMessage) noAttendanceDataMessage.style.display = 'none';
            if (attendanceTable) attendanceTable.style.display = 'table';
        }
    }
    
    function clearAttendanceSearch() {
        if (attendanceSearchInput) {
            attendanceSearchInput.value = '';
            performAttendanceSearch();
            attendanceSearchInput.focus();
        }
    }
    
    // Check if we need to filter by student (coming from student search)
    const filterStudentId = sessionStorage.getItem('filterStudentId');
    if (filterStudentId) {
        // Clear the stored ID
        sessionStorage.removeItem('filterStudentId');
        
        // Wait a bit for the page to fully load, then set the student filter
        setTimeout(() => {
            const studentSelect = document.getElementById('student_id');
            if (studentSelect) {
                studentSelect.value = filterStudentId;
                // Trigger change event if there's a listener
                const event = new Event('change', { bubbles: true });
                studentSelect.dispatchEvent(event);
            }
        }, 500);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
