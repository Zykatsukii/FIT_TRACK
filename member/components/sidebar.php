<style>
    :root {
        --primary: #6C5CE7;
        --primary-light: #A29BFE;
        --primary-dark: #5649C0;
        --secondary: #FD79A8;
        --accent: #00CEC9;
        --dark: #2D3436;
        --darker: #1E272E;
        --light: #F5F6FA;
        --gray: #B2BEC3;
        --success: #00B894;
        --danger: #D63031;
        --warning: #FDCB6E;
        --info: #0984E3;
        --card-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        --sidebar-width: 280px;
        --transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1.2);
        --glass-effect: rgba(30, 39, 46, 0.9);
    }

    /* Sidebar-specific styles - scoped to avoid conflicts */
    .member-sidebar {
        --sidebar-primary: #6C5CE7;
        --sidebar-primary-light: #A29BFE;
        --sidebar-accent: #00CEC9;
        --sidebar-dark: #1E272E;
        --sidebar-darker: #2D3436;
        --sidebar-gray: #B2BEC3;
        --sidebar-success: #00B894;
    }

    /* Sidebar Styles */
    .member-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background: linear-gradient(180deg, var(--sidebar-darker), var(--glass-effect));
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #fff;
        padding: 25px 0;
        z-index: 1000;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 8px 0 40px rgba(0, 0, 0, 0.2);
        transition: var(--transition);
        overflow-y: auto;
        scrollbar-width: thin;
        transform: translateX(0);
        display: flex;
        flex-direction: column;
    }

    .member-sidebar.hidden {
        transform: translateX(calc(-1 * var(--sidebar-width)));
    }

    .member-sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .member-sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    /* Brand Section */
    .member-sidebar-brand {
        text-align: center;
        margin-bottom: 35px;
        padding: 0 25px;
        position: relative;
    }

    .member-sidebar-brand::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 25px;
        right: 25px;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    }

    .member-sidebar-brand .logo {
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: var(--transition);
    }

    .logo-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--sidebar-primary), var(--sidebar-accent));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        box-shadow: 0 6px 25px rgba(108, 92, 231, 0.5);
        transition: var(--transition);
    }

    .logo-text {
        font-size: 22px;
        font-weight: 800;
        letter-spacing: 0.5px;
        background: linear-gradient(45deg, #fff, var(--sidebar-primary-light));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        text-shadow: 0 2px 8px rgba(108, 92, 231, 0.3);
    }

    /* Menu Section */
    .member-sidebar-menu {
        padding: 15px 15px;
        flex: 1;
    }

    .member-sidebar-menu ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .member-sidebar-menu ul li {
        margin: 6px 0;
        position: relative;
    }

    .member-sidebar-menu ul li a {
        text-decoration: none;
        color: rgba(255, 255, 255, 0.9);
        padding: 15px 20px;
        display: flex;
        align-items: center;
        font-size: 15px;
        font-weight: 500;
        transition: var(--transition);
        border-radius: 8px;
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }

    .member-sidebar-menu ul li a::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 3px;
        height: 100%;
        background: linear-gradient(to bottom, var(--sidebar-primary), var(--sidebar-accent));
        transform: scaleY(0);
        transition: var(--transition);
        transform-origin: bottom;
        opacity: 0;
    }

    .member-sidebar-menu ul li a::after {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, rgba(108, 92, 231, 0.1), transparent);
        opacity: 0;
        transition: var(--transition);
    }

    .member-sidebar-menu ul li a:hover,
    .member-sidebar-menu ul li a.active {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        transform: translateX(8px);
    }

    .member-sidebar-menu ul li a:hover::before,
    .member-sidebar-menu ul li a.active::before {
        transform: scaleY(1);
        opacity: 1;
    }

    .member-sidebar-menu ul li a:hover::after,
    .member-sidebar-menu ul li a.active::after {
        opacity: 1;
    }

    .member-sidebar-menu ul li a i {
        margin-right: 15px;
        font-size: 18px;
        width: 24px;
        text-align: center;
        color: var(--sidebar-accent);
        transition: var(--transition);
    }

    .member-sidebar-menu ul li a:hover i,
    .member-sidebar-menu ul li a.active i {
        color: #fff;
        transform: scale(1.1);
    }

    .menu-text {
        position: relative;
        z-index: 1;
    }

    .notification-badge {
        margin-left: auto;
        background: var(--secondary);
        color: white;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 9px;
        border-radius: 10px;
        animation: pulse 1.8s infinite;
        box-shadow: 0 3px 8px rgba(253, 121, 168, 0.3);
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); box-shadow: 0 0 0 5px rgba(253, 121, 168, 0.1); }
        100% { transform: scale(1); }
    }

    /* Footer Section */
    .member-sidebar-footer {
        padding: 20px;
        margin-top: auto;
        background: rgba(0, 0, 0, 0.2);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }

    .member-sidebar-footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 25px;
        right: 25px;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--sidebar-primary-light);
        transition: var(--transition);
        box-shadow: 0 4px 15px rgba(108, 92, 231, 0.3);
    }

    .user-avatar:hover {
        transform: scale(1.05);
        border-color: var(--sidebar-accent);
    }

    .user-info h4 {
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 3px;
        color: white;
        letter-spacing: 0.5px;
    }

    .user-info p {
        font-size: 11px;
        color: var(--sidebar-gray);
        display: flex;
        align-items: center;
        letter-spacing: 0.5px;
    }

    .user-status {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--sidebar-success);
        margin-left: 6px;
        box-shadow: 0 0 0 2px rgba(0, 184, 148, 0.3);
        animation: status-pulse 2s infinite;
    }

    @keyframes status-pulse {
        0% { box-shadow: 0 0 0 0 rgba(0, 184, 148, 0.4); }
        70% { box-shadow: 0 0 0 8px rgba(0, 184, 148, 0); }
        100% { box-shadow: 0 0 0 0 rgba(0, 184, 148, 0); }
    }

    /* Toggle Button */
    .member-toggle-btn-container {
        position: fixed;
        left: calc(var(--sidebar-width) - 5px);
        top: 50%;
        transform: translateY(-50%);
        z-index: 1100;
        transition: var(--transition);
    }

    .member-sidebar.hidden + .member-toggle-btn-container {
        left: 0;
    }

    .member-toggle-btn {
        background: var(--sidebar-primary);
        border: none;
        color: white;
        width: 26px;
        height: 85px;
        border-radius: 0 10px 10px 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        margin: 0;
        padding: 0;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.2);
        position: relative;
        overflow: hidden;
    }

    .member-toggle-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to right, var(--sidebar-primary), var(--sidebar-primary-light));
        opacity: 0;
        transition: var(--transition);
    }

    .member-toggle-btn:hover {
        width: 32px;
    }

    .member-toggle-btn:hover::before {
        opacity: 1;
    }

    .member-toggle-btn i {
        transition: var(--transition);
        font-size: 14px;
        position: relative;
        z-index: 1;
    }

    .member-sidebar.hidden + .member-toggle-btn-container .member-toggle-btn i {
        transform: rotate(180deg);
    }

    /* Responsive Styles */
    @media (max-width: 992px) {
        .member-sidebar {
            transform: translateX(calc(-1 * var(--sidebar-width)));
            z-index: 9999;
        }
        
        .member-toggle-btn-container {
            left: 0;
        }
        
        .member-sidebar.hidden {
            transform: translateX(0);
        }

        .member-sidebar-menu {
            padding: 10px 15px;
        }

        .member-sidebar-menu ul li a {
            padding: 12px 15px;
            font-size: 14px;
        }

        .member-sidebar-brand {
            margin-bottom: 25px;
            padding: 0 15px;
        }

        .logo-text {
            font-size: 20px;
        }

        .member-sidebar-footer {
            padding: 15px;
        }
    }

    @media (max-width: 768px) {
        .member-sidebar {
            width: 100%;
            max-width: 280px;
        }

        .member-sidebar-menu ul li a {
            padding: 15px 20px;
        }

        .member-sidebar-menu ul li a i {
            font-size: 16px;
            width: 20px;
        }
    }

    /* Menu Item Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateX(-15px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .member-sidebar-menu ul li {
        animation: fadeIn 0.4s ease forwards;
        opacity: 0;
    }

    .member-sidebar-menu ul li:nth-child(1) { animation-delay: 0.1s; }
    .member-sidebar-menu ul li:nth-child(2) { animation-delay: 0.2s; }
    .member-sidebar-menu ul li:nth-child(3) { animation-delay: 0.3s; }
    .member-sidebar-menu ul li:nth-child(4) { animation-delay: 0.4s; }
    .member-sidebar-menu ul li:nth-child(5) { animation-delay: 0.5s; }
    .member-sidebar-menu ul li:nth-child(6) { animation-delay: 0.6s; }
    .member-sidebar-menu ul li:nth-child(7) { animation-delay: 0.7s; }
</style>

<div class="member-sidebar" id="memberSidebar">
    <div class="member-sidebar-brand">
        <a href="dashboard.php" class="logo">
            <img src="../assets/img/FIT.png" alt="FIT_TRACK Logo" style="max-width: 80px; height: auto;" />
            <span class="logo-text" style="font-size: 24px; font-weight: bold;">RVG POWER BUILD</span>
        </a>
    </div>

    <div class="member-sidebar-menu">
        <ul>
            <li>
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="profile.php">
                    <i class="fas fa-user"></i>
                    <span class="menu-text">My Profile</span>
                </a>
            </li>
          
            <li>
                <a href="attendance.php">
                    <i class="fas fa-calendar-check"></i>
                    <span class="menu-text">Gym Visit Logs</span>
                </a>
            </li>
            <li>
                <a href="bmi.php">
                    <i class="fas fa-weight"></i>
                    <span class="menu-text">BMI Calculator</span>
                </a>
            </li>
            <li>
                <a href="health.php">
                    <i class="fas fa-heartbeat"></i>
                    <span class="menu-text">Health Tracker</span>
                </a>
            </li>
            <li>
                <a href="schedule.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="menu-text">Workout Schedule</span>
                </a>
            </li>
            <li>
                <a href="videos.php">
                    <i class="fas fa-video"></i>
                    <span class="menu-text">Workout Videos</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="member-sidebar-footer">
        <div class="user-profile">
            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Member" class="user-avatar">
            <div class="user-info">
                <h4><?php echo $_SESSION['member_name'] ?? 'Member'; ?></h4>
                <p>Active <span class="user-status">✓</span></p>
            </div>
        </div>
    </div>
</div>

<div class="member-toggle-btn-container">
    <button id="memberSidebarToggle" class="member-toggle-btn">
        <i class="fas fa-chevron-left"></i>
    </button>
</div>

<script>
    const toggleBtn = document.getElementById('memberSidebarToggle');
    const sidebar = document.getElementById('memberSidebar');
    const body = document.body;

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('hidden');
        body.classList.toggle('sidebar-hidden');
    });

    document.addEventListener('DOMContentLoaded', () => {
        const currentPage = window.location.pathname.split('/').pop();
        const menuItems = document.querySelectorAll('.member-sidebar-menu ul li a');

        menuItems.forEach(item => {
            const href = item.getAttribute('href');
            // Check if current page matches the href or if it's dashboard and we're on index
            if (href === currentPage || (href === 'dashboard.php' && (currentPage === 'index.php' || currentPage === ''))) {
                item.classList.add('active');
            }

            item.addEventListener('click', function (e) {
                // Remove active class from all items
                menuItems.forEach(i => i.classList.remove('active'));
                // Add active class to clicked item
                this.classList.add('active');
                
                // On mobile, close sidebar after clicking a menu item
                if (window.innerWidth <= 992) {
                    setTimeout(() => {
                        sidebar.classList.add('hidden');
                        body.classList.add('sidebar-hidden');
                    }, 150);
                }
            });
        });
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function (event) {
        if (window.innerWidth <= 992 &&
            !sidebar.contains(event.target) &&
            event.target !== toggleBtn &&
            !toggleBtn.contains(event.target)) {
            sidebar.classList.add('hidden');
            body.classList.add('sidebar-hidden');
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            // On desktop, ensure sidebar is visible
            sidebar.classList.remove('hidden');
            body.classList.remove('sidebar-hidden');
        } else {
            // On mobile, ensure sidebar starts hidden
            if (!sidebar.classList.contains('hidden')) {
                sidebar.classList.add('hidden');
                body.classList.add('sidebar-hidden');
            }
        }
    });

    // Handle escape key to close sidebar
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && window.innerWidth <= 992) {
            sidebar.classList.add('hidden');
            body.classList.add('sidebar-hidden');
        }
    });
</script>
