<?php
// Set timezone to Philippines for correct time display
date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if user is logged in as admin
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$page_title = "Announcements Management";
include 'components/header.php';

// Include announcement-specific CSS and JS
echo '<link rel="stylesheet" href="../assets/css/admin/announcement.css">';
echo '<script src="../assets/js/admin/announcement.js" defer></script>';

// Get announcements with basic data
$sql = "SELECT a.*, 
               COALESCE(adm.name, 'System') as created_by_name
        FROM announcements a
        LEFT JOIN admins adm ON a.created_by = adm.id
        ORDER BY a.is_pinned DESC, a.priority DESC, a.created_at DESC";
$result = $conn->query($sql);
$announcements = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_announcements,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN is_pinned = 1 THEN 1 ELSE 0 END) as pinned_count,
    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count
FROM announcements";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result ? $stats_result->fetch_assoc() : [];
?>

<!-- Modern Hero Section -->
<div class="hero-section">
    <div class="hero-background"></div>
    <div class="container-fluid">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">
                    <i class="fas fa-bullhorn hero-icon"></i>
                    Announcements Hub
                </h1>
                <p class="hero-subtitle">Connect, communicate, and keep your community informed</p>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['total_announcements'] ?? 0; ?></span>
                        <span class="stat-label">Total</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['active_count'] ?? 0; ?></span>
                        <span class="stat-label">Active</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['pinned_count'] ?? 0; ?></span>
                        <span class="stat-label">Pinned</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['urgent_count'] ?? 0; ?></span>
                        <span class="stat-label">Urgent</span>
                    </div>
                </div>
            </div>
            <div class="hero-action">
                <button class="btn btn-primary btn-lg hero-btn" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                    <i class="fas fa-plus me-2"></i>
                    Create Announcement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Smart Filters Section -->
<div class="container-fluid mb-4">
    <div class="filters-card">
        <div class="filters-header">
            <h5 class="filters-title">
                <i class="fas fa-sliders-h me-2"></i>
                Smart Filters
            </h5>
            <button class="btn btn-outline-secondary btn-sm" id="clearFilters">
                <i class="fas fa-refresh me-1"></i>
                Clear All
            </button>
        </div>
        <div class="filters-body">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-filter me-1"></i>
                            Status
                        </label>
                        <select class="form-select filter-select" id="statusFilter">
                            <option value="all">All Status</option>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-flag me-1"></i>
                            Priority
                        </label>
                        <select class="form-select filter-select" id="priorityFilter">
                            <option value="all">All Priorities</option>
                            <option value="urgent">Urgent</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-users me-1"></i>
                            Audience
                        </label>
                        <select class="form-select filter-select" id="audienceFilter">
                            <option value="all">All Audiences</option>
                            <option value="all">Everyone</option>
                            <option value="members">Members Only</option>
                            <option value="staff">Staff Only</option>
                            <option value="walk_in">Walk-in Only</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-search me-1"></i>
                            Search
                        </label>
                        <div class="search-input-group">
                            <input type="text" class="form-control search-input" id="searchFilter" placeholder="Search announcements...">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Toggle and Count -->
