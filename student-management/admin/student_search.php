<?php
$pageTitle = 'Student Search';
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$students = [];
$searchTerm = '';

// Handle search form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search_students') {
    $searchTerm = cleanInput($_POST['search_term'] ?? '');
    
    if (!empty($searchTerm)) {
        try {
            $pdo = getDBConnection();
            
            // Enhanced search query with multiple fields
            $stmt = $pdo->prepare("
                SELECT 
                    s.id,
                    s.first_name,
                    s.last_name,
                    s.roll_number,
                    u.email,
                    u.username,
                    u.created_at as user_created_at,
                    COUNT(DISTINCT e.course_id) as enrolled_courses_count,
                    COUNT(DISTINCT a.id) as total_attendance_records,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                    ROUND(
                        CASE WHEN COUNT(a.id) > 0 THEN 
                            (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) * 100.0) / COUNT(a.id)
                        ELSE 0 END, 
                        2
                    ) as attendance_percentage,
                    GROUP_CONCAT(DISTINCT c.course_code ORDER BY c.course_code SEPARATOR ', ') as enrolled_courses,
                    MAX(a.date) as last_attendance_date
                FROM students s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                LEFT JOIN attendance a ON s.id = a.student_id
                LEFT JOIN courses c ON e.course_id = c.id
                WHERE (
                    LOWER(s.first_name) LIKE LOWER(:search1) OR
                    LOWER(s.last_name) LIKE LOWER(:search2) OR
                    LOWER(CONCAT(s.first_name, ' ', s.last_name)) LIKE LOWER(:search3) OR
                    LOWER(s.roll_number) LIKE LOWER(:search4) OR
                    LOWER(u.username) LIKE LOWER(:search5) OR
                    LOWER(u.email) LIKE LOWER(:search6)
                )
                GROUP BY s.id, s.first_name, s.last_name, s.roll_number, u.email, u.username, u.created_at
                ORDER BY s.first_name, s.last_name
            ");
            
            $searchParam = '%' . $searchTerm . '%';
            $stmt->bindParam(':search1', $searchParam);
            $stmt->bindParam(':search2', $searchParam);
            $stmt->bindParam(':search3', $searchParam);
            $stmt->bindParam(':search4', $searchParam);
            $stmt->bindParam(':search5', $searchParam);
            $stmt->bindParam(':search6', $searchParam);
            $stmt->execute();
            $students = $stmt->fetchAll();
            
        } catch (PDOException $e) {
            setErrorMessage('Database error: ' . $e->getMessage());
        }
    }
} else {
    // Load all students if no search
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->query("
            SELECT 
                s.id,
                s.first_name,
                s.last_name,
                s.roll_number,
                u.email,
                u.username,
                u.created_at as user_created_at,
                COUNT(DISTINCT e.course_id) as enrolled_courses_count,
                COUNT(DISTINCT a.id) as total_attendance_records,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                ROUND(
                    CASE WHEN COUNT(a.id) > 0 THEN 
                        (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) * 100.0) / COUNT(a.id)
                    ELSE 0 END, 
                    2
                ) as attendance_percentage,
                GROUP_CONCAT(DISTINCT c.course_code ORDER BY c.course_code SEPARATOR ', ') as enrolled_courses,
                MAX(a.date) as last_attendance_date
            FROM students s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
            LEFT JOIN attendance a ON s.id = a.student_id
            LEFT JOIN courses c ON e.course_id = c.id
            GROUP BY s.id, s.first_name, s.last_name, s.roll_number, u.email, u.username, u.created_at
            ORDER BY s.first_name, s.last_name
            LIMIT 50
        ");
        $students = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        setErrorMessage('Database error: ' . $e->getMessage());
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
                <h2><i class="fas fa-user-graduate me-2"></i>Student Search</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" onclick="exportResults()">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                </div>
            </div>
            
            <!-- Search Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-search me-2"></i>Search Students
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="search_students">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" name="search_term" 
                                           value="<?php echo htmlspecialchars($searchTerm); ?>"
                                           placeholder="Search by name, roll number, username, or email..."
                                           id="studentSearchInput">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                        <i class="fas fa-times me-2"></i>Clear
                                    </button>
                                </div>
                                <small class="text-muted">
                                    Search across student names, roll numbers, usernames, and email addresses
                                </small>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="me-2">Results:</span>
                                    <span class="badge bg-primary" id="resultCount"><?php echo count($students); ?></span>
                                    <?php if (!empty($searchTerm)): ?>
                                        <span class="ms-2 me-2">For:</span>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($searchTerm); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Quick Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-filter me-2"></i>Quick Filters
                    </h6>
                    <div class="row">
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="quickSearch('')">
                                <i class="fas fa-users me-2"></i>All Students
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-success btn-sm w-100 mb-2" onclick="quickSearch('present')">
                                <i class="fas fa-check me-2"></i>Has Attendance
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-warning btn-sm w-100 mb-2" onclick="quickSearch('no')">
                                <i class="fas fa-exclamation-triangle me-2"></i>No Attendance
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-info btn-sm w-100 mb-2" onclick="quickSearch('enrolled')">
                                <i class="fas fa-book me-2"></i>Enrolled in Courses
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Results Section -->
            <?php if (!empty($students)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Student Results
                            <?php if (!empty($searchTerm)): ?>
                                <small class="text-muted">(<?php echo count($students); ?> found for "<?php echo htmlspecialchars($searchTerm); ?>")</small>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="studentTable">
                                <thead>
                                    <tr>
                                        <th>Student Details</th>
                                        <th>Contact Information</th>
                                        <th>Academic Info</th>
                                        <th>Attendance Summary</th>
                                        <th>Performance</th>
                                                                            </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $index => $student): ?>
                                        <tr class="student-row" 
                                            data-id="<?php echo $student['id']; ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($student['first_name'] . ' ' . $student['last_name'])); ?>"
                                            data-roll="<?php echo htmlspecialchars(strtolower($student['roll_number'])); ?>"
                                            data-username="<?php echo htmlspecialchars(strtolower($student['username'])); ?>"
                                            data-email="<?php echo htmlspecialchars(strtolower($student['email'])); ?>"
                                            data-courses="<?php echo htmlspecialchars(strtolower($student['enrolled_courses'] ?? '')); ?>"
                                            data-attendance="<?php echo $student['attendance_percentage']; ?>"
                                            data-search-text="<?php echo htmlspecialchars(strtolower($student['first_name'] . ' ' . $student['last_name'] . ' ' . $student['roll_number'] . ' ' . $student['username'] . ' ' . $student['email'] . ' ' . ($student['enrolled_courses'] ?? '') . ' ' . $student['attendance_percentage'])); ?>">
                                            <td>
                                                <div class="student-info">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="avatar-circle me-3">
                                                            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <strong class="text-primary"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-id-card me-1"></i><?php echo htmlspecialchars($student['roll_number']); ?>
                                                                <span class="ms-2"><i class="fas fa-user me-1"></i>@<?php echo htmlspecialchars($student['username']); ?></span>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>Joined: <?php echo formatDate($student['user_created_at']); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="contact-info">
                                                    <?php if (!empty($student['email'])): ?>
                                                        <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>" class="d-block mb-2">
                                                            <i class="fas fa-envelope text-primary me-2"></i>
                                                            <small><?php echo htmlspecialchars($student['email']); ?></small>
                                                        </a>
                                                    <?php endif; ?>
                                                    <div class="d-block">
                                                        <i class="fas fa-info-circle text-muted me-2"></i>
                                                        <small class="text-muted">Phone not available</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="academic-info">
                                                    <div class="mb-2">
                                                        <small class="text-muted">Enrolled Courses:</small>
                                                        <div>
                                                            <?php 
                                                            $courses = $student['enrolled_courses'] ?? '';
                                                            if (!empty($courses)) {
                                                                $courseArray = explode(', ', $courses);
                                                                foreach ($courseArray as $course) {
                                                                    echo '<span class="badge bg-info me-1 mb-1">' . htmlspecialchars($course) . '</span>';
                                                                }
                                                            } else {
                                                                echo '<span class="text-muted">No courses</span>';
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-book me-1"></i><?php echo $student['enrolled_courses_count']; ?> course(s)
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="attendance-info">
                                                    <?php if ($student['total_attendance_records'] > 0): ?>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <small><i class="fas fa-check text-success me-1"></i>Present</small>
                                                                <small class="badge bg-success"><?php echo $student['present_count']; ?></small>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <small><i class="fas fa-times text-danger me-1"></i>Absent</small>
                                                                <small class="badge bg-danger"><?php echo $student['absent_count']; ?></small>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <small><i class="fas fa-clock text-warning me-1"></i>Late</small>
                                                                <small class="badge bg-warning"><?php echo $student['late_count']; ?></small>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar me-1"></i>Total: <?php echo $student['total_attendance_records']; ?> records
                                                        </small>
                                                    <?php else: ?>
                                                        <div class="text-center">
                                                            <i class="fas fa-info-circle text-muted fa-2x mb-2"></i>
                                                            <small class="text-muted">No attendance data</small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="performance-info">
                                                    <?php 
                                                    $percentage = $student['attendance_percentage'];
                                                    $badgeClass = $percentage >= 75 ? 'bg-success' : 
                                                                 ($percentage >= 60 ? 'bg-warning' : 'bg-danger');
                                                    ?>
                                                    <div class="text-center mb-2">
                                                        <span class="badge <?php echo $badgeClass; ?> fs-6"><?php echo $percentage; ?>%</span>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar <?php echo $badgeClass; ?>" 
                                                             style="width: <?php echo $percentage; ?>%"></div>
                                                    </div>
                                                    <?php if ($student['last_attendance_date']): ?>
                                                        <small class="text-muted d-block mt-2">
                                                            <i class="fas fa-clock me-1"></i>Last: <?php echo formatDate($student['last_attendance_date']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                                                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">
                            <?php if (!empty($searchTerm)): ?>
                                No students found matching "<?php echo htmlspecialchars($searchTerm); ?>"
                            <?php else: ?>
                                No students found in the system
                            <?php endif; ?>
                        </h5>
                        <p class="text-muted">
                            <?php if (!empty($searchTerm)): ?>
                                Try adjusting your search terms or browse all students.
                            <?php else: ?>
                                Add students to the system to see them here.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($searchTerm)): ?>
                            <button class="btn btn-outline-primary" onclick="clearSearchForm()">
                                <i class="fas fa-times me-2"></i>Clear Search
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

.student-info {
    line-height: 1.4;
}

.contact-info a {
    text-decoration: none;
    color: inherit;
}

.contact-info a:hover {
    text-decoration: underline;
}

.academic-info .badge {
    font-size: 0.7rem;
    margin-bottom: 2px;
}

.attendance-info {
    font-size: 0.85rem;
    line-height: 1.3;
}

.performance-info {
    min-width: 100px;
}

.performance-info .badge {
    font-size: 0.9rem;
    font-weight: bold;
}

.student-row {
    transition: all 0.2s ease;
}

.student-row:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.action-buttons .btn {
    width: 100%;
}

@media print {
    .sidebar, .btn, .no-print {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('studentSearchInput');
    const clearButton = document.getElementById('clearSearch');
    const studentRows = document.querySelectorAll('.student-row');
    const resultCount = document.getElementById('resultCount');
    
    // Real-time search
    if (searchInput) {
        searchInput.addEventListener('input', performRealTimeSearch);
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Escape') {
                clearSearchForm();
            }
        });
    }
    
    // Clear search
    if (clearButton) {
        clearButton.addEventListener('click', clearSearchForm);
    }
    
    function performRealTimeSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        
        studentRows.forEach(function(row) {
            const searchText = row.getAttribute('data-search-text');
            const name = row.getAttribute('data-name');
            const roll = row.getAttribute('data-roll');
            const username = row.getAttribute('data-username');
            const email = row.getAttribute('data-email');
            const courses = row.getAttribute('data-courses');
            const attendance = row.getAttribute('data-attendance');
            
            const matches = searchTerm === '' || 
                           searchText.includes(searchTerm) ||
                           name.includes(searchTerm) ||
                           roll.includes(searchTerm) ||
                           username.includes(searchTerm) ||
                           email.includes(searchTerm) ||
                           courses.includes(searchTerm) ||
                           attendance.includes(searchTerm);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        if (resultCount) {
            resultCount.textContent = visibleCount;
        }
    }
    
    window.clearSearchForm = function() {
        if (searchInput) {
            searchInput.value = '';
            performRealTimeSearch();
            searchInput.focus();
        }
    };
    
    window.quickSearch = function(type) {
        if (searchInput) {
            switch(type) {
                case '':
                    searchInput.value = '';
                    break;
                case 'present':
                    searchInput.value = '';
                    // Filter students with attendance
                    studentRows.forEach(function(row) {
                        const attendance = parseFloat(row.getAttribute('data-attendance'));
                        row.style.display = attendance > 0 ? '' : 'none';
                    });
                    updateVisibleCount();
                    return;
                case 'no':
                    searchInput.value = '';
                    // Filter students with no attendance
                    studentRows.forEach(function(row) {
                        const attendance = parseFloat(row.getAttribute('data-attendance'));
                        row.style.display = attendance === 0 ? '' : 'none';
                    });
                    updateVisibleCount();
                    return;
                case 'enrolled':
                    searchInput.value = '';
                    // Filter enrolled students
                    studentRows.forEach(function(row) {
                        const courses = row.getAttribute('data-courses');
                        row.style.display = courses && courses.trim() !== '' ? '' : 'none';
                    });
                    updateVisibleCount();
                    return;
            }
            performRealTimeSearch();
        }
    };
    
    function updateVisibleCount() {
        let visibleCount = 0;
        studentRows.forEach(function(row) {
            if (row.style.display !== 'none') {
                visibleCount++;
            }
        });
        if (resultCount) {
            resultCount.textContent = visibleCount;
        }
    }
    
    window.exportResults = function() {
        // Simple CSV export
        let csv = 'Student Name,Roll Number,Username,Email,Enrolled Courses,Attendance %\n';
        
        studentRows.forEach(function(row) {
            if (row.style.display !== 'none') {
                const name = row.querySelector('.text-primary').textContent;
                const roll = row.getAttribute('data-roll');
                const username = row.getAttribute('data-username');
                const email = row.getAttribute('data-email');
                const courses = row.getAttribute('data-courses');
                const attendance = row.getAttribute('data-attendance');
                
                csv += `"${name}","${roll}","${username}","${email}","${courses}","${attendance}%"\n`;
            }
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'students_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    };
});
</script>


<?php include __DIR__ . '/../includes/footer.php'; ?>
