<?php
require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'teacher':
            redirect('teacher/dashboard.php');
            break;
        case 'student':
            redirect('student/dashboard.php');
            break;
        default:
            redirect('index.php');
    }
}

$errors = [];
$forgotPasswordMode = false;
$forgotStep = 1; // 1: email, 2: verify details, 3: new password
$forgotEmail = '';
$forgotUser = null;

// Handle forgot password requests
if (isset($_GET['action']) && $_GET['action'] === 'forgot') {
    $forgotPasswordMode = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['forgot_password'])) {
        // Handle forgot password form submission
        $step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
        
        if ($step === 1) {
            // Step 1: Get email
            $email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';
            
            if (empty($email)) {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address';
            }
            
            if (empty($errors)) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $forgotUser = $stmt->fetch();
                    
                    if ($forgotUser) {
                        $forgotEmail = $email;
                        $forgotStep = 2;
                    } else {
                        $errors['email'] = 'No account found with this email address';
                    }
                } catch (PDOException $e) {
                    $errors['database'] = 'Database error. Please try again.';
                }
            }
        } elseif ($step === 2) {
            // Step 2: Verify user details
            $email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';
            $dateOfBirth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
            $phone = isset($_POST['phone']) ? cleanInput($_POST['phone']) : '';
            
            if (empty($dateOfBirth)) {
                $errors['date_of_birth'] = 'Date of birth is required';
            }
            
            if (empty($phone)) {
                $errors['phone'] = 'Phone number is required';
            } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
                $errors['phone'] = 'Please enter a valid 10-digit phone number';
            }
            
            if (empty($errors)) {
                try {
                    $pdo = getDBConnection();
                    
                    // First check students table
                    $stmt = $pdo->prepare("
                        SELECT u.id, u.username, u.email, u.role, 'student' as user_type
                        FROM users u
                        INNER JOIN students s ON u.id = s.user_id
                        WHERE u.email = ? AND s.date_of_birth = ? AND s.phone = ?
                    ");
                    $stmt->execute([$email, $dateOfBirth, $phone]);
                    $forgotUser = $stmt->fetch();
                    
                    // If not found in students, check teachers table
                    if (!$forgotUser) {
                        $stmt = $pdo->prepare("
                            SELECT u.id, u.username, u.email, u.role, 'teacher' as user_type
                            FROM users u
                            INNER JOIN teachers t ON u.id = t.user_id
                            WHERE u.email = ? AND t.date_of_birth = ? AND t.phone = ?
                        ");
                        $stmt->execute([$email, $dateOfBirth, $phone]);
                        $forgotUser = $stmt->fetch();
                    }
                    
                    if ($forgotUser) {
                        $forgotEmail = $email;
                        $forgotStep = 3;
                    } else {
                        $errors['verify'] = 'User details do not match our records. Please check your email, date of birth, and phone number.';
                    }
                } catch (PDOException $e) {
                    $errors['database'] = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($step === 3) {
            // Step 3: Update password
            $email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';
            $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            if (empty($newPassword)) {
                $errors['new_password'] = 'New password is required';
            } elseif (strlen($newPassword) < 6) {
                $errors['new_password'] = 'Password must be at least 6 characters';
            }
            
            if (empty($confirmPassword)) {
                $errors['confirm_password'] = 'Please confirm your password';
            } elseif ($newPassword !== $confirmPassword) {
                $errors['confirm_password'] = 'Passwords do not match';
            }
            
            if (empty($errors)) {
                try {
                    $pdo = getDBConnection();
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $result = $stmt->execute([$hashedPassword, $email]);
                    
                    if ($result) {
                        setSuccessMessage('Password updated successfully! Please login with your new password.');
                        header('Location: login.php');
                        exit();
                    } else {
                        $errors['database'] = 'Failed to update password. Please try again.';
                    }
                } catch (PDOException $e) {
                    $errors['database'] = 'Database error: ' . $e->getMessage();
                }
            }
        }
    } else {
        // Normal login process
        $username = isset($_POST['username']) ? cleanInput($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        // Validation
        if (empty($username)) {
            $errors['username'] = 'Username is required';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        }
        
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                
                $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    setSuccessMessage('Login successful!');
                    
                    // Redirect based on role
                    switch ($user['role']) {
                        case 'admin':
                            redirect('admin/dashboard.php');
                            break;
                        case 'teacher':
                            redirect('teacher/dashboard.php');
                            break;
                        case 'student':
                            redirect('student/dashboard.php');
                            break;
                        default:
                            redirect('index.php');
                    }
                } else {
                    $errors['login'] = 'Invalid username or password';
                }
            } catch (PDOException $e) {
                $errors['database'] = 'Database error. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(240, 147, 251, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(102, 126, 234, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(245, 87, 108, 0.2) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .animated-bg {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
        }
        
        .floating-shape {
            position: absolute;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape1 {
            top: 10%;
            left: 10%;
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #f093fb, #f5576c);
            border-radius: 50%;
            animation-delay: 0s;
        }
        
        .shape2 {
            top: 70%;
            right: 10%;
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            animation-delay: 2s;
        }
        
        .shape3 {
            bottom: 10%;
            left: 70%;
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, #fa709a, #fee140);
            border-radius: 50%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            overflow: hidden;
            max-width: 420px;
            width: 100%;
            position: relative;
            z-index: 10;
            transition: all 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 30px 80px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.2) inset;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .login-body {
            padding: 2.5rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(240, 147, 251, 0.05));
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px 0 0 10px;
            padding: 0.75rem 1rem;
            font-weight: 500;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 0 10px 10px 0;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 
                0 0 0 0.2rem rgba(102, 126, 234, 0.25),
                inset 0 0 10px rgba(102, 126, 234, 0.1);
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: 25px;
            color: white;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 50%, #e673c7 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            backdrop-filter: blur(10px);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.9), rgba(34, 139, 34, 0.9));
            color: white;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.9), rgba(185, 28, 28, 0.9));
            color: white;
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(13, 202, 240, 0.9), rgba(6, 148, 162, 0.9));
            color: white;
        }
        
        .text-muted {
            color: #6c757d !important;
            transition: color 0.3s ease;
        }
        
        .text-muted:hover {
            color: #667eea !important;
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .form-check-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .login-card {
                margin: 1rem;
                max-width: calc(100% - 2rem);
            }
            
            .login-header {
                padding: 2rem 1.5rem;
            }
            
            .login-body {
                padding: 2rem 1.5rem;
            }
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Animated Background Shapes -->
    <div class="floating-shape shape1"></div>
    <div class="floating-shape shape2"></div>
    <div class="floating-shape shape3"></div>
    
    <div class="login-card">
        <div class="login-header">
            <?php if ($forgotPasswordMode): ?>
                <h3><i class="fas fa-key me-2"></i>Forgot Password</h3>
                <p class="mb-0">Reset your password</p>
            <?php else: ?>
                <h3><i class="fas fa-graduation-cap me-2"></i><?php echo APP_NAME; ?></h3>
                <p class="mb-0">Please login to continue</p>
            <?php endif; ?>
        </div>
        <div class="login-body">
            <?php
            $successMessage = getSuccessMessage();
            $errorMessage = getErrorMessage();
            
            if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $successMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $errorMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors['login'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $errors['login']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors['database'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $errors['database']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($forgotPasswordMode): ?>
                <!-- Forgot Password Forms -->
                <?php if ($forgotStep === 1): ?>
                    <!-- Step 1: Enter Email -->
                    <form method="POST" action="">
                        <input type="hidden" name="forgot_password" value="1">
                        <input type="hidden" name="step" value="1">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       placeholder="Enter your email address" required>
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="text-danger small mt-1"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-arrow-right me-2"></i>Continue
                        </button>
                        
                        <div class="text-center">
                            <a href="login.php" class="text-muted">
                                <i class="fas fa-arrow-left me-1"></i>Back to Login
                            </a>
                        </div>
                    </form>
                    
                <?php elseif ($forgotStep === 2): ?>
                    <!-- Step 2: Verify Details -->
                    <form method="POST" action="">
                        <input type="hidden" name="forgot_password" value="1">
                        <input type="hidden" name="step" value="2">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($forgotEmail); ?>">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Please verify your personal details to continue
                        </div>
                        
                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>" required>
                            </div>
                            <?php if (isset($errors['date_of_birth'])): ?>
                                <div class="text-danger small mt-1"><?php echo $errors['date_of_birth']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       placeholder="Enter 10-digit phone number" 
                                       pattern="[0-9]{10}" maxlength="10" required>
                            </div>
                            <?php if (isset($errors['phone'])): ?>
                                <div class="text-danger small mt-1"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Enter your 10-digit mobile number</small>
                        </div>
                        
                        <?php if (isset($errors['verify'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $errors['verify']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-check me-2"></i>Verify Details
                        </button>
                        
                        <div class="text-center">
                            <a href="login.php" class="text-muted">
                                <i class="fas fa-arrow-left me-1"></i>Back to Login
                            </a>
                        </div>
                    </form>
                    
                <?php elseif ($forgotStep === 3): ?>
                    <!-- Step 3: New Password -->
                    <form method="POST" action="">
                        <input type="hidden" name="forgot_password" value="1">
                        <input type="hidden" name="step" value="3">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($forgotEmail); ?>">
                        
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Details verified! Please set your new password.
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       placeholder="Enter new password" minlength="6" required>
                            </div>
                            <?php if (isset($errors['new_password'])): ?>
                                <div class="text-danger small mt-1"><?php echo $errors['new_password']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm new password" minlength="6" required>
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="text-danger small mt-1"><?php echo $errors['confirm_password']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-save me-2"></i>Update Password
                        </button>
                        
                        <div class="text-center">
                            <a href="login.php" class="text-muted">
                                <i class="fas fa-arrow-left me-1"></i>Back to Login
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Normal Login Form -->
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   placeholder="Enter username or email">
                        </div>
                        <?php if (isset($errors['username'])): ?>
                            <div class="text-danger small mt-1"><?php echo $errors['username']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter password">
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="text-danger small mt-1"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                    
                    <div class="text-center">
                        <a href="login.php?action=forgot" class="text-muted text-decoration-none">
                            <i class="fas fa-key me-1"></i>Forgot Password?
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add entrance animation to login card
    const loginCard = document.querySelector('.login-card');
    loginCard.style.opacity = '0';
    loginCard.style.transform = 'translateY(50px)';
    
    setTimeout(() => {
        loginCard.style.transition = 'all 0.8s ease';
        loginCard.style.opacity = '1';
        loginCard.style.transform = 'translateY(0)';
    }, 100);
    
    // Add floating effect to shapes
    const shapes = document.querySelectorAll('.floating-shape');
    shapes.forEach((shape, index) => {
        shape.style.animationDelay = `${index * 2}s`;
    });
    
    // Enhanced input interactions
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
    
    // Button ripple effect
    const buttons = document.querySelectorAll('.btn-primary');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(255, 255, 255, 0.5)';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple 0.6s linear';
            ripple.style.pointerEvents = 'none';
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Add ripple animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Password visibility toggle
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.innerHTML = '<i class="fas fa-eye"></i>';
        toggle.style.position = 'absolute';
        toggle.style.right = '10px';
        toggle.style.top = '50%';
        toggle.style.transform = 'translateY(-50%)';
        toggle.style.background = 'none';
        toggle.style.border = 'none';
        toggle.style.color = '#667eea';
        toggle.style.cursor = 'pointer';
        toggle.style.padding = '5px';
        
        toggle.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
        
        wrapper.appendChild(toggle);
    });
    
    // Form validation feedback
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-primary');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<span class="loading"></span> Processing...';
            submitBtn.disabled = true;
            
            // Reset after 3 seconds (in case of network issues)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
    }
    
    // Add hover effect to alert messages
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.cursor = 'pointer';
        alert.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    });
});
</script>

<style>
/* Additional dynamic styles */
.input-group {
    transition: transform 0.3s ease;
}

.btn-primary {
    position: relative;
    overflow: hidden;
}

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.form-check-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Enhanced alert animations */
.alert {
    cursor: pointer;
    transition: all 0.3s ease;
    animation: slideIn 0.5s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Input group enhancements */
.input-group-text {
    transition: all 0.3s ease;
}

.input-group:hover .input-group-text {
    background: linear-gradient(135deg, #5a6fd8, #6a4190);
}
</style>
</body>
</html>