<div class="container-fluid mb-4">
    <div class="view-controls">
        <div class="view-info">
            <span class="announcement-count" id="announcementCount">
                <?php echo count($announcements); ?> announcement<?php echo count($announcements) !== 1 ? 's' : ''; ?>
            </span>
        </div>
        <div class="view-toggle">
            <span class="view-label">View:</span>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm" id="viewModeGrid" title="Grid View">
                    <i class="fas fa-th-large"></i>
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm active" id="viewModeList" title="List View">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Announcements Container -->
<div class="container-fluid">
    <div id="announcementsList">
        <?php if (empty($announcements)): ?>
            <div class="empty-state-container">
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3 class="empty-title">No announcements yet</h3>
                    <p class="empty-description">Start building your communication hub by creating your first announcement</p>
                    <button class="btn btn-primary btn-lg empty-action" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                        <i class="fas fa-plus me-2"></i>
                        Create First Announcement
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="announcements-grid" id="announcementsContainer">
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-card modern-card" data-id="<?php echo $announcement['id']; ?>" 
                         data-status="<?php echo $announcement['status']; ?>" 
                         data-priority="<?php echo $announcement['priority']; ?>" 
                         data-audience="<?php echo $announcement['target_audience']; ?>">
                        
                        <!-- Priority Ribbon -->
                        <div class="priority-ribbon priority-<?php echo $announcement['priority']; ?>">
                            <?php echo ucfirst($announcement['priority']); ?>
                        </div>
                        
                        <!-- Pin Badge -->
                        <?php if ($announcement['is_pinned']): ?>
                            <div class="pin-badge" title="Pinned Announcement">
                                <i class="fas fa-thumbtack"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Card Header -->
                        <div class="card-header-modern">
                            <div class="header-top">
                                <div class="audience-tag audience-<?php echo $announcement['target_audience']; ?>">
                                    <?php echo ucfirst($announcement['target_audience']); ?>
                                </div>
                                <div class="status-indicator status-<?php echo $announcement['status']; ?>">
                                    <span class="status-dot"></span>
                                    <?php echo ucfirst($announcement['status']); ?>
                                </div>
                            </div>
                            
                            <h3 class="announcement-title-modern">
                                <?php echo htmlspecialchars($announcement['title']); ?>
                            </h3>
                        </div>
                        
                        <!-- Card Content -->
                        <div class="card-content-modern">
                            <div class="announcement-message-modern">
                                <?php echo nl2br(htmlspecialchars($announcement['message'])); ?>
                            </div>
                        </div>
                        
                        <!-- Card Footer -->
                        <div class="card-footer-modern">
                            <div class="footer-stats">
                                <div class="stat-item-modern">
                                    <i class="fas fa-user stat-icon"></i>
                                    <span class="stat-text"><?php echo htmlspecialchars($announcement['created_by_name'] ?: 'Admin'); ?></span>
                                </div>
                                <div class="stat-item-modern">
                                    <i class="fas fa-clock stat-icon"></i>
                                    <span class="stat-text"><?php echo date('M j, Y g:i A', strtotime($announcement['created_at'])); ?></span>
                                </div>
                                <?php if ($announcement['expires_at']): ?>
                                    <div class="stat-item-modern">
                                        <i class="fas fa-calendar-times stat-icon"></i>
                                        <span class="stat-text">Expires: <?php echo date('M j, Y', strtotime($announcement['expires_at'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Action Menu -->
                            <div class="action-menu">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm action-btn" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end modern-dropdown">
                                        <li>
                                            <a class="dropdown-item edit-announcement" href="#" data-id="<?php echo $announcement['id']; ?>">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item toggle-pin" href="#" data-id="<?php echo $announcement['id']; ?>">
                                                <i class="fas fa-thumbtack me-2"></i>
                                                <?php echo $announcement['is_pinned'] ? 'Unpin' : 'Pin'; ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item toggle-status" href="#" data-id="<?php echo $announcement['id']; ?>">
                                                <i class="fas fa-toggle-on me-2"></i>
                                                <?php echo $announcement['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger delete-announcement" href="#" data-id="<?php echo $announcement['id']; ?>">
                                                <i class="fas fa-trash me-2"></i>Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modern Add/Edit Announcement Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content modern-modal">
            <div class="modal-header modern-header">
                <div class="header-content">
                    <h5 class="modal-title" id="announcementModalLabel">
                        <i class="fas fa-plus me-2"></i>
                        New Announcement
                    </h5>
                    <p class="modal-subtitle">Share important information with your community</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="announcementForm" action="announcement_actions.php" method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="id" id="editId">
                
                <div class="modal-body modern-body">
                    <div class="form-sections">
                        <!-- Title Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class="fas fa-heading section-icon"></i>
                                <h6 class="section-title">Announcement Details</h6>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <div class="form-group-modern">
                                        <label for="announcementTitle" class="form-label-modern">Title *</label>
                                        <input type="text" class="form-control form-control-modern" id="announcementTitle" name="title" 
                                               placeholder="Enter a compelling title..." required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label for="announcementPriority" class="form-label-modern">Priority Level</label>
                                        <select class="form-select form-select-modern" id="announcementPriority" name="priority">
                                            <option value="low">Low Priority</option>
                                            <option value="medium" selected>Medium Priority</option>
                                            <option value="high">High Priority</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Audience Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class="fas fa-users section-icon"></i>
                                <h6 class="section-title">Target Audience & Settings</h6>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label for="announcementAudience" class="form-label-modern">Target Audience</label>
                                        <select class="form-select form-select-modern" id="announcementAudience" name="target_audience">
                                            <option value="all" selected>Everyone</option>
                                            <option value="members">Members Only</option>
                                            <option value="staff">Staff Only</option>
                                            <option value="walk_in">Walk-in Only</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label for="announcementExpires" class="form-label-modern">Expiry Date (Optional)</label>
                                        <input type="datetime-local" class="form-control form-control-modern" id="announcementExpires" name="expires_at">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Message Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class="fas fa-comment section-icon"></i>
                                <h6 class="section-title">Message Content</h6>
                            </div>
                            <div class="form-group-modern">
                                <label for="announcementMessage" class="form-label-modern">Message *</label>
                                <textarea class="form-control form-control-modern" id="announcementMessage" name="message" rows="8" 
                                          placeholder="Write your announcement message here..." required></textarea>
                                <div class="form-help">
                                    <i class="fas fa-info-circle me-1"></i>
                                    You can use basic HTML formatting. Keep your message clear and engaging.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Options Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <i class="fas fa-cog section-icon"></i>
                                <h6 class="section-title">Additional Options</h6>
                            </div>
                            <div class="form-check-modern">
                                <input class="form-check-input-modern" type="checkbox" id="announcementPinned" name="is_pinned">
                                <label class="form-check-label-modern" for="announcementPinned">
                                    <i class="fas fa-thumbtack me-2"></i>
                                    Pin this announcement (will appear at the top)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer modern-footer">
                    <button type="button" class="btn btn-secondary btn-modern" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern" id="submitBtn">
                        <i class="fas fa-paper-plane me-1"></i>
                        Post Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modern Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modern-modal">
            <div class="modal-header modern-header">
                <h5 class="modal-title" id="confirmModalTitle">Confirm Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body modern-body" id="confirmModalBody">
                Are you sure you want to perform this action?
            </div>
            <div class="modal-footer modern-footer">
                <button type="button" class="btn btn-secondary btn-modern" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-modern" id="confirmActionBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Modern Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <div id="announcementToast" class="toast modern-toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header modern-toast-header">
            <i class="fas fa-bullhorn me-2"></i>
            <strong class="me-auto" id="toastTitle">Announcement</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body modern-toast-body" id="toastBody">
            This is a toast message.
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>
