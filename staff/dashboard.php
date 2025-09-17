<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Authentication check
if (!isset($_SESSION['staff_logged_in']) || !$_SESSION['staff_logged_in']) {
    header('Location: login.php');
    exit;
}

require_once '../includes/functions.php';

$page_title = "Staff Dashboard";
include 'components/header.php';

// Include dashboard-specific CSS
echo '<link rel="stylesheet" href="../assets/css/staff/dashboard.css">';

// Get recent announcements for staff
$announcements = getRecentAnnouncements(5, 'staff');

// Get current date and time
$currentDate = date('l, F j, Y');
$currentTime = date('g:i A');

// Get staff member's shift info (you can customize this based on your database)
$shiftStart = '8:00 AM';
$shiftEnd = '5:00 PM';
$shiftStatus = 'On Duty';

?>

<div class="container-fluid px-4">
    <div class="row g-4">
        <!-- Welcome Card with Enhanced Info -->
        <div class="col-12">
            <div class="card shadow-sm welcome-card">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-2"><i class="fas fa-tachometer-alt me-2 text-green-400"></i>Welcome back, <?= htmlspecialchars($_SESSION['staff_name']) ?>!</h4>
                            <p class="mb-2 text-light">Manage gym operations, track member attendance, and handle daily tasks from your staff dashboard.</p>
                            <div class="d-flex flex-wrap gap-3">
                                <span class="badge bg-success"><i class="fas fa-calendar me-1"></i><?= $currentDate ?></span>
                                <span class="badge bg-info"><i class="fas fa-clock me-1"></i><?= $currentTime ?></span>
                                <span class="badge bg-warning text-dark"><i class="fas fa-user-clock me-1"></i><?= $shiftStatus ?></span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="shift-info">
                                <h6 class="text-muted mb-1">Shift Hours</h6>
                                <h5 class="mb-0"><?= $shiftStart ?> - <?= $shiftEnd ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Stats Cards -->
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card text-center shadow-sm stats-card">
                <div class="card-body p-4">
                    <div class="stats-icon mb-3">
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                    <h6 class="card-title text-muted mb-2">Total Members</h6>
                    <h3 class="mb-1">150</h3>
                    <small class="text-success"><i class="fas fa-arrow-up me-1"></i>+12 this week</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card text-center shadow-sm stats-card">
                <div class="card-body p-4">
                    <div class="stats-icon mb-3">
                        <i class="fas fa-calendar-check fa-2x text-success"></i>
                    </div>
                    <h6 class="card-title text-muted mb-2">Today's Attendance</h6>
                    <h3 class="mb-1">45</h3>
                    <small class="text-info"><i class="fas fa-clock me-1"></i>Last: 2 min ago</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card text-center shadow-sm stats-card">
                <div class="card-body p-4">
                    <div class="stats-icon mb-3">
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                    <h6 class="card-title text-muted mb-2">Active Sessions</h6>
                    <h3 class="mb-1">12</h3>
                    <small class="text-warning"><i class="fas fa-running me-1"></i>Peak hours</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card text-center shadow-sm stats-card">
                <div class="card-body p-4">
                    <div class="stats-icon mb-3">
                        <i class="fas fa-dollar-sign fa-2x text-info"></i>
                    </div>
                    <h6 class="card-title text-muted mb-2">Today's Revenue</h6>
                    <h3 class="mb-1">₱15,000</h3>
                    <small class="text-success"><i class="fas fa-chart-line me-1"></i>+8% vs yesterday</small>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <a href="attendance.php" class="quick-action-btn">
                                <div class="text-center p-3">
                                    <i class="fas fa-clipboard-check fa-2x mb-2 text-success"></i>
                                    <h6>Mark Attendance</h6>
                                    <small class="text-muted">Record member check-ins</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="schedule.php" class="quick-action-btn">
                                <div class="text-center p-3">
                                    <i class="fas fa-calendar-alt fa-2x mb-2 text-primary"></i>
                                    <h6>View Schedule</h6>
                                    <small class="text-muted">Check today's classes</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="profile.php" class="quick-action-btn">
                                <div class="text-center p-3">
                                    <i class="fas fa-user-edit fa-2x mb-2 text-info"></i>
                                    <h6>Update Profile</h6>
                                    <small class="text-muted">Manage your information</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="salary.php" class="quick-action-btn">
                                <div class="text-center p-3">
                                    <i class="fas fa-money-bill-wave fa-2x mb-2 text-warning"></i>
                                    <h6>Salary Info</h6>
                                    <small class="text-muted">View earnings & history</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Announcements Section -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bullhorn me-2 text-info"></i>Recent Announcements</h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary"><?= count($announcements) ?> announcements</span>
                        <button class="btn btn-sm btn-outline-light" onclick="refreshAnnouncements()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($announcements)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bullhorn fa-4x text-muted mb-3"></i>
                            <h6 class="text-muted mb-2">No announcements at the moment</h6>
                            <p class="text-muted small">Check back later for updates from management</p>
                        </div>
                    <?php else: ?>
                        <div class="announcements-container">
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-item <?= $announcement['is_pinned'] ? 'pinned' : '' ?>" 
                                     style="border-left: 4px solid <?= $announcement['is_pinned'] ? '#ffc107' : '#e3e6f0' ?>;">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="announcement-title mb-0"><?= htmlspecialchars($announcement['title']) ?></h6>
                                        <div class="d-flex gap-2">
                                            <?php if ($announcement['is_pinned']): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-thumbtack me-1"></i>Pinned
                                                </span>
                                            <?php endif; ?>
                                            <?= getAnnouncementPriorityBadge($announcement['priority']) ?>
                                            <?= getAnnouncementAudienceBadge($announcement['target_audience']) ?>
                                        </div>
                                    </div>
                                    <p class="announcement-message mb-3"><?= nl2br(htmlspecialchars($announcement['message'])) ?></p>
                                    <div class="announcement-meta">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($announcement['created_by_name']) ?> • 
                                                <i class="fas fa-clock me-1"></i><?= date('M j, Y g:i A', strtotime($announcement['created_at'])) ?>
                                                <?php if ($announcement['expires_at']): ?>
                                                    • <i class="fas fa-calendar-times me-1"></i>Expires: <?= date('M j, Y', strtotime($announcement['expires_at'])) ?>
                                                <?php endif; ?>
                                            </small>
                                            <div class="announcement-actions">
                                                <button class="btn btn-sm btn-outline-primary" onclick="markAsRead(<?= $announcement['id'] ?>)">
                                                    <i class="fas fa-check me-1"></i>Mark Read
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2 text-success"></i>Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        <div class="activity-item">
                            <div class="activity-icon bg-success">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="activity-content">
                                <h6 class="mb-1">Member Check-in</h6>
                                <p class="mb-1">John Doe checked in at 2:30 PM</p>
                                <small class="text-muted">2 minutes ago</small>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon bg-info">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <div class="activity-content">
                                <h6 class="mb-1">Class Scheduled</h6>
                                <p class="mb-1">Yoga class added for tomorrow 9:00 AM</p>
                                <small class="text-muted">15 minutes ago</small>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon bg-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="activity-content">
                                <h6 class="mb-1">Equipment Alert</h6>
                                <p class="mb-1">Treadmill #3 needs maintenance</p>
                                <small class="text-muted">1 hour ago</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Include enhanced dashboard JavaScript -->
<script src="../assets/js/staff/dashboard.js"></script>

<?php include 'components/footer.php'; ?>
