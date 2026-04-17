<?php
$pageTitle = 'My Grades';
require_once __DIR__ . '/../config/config.php';
requireRole('student');

$studentInfo = [];
$gradesData = [];
$courseFilter = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Get student information
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([getUserId()]);
    $student = $stmt->fetch();
    
    if ($student) {
        $studentId = $student['id'];
        
        // Get student's courses for filter
        $stmt = $pdo->prepare("
            SELECT c.id, c.course_code, c.course_name
            FROM courses c 
            JOIN enrollments e ON c.id = e.course_id 
            WHERE e.student_id = ? AND e.status = 'active'
            ORDER BY c.course_name
        ");
        $stmt->execute([$studentId]);
        $myCourses = $stmt->fetchAll();
        
        // Note: This is a placeholder for grades functionality
        // In a real system, you would have a grades/marks table
        // For now, we'll show a message that grades functionality would be implemented
        
        if ($courseFilter > 0) {
            // Check if course belongs to student
            $stmt = $pdo->prepare("
                SELECT c.course_code, c.course_name
                FROM courses c 
                JOIN enrollments e ON c.id = e.course_id 
                WHERE e.student_id = ? AND c.id = ? AND e.status = 'active'
            ");
            $stmt->execute([$studentId, $courseFilter]);
            $selectedCourse = $stmt->fetch();
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
                <h2><i class="fas fa-chart-line me-2"></i>My Grades</h2>
            </div>
            
            <!-- Information Card -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Grades Module</strong><br>
                This is a placeholder for the grades functionality. In a complete implementation, this section would include:
                <ul class="mb-0 mt-2">
                    <li>View marks and grades for each course</li>
                    <li>Assignment scores and exam results</li>
                    <li>Grade point average (GPA) calculation</li>
                    <li>Grade history and trends</li>
                    <li>Downloadable grade reports</li>
                </ul>
            </div>
            
            <!-- Filter Section -->
            <?php if (!empty($myCourses)): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Filter by Course</label>
                                    <select class="form-select" name="course_id">
                                        <option value="">All Courses</option>
                                        <?php foreach ($myCourses as $course): ?>
                                            <option value="<?php echo $course['id']; ?>" 
                                                    <?php echo ($courseFilter == $course['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-filter me-2"></i>Filter
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='grades.php'">
                                        <i class="fas fa-times me-2"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Sample Grade Display (Placeholder) -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Grade Summary
                        <?php if (isset($selectedCourse)): ?>
                            - <?php echo htmlspecialchars($selectedCourse['course_code'] . ' - ' . $selectedCourse['course_name']); ?>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                        <h4>Grades Module Coming Soon</h4>
                        <p class="text-muted">
                            The grades functionality will be implemented in a future update.
                            This would include detailed grade reports, GPA calculations, and performance analytics.
                        </p>
                        
                        <!-- Sample Grade Table Structure -->
                        <div class="mt-4">
                            <h6>Sample Grade Structure:</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Assessment Type</th>
                                            <th>Total Marks</th>
                                            <th>Obtained Marks</th>
                                            <th>Grade</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Internal Assessment</td>
                                            <td>50</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                        </tr>
                                        <tr>
                                            <td>Mid Term Exam</td>
                                            <td>50</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                        </tr>
                                        <tr>
                                            <td>Final Exam</td>
                                            <td>100</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                        </tr>
                                        <tr class="table-primary fw-bold">
                                            <td>Total</td>
                                            <td>200</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Sample GPA Scale -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h6>Grade Scale:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Percentage</th>
                                                <th>Grade</th>
                                                <th>GPA</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td>90-100</td><td>A+</td><td>4.0</td></tr>
                                            <tr><td>85-89</td><td>A</td><td>3.7</td></tr>
                                            <tr><td>80-84</td><td>B+</td><td>3.3</td></tr>
                                            <tr><td>75-79</td><td>B</td><td>3.0</td></tr>
                                            <tr><td>70-74</td><td>C+</td><td>2.7</td></tr>
                                            <tr><td>65-69</td><td>C</td><td>2.3</td></tr>
                                            <tr><td>60-64</td><td>D</td><td>2.0</td></tr>
                                            <tr><td>Below 60</td><td>F</td><td>0.0</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Features to be Implemented:</h6>
                                <ul class="text-start">
                                    <li>Real-time grade updates</li>
                                    <li>Performance trends and graphs</li>
                                    <li>Subject-wise grade analysis</li>
                                    <li>Semester/term GPA calculation</li>
                                    <li>Grade comparison with class average</li>
                                    <li>Export grade reports (PDF/Excel)</li>
                                    <li>Parent/guardian access (if applicable)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
