<?php
require_once __DIR__ . '/../config/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .error-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem;
        }
        .error-body {
            padding: 2rem;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-header">
            <h3><i class="fas fa-exclamation-triangle me-2"></i>Access Denied</h3>
        </div>
        <div class="error-body">
            <div class="error-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h4 class="mb-3">Unauthorized Access</h4>
            <p class="text-muted mb-4">
                You don't have permission to access this page. 
                Please contact your administrator if you believe this is an error.
            </p>
            
            <div class="d-grid gap-2">
                <?php if (isLoggedIn()): ?>
                    <?php
                    $role = getUserRole();
                    switch ($role) {
                        case 'admin':
                            $dashboard = 'admin/dashboard.php';
                            break;
                        case 'teacher':
                            $dashboard = 'teacher/dashboard.php';
                            break;
                        case 'student':
                            $dashboard = 'student/dashboard.php';
                            break;
                        default:
                            $dashboard = 'index.php';
                    }
                    ?>
                    <a href="<?php echo BASE_URL . $dashboard; ?>" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Go to Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-outline-secondary">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                    <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-home me-2"></i>Home
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
