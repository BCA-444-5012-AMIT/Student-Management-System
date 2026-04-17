<?php
$pageTitle = 'Manage Enrollments';
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$enrollments = [];
$students = [];
$courses = [];
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Add new enrollment
            $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
            $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
            $enrollmentDate = isset($_POST['enrollment_date']) ? $_POST['enrollment_date'] : date('Y-m-d');
            $status = isset($_POST['status']) ? $_POST['status'] : 'active';
            
            // Validation
            if ($studentId <= 0) $errors['student_id'] = 'Student is required';
            if ($courseId <= 0) $errors['course_id'] = 'Course is required';
            if (empty($enrollmentDate)) $errors['enrollment_date'] = 'Enrollment date is required';
            
            // Enrollment date validation (cannot be future date)
            if (!empty($enrollmentDate)) {
                $enrollmentTimestamp = strtotime($enrollmentDate);
                $todayTimestamp = strtotime(date('Y-m-d'));
                
                if ($enrollmentTimestamp > $todayTimestamp) {
                    $errors['enrollment_date'] = 'Enrollment date cannot be in the future';
                }
            }
            
            if (empty($errors)) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("
                        INSERT INTO enrollments (student_id, course_id, enrollment_date, status) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$studentId, $courseId, $enrollmentDate, $status]);
                    
                    setSuccessMessage('Enrollment added successfully!');
                    header('Location: enrollments.php');
                    exit();
                    
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $errors['duplicate'] = 'Student is already enrolled in this course';
                    } else {
                        $errors['database'] = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }
        
        elseif ($_POST['action'] === 'delete') {
            // Delete enrollment
            $enrollmentId = isset($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : 0;
            
            if ($enrollmentId > 0) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("DELETE FROM enrollments WHERE id = ?");
                    $stmt->execute([$enrollmentId]);
                    setSuccessMessage('Enrollment deleted successfully!');
                    header('Location: enrollments.php');
                    exit();
                } catch (PDOException $e) {
                    setErrorMessage('Cannot delete enrollment. It may have attendance records.');
                }
            }
        }
        
        elseif ($_POST['action'] === 'edit') {
            // Edit enrollment
            $enrollmentId = isset($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : 0;
            $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
            $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
            $enrollmentDate = isset($_POST['enrollment_date']) ? $_POST['enrollment_date'] : '';
            $status = isset($_POST['status']) ? $_POST['status'] : 'active';
            
            // Validation
            if ($studentId <= 0) $errors['student_id'] = 'Student is required';
            if ($courseId <= 0) $errors['course_id'] = 'Course is required';
            if (empty($enrollmentDate)) $errors['enrollment_date'] = 'Enrollment date is required';
            
            // Enrollment date validation (cannot be future date)
            if (!empty($enrollmentDate)) {
                $enrollmentTimestamp = strtotime($enrollmentDate);
                $todayTimestamp = strtotime(date('Y-m-d'));
                
                if ($enrollmentTimestamp > $todayTimestamp) {
                    $errors['enrollment_date'] = 'Enrollment date cannot be in the future';
                }
            }
            
            if (empty($errors)) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("
                        UPDATE enrollments SET student_id = ?, course_id = ?, enrollment_date = ?, 
                        status = ? WHERE id = ?
                    ");
                    $stmt->execute([$studentId, $courseId, $enrollmentDate, $status, $enrollmentId]);
                    
                    setSuccessMessage('Enrollment updated successfully!');
                    header('Location: enrollments.php');
                    exit();
                    
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $errors['duplicate'] = 'Student is already enrolled in this course';
                    } else {
                        $errors['database'] = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Fetch all enrollments with student and course details
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT e.*, s.first_name, s.last_name, s.roll_number,
               c.course_code, c.course_name, c.credits
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        JOIN courses c ON e.course_id = c.id
        ORDER BY e.id ASC
    ");
    $enrollments = $stmt->fetchAll();
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
        ORDER BY s.last_name, s.first_name
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
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 d-none d-md-block">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-graduation-cap me-2"></i>Manage Enrollments</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEnrollmentModal">
                    <i class="fas fa-plus me-2"></i>Add Enrollment
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="enrollmentSearch" placeholder="Search enrollments by student, course, or status...">
                        <button class="btn btn-outline-secondary" type="button" id="clearEnrollmentSearch">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                    <small class="text-muted">Type to search enrollments in real-time</small>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-end">
                        <span class="me-2">Total Enrollments:</span>
                        <span class="badge bg-primary" id="enrollmentCount">0</span>
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
            <div id="enrollmentSearchResults" class="alert alert-info" style="display: none;">
                <i class="fas fa-info-circle me-2"></i>
                <span id="enrollmentSearchMessage"></span>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div id="noEnrollmentsMessage" style="display: none;">
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>No enrollments found</h5>
                            <p class="text-muted">Try adjusting your search criteria.</p>
                        </div>
                    </div>
                    
                    <div id="noEnrollmentDataMessage" style="display: none;">
                        <div class="text-center py-4">
                            <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                            <h5>No enrollments found</h5>
                            <p class="text-muted">Start by adding student enrollments.</p>
                        </div>
                    </div>
                    
                    <?php if (!empty($enrollments)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="enrollmentsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Student</th>
                                        <th>Roll Number</th>
                                        <th>Course</th>
                                        <th>Enrollment Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrollments as $enrollment): ?>
                                        <tr class="enrollment-row" 
                                            data-id="<?php echo $enrollment['id']; ?>"
                                            data-student="<?php echo htmlspecialchars(strtolower($enrollment['first_name'] . ' ' . $enrollment['last_name'])); ?>"
                                            data-roll="<?php echo htmlspecialchars(strtolower($enrollment['roll_number'])); ?>"
                                            data-course="<?php echo htmlspecialchars(strtolower($enrollment['course_code'] . ' ' . $enrollment['course_name'])); ?>"
                                            data-date="<?php echo formatDate($enrollment['enrollment_date']); ?>"
                                            data-status="<?php echo htmlspecialchars(strtolower($enrollment['status'])); ?>"
                                            data-search-text="<?php echo htmlspecialchars(strtolower($enrollment['first_name'] . ' ' . $enrollment['last_name'] . ' ' . $enrollment['roll_number'] . ' ' . $enrollment['course_code'] . ' ' . $enrollment['course_name'] . ' ' . formatDate($enrollment['enrollment_date']) . ' ' . $enrollment['status'])); ?>">
                                            <td><?php echo $enrollment['id']; ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['roll_number']); ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($enrollment['course_code']); ?></span>
                                                <?php echo htmlspecialchars($enrollment['course_name']); ?>
                                            </td>
                                            <td><?php echo formatDate($enrollment['enrollment_date']); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                switch($enrollment['status']) {
                                                    case 'active':
                                                        $statusClass = 'bg-success';
                                                        break;
                                                    case 'completed':
                                                        $statusClass = 'bg-primary';
                                                        break;
                                                    case 'dropped':
                                                        $statusClass = 'bg-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($enrollment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewEnrollment(<?php echo $enrollment['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="editEnrollment(<?php echo $enrollment['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this enrollment?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['id']; ?>">
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

<!-- Add Enrollment Modal -->
<div class="modal fade" id="addEnrollmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Enrollment</h5>
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
                        <label class="form-label">Enrollment Date *</label>
                        <input type="date" class="form-control" name="enrollment_date" 
                               value="<?php echo date('Y-m-d'); ?>" 
                               max="<?php echo date('Y-m-d'); ?>" required>
                        <small class="text-muted">Cannot be a future date</small>
                        <?php if (isset($errors['enrollment_date'])): ?>
                            <div class="text-danger small"><?php echo $errors['enrollment_date']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="dropped">Dropped</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Enrollment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Enrollment Modal -->
<div class="modal fade" id="viewEnrollmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enrollment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="enrollmentDetails">
                    <!-- Enrollment details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Enrollment Modal -->
<div class="modal fade" id="editEnrollmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Enrollment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="editEnrollmentForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="enrollment_id" id="editEnrollmentId">
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
                        <label class="form-label">Enrollment Date *</label>
                        <input type="date" class="form-control" name="enrollment_date" id="editEnrollmentDate" 
                               max="<?php echo date('Y-m-d'); ?>" required>
                        <small class="text-muted">Cannot be a future date</small>
                        <?php if (isset($errors['enrollment_date'])): ?>
                            <div class="text-danger small"><?php echo $errors['enrollment_date']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="editStatus">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="dropped">Dropped</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Enrollment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store enrollment data for view and edit functions
const enrollmentsData = <?php echo json_encode($enrollments); ?>;
const studentsData = <?php echo json_encode($students); ?>;
const coursesData = <?php echo json_encode($courses); ?>;

// JavaScript date format function
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric' 
    });
}

