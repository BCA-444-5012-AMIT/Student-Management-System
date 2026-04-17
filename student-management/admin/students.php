<?php
$pageTitle = 'Manage Students';
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$students = [];
$errors = [];
$successMessage = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Add new student
            $firstName = isset($_POST['first_name']) ? cleanInput($_POST['first_name']) : '';
            $lastName = isset($_POST['last_name']) ? cleanInput($_POST['last_name']) : '';
            $rollNumber = isset($_POST['roll_number']) ? cleanInput($_POST['roll_number']) : '';
            $dateOfBirth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
            $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
            $phone = isset($_POST['phone']) ? cleanInput($_POST['phone']) : '';
            $address = isset($_POST['address']) ? cleanInput($_POST['address']) : '';
            $enrollmentDate = isset($_POST['enrollment_date']) ? $_POST['enrollment_date'] : '';
            $username = isset($_POST['username']) ? cleanInput($_POST['username']) : '';
            $email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            // Validation
            if (empty($firstName)) $errors['first_name'] = 'First name is required';
            if (empty($lastName)) $errors['last_name'] = 'Last name is required';
            if (empty($rollNumber)) $errors['roll_number'] = 'Roll number is required';
            if (empty($dateOfBirth)) $errors['date_of_birth'] = 'Date of birth is required';
            if (empty($gender)) $errors['gender'] = 'Gender is required';
            if (empty($enrollmentDate)) $errors['enrollment_date'] = 'Enrollment date is required';
            if (empty($username)) $errors['username'] = 'Username is required';
            if (empty($email)) $errors['email'] = 'Email is required';
            if (empty($password)) $errors['password'] = 'Password is required';
            if (empty($confirmPassword)) $errors['confirm_password'] = 'Password confirmation is required';
            if ($password !== $confirmPassword) $errors['confirm_password'] = 'Passwords do not match';
            if (strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters';
            
            // Username format validation (alphanumeric, underscores, hyphens, 3-20 characters)
            if (!empty($username)) {
                if (!preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username)) {
                    $errors['username'] = 'Username must be 3-20 characters and contain only letters, numbers, underscores, or hyphens';
                }
            }
            
            // Email format validation (strict)
            if (!empty($email)) {
                // Remove any extra spaces
                $email = trim($email);
                
                // Check basic format with filter_var
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Please enter a valid email address (e.g., user@example.com)';
                } else {
                    // Additional strict validation
                    $emailRegex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
                    if (!preg_match($emailRegex, $email)) {
                        $errors['email'] = 'Email format is invalid. Use format: user@domain.com';
                    } elseif (strlen($email) > 254) {
                        $errors['email'] = 'Email address is too long (maximum 254 characters)';
                    }
                }
            }
            
            // Date of birth validation (must be at least 16 years old and not future date)
            if (!empty($dateOfBirth)) {
                $dobTimestamp = strtotime($dateOfBirth);
                $todayTimestamp = strtotime(date('Y-m-d'));
                $minAgeTimestamp = strtotime('-16 years');
                
                if ($dobTimestamp > $todayTimestamp) {
                    $errors['date_of_birth'] = 'Date of birth cannot be in the future';
                } elseif ($dobTimestamp > $minAgeTimestamp) {
                    $errors['date_of_birth'] = 'Student must be at least 16 years old';
                }
            }
            
            // Enrollment date validation (cannot be future date)
            if (!empty($enrollmentDate)) {
                $enrollmentTimestamp = strtotime($enrollmentDate);
                $todayTimestamp = strtotime(date('Y-m-d'));
                
                if ($enrollmentTimestamp > $todayTimestamp) {
                    $errors['enrollment_date'] = 'Enrollment date cannot be in the future';
                }
                
                // Enrollment date should not be before date of birth
                if (!empty($dateOfBirth)) {
                    $dobTimestamp = strtotime($dateOfBirth);
                    if ($enrollmentTimestamp < $dobTimestamp) {
                        $errors['enrollment_date'] = 'Enrollment date cannot be before date of birth';
                    }
                }
            }
            
            // Phone validation - only numbers allowed
            if (!empty($phone)) {
                if (!preg_match('/^[0-9]{10}$/', $phone)) {
                    $errors['phone'] = 'Phone number must be exactly 10 digits';
                }
            }
            
            if (empty($errors)) {
                try {
                    $pdo = getDBConnection();
                    $pdo->beginTransaction();
                    
                    // Insert into users table
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')");
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->execute([$username, $email, $hashedPassword]);
                    $userId = $pdo->lastInsertId();
                    
                    // Insert into students table
                    $stmt = $pdo->prepare("
                        INSERT INTO students (user_id, first_name, last_name, roll_number, date_of_birth, gender, phone, address, enrollment_date) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$userId, $firstName, $lastName, $rollNumber, $dateOfBirth, $gender, $phone, $address, $enrollmentDate]);
                    $studentId = $pdo->lastInsertId();
                    
                    // Insert into password history
                    $stmt = $pdo->prepare("
                        INSERT INTO password_history (user_id, old_password, new_password, changed_by, change_reason) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$userId, 'NEW_USER', $hashedPassword, getUserId(), 'Account created']);
                    
                    $pdo->commit();
                    setSuccessMessage('Student added successfully!');
                    header('Location: students.php');
                    exit();
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    if ($e->getCode() == 23000) {
                        $errors['duplicate'] = 'Username, email, or roll number already exists';
                    } else {
                        $errors['database'] = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }
        
        elseif ($_POST['action'] === 'delete') {
            // Delete student
            $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
            
            if ($studentId > 0) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
                    $stmt->execute([$studentId]);
                    setSuccessMessage('Student deleted successfully!');
                    header('Location: students.php');
                    exit();
                } catch (PDOException $e) {
                    setErrorMessage('Cannot delete student. They may have enrollments or attendance records.');
                }
            }
        }
        
        elseif ($_POST['action'] === 'edit') {
            // Edit student
            $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
            $firstName = isset($_POST['first_name']) ? cleanInput($_POST['first_name']) : '';
            $lastName = isset($_POST['last_name']) ? cleanInput($_POST['last_name']) : '';
            $rollNumber = isset($_POST['roll_number']) ? cleanInput($_POST['roll_number']) : '';
            $dateOfBirth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
            $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
            $phone = isset($_POST['phone']) ? cleanInput($_POST['phone']) : '';
            $address = isset($_POST['address']) ? cleanInput($_POST['address']) : '';
            $enrollmentDate = isset($_POST['enrollment_date']) ? $_POST['enrollment_date'] : '';
            $username = isset($_POST['username']) ? cleanInput($_POST['username']) : '';
            $email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';
            
            // Validation
            if (empty($firstName)) $errors['first_name'] = 'First name is required';
            if (empty($lastName)) $errors['last_name'] = 'Last name is required';
            if (empty($rollNumber)) $errors['roll_number'] = 'Roll number is required';
            if (empty($dateOfBirth)) $errors['date_of_birth'] = 'Date of birth is required';
            if (empty($gender)) $errors['gender'] = 'Gender is required';
            if (empty($enrollmentDate)) $errors['enrollment_date'] = 'Enrollment date is required';
            if (empty($username)) $errors['username'] = 'Username is required';
            if (empty($email)) $errors['email'] = 'Email is required';
            
            // Email format validation
            if (!empty($email)) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Please enter a valid email address';
                }
            }
            
            // Phone validation - only numbers allowed
            if (!empty($phone)) {
                if (!preg_match('/^[0-9]{10}$/', $phone)) {
                    $errors['phone'] = 'Phone number must be exactly 10 digits';
                }
            }
            
            if (empty($errors)) {
                try {
                    $pdo = getDBConnection();
                    $pdo->beginTransaction();
                    
                    // Get user_id from students table
                    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
                    $stmt->execute([$studentId]);
                    $student = $stmt->fetch();
                    
                    if ($student) {
                        // Get current password for history
                        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                        $stmt->execute([$student['user_id']]);
                        $currentUser = $stmt->fetch();
                        $currentPassword = $currentUser ? $currentUser['password'] : '';
                        
                        // Update users table
                        if (!empty($password)) {
                            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            $stmt->execute([$username, $email, $hashedPassword, $student['user_id']]);
                            
                            // Insert into password history
                            $stmt = $pdo->prepare("
                                INSERT INTO password_history (user_id, old_password, new_password, changed_by, change_reason) 
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$student['user_id'], $currentPassword, $hashedPassword, getUserId(), 'Password changed by admin']);
                        } else {
                            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                            $stmt->execute([$username, $email, $student['user_id']]);
                        }
                        
                        // Update students table
                        $stmt = $pdo->prepare("
                            UPDATE students SET first_name = ?, last_name = ?, roll_number = ?, 
                            date_of_birth = ?, gender = ?, phone = ?, address = ?, enrollment_date = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$firstName, $lastName, $rollNumber, $dateOfBirth, $gender, $phone, $address, $enrollmentDate, $studentId]);
                        
                        $pdo->commit();
                        setSuccessMessage('Student updated successfully!');
                        header('Location: students.php');
                        exit();
                    }
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    if ($e->getCode() == 23000) {
                        $errors['duplicate'] = 'Username, email, or roll number already exists';
                    } else {
                        $errors['database'] = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Fetch all students
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT s.*, u.username, u.email 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        ORDER BY s.roll_number
    ");
    $students = $stmt->fetchAll();
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
                <h2><i class="fas fa-user-graduate me-2"></i>Manage Students</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus me-2"></i>Add Student
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="studentSearch" placeholder="Search students by name, roll number, username, email, or phone...">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                    <small class="text-muted">Type to search students in real-time</small>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-end">
                        <span class="me-2">Total Students:</span>
                        <span class="badge bg-primary" id="studentCount">0</span>
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
            <div id="searchResults" class="alert alert-info" style="display: none;">
                <i class="fas fa-info-circle me-2"></i>
                <span id="searchMessage"></span>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div id="noStudentsMessage" style="display: none;">
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>No students found</h5>
                            <p class="text-muted">Try adjusting your search criteria.</p>
                        </div>
                    </div>
                    
                    <div id="noDataMessage" style="display: none;">
                        <div class="text-center py-4">
                            <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                            <h5>No students found</h5>
                            <p class="text-muted">Start by adding your first student.</p>
                        </div>
                    </div>
                    
                    <?php if (!empty($students)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="studentsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Roll Number</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Enrollment Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr class="student-row" 
                                            data-id="<?php echo $student['id']; ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($student['first_name'] . ' ' . $student['last_name'])); ?>"
                                            data-roll="<?php echo htmlspecialchars(strtolower($student['roll_number'])); ?>"
                                            data-username="<?php echo htmlspecialchars(strtolower($student['username'])); ?>"
                                            data-email="<?php echo htmlspecialchars(strtolower($student['email'])); ?>"
                                            data-phone="<?php echo htmlspecialchars($student['phone']); ?>"
                                            data-search-text="<?php echo htmlspecialchars(strtolower($student['first_name'] . ' ' . $student['last_name'] . ' ' . $student['roll_number'] . ' ' . $student['username'] . ' ' . $student['email'] . ' ' . $student['phone'])); ?>">
                                            <td><?php echo $student['id']; ?></td>
                                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                            <td><?php echo formatDate($student['enrollment_date']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" onclick="generateReport(<?php echo $student['id']; ?>)" title="Generate Report">
                                                    <i class="fas fa-file-alt"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewStudent(<?php echo $student['id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="editStudent(<?php echo $student['id']; ?>)" title="Edit Student">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this student?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Student">
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

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Account Information</h6>
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" 
                                       pattern="[a-zA-Z0-9_-]{3,20}" 
                                       placeholder="3-20 characters: letters, numbers, _, -" required>
                                <small class="text-muted">Use letters, numbers, underscores, or hyphens (3-20 chars)</small>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="text-danger small"><?php echo $errors['username']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" 
                                       placeholder="user@example.com" 
                                       pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                       title="Please enter a valid email address (e.g., user@example.com)"
                                       maxlength="254" required>
                                <small class="text-muted">Format: user@domain.com (e.g., john.doe@example.com)</small>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="text-danger small"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="addPassword" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye" id="passwordIcon"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Minimum 6 characters</small>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="text-danger small"><?php echo $errors['password']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="confirm_password" id="addConfirmPassword" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Re-enter your password</small>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="text-danger small"><?php echo $errors['confirm_password']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Personal Information</h6>
                            <div class="mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" required>
                                <?php if (isset($errors['first_name'])): ?>
                                    <div class="text-danger small"><?php echo $errors['first_name']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" required>
                                <?php if (isset($errors['last_name'])): ?>
                                    <div class="text-danger small"><?php echo $errors['last_name']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Roll Number *</label>
                                <input type="text" class="form-control" name="roll_number" required>
                                <?php if (isset($errors['roll_number'])): ?>
                                    <div class="text-danger small"><?php echo $errors['roll_number']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" name="date_of_birth" 
                                       max="<?php echo date('Y-m-d'); ?>" required>
                                <small class="text-muted">Cannot be a future date</small>
                                <?php if (isset($errors['date_of_birth'])): ?>
                                    <div class="text-danger small"><?php echo $errors['date_of_birth']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gender *</label>
                                <select class="form-select" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                <?php if (isset($errors['gender'])): ?>
                                    <div class="text-danger small"><?php echo $errors['gender']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" pattern="[0-9]{10}" maxlength="10" placeholder="Enter 10-digit mobile number" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                                <small class="text-muted">Enter 10-digit mobile number only</small>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="text-danger small"><?php echo $errors['phone']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Enrollment Date *</label>
                                <input type="date" class="form-control" name="enrollment_date" 
                                       max="<?php echo date('Y-m-d'); ?>" required>
                                <small class="text-muted">Cannot be a future date</small>
                                <?php if (isset($errors['enrollment_date'])): ?>
                                    <div class="text-danger small"><?php echo $errors['enrollment_date']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="editStudentForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="student_id" id="editStudentId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Account Information</h6>
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" id="editUsername" required>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="text-danger small"><?php echo $errors['username']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" id="editEmail" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="text-danger small"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password (leave blank to keep current)</label>
                                <input type="password" class="form-control" name="password" id="editPassword" placeholder="Enter new password or leave empty">
                                <?php if (isset($errors['password'])): ?>
                                    <div class="text-danger small"><?php echo $errors['password']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Personal Information</h6>
                            <div class="mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" id="editFirstName" required>
                                <?php if (isset($errors['first_name'])): ?>
                                    <div class="text-danger small"><?php echo $errors['first_name']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" id="editLastName" required>
                                <?php if (isset($errors['last_name'])): ?>
                                    <div class="text-danger small"><?php echo $errors['last_name']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Roll Number *</label>
                                <input type="text" class="form-control" name="roll_number" id="editRollNumber" required>
                                <?php if (isset($errors['roll_number'])): ?>
                                    <div class="text-danger small"><?php echo $errors['roll_number']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" name="date_of_birth" id="editDateOfBirth" required>
                                <?php if (isset($errors['date_of_birth'])): ?>
                                    <div class="text-danger small"><?php echo $errors['date_of_birth']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gender *</label>
                                <select class="form-select" name="gender" id="editGender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                <?php if (isset($errors['gender'])): ?>
                                    <div class="text-danger small"><?php echo $errors['gender']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" id="editPhone" pattern="[0-9]{10}" maxlength="10" placeholder="Enter 10-digit mobile number" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                                <small class="text-muted">Enter 10-digit mobile number only</small>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="text-danger small"><?php echo $errors['phone']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" id="editAddress" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Enrollment Date *</label>
                                <input type="date" class="form-control" name="enrollment_date" id="editEnrollmentDate" required>
                                <?php if (isset($errors['enrollment_date'])): ?>
                                    <div class="text-danger small"><?php echo $errors['enrollment_date']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store student data for view and edit functions
const studentsData = <?php echo json_encode($students); ?>;

// JavaScript date format function
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric' 
    });
}

function viewStudent(id) {
    const student = studentsData.find(s => s.id == id);
    if (student) {
        const detailsHtml = `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Account Information</h6>
                    <p><strong>Username:</strong> ${student.username}</p>
                    <p><strong>Email:</strong> ${student.email}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Personal Information</h6>
                    <p><strong>Name:</strong> ${student.first_name} ${student.last_name}</p>
                    <p><strong>Roll Number:</strong> ${student.roll_number}</p>
                    <p><strong>Date of Birth:</strong> ${formatDate(student.date_of_birth)}</p>
                    <p><strong>Gender:</strong> ${student.gender.charAt(0).toUpperCase() + student.gender.slice(1)}</p>
                    <p><strong>Phone:</strong> ${student.phone || 'N/A'}</p>
                    <p><strong>Address:</strong> ${student.address || 'N/A'}</p>
                    <p><strong>Enrollment Date:</strong> ${formatDate(student.enrollment_date)}</p>
                </div>
            </div>
        `;
        document.getElementById('studentDetails').innerHTML = detailsHtml;
        new bootstrap.Modal(document.getElementById('viewStudentModal')).show();
    }
}

function editStudent(id) {
    const student = studentsData.find(s => s.id == id);
    if (student) {
        // Wait for DOM to be ready
        setTimeout(() => {
            // Populate form fields
            document.getElementById('editStudentId').value = student.id;
            document.getElementById('editUsername').value = student.username;
            document.getElementById('editEmail').value = student.email;
            document.getElementById('editFirstName').value = student.first_name;
            document.getElementById('editLastName').value = student.last_name;
            document.getElementById('editRollNumber').value = student.roll_number;
            document.getElementById('editDateOfBirth').value = student.date_of_birth;
            document.getElementById('editGender').value = student.gender;
            document.getElementById('editPhone').value = student.phone || '';
            document.getElementById('editAddress').value = student.address || '';
            document.getElementById('editEnrollmentDate').value = student.enrollment_date;
            document.getElementById('editPassword').value = ''; // Clear password field for security
            
            new bootstrap.Modal(document.getElementById('editStudentModal')).show();
        }, 100);
    }
}

function confirmDelete(message) {
    return confirm(message);
}

// Phone number validation function
function validatePhoneNumber(input) {
    // Remove any non-digit characters
    input.value = input.value.replace(/\D/g, '');
    
    // Limit to 10 digits
    if (input.value.length > 10) {
        input.value = input.value.slice(0, 10);
    }
}

// Email validation function
function validateEmail(input) {
    const email = input.value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailRegex.test(email)) {
        input.setCustomValidity('Please enter a valid email address');
    } else {
        input.setCustomValidity('');
    }
}

// Add validation to inputs
document.addEventListener('DOMContentLoaded', function() {
    // Phone validation
    const phoneInputs = document.querySelectorAll('input[name="phone"]');
    phoneInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            validatePhoneNumber(this);
        });
    });
    
    // Email validation
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            validateEmail(this);
        });
        
        input.addEventListener('blur', function() {
            validateEmail(this);
        });
    });
    
    // Student Search Functionality
    const searchInput = document.getElementById('studentSearch');
    const clearButton = document.getElementById('clearSearch');
    const studentRows = document.querySelectorAll('.student-row');
    const studentCount = document.getElementById('studentCount');
    const searchResults = document.getElementById('searchResults');
    const searchMessage = document.getElementById('searchMessage');
    const noStudentsMessage = document.getElementById('noStudentsMessage');
    const noDataMessage = document.getElementById('noDataMessage');
    const studentsTable = document.getElementById('studentsTable');
    
    // Initialize student count
    if (studentCount) {
        studentCount.textContent = studentRows.length;
    }
    
    // Show/hide appropriate messages based on data availability
    if (studentRows.length === 0) {
        if (noDataMessage) noDataMessage.style.display = 'block';
        if (studentsTable) studentsTable.style.display = 'none';
    }
    
    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            performSearch();
        });
        
        searchInput.addEventListener('keyup', function(e) {
            // Clear search on Escape key
            if (e.key === 'Escape') {
                clearSearch();
            }
        });
    }
    
    // Clear search functionality
    if (clearButton) {
        clearButton.addEventListener('click', clearSearch);
    }
    
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        let totalStudents = studentRows.length;
        
        studentRows.forEach(function(row) {
            const searchText = row.getAttribute('data-search-text');
            const name = row.getAttribute('data-name');
            const rollNumber = row.getAttribute('data-roll');
            const username = row.getAttribute('data-username');
            const email = row.getAttribute('data-email');
            const phone = row.getAttribute('data-phone');
            
            // Check if search term matches any field
            const matches = searchTerm === '' || 
                           searchText.includes(searchTerm) ||
                           name.includes(searchTerm) ||
                           rollNumber.includes(searchTerm) ||
                           username.includes(searchTerm) ||
                           email.includes(searchTerm) ||
                           phone.includes(searchTerm);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update UI based on search results
        updateSearchUI(searchTerm, visibleCount, totalStudents);
    }
    
    function updateSearchUI(searchTerm, visibleCount, totalStudents) {
        // Update student count
        if (studentCount) {
            studentCount.textContent = visibleCount;
        }
        
        // Show/hide search results message
        if (searchResults && searchMessage) {
            if (searchTerm !== '') {
                searchResults.style.display = 'block';
                if (visibleCount > 0) {
                    searchResults.className = 'alert alert-success';
                    searchMessage.innerHTML = `<i class="fas fa-check-circle me-2"></i>Found ${visibleCount} student${visibleCount !== 1 ? 's' : ''} matching "${searchTerm}"`;
                } else {
                    searchResults.className = 'alert alert-warning';
                    searchMessage.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>No students found matching "${searchTerm}"`;
                }
            } else {
                searchResults.style.display = 'none';
            }
        }
        
        // Show/hide appropriate messages
        if (visibleCount === 0) {
            if (searchTerm !== '') {
                // Search returned no results
                if (noStudentsMessage) noStudentsMessage.style.display = 'block';
                if (noDataMessage) noDataMessage.style.display = 'none';
                if (studentsTable) studentsTable.style.display = 'none';
            } else {
                // No students at all
                if (noDataMessage) noDataMessage.style.display = 'block';
                if (noStudentsMessage) noStudentsMessage.style.display = 'none';
                if (studentsTable) studentsTable.style.display = 'none';
            }
        } else {
            // Students found
            if (noStudentsMessage) noStudentsMessage.style.display = 'none';
            if (noDataMessage) noDataMessage.style.display = 'none';
            if (studentsTable) studentsTable.style.display = 'table';
        }
    }
    
    function clearSearch() {
        if (searchInput) {
            searchInput.value = '';
            performSearch();
            searchInput.focus();
        }
    }
    
    // Generate student report
    window.generateReport = function(studentId) {
        // Open report in new window/tab
        const reportUrl = 'student_report.php?id=' + studentId;
        window.open(reportUrl, '_blank', 'width=1000,height=800,scrollbars=yes,resizable=yes');
    };
    
    // Check if we need to auto-edit a student (coming from student search)
    const editStudentId = sessionStorage.getItem('editStudentId');
    if (editStudentId) {
        // Clear the stored ID
        sessionStorage.removeItem('editStudentId');
        
        // Wait a bit for the page to fully load, then trigger edit
        setTimeout(() => {
            if (typeof editStudent === 'function') {
                editStudent(editStudentId);
            }
        }, 500);
    }
    
    // Password visibility toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('addPassword');
    const passwordIcon = document.getElementById('passwordIcon');
    
    if (togglePassword && passwordInput && passwordIcon) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            if (type === 'password') {
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            } else {
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            }
        });
    }
    
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordInput = document.getElementById('addConfirmPassword');
    const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');
    
    if (toggleConfirmPassword && confirmPasswordInput && confirmPasswordIcon) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            
            // Toggle icon
            if (type === 'password') {
                confirmPasswordIcon.classList.remove('fa-eye-slash');
                confirmPasswordIcon.classList.add('fa-eye');
            } else {
                confirmPasswordIcon.classList.remove('fa-eye');
                confirmPasswordIcon.classList.add('fa-eye-slash');
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
