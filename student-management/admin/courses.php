<?php
$pageTitle = 'Manage Courses';
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$courses = [];
$teachers = [];
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Add new course
            $courseCode = isset($_POST['course_code']) ? cleanInput($_POST['course_code']) : '';
            $courseName = isset($_POST['course_name']) ? cleanInput($_POST['course_name']) : '';
            $description = isset($_POST['description']) ? cleanInput($_POST['description']) : '';
            $credits = isset($_POST['credits']) ? (int)$_POST['credits'] : 3;
            $teacherId = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
            
            // Validation
            if (empty($courseCode)) $errors['course_code'] = 'Course code is required';
            if (empty($courseName)) $errors['course_name'] = 'Course name is required';
            if ($credits < 1 || $credits > 10) $errors['credits'] = 'Credits must be between 1 and 10';
            
            if (empty($errors)) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("
                        INSERT INTO courses (course_code, course_name, description, credits, teacher_id) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$courseCode, $courseName, $description, $credits, $teacherId > 0 ? $teacherId : null]);
                    
                    setSuccessMessage('Course added successfully!');
                    header('Location: courses.php');
                    exit();
                    
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $errors['duplicate'] = 'Course code already exists';
                    } else {
                        $errors['database'] = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }
        
        elseif ($_POST['action'] === 'delete') {
            // Delete course
            $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
            
            if ($courseId > 0) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
                    $stmt->execute([$courseId]);
                    setSuccessMessage('Course deleted successfully!');
                    header('Location: courses.php');
                    exit();
                } catch (PDOException $e) {
                    setErrorMessage('Cannot delete course. It may have enrollments or attendance records.');
                }
            }
        }
        
        elseif ($_POST['action'] === 'edit') {
            // Edit course
            $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
            $courseCode = isset($_POST['course_code']) ? cleanInput($_POST['course_code']) : '';
            $courseName = isset($_POST['course_name']) ? cleanInput($_POST['course_name']) : '';
            $description = isset($_POST['description']) ? cleanInput($_POST['description']) : '';
            $credits = isset($_POST['credits']) ? (int)$_POST['credits'] : 3;
            $teacherId = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
            
            // Validation
            if (empty($courseCode)) $errors['course_code'] = 'Course code is required';
            if (empty($courseName)) $errors['course_name'] = 'Course name is required';
            if ($credits < 1 || $credits > 10) $errors['credits'] = 'Credits must be between 1 and 10';
            
            if (empty($errors)) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("
                        UPDATE courses SET course_code = ?, course_name = ?, description = ?, 
                        credits = ?, teacher_id = ? WHERE id = ?
                    ");
                    $stmt->execute([$courseCode, $courseName, $description, $credits, $teacherId > 0 ? $teacherId : null, $courseId]);
                    
                    setSuccessMessage('Course updated successfully!');
                    header('Location: courses.php');
                    exit();
                    
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $errors['duplicate'] = 'Course code already exists';
                    } else {
                        $errors['database'] = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Fetch all courses with teacher names
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT c.*, t.first_name, t.last_name 
        FROM courses c 
        LEFT JOIN teachers t ON c.teacher_id = t.id 
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
        ORDER BY t.first_name, t.last_name
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
                <h2><i class="fas fa-book me-2"></i>Manage Courses</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                    <i class="fas fa-plus me-2"></i>Add Course
                </button>
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
                        <span class="me-2">Total Courses:</span>
                        <span class="badge bg-primary" id="courseCount">0</span>
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
            <div id="courseSearchResults" class="alert alert-info" style="display: none;">
                <i class="fas fa-info-circle me-2"></i>
                <span id="courseSearchMessage"></span>
            </div>
            
            <div class="card">
                <div class="card-body">
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
                            <h5>No courses found</h5>
                            <p class="text-muted">Start by adding your first course.</p>
                        </div>
                    </div>
                    
                    <?php if (!empty($courses)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="coursesTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Description</th>
                                        <th>Credits</th>
                                        <th>Assigned Teacher</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $course): ?>
                                        <tr class="course-row" 
                                            data-id="<?php echo $course['id']; ?>"
                                            data-code="<?php echo htmlspecialchars(strtolower($course['course_code'])); ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($course['course_name'])); ?>"
                                            data-description="<?php echo htmlspecialchars(strtolower($course['description'])); ?>"
                                            data-credits="<?php echo $course['credits']; ?>"
                                            data-teacher="<?php echo htmlspecialchars(strtolower($course['first_name'] . ' ' . $course['last_name'])); ?>"
                                            data-search-text="<?php echo htmlspecialchars(strtolower($course['course_code'] . ' ' . $course['course_name'] . ' ' . $course['description'] . ' ' . $course['first_name'] . ' ' . $course['last_name'])); ?>">
                                            <td><?php echo $course['id']; ?></td>
                                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($course['course_code']); ?></span></td>
                                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($course['description'], 0, 50)) . (strlen($course['description']) > 50 ? '...' : ''); ?></td>
                                            <td><?php echo $course['credits']; ?></td>
                                            <td>
                                                <?php if ($course['first_name']): ?>
                                                    <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewCourse(<?php echo $course['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="editCourse(<?php echo $course['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this course?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
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

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Course Code *</label>
                        <input type="text" class="form-control" name="course_code" placeholder="e.g., BCA101" required>
                        <?php if (isset($errors['course_code'])): ?>
                            <div class="text-danger small"><?php echo $errors['course_code']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Course Name *</label>
                        <input type="text" class="form-control" name="course_name" placeholder="e.g., Fundamentals of Computers" required>
                        <?php if (isset($errors['course_name'])): ?>
                            <div class="text-danger small"><?php echo $errors['course_name']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Course description..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Credits *</label>
                        <input type="number" class="form-control" name="credits" min="1" max="10" value="3" required>
                        <?php if (isset($errors['credits'])): ?>
                            <div class="text-danger small"><?php echo $errors['credits']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Assign Teacher</label>
                        <select class="form-select" name="teacher_id">
                            <option value="">Select Teacher (Optional)</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Course Modal -->
<div class="modal fade" id="viewCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Course Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="courseDetails">
                    <!-- Course details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="editCourseForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="course_id" id="editCourseId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Course Code *</label>
                        <input type="text" class="form-control" name="course_code" id="editCourseCode" placeholder="e.g., BCA101" required>
                        <?php if (isset($errors['course_code'])): ?>
                            <div class="text-danger small"><?php echo $errors['course_code']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Course Name *</label>
                        <input type="text" class="form-control" name="course_name" id="editCourseName" placeholder="e.g., Fundamentals of Computers" required>
                        <?php if (isset($errors['course_name'])): ?>
                            <div class="text-danger small"><?php echo $errors['course_name']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="editDescription" rows="3" placeholder="Course description..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Credits *</label>
                        <input type="number" class="form-control" name="credits" id="editCredits" min="1" max="10" value="3" required>
                        <?php if (isset($errors['credits'])): ?>
                            <div class="text-danger small"><?php echo $errors['credits']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Assign Teacher</label>
                        <select class="form-select" name="teacher_id" id="editTeacherId">
                            <option value="">Select Teacher (Optional)</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store course data for view and edit functions
const coursesData = <?php echo json_encode($courses); ?>;
const teachersData = <?php echo json_encode($teachers); ?>;

function viewCourse(id) {
    const course = coursesData.find(c => c.id == id);
    if (course) {
        const teacherName = course.first_name ? `${course.first_name} ${course.last_name}` : 'Not assigned';
        const detailsHtml = `
            <div class="row">
                <div class="col-12">
                    <h6 class="text-primary mb-3">Course Information</h6>
                    <p><strong>Course Code:</strong> <span class="badge bg-primary">${course.course_code}</span></p>
                    <p><strong>Course Name:</strong> ${course.course_name}</p>
                    <p><strong>Description:</strong> ${course.description || 'No description available'}</p>
                    <p><strong>Credits:</strong> ${course.credits}</p>
                    <p><strong>Assigned Teacher:</strong> ${teacherName}</p>
                </div>
            </div>
        `;
        document.getElementById('courseDetails').innerHTML = detailsHtml;
        new bootstrap.Modal(document.getElementById('viewCourseModal')).show();
    }
}

function editCourse(id) {
    const course = coursesData.find(c => c.id == id);
    if (course) {
        // Populate form fields
        document.getElementById('editCourseId').value = course.id;
        document.getElementById('editCourseCode').value = course.course_code;
        document.getElementById('editCourseName').value = course.course_name;
        document.getElementById('editDescription').value = course.description || '';
        document.getElementById('editCredits').value = course.credits;
        document.getElementById('editTeacherId').value = course.teacher_id || '';
        
        new bootstrap.Modal(document.getElementById('editCourseModal')).show();
    }
}

function confirmDelete(message) {
    return confirm(message);
}

// Course Search Functionality
document.addEventListener('DOMContentLoaded', function() {
    const courseSearchInput = document.getElementById('courseSearch');
    const clearCourseButton = document.getElementById('clearCourseSearch');
    const courseRows = document.querySelectorAll('.course-row');
    const courseCount = document.getElementById('courseCount');
    const courseSearchResults = document.getElementById('courseSearchResults');
    const courseSearchMessage = document.getElementById('courseSearchMessage');
    const noCoursesMessage = document.getElementById('noCoursesMessage');
    const noCourseDataMessage = document.getElementById('noCourseDataMessage');
    const coursesTable = document.getElementById('coursesTable');
    
    // Initialize course count
    if (courseCount) {
        courseCount.textContent = courseRows.length;
    }
    
    // Show/hide appropriate messages based on data availability
    if (courseRows.length === 0) {
        if (noCourseDataMessage) noCourseDataMessage.style.display = 'block';
        if (coursesTable) coursesTable.style.display = 'none';
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
        let totalCourses = courseRows.length;
        
        courseRows.forEach(function(row) {
            const searchText = row.getAttribute('data-search-text');
            const code = row.getAttribute('data-code');
            const name = row.getAttribute('data-name');
            const description = row.getAttribute('data-description');
            const teacher = row.getAttribute('data-teacher');
            
            // Check if search term matches any field
            const matches = searchTerm === '' || 
                           searchText.includes(searchTerm) ||
                           code.includes(searchTerm) ||
                           name.includes(searchTerm) ||
                           description.includes(searchTerm) ||
                           teacher.includes(searchTerm);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
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
                if (coursesTable) coursesTable.style.display = 'none';
            } else {
                // No courses at all
                if (noCourseDataMessage) noCourseDataMessage.style.display = 'block';
                if (noCoursesMessage) noCoursesMessage.style.display = 'none';
                if (coursesTable) coursesTable.style.display = 'none';
            }
        } else {
            // Courses found
            if (noCoursesMessage) noCoursesMessage.style.display = 'none';
            if (noCourseDataMessage) noCourseDataMessage.style.display = 'none';
            if (coursesTable) coursesTable.style.display = 'table';
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