function viewEnrollment(id) {
    const enrollment = enrollmentsData.find(e => e.id == id);
    if (enrollment) {
        const statusClass = {
            'active': 'bg-success',
            'completed': 'bg-primary',
            'dropped': 'bg-danger'
        }[enrollment.status] || 'bg-secondary';
        
        const detailsHtml = `
            <div class="row">
                <div class="col-12">
                    <h6 class="text-primary mb-3">Enrollment Information</h6>
                    <p><strong>Student:</strong> ${enrollment.first_name} ${enrollment.last_name}</p>
                    <p><strong>Roll Number:</strong> ${enrollment.roll_number}</p>
                    <p><strong>Course:</strong> <span class="badge bg-primary">${enrollment.course_code}</span> ${enrollment.course_name}</p>
                    <p><strong>Credits:</strong> ${enrollment.credits}</p>
                    <p><strong>Enrollment Date:</strong> ${formatDate(enrollment.enrollment_date)}</p>
                    <p><strong>Status:</strong> <span class="badge ${statusClass}">${enrollment.status.charAt(0).toUpperCase() + enrollment.status.slice(1)}</span></p>
                </div>
            </div>
        `;
        document.getElementById('enrollmentDetails').innerHTML = detailsHtml;
        new bootstrap.Modal(document.getElementById('viewEnrollmentModal')).show();
    }
}

