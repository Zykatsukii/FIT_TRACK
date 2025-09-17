<?php
session_start();
include '../includes/db.php'; // MySQLi connection in $conn

// Redirect if already logged in
if (isset($_SESSION['member_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

// Initialize variablesQuick Fitness Tips
$error = '';
$email = '';
$login_attempts = $_SESSION['login_attempts'] ?? 0;
$last_attempt = $_SESSION['last_attempt'] ?? 0;
$lockout_time = 300; // 5 minutes in seconds

// Check if account is temporarily locked
if ($login_attempts >= 5 && (time() - $last_attempt) < $lockout_time) {
    $remaining_time = $lockout_time - (time() - $last_attempt);
    $error = "Too many failed attempts. Please try again in " . ceil($remaining_time / 60) . " minutes.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process login form
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validate inputs
    if (empty($email)) {
        $error = "Email is required.";
    } elseif (empty($password)) {
        $error = "Password is required.";
    } else {
        // Prepare and execute query to get member by email
        $stmt = $conn->prepare("SELECT * FROM members WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $member = $result->fetch_assoc();

        if ($member) {
            // Verify password (assuming password is hashed in DB)
            if (password_verify($password, $member['password']) || $password === $member['password']) {
                // Reset login attempts on successful login
                unset($_SESSION['login_attempts']);
                unset($_SESSION['last_attempt']);

                // Set session variables
                $_SESSION['member_logged_in'] = true;
                $_SESSION['member_id'] = $member['id'];
                $_SESSION['member_name'] = $member['first_name'] . ' ' . $member['last_name'];
                $_SESSION['member_email'] = $member['email'];
                $_SESSION['member_data'] = $member;

                // Set remember me cookie if checked
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + 60 * 60 * 24 * 30; // 30 days
                    
                    // Store token in database
                    $stmt = $conn->prepare("UPDATE members SET remember_token = ?, token_expiry = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $token, date('Y-m-d H:i:s', $expiry), $member['id']);
                    $stmt->execute();
                    
                    // Set cookie
                    setcookie('remember_token', $token, $expiry, '/', '', true, true);
                }

                // Update last login
                $stmt = $conn->prepare("UPDATE members SET last_login = NOW() WHERE id = ?");
                $stmt->bind_param("i", $member['id']);
                $stmt->execute();

                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                // Increment failed login attempts
                $_SESSION['login_attempts'] = $login_attempts + 1;
                $_SESSION['last_attempt'] = time();
                
                $error = "Invalid email or password.";
                $remaining_attempts = 5 - ($login_attempts + 1);
                if ($remaining_attempts > 0) {
                    $error .= " You have $remaining_attempts attempts remaining.";
                } else {
                    $error .= " Account temporarily locked. Please try again later.";
                }
            }
        } else {
            // Don't reveal if email exists for security
            $error = "Invalid email or password.";
            $_SESSION['login_attempts'] = $login_attempts + 1;
            $_SESSION['last_attempt'] = time();
        }
    }
}

// Check for remember me cookie
if (empty($error) && !isset($_SESSION['member_logged_in']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    $stmt = $conn->prepare("SELECT * FROM members WHERE remember_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();
    
    if ($member) {
        $_SESSION['member_logged_in'] = true;
        $_SESSION['member_id'] = $member['id'];
        $_SESSION['member_name'] = $member['first_name'] . ' ' . $member['last_name'];
        $_SESSION['member_email'] = $member['email'];
        $_SESSION['member_data'] = $member;
        
        header('Location: dashboard.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="FIT_TRACK Member Login Portal" />
    <title>FIT_TRACK MEMBER - Login</title>

    <!-- Favicon -->
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon" />

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Bebas+Neue&display=swap" rel="stylesheet" />

    <style>
        :root {
            --primary: #3B82F6;
            --secondary: #1E3A8A;
            --accent: #EF4444;
            --dark: #111827;
        }
        body {
            font-family: 'Poppins', sans-serif;
                        background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)), 
                        url('https://images.unsplash.com/photo-1571902943202-507ec2618e8f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1075&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
        }
        .brand {
            font-family: 'Bebas Neue', cursive;
            letter-spacing: 2px;
        }
        .login-card {
            backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .input-field {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .input-field:focus {
            background: rgba(255,255,255,0.1);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.3);
        }
        .btn-login {
            transition: all 0.3s ease;
            background-image: linear-gradient(to right, var(--primary), var(--secondary));
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }
        .password-toggle {
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="login-card w-full max-w-md p-8 rounded-2xl shadow-2xl space-y-6 text-white">
        <!-- Logo Header -->
        <div class="text-center">
            <div class="flex justify-center mb-4">
                <div class="gym-stats-card p-4 rounded-xl shadow-lg transform rotate-45 w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-dumbbell text-white text-2xl transform -rotate-45"></i>
                </div>
            </div>
            <h2 class="text-4xl font-bold gym-brand">RVG POWER BUILD <span class="text-orange-400">MEMBER</span></h2>
            <p class="mt-2 gym-subtitle text-orange-200">GYM MEMBER PORTAL</p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="bg-red-900/50 border-l-4 border-red-500 text-red-100 p-4 rounded-lg flex items-start animate-fade-in">
                <i class="fas fa-exclamation-triangle mt-1 mr-3"></i>
                <div>
                    <p class="font-medium">Authentication Error</p>
                    <p class="text-sm opacity-90"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="" method="POST" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium text-blue-100 mb-2">EMAIL</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-blue-300"></i>
                    </div>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        required
                        class="input-field w-full pl-10 pr-4 py-3 rounded-lg text-white placeholder-blue-300 focus:outline-none focus:ring-0"
                        placeholder="member@example.com"
                        value="<?= htmlspecialchars($email) ?>"
                        autocomplete="email"
                        <?= ($login_attempts >= 5 && (time() - $last_attempt) < $lockout_time) ? 'disabled' : '' ?>
                    />
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-blue-100 mb-2">PASSWORD</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-key text-blue-300"></i>
                    </div>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        class="input-field w-full pl-10 pr-10 py-3 rounded-lg text-white placeholder-blue-300 focus:outline-none focus:ring-0"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        <?= ($login_attempts >= 5 && (time() - $last_attempt) < $lockout_time) ? 'disabled' : '' ?>
                    />
                    <span class="password-toggle absolute text-blue-300" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        name="remember"
                        id="remember"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <label for="remember" class="ml-2 block text-sm text-blue-200">
                        Remember me
                    </label>
                </div>
                <div class="text-sm">
                    <a href="forgot-password.php" class="font-medium text-blue-300 hover:text-blue-200">
                        Forgot password?
                    </a>
                </div>
            </div>

            <button
                type="submit"
                class="w-full btn-login text-white py-3 px-4 rounded-lg transition duration-300 flex items-center justify-center font-semibold shadow-lg group"
                <?= ($login_attempts >= 5 && (time() - $last_attempt) < $lockout_time) ? 'disabled' : '' ?>
            >
                <span class="group-hover:animate-pulse mr-2">
                    <i class="fas fa-sign-in-alt"></i>
                </span>
                LOGIN
            </button>
        </form>

        <!-- Footer -->
        <div class="text-center text-xs text-blue-300/70 mt-6">
            <div class="flex items-center justify-center space-x-2">
                <i class="fas fa-lock"></i>
                <span>Secure Member Portal • v2.0</span>
            </div>
            <div class="mt-1">&copy; <?= date('Y'); ?> FIT_TRACK SYSTEM</div>
        </div>
    </div>

    <!-- Watermark -->
    <div class="fixed bottom-4 right-4 text-xs text-blue-400/30">
        <?= htmlspecialchars($_SERVER['SERVER_NAME']) ?> • <?= date('H:i') ?>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Add animation class to error message
        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.querySelector('.bg-red-900/50');
            if (errorMessage) {
                errorMessage.classList.add('animate-fade-in');
            }
        });
    </script>
</body>
</html>