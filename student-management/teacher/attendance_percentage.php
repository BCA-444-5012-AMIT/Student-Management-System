<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');

header('Content-Type: application/json');

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if ($studentId <= 0) {
    echo json_encode(['error' => 'Invalid student ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get teacher information
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([getUserId()]);
    $teacher = $stmt->fetch();
    
    if (!$teacher) {
        echo json_encode(['error' => 'Teacher not found']);
        exit;
    }
    
    $teacherId = $teacher['id'];
    
    // Verify student is enrolled in teacher's courses
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.student_id = ? AND c.teacher_id = ? AND e.status = 'active'
    ");
    $stmt->execute([$studentId, $teacherId]);
    $enrollmentCheck = $stmt->fetch();
    
    if ($enrollmentCheck['count'] == 0) {
        echo json_encode(['error' => 'Student not enrolled in your courses']);
        exit;
    }
    
    // Get overall attendance statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
            COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
            COUNT(*) as total_classes
        FROM attendance a
        JOIN courses c ON a.course_id = c.id
        WHERE a.student_id = ? AND c.teacher_id = ?
    ");
    $stmt->execute([$studentId, $teacherId]);
    $overallStats = $stmt->fetch();
    
    // Calculate overall percentage
    $totalClasses = $overallStats['total_classes'];
    $presentDays = $overallStats['present'];
    $absentDays = $overallStats['absent'];
    $lateDays = $overallStats['late'];
    
    // Consider late as half present for percentage calculation
    $effectivePresent = $presentDays + ($lateDays * 0.5);
    $overallPercentage = $totalClasses > 0 ? round(($effectivePresent / $totalClasses) * 100, 2) : 0;
    
    // Get course-wise attendance
    $stmt = $pdo->prepare("
        SELECT 
            c.course_name,
            COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present,
            COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent,
            COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late,
            COUNT(*) as total_classes
        FROM attendance a
        JOIN courses c ON a.course_id = c.id
        WHERE a.student_id = ? AND c.teacher_id = ?
        GROUP BY c.id, c.course_name
        ORDER BY c.course_name
    ");
    $stmt->execute([$studentId, $teacherId]);
    $courseStats = $stmt->fetchAll();
    
    // Calculate course-wise percentages
    $courseWiseData = [];
    foreach ($courseStats as $course) {
        $courseTotal = $course['total_classes'];
        $courseEffectivePresent = $course['present'] + ($course['late'] * 0.5);
        $coursePercentage = $courseTotal > 0 ? round(($courseEffectivePresent / $courseTotal) * 100, 2) : 0;
        
        $courseWiseData[] = [
            'course_name' => $course['course_name'],
            'present' => $course['present'],
            'absent' => $course['absent'],
            'late' => $course['late'],
            'percentage' => $coursePercentage
        ];
    }
    
    // Prepare response data
    $response = [
        'overall_percentage' => $overallPercentage,
        'present_days' => $presentDays,
        'absent_days' => $absentDays,
        'late_days' => $lateDays,
        'total_classes' => $totalClasses,
        'course_wise' => $courseWiseData
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
