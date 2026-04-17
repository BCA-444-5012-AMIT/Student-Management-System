<?php
$pageTitle = 'Manage Teachers';
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$teachers = [];
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Add new teacher
            $firstName = isset($_POST['first_name']) ? cleanInput($_POST['first_name']) : '';
            $lastName = isset($_POST['last_name']) ? cleanInput($_POST['last_name']) : '';
            // Auto-generate employee ID
            $dateOfBirth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
            $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
            $phone = isset($_POST['phone']) ? cleanInput($_POST['phone']) : '';
            $address = isset($_POST['address']) ? cleanInput($_POST['address']) : '';
            $qualification = isset($_POST['qualification']) ? cleanInput($_POST['qualification']) : '';
            $specialization = isset($_POST['specialization']) ? cleanInput($_POST['specialization']) : '';
            $hireDate = isset($_POST['hire_date']) ? $_POST['hire_date'] : '';
            $username = isset($_POST['username']) ? cleanInput($_POST['username']) : '';
            $email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            // Validation
            if (empty($firstName)) $errors['first_name'] = 'First name is required';
            if (empty($lastName)) $errors['last_name'] = 'Last name is required';
            if (empty($dateOfBirth)) $errors['date_of_birth'] = 'Date of birth is required';
            if (empty($gender)) $errors['gender'] = 'Gender is required';
            if (empty($hireDate)) $errors['hire_date'] = 'Hire date is required';
            if (empty($username)) $errors['username'] = 'Username is required';
            if (empty($email)) $errors['email'] = 'Email is required';
            if (empty($password)) $errors['password'] = 'Password is required';
            if (empty($confirmPassword)) $errors['confirm_password'] = 'Password confirmation is required';
            if ($password !== $confirmPassword) $errors['confirm_password'] = 'Passwords do not match';
            if (strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters';
            
            // Email format validation - stricter validation
            if (!empty($email)) {
                // First check with PHP's built-in filter
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Please enter a valid email address';
                } else {
                    // Additional validation for stricter rules
                    $email = strtolower(trim($email));
                    
                    // Check for common invalid patterns
                    $invalidPatterns = [
                        '/^[0-9]/',              // Cannot start with number
                        '/\.\./',                 // Cannot have consecutive dots
                        '/\.$/',                  // Cannot end with dot
                        '/^\./',                  // Cannot start with dot
                        '/@.*@/',                 // Cannot have multiple @ symbols
                        '/\.@/',                  // Cannot have dot before @
                        '/@\./',                  // Cannot have dot immediately after @
                        '/\..*@/',                // Cannot have consecutive dots before @
                        '/@.*\.$/',               // Cannot end with dot after @
                    ];
                    
                    foreach ($invalidPatterns as $pattern) {
                        if (preg_match($pattern, $email)) {
                            $errors['email'] = 'Please enter a valid email address format';
                            break;
                        }
                    }
                    
                    // Check for valid domain extension
                    if (!isset($errors['email'])) {
                        $domain = substr(strrchr($email, "@"), 1);
                        if (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $domain)) {
                            $errors['email'] = 'Please enter a valid email domain';
                        }
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
                    
                    // Auto-generate employee ID
                    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(employee_id, 4) AS UNSIGNED)) as max_num FROM teachers WHERE employee_id LIKE 'EMP%'");
                    $result = $stmt->fetch();
                    $nextNum = $result['max_num'] + 1;
                    $employeeId = 'EMP' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                    
                    // Insert into users table
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'teacher')");
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->execute([$username, $email, $hashedPassword]);
                    $userId = $pdo->lastInsertId();
                    
                    // Insert into teachers table
                    $stmt = $pdo->prepare("
                        INSERT INTO teachers (user_id, first_name, last_name, employee_id, date_of_birth, gender, phone, address, qualification, specialization, hire_date) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$userId, $firstName, $lastName, $employeeId, $dateOfBirth, $gender, $phone, $address, $qualification, $specialization, $hireDate]);
                    
                    $pdo->commit();
                    setSuccessMessage('Teacher added successfully!');
                    header('Location: teachers.php');
                    exit();
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    if ($e->getCode() == 23000) {
                        $errors['duplicate'] = 'Username or email already exists';
                    } else {
                        $errors['database'] = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }
        
        elseif ($_POST['action'] === 'delete') {
            // Delete teacher
            $teacherId = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
            
            if ($teacherId > 0) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
                    $stmt->execute([$teacherId]);
                    setSuccessMessage('Teacher deleted successfully!');
                    header('Location: teachers.php');
                    exit();
                } catch (PDOException $e) {
                    setErrorMessage('Cannot delete teacher. They may have assigned courses or attendance records.');
                }
            }
        }
        
        elseif ($_POST['action'] === 'edit') {
            // Edit teacher
            $teacherId = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
            $firstName = isset($_POST['first_name']) ? cleanInput($_POST['first_name']) : '';
            $lastName = isset($_POST['last_name']) ? cleanInput($_POST['last_name']) : '';
            $dateOfBirth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
            $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
            $phone = isset($_POST['phone']) ? cleanInput($_POST['phone']) : '';
            $address = isset($_POST['address']) ? cleanInput($_POST['address']) : '';
            $qualification = isset($_POST['qualification']) ? cleanInput($_POST['qualification']) : '';
            $specialization = isset($_POST['specialization']) ? cleanInput($_POST['specialization']) : '';
            $hireDate = isset($_POST['hire_date']) ? $_POST['hire_date'] : '';
            $username = isset($_POST['username']) ? cleanInput($_POST['username']) : '';
            $email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';
            
            // Validation
            if (empty($firstName)) $errors['first_name'] = 'First name is required';
            if (empty($lastName)) $errors['last_name'] = 'Last name is required';
            if (empty($dateOfBirth)) $errors['date_of_birth'] = 'Date of birth is required';
            if (empty($gender)) $errors['gender'] = 'Gender is required';
            if (empty($hireDate)) $errors['hire_date'] = 'Hire date is required';
            if (empty($username)) $errors['username'] = 'Username is required';
            if (empty($email)) $errors['email'] = 'Email is required';
            
            // Email format validation
            if (!empty($email)) {
                // First check with PHP's built-in filter
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Please enter a valid email address';
                } else {
                    // Additional validation for stricter rules
                    $email = strtolower(trim($email));
                    
                    // Check for common invalid patterns
                    $invalidPatterns = [
                        '/^[0-9]/',              // Cannot start with number
                        '/\.\./',                 // Cannot have consecutive dots
                        '/\.$/',                  // Cannot end with dot
                        '/^\./',                  // Cannot start with dot
                        '/@.*@/',                 // Cannot have multiple @ symbols
                        '/\.@/',                  // Cannot have dot before @
                        '/@\./',                  // Cannot have dot immediately after @
                        '/\..*@/',                // Cannot have consecutive dots before @
                        '/@.*\.$/',               // Cannot end with dot after @
                    ];
                    
                    foreach ($invalidPatterns as $pattern) {
                        if (preg_match($pattern, $email)) {
                            $errors['email'] = 'Please enter a valid email address format';
                            break;
                        }
                    }
                    
                    // Check for valid domain extension
                    if (!isset($errors['email'])) {
                        $domain = substr(strrchr($email, "@"), 1);
                        if (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $domain)) {
                            $errors['email'] = 'Please enter a valid email domain';
                        }
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
                    
                    // Get user_id from teachers table
                    $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id = ?");
                    $stmt->execute([$teacherId]);
                    $teacher = $stmt->fetch();
                    
                    if ($teacher) {
                        // Update users table
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                        $stmt->execute([$username, $email, $teacher['user_id']]);
                        
                        // Update teachers table
                        $stmt = $pdo->prepare("
                            UPDATE teachers SET first_name = ?, last_name = ?, 
                            date_of_birth = ?, gender = ?, phone = ?, address = ?, qualification = ?, 
                            specialization = ?, hire_date = ? WHERE id = ?
                        ");
                        $stmt->execute([$firstName, $lastName, $dateOfBirth, $gender, $phone, $address, $qualification, $specialization, $hireDate, $teacherId]);
                        
                        $pdo->commit();
                        setSuccessMessage('Teacher updated successfully!');
                        header('Location: teachers.php');
                        exit();
                    }
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    if ($e->getCode() == 23000) {
                        $errors['duplicate'] = 'Username or email already exists';
                    } else {
                        $errors['database'] = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Fetch all teachers
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT t.*, u.username, u.email 
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
                <h2><i class="fas fa-chalkboard-teacher me-2"></i>Manage Teachers</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                    <i class="fas fa-plus me-2"></i>Add Teacher
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="teacherSearch" placeholder="Search teachers by name, username, email, phone, or specialization...">
                        <button class="btn btn-outline-secondary" type="button" id="clearTeacherSearch">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                    <small class="text-muted">Type to search teachers in real-time</small>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-end">
                        <span class="me-2">Total Teachers:</span>
                        <span class="badge bg-primary" id="teacherCount">0</span>
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
            <div id="teacherSearchResults" class="alert alert-info" style="display: none;">
                <i class="fas fa-info-circle me-2"></i>
                <span id="teacherSearchMessage"></span>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div id="noTeachersMessage" style="display: none;">
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>No teachers found</h5>
                            <p class="text-muted">Try adjusting your search criteria.</p>
                        </div>
                    </div>
                    
                    <div id="noTeacherDataMessage" style="display: none;">
                        <div class="text-center py-4">
                            <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                            <h5>No teachers found</h5>
                            <p class="text-muted">Start by adding your first teacher.</p>
                        </div>
                    </div>
                    
                    <?php if (!empty($teachers)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="teachersTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Specialization</th>
                                        <th>Hire Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr class="teacher-row" 
                                            data-id="<?php echo $teacher['id']; ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($teacher['first_name'] . ' ' . $teacher['last_name'])); ?>"
                                            data-username="<?php echo htmlspecialchars(strtolower($teacher['username'])); ?>"
                                            data-email="<?php echo htmlspecialchars(strtolower($teacher['email'])); ?>"
                                            data-phone="<?php echo htmlspecialchars($teacher['phone']); ?>"
                                            data-specialization="<?php echo htmlspecialchars(strtolower($teacher['specialization'])); ?>"
                                            data-search-text="<?php echo htmlspecialchars(strtolower($teacher['first_name'] . ' ' . $teacher['last_name'] . ' ' . $teacher['username'] . ' ' . $teacher['email'] . ' ' . $teacher['phone'] . ' ' . $teacher['specialization'])); ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 32px; height: 32px; font-size: 12px; font-weight: bold;">
                                                        <?php echo strtoupper(substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars(ucwords(strtolower($teacher['first_name'] . ' ' . $teacher['last_name']))); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($teacher['username']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="mailto:<?php echo htmlspecialchars($teacher['email']); ?>" 
                                                   class="text-decoration-none" 
                                                   title="Send email to <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    <?php echo htmlspecialchars(strtolower($teacher['email'])); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php 
                                                $phone = htmlspecialchars($teacher['phone']);
                                                if (!empty($phone)) {
                                                    echo '<i class="fas fa-phone me-1"></i>' . $phone;
                                                } else {
                                                    echo '<span class="text-muted">N/A</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $specialization = htmlspecialchars($teacher['specialization']);
                                                if (!empty($specialization)) {
                                                    echo '<span class="badge bg-primary me-1"><i class="fas fa-graduation-cap me-1"></i>' . ucwords(strtolower($specialization)) . '</span>';
                                                } else {
                                                    echo '<span class="text-muted">Not specified</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-dark">
                                                    <i class="fas fa-calendar-alt me-1"></i>
                                                    <?php echo formatDate($teacher['hire_date']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" onclick="generateReport(<?php echo $teacher['id']; ?>)" title="Generate Report">
                                                    <i class="fas fa-file-alt"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewTeacher(<?php echo $teacher['id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="editTeacher(<?php echo $teacher['id']; ?>)" title="Edit Teacher">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Are you sure you want to delete this teacher?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Teacher">
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

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Teacher</h5>
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
                                <input type="text" class="form-control" name="username" required>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="text-danger small"><?php echo $errors['username']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required 
                                       placeholder="Enter valid email address (e.g., teacher@school.com)"
                                       pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                       title="Please enter a valid email address (e.g., teacher@school.com)">
                                <small class="text-muted">Enter a valid email address (e.g., teacher@school.com)</small>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="text-danger small"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="addTeacherPassword" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleTeacherPassword">
                                        <i class="fas fa-eye" id="teacherPasswordIcon"></i>
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
                                    <input type="password" class="form-control" name="confirm_password" id="addTeacherConfirmPassword" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleTeacherConfirmPassword">
                                        <i class="fas fa-eye" id="teacherConfirmPasswordIcon"></i>
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
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" name="date_of_birth" 
                                       max="<?php echo date('Y-m-d', strtotime('-21 years')); ?>" required>
                                <small class="text-muted">Teacher must be at least 21 years old</small>
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
                                <label class="form-label">Qualification</label>
                                <input type="text" class="form-control" name="qualification">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Specialization</label>
                                <input type="text" class="form-control" name="specialization">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hire Date *</label>
                                <input type="date" class="form-control" name="hire_date" 
                                       max="<?php echo date('Y-m-d'); ?>" required>
                                <small class="text-muted">Cannot be a future date</small>
                                <?php if (isset($errors['hire_date'])): ?>
                                    <div class="text-danger small"><?php echo $errors['hire_date']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Teacher Modal -->
<div class="modal fade" id="viewTeacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Teacher Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="teacherDetails">
                    <!-- Teacher details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="editTeacherForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="teacher_id" id="editTeacherId">
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
                                <input type="email" class="form-control" name="email" id="editEmail" required 
                                       placeholder="Enter valid email address (e.g., teacher@school.com)"
                                       pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                       title="Please enter a valid email address (e.g., teacher@school.com)">
                                <small class="text-muted">Enter a valid email address (e.g., teacher@school.com)</small>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="text-danger small"><?php echo $errors['email']; ?></div>
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
                                <label class="form-label">Qualification</label>
                                <input type="text" class="form-control" name="qualification" id="editQualification">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Specialization</label>
                                <input type="text" class="form-control" name="specialization" id="editSpecialization">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hire Date *</label>
                                <input type="date" class="form-control" name="hire_date" id="editHireDate" required>
                                <?php if (isset($errors['hire_date'])): ?>
                                    <div class="text-danger small"><?php echo $errors['hire_date']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store teacher data for view and edit functions
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

function viewTeacher(id) {
    const teacher = teachersData.find(t => t.id == id);
    if (teacher) {
        const detailsHtml = `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Account Information</h6>
                    <p><strong>Username:</strong> ${teacher.username}</p>
                    <p><strong>Email:</strong> ${teacher.email}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Personal Information</h6>
                    <p><strong>Name:</strong> ${teacher.first_name} ${teacher.last_name}</p>
                    <p><strong>Date of Birth:</strong> ${formatDate(teacher.date_of_birth)}</p>
                    <p><strong>Gender:</strong> ${teacher.gender.charAt(0).toUpperCase() + teacher.gender.slice(1)}</p>
                    <p><strong>Phone:</strong> ${teacher.phone || 'N/A'}</p>
                    <p><strong>Address:</strong> ${teacher.address || 'N/A'}</p>
                    <p><strong>Qualification:</strong> ${teacher.qualification || 'N/A'}</p>
                    <p><strong>Specialization:</strong> ${teacher.specialization || 'N/A'}</p>
                    <p><strong>Hire Date:</strong> ${formatDate(teacher.hire_date)}</p>
                </div>
            </div>
        `;
        document.getElementById('teacherDetails').innerHTML = detailsHtml;
        new bootstrap.Modal(document.getElementById('viewTeacherModal')).show();
    }
}

function editTeacher(id) {
    const teacher = teachersData.find(t => t.id == id);
    if (teacher) {
        // Populate form fields
        document.getElementById('editTeacherId').value = teacher.id;
        document.getElementById('editUsername').value = teacher.username;
        document.getElementById('editEmail').value = teacher.email;
        document.getElementById('editFirstName').value = teacher.first_name;
        document.getElementById('editLastName').value = teacher.last_name;
        document.getElementById('editDateOfBirth').value = teacher.date_of_birth;
        document.getElementById('editGender').value = teacher.gender;
        document.getElementById('editPhone').value = teacher.phone || '';
        document.getElementById('editAddress').value = teacher.address || '';
        document.getElementById('editQualification').value = teacher.qualification || '';
        document.getElementById('editSpecialization').value = teacher.specialization || '';
        document.getElementById('editHireDate').value = teacher.hire_date;
        
        new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
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

// Email validation function - stricter validation
function validateEmail(input) {
    const email = input.value.toLowerCase().trim();
    
    // Basic email regex
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailRegex.test(email)) {
        input.setCustomValidity('Please enter a valid email address');
        return;
    }
    
    // Additional validation rules
    const invalidPatterns = [
        /^[0-9]/,               // Cannot start with number
        /\.\./,                  // Cannot have consecutive dots
        /\.$/,                   // Cannot end with dot
        /^\./,                   // Cannot start with dot
        /@.*@/,                  // Cannot have multiple @ symbols
        /\.@/,                   // Cannot have dot before @
        /@\./,                   // Cannot have dot immediately after @
        /\..*@/,                 // Cannot have consecutive dots before @
        /@.*\.$/,                // Cannot end with dot after @
    ];
    
    for (const pattern of invalidPatterns) {
        if (pattern.test(email)) {
            input.setCustomValidity('Please enter a valid email address format');
            return;
        }
    }
    
    // Check for valid domain extension
    const domain = email.split('@')[1];
    if (!domain || !/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(domain)) {
        input.setCustomValidity('Please enter a valid email domain');
        return;
    }
    
    // Check domain has at least one dot and valid extension
    const domainParts = domain.split('.');
    if (domainParts.length < 2 || domainParts[domainParts.length - 1].length < 2) {
        input.setCustomValidity('Please enter a valid email domain extension');
        return;
    }
    
    // If all checks pass
    input.setCustomValidity('');
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
        // Real-time validation
        input.addEventListener('input', function() {
            validateEmail(this);
        });
        
        // Validation on blur (when user leaves field)
        input.addEventListener('blur', function() {
            validateEmail(this);
        });
        
        // Validation on change (for programmatic changes)
        input.addEventListener('change', function() {
            validateEmail(this);
        });
    });
    
    // Form submission validation
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const emailInput = form.querySelector('input[type="email"]');
            if (emailInput) {
                validateEmail(emailInput);
                if (!emailInput.checkValidity()) {
                    e.preventDefault();
                    emailInput.focus();
                    return false;
                }
            }
        });
    });
    
    // Teacher Search Functionality
    const teacherSearchInput = document.getElementById('teacherSearch');
    const clearTeacherButton = document.getElementById('clearTeacherSearch');
    const teacherRows = document.querySelectorAll('.teacher-row');
    const teacherCount = document.getElementById('teacherCount');
    const teacherSearchResults = document.getElementById('teacherSearchResults');
    const teacherSearchMessage = document.getElementById('teacherSearchMessage');
    const noTeachersMessage = document.getElementById('noTeachersMessage');
    const noTeacherDataMessage = document.getElementById('noTeacherDataMessage');
    const teachersTable = document.getElementById('teachersTable');
    
    // Initialize teacher count
    if (teacherCount) {
        teacherCount.textContent = teacherRows.length;
    }
    
    // Show/hide appropriate messages based on data availability
    if (teacherRows.length === 0) {
        if (noTeacherDataMessage) noTeacherDataMessage.style.display = 'block';
        if (teachersTable) teachersTable.style.display = 'none';
    }
    
    // Search functionality
    if (teacherSearchInput) {
        teacherSearchInput.addEventListener('input', function() {
            performTeacherSearch();
        });
        
        teacherSearchInput.addEventListener('keyup', function(e) {
            // Clear search on Escape key
            if (e.key === 'Escape') {
                clearTeacherSearch();
            }
        });
    }
    
    // Clear search functionality
    if (clearTeacherButton) {
        clearTeacherButton.addEventListener('click', clearTeacherSearch);
    }
    
    function performTeacherSearch() {
        const searchTerm = teacherSearchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        let totalTeachers = teacherRows.length;
        
        teacherRows.forEach(function(row) {
            const searchText = row.getAttribute('data-search-text');
            const name = row.getAttribute('data-name');
            const username = row.getAttribute('data-username');
            const email = row.getAttribute('data-email');
            const phone = row.getAttribute('data-phone');
            const specialization = row.getAttribute('data-specialization');
            
            // Check if search term matches any field
            const matches = searchTerm === '' || 
                           searchText.includes(searchTerm) ||
                           name.includes(searchTerm) ||
                           username.includes(searchTerm) ||
                           email.includes(searchTerm) ||
                           phone.includes(searchTerm) ||
                           specialization.includes(searchTerm);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update UI based on search results
        updateTeacherSearchUI(searchTerm, visibleCount, totalTeachers);
    }
    
    function updateTeacherSearchUI(searchTerm, visibleCount, totalTeachers) {
        // Update teacher count
        if (teacherCount) {
            teacherCount.textContent = visibleCount;
        }
        
        // Show/hide search results message
        if (teacherSearchResults && teacherSearchMessage) {
            if (searchTerm !== '') {
                teacherSearchResults.style.display = 'block';
                if (visibleCount > 0) {
                    teacherSearchResults.className = 'alert alert-success';
                    teacherSearchMessage.innerHTML = `<i class="fas fa-check-circle me-2"></i>Found ${visibleCount} teacher${visibleCount !== 1 ? 's' : ''} matching "${searchTerm}"`;
                } else {
                    teacherSearchResults.className = 'alert alert-warning';
                    teacherSearchMessage.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>No teachers found matching "${searchTerm}"`;
                }
            } else {
                teacherSearchResults.style.display = 'none';
            }
        }
        
        // Show/hide appropriate messages
        if (visibleCount === 0) {
            if (searchTerm !== '') {
                // Search returned no results
                if (noTeachersMessage) noTeachersMessage.style.display = 'block';
                if (noTeacherDataMessage) noTeacherDataMessage.style.display = 'none';
                if (teachersTable) teachersTable.style.display = 'none';
            } else {
                // No teachers at all
                if (noTeacherDataMessage) noTeacherDataMessage.style.display = 'block';
                if (noTeachersMessage) noTeachersMessage.style.display = 'none';
                if (teachersTable) teachersTable.style.display = 'none';
            }
        } else {
            // Teachers found
            if (noTeachersMessage) noTeachersMessage.style.display = 'none';
            if (noTeacherDataMessage) noTeacherDataMessage.style.display = 'none';
            if (teachersTable) teachersTable.style.display = 'table';
        }
    }
    
    function clearTeacherSearch() {
        if (teacherSearchInput) {
            teacherSearchInput.value = '';
            performTeacherSearch();
            teacherSearchInput.focus();
        }
    }
    
    // Generate teacher report
    window.generateReport = function(teacherId) {
        // Open report in new window/tab
        const reportUrl = 'teacher_report.php?id=' + teacherId;
        window.open(reportUrl, '_blank', 'width=1000,height=800,scrollbars=yes,resizable=yes');
    };
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
