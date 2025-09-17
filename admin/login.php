<?php
// Set timezone to Philippines for correct time display
date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/db.php'; // Siguraduhin tama path

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && $password === $admin['password']) { 
        // Kung gusto mo secure, dapat password_verify dito
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        header("Location: index.php?page=dashboard");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FIT_TRACK PRO - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Bebas+Neue&display=swap" rel="stylesheet">
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
            min-height: 100vh;
        }
        
        .brand {
            font-family: 'Bebas Neue', sans-serif;
            letter-spacing: 3px;
            background: linear-gradient(45deg, #fff, #E0E7FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
        }
        
        .login-card {
            backdrop-filter: blur(20px);
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.15) 0%, 
                rgba(255, 255, 255, 0.05) 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .gym-stats-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: rotate(45deg) translateY(0px); }
            50% { transform: rotate(45deg) translateY(-10px); }
        }
        
        .input-field {
            background: rgba(75, 85, 99, 0.9);
            border: 1px solid rgba(55, 65, 81, 0.8);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            color: #f9fafb;
        }
        
        .input-field:focus {
            background: rgba(75, 85, 99, 0.9);
            border-color: var(--primary);
            box-shadow: 
                0 0 0 4px rgba(59, 130, 246, 0.2),
                0 0 20px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }
        
        .input-field:hover {
            background: rgba(75, 85, 99, 0.95);
            border-color: rgba(55, 65, 81, 0.9);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
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
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(59, 130, 246, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .error-card {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
            border: 1px solid rgba(239, 68, 68, 0.3);
            backdrop-filter: blur(10px);
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .checkbox-custom {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.05);
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .checkbox-custom:checked {
            background: var(--primary);
            border-color: var(--primary);
        }
        
        .checkbox-custom:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: bold;
            font-size: 12px;
        }
        
        .link-hover {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .link-hover::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }
        
        .link-hover:hover::after {
            width: 100%;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="login-card w-full max-w-md p-8 rounded-2xl shadow-2xl space-y-6">
        <!-- Logo Header -->
        <div class="text-center">
            <div class="flex justify-center mb-4">
                <div class="gym-stats-card p-4 rounded-xl shadow-lg transform rotate-45 w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-dumbbell text-white text-2xl transform -rotate-45"></i>
                </div>
            </div>
            <h2 class="text-4xl font-bold gym-brand text-white">RVG POWER BUILD</h2>
            <p class="mt-2 gym-subtitle text-white">GYM MANAGEMENT SYSTEM</p>
        </div>

        <!-- Error Message -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-card p-4 rounded-lg flex items-start">
                <i class="fas fa-exclamation-triangle mt-1 mr-3 text-red-400"></i>
                <div>
                    <p class="font-medium text-red-200">Authentication Error</p>
                    <p class="text-sm text-red-300 opacity-90"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="login.php" method="POST" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium text-blue-100 mb-2">EMAIL</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user-shield text-blue-300"></i>
                    </div>
                    <input 
                        type="email" 
                        name="email" 
                        id="email"
                        required 
                        class="input-field w-full pl-10 pr-4 py-3 rounded-lg text-white placeholder-blue-300 focus:outline-none focus:ring-0"
                        placeholder="example@fittrack.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
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
                        class="input-field w-full pl-10 pr-4 py-3 rounded-lg text-white placeholder-blue-300 focus:outline-none focus:ring-0"
                        placeholder="••••••••"
                    >
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        id="remember"
                        name="remember" 
                        class="checkbox-custom"
                    >
                    <label for="remember" class="ml-2 block text-sm text-blue-200 cursor-pointer">Remember session</label>
                </div>
                <a href="#" class="text-sm text-blue-300 hover:text-blue-100 font-medium link-hover">Recover access</a>
            </div>

            <button 
                type="submit" 
                class="btn-primary w-full text-white py-4 px-6 rounded-lg flex items-center justify-center font-semibold shadow-lg group"
            >
                <span class="group-hover:animate-pulse mr-2">
                    <i class="fas fa-sign-in-alt"></i>
                </span> 
                ACCESS DASHBOARD
            </button>
        </form>

        <!-- Footer -->
        <div class="text-center text-xs text-blue-300/70 mt-6">
            <div class="flex items-center justify-center space-x-2">
                <i class="fas fa-lock"></i>
                <span>Secure Admin Portal • v2.4.1</span>
            </div>
            <div class="mt-1">&copy; <?= date('Y'); ?> FIT_TRACK SYSTEMS</div>
        </div>
    </div>

    <!-- Watermark -->
    <div class="fixed bottom-4 right-4 text-xs text-blue-400/30">
        <?= $_SERVER['SERVER_NAME'] ?> • <?= date('H:i') ?>
    </div>

    <!-- Enhanced JavaScript for interactivity -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form submission with loading state
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            form.addEventListener('submit', function(e) {
                // Add loading state
                submitBtn.innerHTML = '<span class="loading mr-2"></span>Authenticating...';
                submitBtn.disabled = true;
                
                // Re-enable button after 3 seconds (in case of slow response)
                setTimeout(() => {
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                }, 3000);
            });
            
            // Input focus animations
            const inputs = document.querySelectorAll('.input-field');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('transform', 'scale-105');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('transform', 'scale-105');
                });
            });
            
            // Password visibility toggle
            const passwordInput = document.getElementById('password');
            const passwordContainer = passwordInput.parentElement;
            
            // Create toggle button
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'absolute inset-y-0 right-0 pr-3 flex items-center text-blue-300 hover:text-white transition-colors';
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            
            passwordContainer.appendChild(toggleBtn);
            passwordInput.classList.add('pr-10');
            
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
            
            // Add ripple effect to button
            submitBtn.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
            
            // Add CSS for ripple animation
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
        });
    </script>
</body>
</html>