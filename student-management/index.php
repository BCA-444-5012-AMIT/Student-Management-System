<?php
$pageTitle = 'Home';
require_once 'config/config.php';
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container-fluid">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-lg-10">
            <!-- Welcome Section -->
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <div class="logo-circle mx-auto" style="width: 120px; height: 120px; background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-graduation-cap fa-3x text-white"></i>
                        </div>
                    </div>
                    
                    <h1 class="display-4 fw-bold mb-3 text-white"><?php echo APP_NAME; ?></h1>
                    <p class="lead text-white-50 mb-4">
                        A comprehensive student management system designed to streamline educational institution operations.
                    </p>
                    
                    <?php if (isLoggedIn()): ?>
                        <!-- Logged in user content -->
                        <div class="alert" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white;">
                            <i class="fas fa-check-circle me-2"></i>
                            Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-0" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); transform: translateY(0); transition: all 0.3s ease;">
                                    <div class="card-body text-white">
                                        <div class="icon-circle mb-3" style="width: 60px; height: 60px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                            <i class="fas fa-tachometer-alt fa-2x"></i>
                                        </div>
                                        <h5>Dashboard</h5>
                                        <p class="text-white-50">View your personalized dashboard</p>
                                        <a href="<?php 
                                            $role = getUserRole();
                                            switch ($role) {
                                                case 'admin':
                                                    echo 'admin/dashboard.php';
                                                    break;
                                                case 'teacher':
                                                    echo 'teacher/dashboard.php';
                                                    break;
                                                case 'student':
                                                    echo 'student/dashboard.php';
                                                    break;
                                                default:
                                                    echo 'auth/login.php';
                                            }
                                        ?>" class="btn btn-light text-dark">
                                            Go to Dashboard
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-0" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); transform: translateY(0); transition: all 0.3s ease;">
                                    <div class="card-body text-white">
                                        <div class="icon-circle mb-3" style="width: 60px; height: 60px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                            <i class="fas fa-user fa-2x"></i>
                                        </div>
                                        <h5>Profile</h5>
                                        <p class="text-white-50">Manage your profile information</p>
                                        <a href="<?php 
                                            $role = getUserRole();
                                            echo ($role === 'student') ? 'student/profile.php' : '#';
                                        ?>" class="btn btn-light text-dark">
                                            View Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-0" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); transform: translateY(0); transition: all 0.3s ease;">
                                    <div class="card-body text-white">
                                        <div class="icon-circle mb-3" style="width: 60px; height: 60px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                            <i class="fas fa-sign-out-alt fa-2x"></i>
                                        </div>
                                        <h5>Logout</h5>
                                        <p class="text-white-50">Sign out of your account</p>
                                        <a href="auth/logout.php" class="btn btn-light text-dark">
                                            Logout
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <!-- Not logged in content -->
                        <div class="row mt-5">
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <div class="card-body text-white">
                                        <div class="icon-circle mb-3" style="width: 70px; height: 70px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                            <i class="fas fa-sign-in-alt fa-2x"></i>
                                        </div>
                                        <h3>Login</h3>
                                        <p class="text-white-50">Access your account to manage your information</p>
                                        <a href="auth/login.php" class="btn btn-light btn-lg">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 border-0" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <div class="card-body text-white">
                                        <div class="icon-circle mb-3" style="width: 70px; height: 70px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                            <i class="fas fa-info-circle fa-2x"></i>
                                        </div>
                                        <h3>About System</h3>
                                        <p class="text-white-50">Learn more about features and capabilities</p>
                                        <button class="btn btn-light btn-lg" onclick="showAbout()">
                                            <i class="fas fa-info-circle me-2"></i>Learn More
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Features Section -->
                        <div class="row mt-5">
                            <div class="col-12">
                                <h3 class="mb-4 text-white">System Features</h3>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="text-center">
                                    <div class="feature-icon mb-3" style="width: 100px; height: 100px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                        <i class="fas fa-users fa-3x text-white"></i>
                                    </div>
                                    <h5 class="text-white">User Management</h5>
                                    <p class="text-white-50">Multi-role system with Admin, Teacher, and Student access</p>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="text-center">
                                    <div class="feature-icon mb-3" style="width: 100px; height: 100px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                        <i class="fas fa-book fa-3x text-white"></i>
                                    </div>
                                    <h5 class="text-white">Course Management</h5>
                                    <p class="text-white-50">Create and manage courses with teacher assignments</p>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="text-center">
                                    <div class="feature-icon mb-3" style="width: 100px; height: 100px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                        <i class="fas fa-calendar-check fa-3x text-white"></i>
                                    </div>
                                    <h5 class="text-white">Attendance Tracking</h5>
                                    <p class="text-white-50">Mark and monitor student attendance efficiently</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Demo Credentials -->
                        <div class="mt-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; padding: 30px; color: white;">
                            <h5><i class="fas fa-key me-2"></i>Demo Credentials</h5>
                            <div class="row text-start">
                                <div class="col-md-4">
                                    <div class="credential-card" style="background: rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 15px; margin-bottom: 10px;">
                                        <strong>Admin:</strong><br>
                                        Username: <code style="background: rgba(255, 255, 255, 0.2); padding: 3px 8px; border-radius: 5px;">admin</code><br>
                                        Password: <code style="background: rgba(255, 255, 255, 0.2); padding: 3px 8px; border-radius: 5px;">password</code>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="credential-card" style="background: rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 15px; margin-bottom: 10px;">
                                        <strong>Teacher:</strong><br>
                                        Username: <code style="background: rgba(255, 255, 255, 0.2); padding: 3px 8px; border-radius: 5px;">teacher1</code><br>
                                        Password: <code style="background: rgba(255, 255, 255, 0.2); padding: 3px 8px; border-radius: 5px;">password</code>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="credential-card" style="background: rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 15px; margin-bottom: 10px;">
                                        <strong>Student:</strong><br>
                                        Username: <code style="background: rgba(255, 255, 255, 0.2); padding: 3px 8px; border-radius: 5px;">student1</code><br>
                                        Password: <code style="background: rgba(255, 255, 255, 0.2); padding: 3px 8px; border-radius: 5px;">password</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Footer Info -->
            <div class="text-center mt-4 text-muted">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Version <?php echo APP_VERSION; ?></p>
                <p>Built with PHP 8.x, MySQL, Bootstrap 5</p>
            </div>
        </div>
    </div>
