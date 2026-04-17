<?php
$pageTitle = 'My Courses';
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');

$teacherId = null;
$myCourses = [];
$courseStudents = [];
$selectedCourse = null;

// Get teacher information
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([getUserId()]);
    $teacher = $stmt->fetch();
    
    if ($teacher) {
        $teacherId = $teacher['id'];
        
        // Get teacher's courses with student counts
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   COUNT(e.student_id) as enrolled_students
            FROM courses c 
            LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
            WHERE c.teacher_id = ?
            GROUP BY c.id
            ORDER BY c.course_name
        ");
        $stmt->execute([$teacherId]);
        $myCourses = $stmt->fetchAll();
        
        // Get students for selected course
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        if ($courseId > 0) {
            $stmt = $pdo->prepare("
                SELECT c.*, COUNT(e.student_id) as enrolled_students
                FROM courses c 
                LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
                WHERE c.id = ? AND c.teacher_id = ?
                GROUP BY c.id
            ");
            $stmt->execute([$courseId, $teacherId]);
            $selectedCourse = $stmt->fetch();
            
            if ($selectedCourse) {
                $stmt = $pdo->prepare("
                    SELECT s.*, u.username, u.email, e.enrollment_date
                    FROM students s 
                    JOIN users u ON s.user_id = u.id 
                    JOIN enrollments e ON s.id = e.student_id 
                    WHERE e.course_id = ? AND e.status = 'active'
                    ORDER BY s.first_name, s.last_name
                ");
                $stmt->execute([$courseId]);
                $courseStudents = $stmt->fetchAll();
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
            
            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="courseSearch" placeholder="Search courses by code, name, or description...">
                        <button class="btn btn-outline-secondary" type="button" id="clearCourseSearch">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                    <small class="text-muted">Type to search courses in real-time</small>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-end">
                        <span class="me-2">Showing:</span>
                        <span class="badge bg-primary" id="courseCount">0</span>
                        <span class="ms-2">of <?php echo count($myCourses); ?> courses</span>
                    </div>
                </div>
            </div>
            
            <!-- Search Results Message -->
            <div id="courseSearchResults" class="alert alert-info" style="display: none;">
                <i class="fas fa-info-circle me-2"></i>
                <span id="courseSearchMessage"></span>
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
                                            (<?php echo $course['enrolled_students']; ?> students)
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
                                <p><strong>Enrolled Students:</strong> <?php echo $selectedCourse['enrolled_students']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Description:</strong></p>
                                <p><?php echo htmlspecialchars($selectedCourse['description'] ?: 'No description available'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Enrolled Students -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>
                            Enrolled Students (<?php echo count($courseStudents); ?>)
                        </h5>
                        <a href="attendance.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-calendar-check me-2"></i>Mark Attendance
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($courseStudents)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                                <h5>No students enrolled</h5>
                                <p class="text-muted">No students have enrolled in this course yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Roll Number</th>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Enrollment Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courseStudents as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                                <td><?php echo formatDate($student['enrollment_date']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info" onclick="viewStudent(<?php echo $student['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success" onclick="contactStudent('<?php echo htmlspecialchars($student['email']); ?>')">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
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
                                <h5>No courses assigned</h5>
                                <p class="text-muted">No courses have been assigned to you yet. Please contact the administrator.</p>
                            </div>
                        <?php else: ?>
                            <div class="row" id="coursesList">
                                <?php foreach ($myCourses as $course): ?>
                                    <div class="col-md-6 col-lg-4 mb-4 course-card" 
                                         data-id="<?php echo $course['id']; ?>"
                                         data-code="<?php echo htmlspecialchars(strtolower($course['course_code'])); ?>"
                                         data-name="<?php echo htmlspecialchars(strtolower($course['course_name'])); ?>"
                                         data-description="<?php echo htmlspecialchars(strtolower($course['description'] ?? '')); ?>"
                                         data-students="<?php echo $course['enrolled_students']; ?>"
                                         data-credits="<?php echo $course['credits']; ?>"
                                         data-search-text="<?php echo htmlspecialchars(strtolower($course['course_code'] . ' ' . $course['course_name'] . ' ' . ($course['description'] ?? '') . ' ' . $course['enrolled_students'] . ' ' . $course['credits'])); ?>">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h6>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                                </div>
                                                <p class="card-text text-muted small">
                                                    <?php echo htmlspecialchars(substr($course['description'], 0, 80)) . (strlen($course['description']) > 80 ? '...' : ''); ?>
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-users me-1"></i>
                                                        <?php echo $course['enrolled_students']; ?> students
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-star me-1"></i>
                                                        <?php echo $course['credits']; ?> credits
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
                            <div id="noCoursesMessage" style="display: none;">
                                <div class="text-center py-4">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <h5>No courses found</h5>
                                    <p class="text-muted">Try adjusting your search criteria.</p>
                                </div>
                            </div>
                            <div id="noCourseDataMessage" style="display: none;">
                                <div class="text-center py-4">
                                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                    <h5>No courses assigned</h5>
                                    <p class="text-muted">No courses have been assigned to you yet. Please contact the administrator.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function viewStudent(studentId) {
    // Implementation for viewing student details
    alert('View student details functionality would be implemented here for student ID: ' + studentId);
}

function contactStudent(email) {
    // Implementation for contacting student via email
    window.location.href = 'mailto:' + email;
}

// Course Search Functionality
document.addEventListener('DOMContentLoaded', function() {
    const courseSearchInput = document.getElementById('courseSearch');
    const clearCourseButton = document.getElementById('clearCourseSearch');
    const courseCards = document.querySelectorAll('.course-card');
    const courseCount = document.getElementById('courseCount');
    const courseSearchResults = document.getElementById('courseSearchResults');
    const courseSearchMessage = document.getElementById('courseSearchMessage');
    const noCoursesMessage = document.getElementById('noCoursesMessage');
    const noCourseDataMessage = document.getElementById('noCourseDataMessage');
    const coursesList = document.getElementById('coursesList');
    
    // Initialize course count
    if (courseCount) {
        courseCount.textContent = courseCards.length;
    }
    
    // Show/hide appropriate messages based on data availability
    if (courseCards.length === 0) {
        if (noCourseDataMessage) noCourseDataMessage.style.display = 'block';
        if (coursesList) coursesList.style.display = 'none';
    }
    
    // Search functionality
    if (courseSearchInput) {
        courseSearchInput.addEventListener('input', function() {
            performCourseSearch();
        });
        
        courseSearchInput.addEventListener('keyup', function(e) {
            // Clear search on Escape key
            if (e.key === 'Escape') {
                clearCourseSearch();
            }
        });
    }
    
    // Clear search functionality
    if (clearCourseButton) {
        clearCourseButton.addEventListener('click', clearCourseSearch);
    }
    
    function performCourseSearch() {
        const searchTerm = courseSearchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        let totalCourses = courseCards.length;
        
        courseCards.forEach(function(card) {
            const searchText = card.getAttribute('data-search-text');
            const code = card.getAttribute('data-code');
            const name = card.getAttribute('data-name');
            const description = card.getAttribute('data-description');
            const students = card.getAttribute('data-students');
            const credits = card.getAttribute('data-credits');
            
            // Check if search term matches any field
            const matches = searchTerm === '' || 
                           searchText.includes(searchTerm) ||
                           code.includes(searchTerm) ||
                           name.includes(searchTerm) ||
                           description.includes(searchTerm) ||
                           students.includes(searchTerm) ||
                           credits.includes(searchTerm);
            
            if (matches) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Update UI based on search results
        updateCourseSearchUI(searchTerm, visibleCount, totalCourses);
    }
    
    function updateCourseSearchUI(searchTerm, visibleCount, totalCourses) {
        // Update course count
        if (courseCount) {
            courseCount.textContent = visibleCount;
        }
        
        // Show/hide search results message
        if (courseSearchResults && courseSearchMessage) {
            if (searchTerm !== '') {
                courseSearchResults.style.display = 'block';
                if (visibleCount > 0) {
                    courseSearchResults.className = 'alert alert-success';
                    courseSearchMessage.innerHTML = `<i class="fas fa-check-circle me-2"></i>Found ${visibleCount} course${visibleCount !== 1 ? 's' : ''} matching "${searchTerm}"`;
                } else {
                    courseSearchResults.className = 'alert alert-warning';
                    courseSearchMessage.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>No courses found matching "${searchTerm}"`;
                }
            } else {
                courseSearchResults.style.display = 'none';
            }
        }
        
        // Show/hide appropriate messages
        if (visibleCount === 0) {
            if (searchTerm !== '') {
                // Search returned no results
                if (noCoursesMessage) noCoursesMessage.style.display = 'block';
                if (noCourseDataMessage) noCourseDataMessage.style.display = 'none';
                if (coursesList) coursesList.style.display = 'none';
            } else {
                // No courses at all
                if (noCourseDataMessage) noCourseDataMessage.style.display = 'block';
                if (noCoursesMessage) noCoursesMessage.style.display = 'none';
                if (coursesList) coursesList.style.display = 'none';
            }
        } else {
            // Course records found
            if (noCoursesMessage) noCoursesMessage.style.display = 'none';
            if (noCourseDataMessage) noCourseDataMessage.style.display = 'none';
            if (coursesList) coursesList.style.display = 'flex';
        }
    }
    
    function clearCourseSearch() {
        if (courseSearchInput) {
            courseSearchInput.value = '';
            performCourseSearch();
            courseSearchInput.focus();
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
