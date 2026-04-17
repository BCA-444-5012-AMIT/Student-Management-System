<?php
$pageTitle = 'Teacher Dashboard';
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');

$teacherId = null;
$teacherInfo = [];
$myCourses = [];
$myStudents = [];
$attendanceStats = [];

// Get teacher information
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT t.* FROM teachers t WHERE t.user_id = ?");
    $stmt->execute([getUserId()]);
    $teacherInfo = $stmt->fetch();
    
    if ($teacherInfo) {
        $teacherId = $teacherInfo['id'];
        
        // Get teacher's courses
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE teacher_id = ?");
        $stmt->execute([$teacherId]);
        $myCourses = $stmt->fetchAll();
        
        // Get students enrolled in teacher's courses
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
        $myStudents = $stmt->fetchAll();
        
        // Get attendance statistics for this week
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                COUNT(CASE WHEN status = 'late' THEN 1 END) as late
            FROM attendance a
            JOIN courses c ON a.course_id = c.id
            WHERE c.teacher_id = ? AND a.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$teacherId]);
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
                <h2><i class="fas fa-tachometer-alt me-2"></i>Teacher Dashboard</h2>
                <div>
                    <span class="badge bg-primary">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
            
            <!-- Teacher Info Card -->
            <?php if ($teacherInfo): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($teacherInfo['first_name'] . ' ' . $teacherInfo['last_name']); ?>
                                </h5>
                                <p class="text-muted mb-2">Employee ID: <?php echo htmlspecialchars($teacherInfo['employee_id']); ?></p>
                                <p class="mb-1"><strong>Specialization:</strong> <?php echo htmlspecialchars($teacherInfo['specialization'] ?: 'Not specified'); ?></p>
                                <p class="mb-1"><strong>Qualification:</strong> <?php echo htmlspecialchars($teacherInfo['qualification'] ?: 'Not specified'); ?></p>
                                <p class="mb-0"><strong>Hire Date:</strong> <?php echo formatDate($teacherInfo['hire_date']); ?></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-outline-primary" onclick="editProfile()">
                                    <i class="fas fa-edit me-2"></i>Edit Profile
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($myCourses); ?></div>
                        <div class="stats-label">My Courses</div>
                        <i class="fas fa-book stats-icon"></i>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($myStudents); ?></div>
                        <div class="stats-label">My Students</div>
                        <i class="fas fa-user-graduate stats-icon"></i>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $attendanceStats['present']; ?></div>
                        <div class="stats-label">Present This Week</div>
                        <i class="fas fa-check-circle stats-icon"></i>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $attendanceStats['absent']; ?></div>
                        <div class="stats-label">Absent This Week</div>
                        <i class="fas fa-times-circle stats-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- My Courses -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-book me-2"></i>My Courses</h5>
                            <a href="courses.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($myCourses)): ?>
                                <p class="text-muted">No courses assigned to you yet.</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($myCourses, 0, 5) as $course): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($course['course_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($course['course_code']); ?> • <?php echo $course['credits']; ?> credits</small>
                                            </div>
                                            <span class="badge bg-primary rounded-pill"><?php echo $course['credits']; ?> credits</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Students -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>My Students</h5>
                            <a href="students.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($myStudents)): ?>
                                <p class="text-muted">No students enrolled in your courses yet.</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($myStudents, 0, 5) as $student): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($student['roll_number']); ?></small>
                                                </div>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewStudent(<?php echo $student['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <a href="attendance.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-calendar-check me-2"></i>Mark Attendance
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="students.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-users me-2"></i>View Students
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="courses.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-book me-2"></i>Manage Courses
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="profile.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-user me-2"></i>My Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editProfile() {
    window.location.href = 'profile.php';
}

function viewStudent(id) {
    // Find student in the teacher's enrolled students
    const student = myStudents.find(s => s.id == id);
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
            <div class="mt-3">
                <button class="btn btn-info" onclick="viewAttendance(${student.id})">
                    <i class="fas fa-calendar-check me-2"></i>View Attendance
                </button>
                <button class="btn btn-success" onclick="contactStudent('${student.email}')">
                    <i class="fas fa-envelope me-2"></i>Contact Student
                </button>
            </div>
        `;
        
        // Create a modal to display student details
        const modalHtml = `
            <div class="modal fade" id="studentDetailsModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Student Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${detailsHtml}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page if it doesn't exist
        if (!document.getElementById('studentDetailsModal')) {
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHtml;
            document.body.appendChild(modalContainer);
        }
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('studentDetailsModal'));
        modal.show();
    }
}

function contactStudent(email) {
    window.location.href = 'mailto:' + email;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
