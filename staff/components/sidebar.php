<!-- Sidebar -->
<link rel="stylesheet" href="../assets/css/staff/sidebar.css">

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="dashboard.php" class="logo">
            <img src="../assets/img/FIT.png" alt="RVG Power Build" style="max-width: 60px; height: auto;" />
            <span class="logo-text" style="font-size: 20px; font-weight: bold;">RVG POWER BUILD</span>
        </a>
    </div>

    <div class="sidebar-menu">
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span class="menu-text">Dashboard</span></a></li>
            <li><a href="attendance.php"><i class="fas fa-clipboard-check"></i><span class="menu-text">Attendance</span></a></li>
            <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i><span class="menu-text">Schedule</span></a></li>

            <li><a href="salary.php"><i class="fas fa-money-bill-wave"></i><span class="menu-text">Salary</span></a></li>
            <li><a href="profile.php"><i class="fas fa-user-edit"></i><span class="menu-text">Profile</span></a></li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="user-profile">
            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User" class="user-avatar">
            <div class="user-info">
                <h4>Staff Member</h4>
                <p>Gym Staff <span class="user-status"></span></p>
            </div>
        </div>
    </div>
</div>

<!-- Toggle Button -->
<div class="toggle-btn-container">
    <button id="sidebarToggle" class="toggle-btn">
        <i class="fas fa-chevron-left"></i>
    </button>
</div>

<!-- JS Connection -->
<script src="../assets/js/staff/sidebar.js"></script>