function editEnrollment(id) {
    const enrollment = enrollmentsData.find(e => e.id == id);
    if (enrollment) {
        // Populate form fields
        document.getElementById('editEnrollmentId').value = enrollment.id;
        document.getElementById('editStudentId').value = enrollment.student_id;
        document.getElementById('editCourseId').value = enrollment.course_id;
        document.getElementById('editEnrollmentDate').value = enrollment.enrollment_date;
        document.getElementById('editStatus').value = enrollment.status;
        
        new bootstrap.Modal(document.getElementById('editEnrollmentModal')).show();
    }
}

function confirmDelete(message) {
    return confirm(message);
}

// Enrollment Search Functionality
document.addEventListener('DOMContentLoaded', function() {
    const enrollmentSearchInput = document.getElementById('enrollmentSearch');
    const clearEnrollmentButton = document.getElementById('clearEnrollmentSearch');
    const enrollmentRows = document.querySelectorAll('.enrollment-row');
    const enrollmentCount = document.getElementById('enrollmentCount');
    const enrollmentSearchResults = document.getElementById('enrollmentSearchResults');
    const enrollmentSearchMessage = document.getElementById('enrollmentSearchMessage');
    const noEnrollmentsMessage = document.getElementById('noEnrollmentsMessage');
    const noEnrollmentDataMessage = document.getElementById('noEnrollmentDataMessage');
    const enrollmentsTable = document.getElementById('enrollmentsTable');
    
    // Initialize enrollment count
    if (enrollmentCount) {
        enrollmentCount.textContent = enrollmentRows.length;
    }
    
    // Show/hide appropriate messages based on data availability
    if (enrollmentRows.length === 0) {
        if (noEnrollmentDataMessage) noEnrollmentDataMessage.style.display = 'block';
        if (enrollmentsTable) enrollmentsTable.style.display = 'none';
    }
    
    // Search functionality
    if (enrollmentSearchInput) {
        enrollmentSearchInput.addEventListener('input', function() {
            performEnrollmentSearch();
        });
        
        enrollmentSearchInput.addEventListener('keyup', function(e) {
            // Clear search on Escape key
            if (e.key === 'Escape') {
                clearEnrollmentSearch();
            }
        });
    }
    
    // Clear search functionality
    if (clearEnrollmentButton) {
        clearEnrollmentButton.addEventListener('click', clearEnrollmentSearch);
    }
    
    function performEnrollmentSearch() {
        const searchTerm = enrollmentSearchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        let totalEnrollments = enrollmentRows.length;
        
        enrollmentRows.forEach(function(row) {
            const searchText = row.getAttribute('data-search-text');
            const student = row.getAttribute('data-student');
            const roll = row.getAttribute('data-roll');
            const course = row.getAttribute('data-course');
            const date = row.getAttribute('data-date');
            const status = row.getAttribute('data-status');
            
            // Check if search term matches any field
            const matches = searchTerm === '' || 
                           searchText.includes(searchTerm) ||
                           student.includes(searchTerm) ||
                           roll.includes(searchTerm) ||
                           course.includes(searchTerm) ||
                           date.includes(searchTerm) ||
                           status.includes(searchTerm);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update UI based on search results
        updateEnrollmentSearchUI(searchTerm, visibleCount, totalEnrollments);
    }
    
    function updateEnrollmentSearchUI(searchTerm, visibleCount, totalEnrollments) {
        // Update enrollment count
        if (enrollmentCount) {
            enrollmentCount.textContent = visibleCount;
        }
        
        // Show/hide search results message
        if (enrollmentSearchResults && enrollmentSearchMessage) {
            if (searchTerm !== '') {
                enrollmentSearchResults.style.display = 'block';
                if (visibleCount > 0) {
                    enrollmentSearchResults.className = 'alert alert-success';
                    enrollmentSearchMessage.innerHTML = `<i class="fas fa-check-circle me-2"></i>Found ${visibleCount} enrollment${visibleCount !== 1 ? 's' : ''} matching "${searchTerm}"`;
                } else {
                    enrollmentSearchResults.className = 'alert alert-warning';
                    enrollmentSearchMessage.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>No enrollments found matching "${searchTerm}"`;
                }
            } else {
                enrollmentSearchResults.style.display = 'none';
            }
        }
        
        // Show/hide appropriate messages
        if (visibleCount === 0) {
            if (searchTerm !== '') {
                // Search returned no results
                if (noEnrollmentsMessage) noEnrollmentsMessage.style.display = 'block';
                if (noEnrollmentDataMessage) noEnrollmentDataMessage.style.display = 'none';
                if (enrollmentsTable) enrollmentsTable.style.display = 'none';
            } else {
                // No enrollments at all
                if (noEnrollmentDataMessage) noEnrollmentDataMessage.style.display = 'block';
                if (noEnrollmentsMessage) noEnrollmentsMessage.style.display = 'none';
                if (enrollmentsTable) enrollmentsTable.style.display = 'none';
            }
        } else {
            // Enrollment records found
            if (noEnrollmentsMessage) noEnrollmentsMessage.style.display = 'none';
            if (noEnrollmentDataMessage) noEnrollmentDataMessage.style.display = 'none';
            if (enrollmentsTable) enrollmentsTable.style.display = 'table';
        }
    }
    
    function clearEnrollmentSearch() {
        if (enrollmentSearchInput) {
            enrollmentSearchInput.value = '';
            performEnrollmentSearch();
            enrollmentSearchInput.focus();
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
