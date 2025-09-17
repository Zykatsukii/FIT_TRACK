<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Authentication check
if (!isset($_SESSION['member_logged_in']) || !$_SESSION['member_logged_in']) {
    header('Location: login.php');
    exit;
}

require_once '../includes/db.php';

$page_title = "Dashboard";
include 'components/header.php';

// Get announcements for members
$member_id = $_SESSION['member_id'] ?? 0;
$sql = "SELECT a.*, 
               COALESCE(adm.name, 'System') as created_by_name
        FROM announcements a
        LEFT JOIN admins adm ON a.created_by = adm.id
        WHERE a.status = 'active' 
        AND (a.target_audience = 'all' OR a.target_audience = 'members')
        AND (a.expires_at IS NULL OR a.expires_at > NOW())
        ORDER BY a.is_pinned DESC, a.priority DESC, a.created_at DESC
        LIMIT 5";
$result = $conn->query($sql);
$announcements = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// Get member info for personalized greeting
$member_name = $_SESSION['member_name'] ?? 'Member';
$current_time = date('H');
$greeting = '';
if ($current_time < 12) {
    $greeting = 'Good Morning';
} elseif ($current_time < 17) {
    $greeting = 'Good Afternoon';
} else {
    $greeting = 'Good Evening';
}
?>

<!-- Hero Section -->
<div class="hero-section mb-4">
    <div class="hero-content">
        <div class="hero-text">
            <h1 class="hero-title"><?php echo $greeting; ?>, <?php echo htmlspecialchars($member_name); ?>! 👋</h1>
            <p class="hero-subtitle">Ready to crush your fitness goals today?</p>
        </div>
        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-number">28</div>
                <div class="stat-label">Days Active</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">85%</div>
                <div class="stat-label">Goal Progress</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="quick-action-card">
            <div class="action-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="action-content">
                <h6>Check In</h6>
                <p>Mark your attendance</p>
            </div>
            <div class="action-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="quick-action-card">
            <div class="action-icon">
                <i class="fas fa-dumbbell"></i>
            </div>
            <div class="action-content">
                <h6>Workout Plan</h6>
                <p>View today's routine</p>
            </div>
            <div class="action-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="quick-action-card">
            <div class="action-icon">
                <i class="fas fa-heartbeat"></i>
            </div>
            <div class="action-content">
                <h6>Health Stats</h6>
                <p>Monitor progress</p>
            </div>
            <div class="action-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="quick-action-card">
            <div class="action-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="action-content">
                <h6>Analytics</h6>
                <p>View insights</p>
            </div>
            <div class="action-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="row g-4">
    <!-- Left Column -->
    <div class="col-lg-8">
        <!-- Progress Overview -->
        <div class="content-card mb-4">
            <div class="card-header-custom">
                <h5><i class="fas fa-chart-area me-2"></i>Weekly Progress</h5>
                <div class="header-actions">
                    <select class="form-select form-select-sm">
                        <option>This Week</option>
                        <option>Last Week</option>
                        <option>This Month</option>
                    </select>
                </div>
            </div>
            <div class="card-body-custom">
                <div class="chart-container">
                    <canvas id="progressChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Announcements -->
        <div class="content-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-bullhorn me-2"></i>Announcements</h5>
                <span class="announcement-badge"><?php echo count($announcements); ?></span>
            </div>
            <div class="card-body-custom">
                <?php if (!empty($announcements)): ?>
                    <div class="announcement-list">
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item-new">
                                <div class="announcement-header">
                                    <h6 class="announcement-title">
                                        <?php if ($announcement['is_pinned']): ?>
                                            <i class="fas fa-thumbtack text-danger me-2" title="Pinned"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($announcement['title']); ?>
                                    </h6>
                                    <span class="priority-badge priority-<?php echo $announcement['priority']; ?>">
                                        <?php echo ucfirst($announcement['priority']); ?>
                                    </span>
                                </div>
                                <p class="announcement-message"><?php echo htmlspecialchars($announcement['message']); ?></p>
                                <div class="announcement-meta">
                                    <small><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($announcement['created_by_name']); ?></small>
                                    <small><i class="fas fa-clock me-1"></i><?php echo date('M d', strtotime($announcement['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bullhorn fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No announcements</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- Stats Cards -->
        <div class="stats-grid mb-4">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">12,450</div>
                    <div class="stat-label">Calories Burned</div>
                </div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">45</div>
                    <div class="stat-label">Workouts</div>
                </div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">28h</div>
                    <div class="stat-label">Total Time</div>
                </div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-target"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">85%</div>
                    <div class="stat-label">Goal Progress</div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="content-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
                <a href="#" class="view-all-link">View All</a>
            </div>
            <div class="card-body-custom">
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon success">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="activity-content">
                            <h6>Workout Completed</h6>
                            <p>Upper body strength training</p>
                            <small>2 hours ago</small>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon info">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="activity-content">
                            <h6>Gym Check-in</h6>
                            <p>Arrived at 8:30 AM</p>
                            <small>5 hours ago</small>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon warning">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="activity-content">
                            <h6>Achievement Unlocked</h6>
                            <p>7-day workout streak!</p>
                            <small>1 day ago</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('progressChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            datasets: [{
                label: 'Workouts',
                data: [3, 2, 4, 5, 3, 4, 6],
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#3B82F6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                }
            }
        }
    });
});
</script>

<?php include 'components/footer.php'; ?>
