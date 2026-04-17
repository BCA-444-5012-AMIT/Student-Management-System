<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');

$teacherInfo = [];
$errors = [];
$successMessage = '';

// Get teacher information
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT t.*, u.username, u.email 
        FROM teachers t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.user_id = ?
    ");
    $stmt->execute([getUserId()]);
    $teacherInfo = $stmt->fetch();
    
} catch (PDOException $e) {
    setErrorMessage('Database error: ' . $e->getMessage());
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $firstName = isset($_POST['first_name']) ? cleanInput($_POST['first_name']) : '';
        $lastName = isset($_POST['last_name']) ? cleanInput($_POST['last_name']) : '';
        $phone = isset($_POST['phone']) ? cleanInput($_POST['phone']) : '';
        $address = isset($_POST['address']) ? cleanInput($_POST['address']) : '';
        $qualification = isset($_POST['qualification']) ? cleanInput($_POST['qualification']) : '';
        $specialization = isset($_POST['specialization']) ? cleanInput($_POST['specialization']) : '';
        
        // Validation
        if (empty($firstName)) $errors['first_name'] = 'First name is required';
        if (empty($lastName)) $errors['last_name'] = 'Last name is required';
        
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    UPDATE teachers 
                    SET first_name = ?, last_name = ?, phone = ?, address = ?, 
                        qualification = ?, specialization = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                ");
                $stmt->execute([$firstName, $lastName, $phone, $address, $qualification, $specialization, getUserId()]);
                
                setSuccessMessage('Profile updated successfully!');
                header('Location: profile.php');
                exit();
                
            } catch (PDOException $e) {
                setErrorMessage('Database error: ' . $e->getMessage());
            }
        }
    }
    
    elseif ($_POST['action'] === 'update_password') {
        $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // Validation
        if (empty($currentPassword)) $errors['current_password'] = 'Current password is required';
        if (empty($newPassword)) $errors['new_password'] = 'New password is required';
        if ($newPassword !== $confirmPassword) $errors['confirm_password'] = 'Passwords do not match';
        if (strlen($newPassword) < 6) $errors['new_password_length'] = 'Password must be at least 6 characters';
        
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([getUserId()]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($currentPassword, $user['password'])) {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, getUserId()]);
                    
                    setSuccessMessage('Password updated successfully!');
                    header('Location: profile.php');
                    exit();
                } else {
                    $errors['current_password_invalid'] = 'Current password is incorrect';
                }
                
            } catch (PDOException $e) {
                setErrorMessage('Database error: ' . $e->getMessage());
            }
        }
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
                <h2><i class="fas fa-user me-2"></i>My Profile</h2>
            </div>
            
            <?php if ($teacherInfo): ?>
                <!-- Profile Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($teacherInfo['username']); ?>" readonly>
                                        <small class="text-muted">Username cannot be changed</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($teacherInfo['email']); ?>" readonly>
                                        <small class="text-muted">Email cannot be changed</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($teacherInfo['employee_id']); ?>" readonly>
                                        <small class="text-muted">Employee ID cannot be changed</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">First Name *</label>
                                        <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($teacherInfo['first_name']); ?>" required>
                                        <?php if (isset($errors['first_name'])): ?>
                                            <div class="text-danger small"><?php echo $errors['first_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($teacherInfo['last_name']); ?>" required>
                                        <?php if (isset($errors['last_name'])): ?>
                                            <div class="text-danger small"><?php echo $errors['last_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" value="<?php echo $teacherInfo['date_of_birth']; ?>" readonly>
                                        <small class="text-muted">Date of birth cannot be changed</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Gender</label>
                                        <input type="text" class="form-control" value="<?php echo ucfirst($teacherInfo['gender']); ?>" readonly>
                                        <small class="text-muted">Gender cannot be changed</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($teacherInfo['phone']); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($teacherInfo['address']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Qualification</label>
                                        <input type="text" class="form-control" name="qualification" value="<?php echo htmlspecialchars($teacherInfo['qualification']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Specialization</label>
                                        <input type="text" class="form-control" name="specialization" value="<?php echo htmlspecialchars($teacherInfo['specialization']); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Hire Date</label>
                                        <input type="date" class="form-control" value="<?php echo $teacherInfo['hire_date']; ?>" readonly>
                                        <small class="text-muted">Hire date cannot be changed</small>
                                    </div>
                                </div>
                            </div>
                                                    </form>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_password">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password *</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                        <?php if (isset($errors['current_password'])): ?>
                                            <div class="text-danger small"><?php echo $errors['current_password']; ?></div>
                                        <?php endif; ?>
                                        <?php if (isset($errors['current_password_invalid'])): ?>
                                            <div class="text-danger small"><?php echo $errors['current_password_invalid']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">New Password *</label>
                                        <input type="password" class="form-control" name="new_password" required>
                                        <?php if (isset($errors['new_password'])): ?>
                                            <div class="text-danger small"><?php echo $errors['new_password']; ?></div>
                                        <?php endif; ?>
                                        <?php if (isset($errors['new_password_length'])): ?>
                                            <div class="text-danger small"><?php echo $errors['new_password_length']; ?></div>
                                        <?php endif; ?>
                                        <small class="text-muted">Minimum 6 characters</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password *</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                        <?php if (isset($errors['confirm_password'])): ?>
                                            <div class="text-danger small"><?php echo $errors['confirm_password']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            
            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Teacher profile not found. Please contact the administrator.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