</div>

<!-- About Modal -->
<div class="modal fade" id="aboutModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">About <?php echo APP_NAME; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>System Overview</h6>
                <p><?php echo APP_NAME; ?> is a comprehensive web-based application designed to streamline the management of educational institutions. It provides tools for administrators, teachers, and students to efficiently manage academic activities.</p>
                
                <h6 class="mt-3">Key Features</h6>
                <ul>
                    <li><strong>Secure Authentication:</strong> Role-based login system with password hashing</li>
                    <li><strong>User Management:</strong> Add and manage students, teachers, and administrative staff</li>
                    <li><strong>Course Management:</strong> Create courses and assign teachers</li>
                    <li><strong>Enrollment System:</strong> Student course enrollment and tracking</li>
                    <li><strong>Attendance Tracking:</strong> Mark and monitor student attendance</li>
                    <li><strong>Dashboard Analytics:</strong> Real-time statistics and reports</li>
                    <li><strong>Responsive Design:</strong> Works on desktop, tablet, and mobile devices</li>
                </ul>
                
                <h6 class="mt-3">Technology Stack</h6>
                <ul>
                    <li><strong>Backend:</strong> PHP 8.x with PDO for database operations</li>
                    <li><strong>Database:</strong> MySQL with InnoDB engine</li>
                    <li><strong>Frontend:</strong> HTML5, CSS3, JavaScript, Bootstrap 5</li>
                    <li><strong>Security:</strong> Password hashing, session management, input validation</li>
                </ul>
                
                <h6 class="mt-3">User Roles</h6>
                <ul>
                    <li><strong>Administrator:</strong> Full system access, user management, course creation</li>
                    <li><strong>Teacher:</strong> Manage assigned courses, mark attendance, view students</li>
                    <li><strong>Student:</strong> View profile, courses, attendance records</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="auth/login.php" class="btn btn-primary">Get Started</a>
            </div>
        </div>
    </div>
</div>

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.min-vh-100 {
    min-height: 100vh;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2) !important;
}

.logo-circle {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(240, 147, 251, 0.7);
    }
    70% {
        transform: scale(1.05);
        box-shadow: 0 0 0 20px rgba(240, 147, 251, 0);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(240, 147, 251, 0);
    }
}

.icon-circle {
    transition: all 0.3s ease;
}

.feature-icon {
    transition: all 0.3s ease;
}

.feature-icon:hover {
    transform: scale(1.1);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.credential-card {
    transition: all 0.3s ease;
}

.credential-card:hover {
    background: rgba(255, 255, 255, 0.2) !important;
    transform: translateY(-2px);
}

.btn {
    transition: all 0.3s ease;
    border-radius: 25px;
    padding: 10px 25px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

code {
    color: #fff !important;
    font-weight: 500;
}

.text-white-50 {
    color: rgba(255, 255, 255, 0.7) !important;
}

/* Footer styling */
.text-center.mt-4.text-muted {
    color: rgba(255, 255, 255, 0.8) !important;
    background: rgba(0, 0, 0, 0.1);
    padding: 20px;
    border-radius: 10px;
    backdrop-filter: blur(10px);
}

/* Modal styling */
.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    border: none;
}

.modal-header .btn-close {
    filter: brightness(0) invert(1);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .logo-circle {
        width: 80px !important;
        height: 80px !important;
    }
    
    .feature-icon {
        width: 70px !important;
        height: 70px !important;
    }
    
    .feature-icon i {
        font-size: 1.5rem !important;
    }
}
</style>

<script>
function showAbout() {
    var modal = new bootstrap.Modal(document.getElementById('aboutModal'));
    modal.show();
}

// Add smooth scroll behavior
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll for any anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Add entrance animations
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
