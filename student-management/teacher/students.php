<?php
$pageTitle = 'My Students';
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');

$teacherId = null;
$myStudents = [];
$selectedCourse = null;
$myCourses = [];
$allStudents = [];
$errors = [];

// Handle enrollment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enroll') {
    $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    
    // Validation
    if ($studentId <= 0) $errors['student_id'] = 'Student is required';
    if ($courseId <= 0) $errors['course_id'] = 'Course is required';
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Check if student is already enrolled
            $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$studentId, $courseId]);
            if ($stmt->fetch()) {
                $errors['duplicate'] = 'Student is already enrolled in this course';
            } else {
                // Enroll student
                $stmt = $pdo->prepare("
                    INSERT INTO enrollments (student_id, course_id, status, enrollment_date) 
                    VALUES (?, ?, 'active', CURDATE())
                ");
                $stmt->execute([$studentId, $courseId]);
                
                setSuccessMessage('Student enrolled successfully!');
                header('Location: students.php');
                exit();
            }
        } catch (PDOException $e) {
            setErrorMessage('Database error: ' . $e->getMessage());
        }
    }
}

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
        
        // Get all available students for enrollment
        $stmt = $pdo->query("
            SELECT s.*, u.username, u.email 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            ORDER BY s.first_name, s.last_name
        ");
        $allStudents = $stmt->fetchAll();
        
        // Get students enrolled in teacher's courses
        $courseFilter = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        
        if ($courseFilter > 0) {
            $stmt = $pdo->prepare("
                SELECT DISTINCT s.*, u.username, u.email, c.course_name, e.enrollment_date
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                JOIN enrollments e ON s.id = e.student_id 
                JOIN courses c ON e.course_id = c.id 
                WHERE c.teacher_id = ? AND e.course_id = ? AND e.status = 'active'
                ORDER BY s.first_name, s.last_name
            ");
            $stmt->execute([$teacherId, $courseFilter]);
        } else {
            $stmt = $pdo->prepare("
                SELECT DISTINCT s.*, u.username, u.email
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                JOIN enrollments e ON s.id = e.student_id 
                JOIN courses c ON e.course_id = c.id 
                WHERE c.teacher_id = ? AND e.status = 'active'
                ORDER BY s.first_name, s.last_name
            ");
            $stmt->execute([$teacherId]);
        }
        
        $myStudents = $stmt->fetchAll();
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
                <h2><i class="fas fa-user-graduate me-2"></i>My Students</h2>
                <div>
                    <span class="badge bg-primary">Total: <?php echo count($myStudents); ?></span>
                    <button class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#enrollStudentModal">
                        <i class="fas fa-plus me-2"></i>Enroll Student
                    </button>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="studentSearch" placeholder="Search students by name, roll number, username, or phone...">
                        <button class="btn btn-outline-secondary" type="button" id="clearStudentSearch">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                    <small class="text-muted">Type to search students in real-time</small>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-end">
                        <span class="me-2">Showing:</span>
                        <span class="badge bg-primary" id="studentCount">0</span>
                        <span class="ms-2">of <?php echo count($myStudents); ?> students</span>
                    </div>
                </div>
            </div>
            
            <!-- Filter by Course -->
            <?php if (!empty($myCourses)): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Filter by Course</label>
                                    <select class="form-select" name="course_id" onchange="this.form.submit()">
                                        <option value="">All Courses</option>
                                        <?php foreach ($myCourses as $course): ?>
                                            <option value="<?php echo $course['id']; ?>" 
                                                    <?php echo (isset($_GET['course_id']) && $_GET['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='students.php'">
                                        <i class="fas fa-times me-2"></i>Clear Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Search Results Message -->
            <div id="studentSearchResults" class="alert alert-info" style="display: none;">
                <i class="fas fa-info-circle me-2"></i>
                <span id="studentSearchMessage"></span>
            </div>
            
            <!-- Students List -->
            <div class="card">
                <div class="card-body">
                    <div id="noStudentsMessage" style="display: none;">
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>No students found</h5>
                            <p class="text-muted">Try adjusting your search criteria.</p>
                        </div>
                    </div>
                    
                    <div id="noStudentDataMessage" style="display: none;">
                        <div class="text-center py-4">
                            <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                            <h5>No students found</h5>
                            <p class="text-muted">
                                <?php if (isset($_GET['course_id']) && $_GET['course_id'] > 0): ?>
                                    No students enrolled in the selected course.
                                <?php else: ?>
                                    No students enrolled in your courses yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if (!empty($myStudents)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="studentsTable">
                                <thead>
                                    <tr>
                                        <th>Roll Number</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Phone</th>
                                        <th>Enrollment Date</th>
                                        <?php if (isset($_GET['course_id']) && $_GET['course_id'] > 0): ?>
                                            <th>Course</th>
                                        <?php endif; ?>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($myStudents as $student): ?>
                                        <tr class="student-row" 
                                            data-id="<?php echo $student['id']; ?>"
                                            data-roll="<?php echo htmlspecialchars(strtolower($student['roll_number'])); ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($student['first_name'] . ' ' . $student['last_name'])); ?>"
                                            data-username="<?php echo htmlspecialchars(strtolower($student['username'])); ?>"
                                            data-phone="<?php echo htmlspecialchars(strtolower($student['phone'])); ?>"
                                            data-date="<?php echo formatDate($student['enrollment_date'] ?? $student['enrollment_date']); ?>"
                                            data-course="<?php echo isset($student['course_name']) ? htmlspecialchars(strtolower($student['course_name'])) : ''; ?>"
                                            data-search-text="<?php echo htmlspecialchars(strtolower($student['roll_number'] . ' ' . $student['first_name'] . ' ' . $student['last_name'] . ' ' . $student['username'] . ' ' . $student['phone'] . ' ' . ($student['course_name'] ?? '') . ' ' . formatDate($student['enrollment_date'] ?? $student['enrollment_date']))); ?>">
                                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                            <td><?php echo formatDate($student['enrollment_date'] ?? $student['enrollment_date']); ?></td>
                                            <?php if (isset($_GET['course_id']) && $_GET['course_id'] > 0): ?>
                                                <td><?php echo htmlspecialchars($student['course_name']); ?></td>
                                            <?php endif; ?>
                                            <td>
                                                <button class="btn btn-sm btn-outline-warning" onclick="generateReport(<?php echo $student['id']; ?>)" title="Generate Report">
                                                    <i class="fas fa-file-alt"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewStudent(<?php echo $student['id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewAttendance(<?php echo $student['id']; ?>)" title="View Attendance">
                                                    <i class="fas fa-calendar-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" onclick="viewAttendancePercentage(<?php echo $student['id']; ?>)" title="View Attendance Percentage">
                                                    <i class="fas fa-chart-pie"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Summary Statistics -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Summary:</strong> 
                                    Showing <?php echo count($myStudents); ?> student(s)
                                    <?php if (isset($_GET['course_id']) && $_GET['course_id'] > 0): ?>
                                        from the selected course
                                    <?php else: ?>
                                        from all your courses
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enroll Student Modal -->
<div class="modal fade" id="enrollStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enroll Student in Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="enroll">
                <div class="modal-body">
                    <?php if (isset($errors['duplicate'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $errors['duplicate']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Student *</label>
                        <select class="form-select" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($allStudents as $student): ?>
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
                        <label class="form-label">Select Course *</label>
                        <select class="form-select" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($myCourses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['course_id'])): ?>
                            <div class="text-danger small"><?php echo $errors['course_id']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Enroll Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Attendance Percentage Modal -->
<div class="modal fade" id="attendancePercentageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attendance Percentage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="attendancePercentageContent">
                    <!-- Attendance percentage data will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="studentDetails">
                    <!-- Student details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Student Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="editStudentForm">
                    <!-- Edit form will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
// Store student data for modals
const studentsData = <?php echo json_encode($myStudents); ?>;
const coursesData = <?php echo json_encode($myCourses); ?>;

console.log('JavaScript loaded');
console.log('studentsData length:', studentsData.length);
console.log('studentsData:', studentsData);

function viewStudent(id) {
    console.log('viewStudent called with id:', id);
    console.log('studentsData:', studentsData);
    const student = studentsData.find(s => s.id == id);
    console.log('found student:', student);
    if (student) {
        const detailsHtml = `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Personal Information</h6>
                    <p><strong>Name:</strong> ${student.first_name} ${student.last_name}</p>
                    <p><strong>Roll Number:</strong> ${student.roll_number}</p>
                    <p><strong>Username:</strong> ${student.username}</p>
                    <p><strong>Email:</strong> ${student.email}</p>
                    <p><strong>Phone:</strong> ${student.phone || 'Not provided'}</p>
                    <p><strong>Enrollment Date:</strong> ${new Date(student.enrollment_date).toLocaleDateString()}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Course Information</h6>
                    <p><strong>Course:</strong> ${student.course_name || 'Not assigned'}</p>
                </div>
            </div>
        `;
        document.getElementById('studentDetails').innerHTML = detailsHtml;
        new bootstrap.Modal(document.getElementById('viewStudentModal')).show();
    } else {
        console.error('Student not found with id:', id);
        alert('Student not found');
    }
}

function editStudent(id) {
    const student = studentsData.find(s => s.id == id);
    if (student) {
        // Show student details in view modal instead of edit
        viewStudent(id);
    }
}

function viewAttendance(studentId) {
    window.location.href = 'attendance.php';
}

function viewAttendancePercentage(studentId) {
    console.log('viewAttendancePercentage called with id:', studentId);
    const student = studentsData.find(s => s.id == studentId);
    
    if (student) {
        // Show loading state
        document.getElementById('attendancePercentageContent').innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading attendance data...</p>
            </div>
        `;
        
        // Show modal
        new bootstrap.Modal(document.getElementById('attendancePercentageModal')).show();
        
        // Fetch attendance data via AJAX
        fetch(`attendance_percentage.php?student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Attendance data received:', data);
                displayAttendancePercentage(student, data);
            })
            .catch(error => {
                console.error('Error fetching attendance data:', error);
                document.getElementById('attendancePercentageContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading attendance data. Please try again.
                    </div>
                `;
            });
    } else {
        console.error('Student not found for attendance percentage with id:', studentId);
        alert('Student not found');
    }
}

function displayAttendancePercentage(student, data) {
    const attendanceHtml = `
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-user-graduate me-2"></i>
                            ${student.first_name} ${student.last_name}
                        </h6>
                        <p class="text-muted mb-2">Roll Number: ${student.roll_number}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary">${data.overall_percentage}%</h3>
                        <p class="mb-0">Overall Attendance</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success">${data.present_days}</h3>
                        <p class="mb-0">Present Days</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-danger">${data.absent_days}</h3>
                        <p class="mb-0">Absent Days</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <h6 class="mb-3">Course-wise Attendance</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                                <th>Percentage</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.course_wise.map(course => `
                                <tr>
                                    <td>${course.course_name}</td>
                                    <td><span class="badge bg-success">${course.present}</span></td>
                                    <td><span class="badge bg-danger">${course.absent}</span></td>
                                    <td><span class="badge bg-warning">${course.late}</span></td>
                                    <td><strong>${course.percentage}%</strong></td>
                                    <td>
                                        ${course.percentage >= 75 ? 
                                            '<span class="badge bg-success">Good</span>' : 
                                            course.percentage >= 60 ? 
                                            '<span class="badge bg-warning">Average</span>' : 
                                            '<span class="badge bg-danger">Poor</span>'
                                        }
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: ${data.overall_percentage}%">
                        ${data.overall_percentage}% Attendance
                    </div>
                </div>
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Minimum required attendance: 75%
                </small>
            </div>
        </div>
    `;
    
    document.getElementById('attendancePercentageContent').innerHTML = attendanceHtml;
}

// Student Search Functionality
document.addEventListener('DOMContentLoaded', function() {
    const studentSearchInput = document.getElementById('studentSearch');
    const clearStudentButton = document.getElementById('clearStudentSearch');
    const studentRows = document.querySelectorAll('.student-row');
    const studentCount = document.getElementById('studentCount');
    const studentSearchResults = document.getElementById('studentSearchResults');
    const studentSearchMessage = document.getElementById('studentSearchMessage');
    const noStudentsMessage = document.getElementById('noStudentsMessage');
    const noStudentDataMessage = document.getElementById('noStudentDataMessage');
    const studentsTable = document.getElementById('studentsTable');
    
    // Initialize student count
    if (studentCount) {
        studentCount.textContent = studentRows.length;
    }
    
    // Show/hide appropriate messages based on data availability
    if (studentRows.length === 0) {
        if (noStudentDataMessage) noStudentDataMessage.style.display = 'block';
        if (studentsTable) studentsTable.style.display = 'none';
    }
    
    // Search functionality
    if (studentSearchInput) {
        studentSearchInput.addEventListener('input', function() {
            performStudentSearch();
        });
        
        studentSearchInput.addEventListener('keyup', function(e) {
            // Clear search on Escape key
            if (e.key === 'Escape') {
                clearStudentSearch();
            }
        });
    }
    
    // Clear search functionality
    if (clearStudentButton) {
        clearStudentButton.addEventListener('click', clearStudentSearch);
    }
    
    function performStudentSearch() {
        const searchTerm = studentSearchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        let totalStudents = studentRows.length;
        
        studentRows.forEach(function(row) {
            const searchText = row.getAttribute('data-search-text');
            const roll = row.getAttribute('data-roll');
            const name = row.getAttribute('data-name');
            const username = row.getAttribute('data-username');
            const phone = row.getAttribute('data-phone');
            const date = row.getAttribute('data-date');
            const course = row.getAttribute('data-course');
            
            // Check if search term matches any field
            const matches = searchTerm === '' || 
                           searchText.includes(searchTerm) ||
                           roll.includes(searchTerm) ||
                           name.includes(searchTerm) ||
                           username.includes(searchTerm) ||
                           phone.includes(searchTerm) ||
                           date.includes(searchTerm) ||
                           course.includes(searchTerm);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update UI based on search results
        updateStudentSearchUI(searchTerm, visibleCount, totalStudents);
    }
    
    function updateStudentSearchUI(searchTerm, visibleCount, totalStudents) {
        // Update student count
        if (studentCount) {
            studentCount.textContent = visibleCount;
        }
        
        // Show/hide search results message
        if (studentSearchResults && studentSearchMessage) {
            if (searchTerm !== '') {
                studentSearchResults.style.display = 'block';
                if (visibleCount > 0) {
                    studentSearchResults.className = 'alert alert-success';
                    studentSearchMessage.innerHTML = `<i class="fas fa-check-circle me-2"></i>Found ${visibleCount} student${visibleCount !== 1 ? 's' : ''} matching "${searchTerm}"`;
                } else {
                    studentSearchResults.className = 'alert alert-warning';
                    studentSearchMessage.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>No students found matching "${searchTerm}"`;
                }
            } else {
                studentSearchResults.style.display = 'none';
            }
        }
        
        // Show/hide appropriate messages
        if (visibleCount === 0) {
            if (searchTerm !== '') {
                // Search returned no results
                if (noStudentsMessage) noStudentsMessage.style.display = 'block';
                if (noStudentDataMessage) noStudentDataMessage.style.display = 'none';
                if (studentsTable) studentsTable.style.display = 'none';
            } else {
                // No students at all
                if (noStudentDataMessage) noStudentDataMessage.style.display = 'block';
                if (noStudentsMessage) noStudentsMessage.style.display = 'none';
                if (studentsTable) studentsTable.style.display = 'none';
            }
        } else {
            // Student records found
            if (noStudentsMessage) noStudentsMessage.style.display = 'none';
            if (noStudentDataMessage) noStudentDataMessage.style.display = 'none';
            if (studentsTable) studentsTable.style.display = 'table';
        }
    }
    
    function clearStudentSearch() {
        if (studentSearchInput) {
            studentSearchInput.value = '';
            performStudentSearch();
            studentSearchInput.focus();
        }
    }
    
    // Generate student report
    window.generateReport = function(studentId) {
        // Open report in new window/tab
        const reportUrl = 'student_report.php?id=' + studentId;
        window.open(reportUrl, '_blank', 'width=1000,height=800,scrollbars=yes,resizable=yes');
    };
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
