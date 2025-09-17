<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Members Management";
include 'components/header.php';
include '../includes/functions.php';

// Query: All Members
$result = $conn->query("
    SELECT 
        member_id, first_name, last_name, email, phone, membership_type, membership_duration, join_date, photo,
        COALESCE(expired_date, 
            CASE 
                WHEN membership_type = 'session' THEN DATE_ADD(join_date, INTERVAL 1 DAY)
                WHEN membership_type = 'regular' AND membership_duration IS NOT NULL THEN DATE_ADD(join_date, INTERVAL membership_duration MONTH)
                ELSE DATE_ADD(join_date, INTERVAL 30 DAY)
            END
        ) AS expired_date
    FROM members
    ORDER BY join_date DESC
");
$allMembers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allMembers[] = $row;
    }
}

// Query: Active Members
$result = $conn->query("
    SELECT 
        member_id, first_name, last_name, email, phone, membership_type, membership_duration, join_date, photo,
        COALESCE(expired_date, 
            CASE 
                WHEN membership_type = 'session' THEN DATE_ADD(join_date, INTERVAL 1 DAY)
                WHEN membership_type = 'regular' AND membership_duration IS NOT NULL THEN DATE_ADD(join_date, INTERVAL membership_duration MONTH)
                ELSE DATE_ADD(join_date, INTERVAL 30 DAY)
            END
        ) AS expired_date
    FROM members
    WHERE COALESCE(expired_date, 
        CASE 
            WHEN membership_type = 'session' THEN DATE_ADD(join_date, INTERVAL 1 DAY)
            WHEN membership_type = 'regular' AND membership_duration IS NOT NULL THEN DATE_ADD(join_date, INTERVAL membership_duration MONTH)
            ELSE DATE_ADD(join_date, INTERVAL 30 DAY)
        END
    ) >= CURDATE()
    ORDER BY join_date DESC
");
$activeMembers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $activeMembers[] = $row;
    }
}

// Query: Expired Members
$result = $conn->query("
    SELECT 
        member_id, first_name, last_name, email, phone, membership_type, membership_duration, join_date, photo,
        COALESCE(expired_date, 
            CASE 
                WHEN membership_type = 'session' THEN DATE_ADD(join_date, INTERVAL 1 DAY)
                WHEN membership_type = 'regular' AND membership_duration IS NOT NULL THEN DATE_ADD(join_date, INTERVAL membership_duration MONTH)
                ELSE DATE_ADD(join_date, INTERVAL 30 DAY)
            END
        ) AS expired_date
    FROM members
    WHERE COALESCE(expired_date, 
        CASE 
            WHEN membership_type = 'session' THEN DATE_ADD(join_date, INTERVAL 1 DAY)
            WHEN membership_type = 'regular' AND membership_duration IS NOT NULL THEN DATE_ADD(join_date, INTERVAL membership_duration MONTH)
            ELSE DATE_ADD(join_date, INTERVAL 30 DAY)
        END
    ) < CURDATE()
    ORDER BY join_date DESC
");
$expiredMembers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $expiredMembers[] = $row;
    }
}

// Function to render table rows
function renderRows($members) {
    if (!$members) {
        return "<tr><td colspan='8' class='text-center text-muted py-4'><i class='fas fa-users fa-2x mb-2'></i><br>No members found.</td></tr>";
    }
    
    $rows = "";
    foreach ($members as $m) {
        $badge = match(strtolower($m['membership_type'])) {
            'regular' => 'primary',
            'session' => 'success',
            default   => 'secondary'
        };
        $fullName = htmlspecialchars($m['first_name'] . ' ' . $m['last_name']);
        $memberId = htmlspecialchars($m['member_id']);
        $email = htmlspecialchars($m['email']);
        $phone = htmlspecialchars($m['phone']);
        $membership = htmlspecialchars(ucfirst($m['membership_type']));
        if ($m['membership_type'] === 'regular' && $m['membership_duration']) {
            $durationText = $m['membership_duration'] == 12 ? '1 Year' : $m['membership_duration'] . ' Month' . ($m['membership_duration'] > 1 ? 's' : '');
            $membership .= ' (' . $durationText . ')';
        }
        $join = htmlspecialchars($m['join_date']);
        $exp = htmlspecialchars($m['expired_date']);
        $photoHtml = $m['photo'] ? "<img src='../../uploads/member_photos/{$m['photo']}' alt='Profile Picture' class='member-photo' onerror='this.style.display=\"none\"; this.nextElementSibling.style.display=\"block\";'>" : "<i class='fas fa-user'></i>";
        if ($m['photo']) {
            $photoHtml .= "<i class='fas fa-user' style='display:none;'></i>";
        }
        
        // Check if membership is expired
        $isExpired = $m['expired_date'] && new DateTime($m['expired_date']) < new DateTime();
        
        // Build action buttons
        $actionButtons = "
            <button class='btn btn-sm btn-outline-info btn-view' data-member-id='{$memberId}' title='View Member'>
                <i class='fas fa-eye'></i>
            </button>
            <button class='btn btn-sm btn-outline-success btn-renew' data-member-id='{$memberId}' title='Renew Membership'>
                <i class='fas fa-sync-alt'></i>
            </button>
            <button class='btn btn-sm btn-outline-danger btn-delete' data-member-id='{$memberId}' title='Delete Member'>
                <i class='fas fa-trash'></i>
            </button>";
        
        $rows .= "
        <tr data-member-id='{$memberId}' class='member-row'>
            <td><span class='member-id-badge'>{$memberId}</span></td>
            <td>
                <div class='member-info'>
                    <div class='member-avatar'>{$photoHtml}</div>
                    <div class='member-details'>
                        <div class='member-name'>{$fullName}</div>
                        <div class='member-email text-muted small'>{$email}</div>
                    </div>
                </div>
            </td>
            <td>{$phone}</td>
            <td><span class='badge bg-{$badge} membership-badge'><i class='fas fa-calendar-alt me-1'></i> {$membership}</span></td>
            <td>{$join}</td>
            <td>{$exp}</td>
            <td>
                <div class='action-buttons'>
                    {$actionButtons}
                </div>
            </td>
        </tr>";
    }
    return $rows;
}
?>

<!-- Custom Styles -->
<link rel="stylesheet" href="../assets/css/admin/member.css">
<style>
.form-control:disabled,
.form-select:disabled,
textarea:disabled {
    background-color: #f8f9fa;
    opacity: 0.6;
    cursor: not-allowed;
}

.form-control:disabled:focus,
.form-select:disabled:focus,
textarea:disabled:focus {
    box-shadow: none;
    border-color: #dee2e6;
}

.form-section.disabled-section {
    opacity: 0.7;
}

.form-section.disabled-section .section-header {
    color: #6c757d;
}

#expiredMembershipNotice {
    border-left: 4px solid #ffc107;
    background-color: #fff3cd;
    color: #856404;
}

#expiredMembershipNotice i {
    color: #ffc107;
}

.table-custom {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    overflow: hidden;
}

.table-custom th {
    background: rgba(59, 130, 246, 0.2);
    border: none;
    color: white;
    font-weight: 600;
}

.table-custom td {
    border-color: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.9);
}

.stats-card {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(147, 51, 234, 0.2));
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 15px;
    backdrop-filter: blur(10px);
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
}

.btn-custom {
    border-radius: 25px;
    padding: 0.5rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-custom:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: rgba(255, 255, 255, 0.6);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-<?= $_SESSION['type'] ?? 'success' ?> alert-dismissible fade show" role="alert">
    <i class="fas fa-<?= $_SESSION['type'] === 'danger' ? 'exclamation-triangle' : 'check-circle' ?> me-2"></i>
    <?= $_SESSION['message'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['message'], $_SESSION['type']); endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card border-left-primary">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Members
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($allMembers) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card border-left-success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Active Members
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($activeMembers) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card border-left-warning">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Expired Members
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($expiredMembers) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card border-left-info">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            This Month
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= count(array_filter($allMembers, function($m) {
                                return date('Y-m', strtotime($m['join_date'])) === date('Y-m');
                            })) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Card -->
<div class="card main-content-card">
    <div class="card-header">
        <h6 class="gym-brand">
            <i class="fas fa-dumbbell gym-icon"></i>Gym Members Management
        </h6>
        <div class="d-flex align-items-center">
            <div class="input-group me-3" style="width: 300px;">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="searchInput" placeholder="Search gym members...">
            </div>
            <button class="btn btn-primary btn-sm" onclick="window.location.href='register.php'">
                <i class="fas fa-plus me-1"></i>Add New Gym Member
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <ul class="nav nav-tabs" id="memberTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-members" type="button" role="tab">
                    <i class="fas fa-dumbbell me-1"></i>All Gym Members
                    <span class="badge bg-secondary ms-1"><?= count($allMembers) ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-members" type="button" role="tab">
                    <i class="fas fa-user-check me-1"></i>Active Gym Members
                    <span class="badge bg-success ms-1"><?= count($activeMembers) ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="expired-tab" data-bs-toggle="tab" data-bs-target="#expired-members" type="button" role="tab">
                    <i class="fas fa-user-clock me-1"></i>Expired Gym Members
                    <span class="badge bg-warning ms-1"><?= count($expiredMembers) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="memberTabsContent">
            <!-- All Members -->
            <div class="tab-pane fade show active" id="all-members" role="tabpanel">
                <div class="table-responsive table-container">
                    <table class="table table-hover mb-0 table-custom" id="membersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Member</th>
                                <th>Phone</th>
                                <th>Membership</th>
                                <th>Join Date</th>
                                <th>Expired Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?= renderRows($allMembers) ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Active Members -->
            <div class="tab-pane fade" id="active-members" role="tabpanel">
                <div class="table-responsive table-container">
                    <table class="table table-hover mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Member</th>
                                <th>Phone</th>
                                <th>Membership</th>
                                <th>Join Date</th>
                                <th>Expired Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?= renderRows($activeMembers) ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Expired Members -->
            <div class="tab-pane fade" id="expired-members" role="tabpanel">
                <div class="table-responsive table-container">
                    <table class="table table-hover mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Member</th>
                                <th>Phone</th>
                                <th>Membership</th>
                                <th>Join Date</th>
                                <th>Expired Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?= renderRows($expiredMembers) ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Member Modal -->
<div class="modal fade" id="viewMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="modal-title">Member Profile</h5>
                        <p class="modal-subtitle">View complete member information</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="memberDetails">
                    <!-- populated by JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>



<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Member
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                    <h5>Are you sure?</h5>
                </div>
                <p>You are about to delete <strong id="delete_member_name"></strong> (<code id="delete_member_id"></code>).</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone and will permanently remove the member from the system.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteMember">
                    <i class="fas fa-trash me-1"></i>Delete Member
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Renew Membership Modal -->
<div class="modal fade" id="renewMembershipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: rgba(30, 41, 59, 0.95); -webkit-backdrop-filter: blur(20px); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 20px; color: #fff;">
            <div class="modal-header" style="background: linear-gradient(135deg, #4e73df, #2e59d9); border-bottom: 1px solid rgba(255, 255, 255, 0.2); border-radius: 20px 20px 0 0; padding: 24px 30px;">
                <div class="d-flex align-items-center">
                    <div class="me-3" style="width: 48px; height: 48px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-sync-alt text-white" style="font-size: 1.5rem;"></i>
            </div>
                    <div>
                        <h5 class="modal-title text-white mb-0" style="font-weight: 700; font-size: 1.5rem;">Renew Membership</h5>
                        <p class="text-white-50 mb-0" style="font-size: 0.9rem; margin-top: 4px;">Extend membership duration</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
            </div>
            <div class="modal-body" style="padding: 30px; background: rgba(255, 255, 255, 0.02);">
                <!-- Profile Section -->
                <div class="profile-section mb-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 24px; margin-bottom: 24px;">
                    <div class="d-flex align-items-center">
                        <div class="profile-avatar me-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #3B82F6, #1D4ED8); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 12px 30px rgba(59, 130, 246, 0.3); overflow: hidden; flex-shrink: 0;">
                            <img id="renew_member_photo" src="" alt="Profile" class="profile-picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: none;">
                            <i class="fas fa-user text-white" id="renew_member_icon" style="font-size: 2.5rem;"></i>
                        </div>
                        <div class="profile-info flex-grow-1">
                            <h4 class="text-white mb-2" style="font-weight: 600; font-size: 1.5rem;">Renew Membership for <strong id="renew_member_name" style="color: #4e73df;"></strong></h4>
                            <p class="text-white-50 mb-1" style="font-size: 1rem;">(<code id="renew_member_id" style="background: rgba(78, 115, 223, 0.1); color: #4e73df; padding: 4px 8px; border-radius: 6px; font-size: 0.9rem;">MEM-2025-0128</code>)</p>
                            <div class="profile-details mt-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <i class="fas fa-envelope me-2" style="color: #3B82F6; width: 16px;"></i>
                                            <span class="text-white-50" style="font-size: 0.9rem;">Email:</span>
                                            <span id="renew_member_email" class="text-white ms-2" style="font-weight: 500; font-size: 0.9rem;">mark.sambrano@email.com</span>
                                        </div>
                                        <div class="mb-2">
                                            <i class="fas fa-phone me-2" style="color: #3B82F6; width: 16px;"></i>
                                            <span class="text-white-50" style="font-size: 0.9rem;">Phone:</span>
                                            <span id="renew_member_phone" class="text-white ms-2" style="font-weight: 500; font-size: 0.9rem;">+63 912 345 6789</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <i class="fas fa-calendar-alt me-2" style="color: #3B82F6; width: 16px;"></i>
                                            <span class="text-white-50" style="font-size: 0.9rem;">Age:</span>
                                            <span id="renew_member_age" class="text-white ms-2" style="font-weight: 500; font-size: 0.9rem;">28 years old</span>
                                        </div>
                                        <div class="mb-2">
                                            <i class="fas fa-map-marker-alt me-2" style="color: #3B82F6; width: 16px;"></i>
                                            <span class="text-white-50" style="font-size: 0.9rem;">Location:</span>
                                            <span id="renew_member_location" class="text-white ms-2" style="font-weight: 500; font-size: 0.9rem;">Manila, Philippines</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                 </div>
                 
                <div class="alert mb-4" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 20px;">
                    <h6 class="text-white mb-3" style="font-weight: 600; display: flex; align-items: center;">
                        <i class="fas fa-info-circle me-2" style="color: #3B82F6;"></i>Current Membership Details
                    </h6>
                     <div class="row">
                         <div class="col-md-6">
                            <div class="mb-2">
                                <strong class="text-white-50" style="font-size: 0.9rem;">Type:</strong> 
                                <span id="current_membership_type" class="text-white ms-2" style="font-weight: 600;">Regular</span>
                            </div>
                            <div class="mb-2">
                                <strong class="text-white-50" style="font-size: 0.9rem;">Duration:</strong> 
                                <span id="current_membership_duration" class="text-white ms-2" style="font-weight: 600;">1 Month</span>
                            </div>
                         </div>
                         <div class="col-md-6">
                            <div class="mb-2">
                                <strong class="text-white-50" style="font-size: 0.9rem;">Join Date:</strong> 
                                <span id="current_join_date" class="text-white ms-2" style="font-weight: 600;">2025-09-14</span>
                            </div>
                            <div class="mb-2">
                                <strong class="text-white-50" style="font-size: 0.9rem;">Expires:</strong> 
                                <span id="current_expired_date" class="text-white ms-2" style="font-weight: 600;">2025-10-14</span>
                            </div>
                         </div>
                     </div>
                 </div>
                
                <form id="renewMembershipForm">
                    <div class="mb-4">
                        <label for="membership_type" class="form-label text-white" style="font-weight: 600; font-size: 0.95rem; margin-bottom: 12px;">Membership Type *</label>
                        <select class="form-select" id="membership_type" name="membership_type" required style="background: rgba(255, 255, 255, 0.1); border: 2px solid rgba(255, 255, 255, 0.2); color: #fff; border-radius: 12px; padding: 12px 16px; font-size: 0.9rem; transition: all 0.3s ease;">
                            <option value="" style="background: #1f2937; color: #fff;">Select membership type</option>
                            <option value="regular" style="background: #1f2937; color: #fff;">Regular (Monthly/Yearly)</option>
                        </select>
                    </div>
                    
                    <div class="mb-4" id="duration_group" style="display: none;">
                        <label for="membership_duration" class="form-label text-white" style="font-weight: 600; font-size: 0.95rem; margin-bottom: 12px;">Duration (Months) *</label>
                        <select class="form-select" id="membership_duration" name="membership_duration" style="background: rgba(255, 255, 255, 0.1); border: 2px solid rgba(255, 255, 255, 0.2); color: #fff; border-radius: 12px; padding: 12px 16px; font-size: 0.9rem; transition: all 0.3s ease;">
                            <option value="" style="background: #1f2937; color: #fff;">Select duration</option>
                            <option value="1" style="background: #1f2937; color: #fff;">1 Month</option>
                            <option value="3" style="background: #1f2937; color: #fff;">3 Months</option>
                            <option value="6" style="background: #1f2937; color: #fff;">6 Months</option>
                            <option value="12" style="background: #1f2937; color: #fff;">12 Months (1 Year)</option>
                        </select>
                    </div>
                    
                    <div class="alert" style="background: rgba(78, 115, 223, 0.1); border: 1px solid rgba(78, 115, 223, 0.2); border-radius: 12px; padding: 16px;">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle me-2 mt-1" style="color: #4e73df; font-size: 1rem;"></i>
                            <div>
                                <strong class="text-white" style="font-size: 0.9rem;">Note:</strong> 
                                <span class="text-white-50" style="font-size: 0.9rem;">The membership will be renewed starting from today's date.</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="background: rgba(255, 255, 255, 0.05); border-top: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0 0 20px 20px; padding: 20px 30px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: #6b7280; border: none; color: #fff; padding: 10px 20px; border-radius: 10px; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmRenewMembership" style="background: linear-gradient(135deg, #4e73df, #2e59d9); border: none; color: #fff; padding: 10px 20px; border-radius: 10px; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fas fa-sync-alt me-2"></i>Renew Membership
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Enhanced Renew Membership Modal Styles */
#renewMembershipModal .form-select:focus {
    background: rgba(255, 255, 255, 0.15) !important;
    border-color: #4e73df !important;
    box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.2) !important;
    outline: none !important;
}

#renewMembershipModal .form-select:hover {
    background: rgba(255, 255, 255, 0.12) !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
}

#renewMembershipModal .btn-primary:hover {
    background: linear-gradient(135deg, #2e59d9, #2653d4) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 25px rgba(78, 115, 223, 0.4) !important;
}

#renewMembershipModal .btn-secondary:hover {
    background: #4b5563 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 25px rgba(107, 114, 128, 0.4) !important;
}

#renewMembershipModal .modal-content {
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5) !important;
}

#renewMembershipModal .alert {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Animation for modal appearance */
#renewMembershipModal .modal-content {
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Enhanced icon animations */
#renewMembershipModal .fas.fa-sync-alt {
    animation: rotate 2s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

#renewMembershipModal .fas.fa-user-check {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Profile Section Styling */
#renewMembershipModal .profile-section {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

#renewMembershipModal .profile-section:hover {
    background: rgba(255, 255, 255, 0.08) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

#renewMembershipModal .profile-avatar {
    transition: all 0.3s ease;
}

#renewMembershipModal .profile-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 15px 35px rgba(59, 130, 246, 0.4);
}

#renewMembershipModal .profile-details .row > div {
    transition: all 0.3s ease;
}

#renewMembershipModal .profile-details .row > div:hover {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 8px;
    padding: 8px;
    margin: -8px;
}

/* Profile photo styling */
#renewMembershipModal .profile-picture {
    transition: all 0.3s ease;
}

#renewMembershipModal .profile-picture:hover {
    transform: scale(1.1);
}

/* Responsive profile section */
@media (max-width: 768px) {
    #renewMembershipModal .profile-section .d-flex {
        flex-direction: column;
        text-align: center;
    }
    
    #renewMembershipModal .profile-avatar {
        margin: 0 auto 20px auto;
    }
    
    #renewMembershipModal .profile-details .row {
        text-align: left;
    }
}
</style>

<script>
// Search functionality - works across all tabs
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.member-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});



// Delete functionality
let toDeleteId = null;
let toDeleteName = '';

// Renew functionality
let toRenewId = null;
let toRenewName = '';

// Delete button click handler
document.addEventListener('click', async function(e) {
    if (e.target.closest('.btn-delete')) {
        const btn = e.target.closest('.btn-delete');
        const memberId = btn.dataset.memberId;
        
        // Delete button clicked
        
        try {
            // Fetch member data to get name
            const response = await fetch(`actions/members_actions.php?action=view&member_id=${encodeURIComponent(memberId)}`);
            const data = await response.json();
            
            if (data.success) {
                const member = data.member;
                toDeleteId = member.member_id;
                toDeleteName = `${member.first_name} ${member.last_name}`;
                
                // Populate delete modal
                document.getElementById('delete_member_name').textContent = toDeleteName;
                document.getElementById('delete_member_id').textContent = toDeleteId;
                
                // Show delete modal
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteMemberModal'));
                deleteModal.show();
            } else {
                alert('Failed to load member data');
            }
        } catch (error) {
            console.error('Error loading member data:', error);
            alert('Failed to load member data');
        }
    }
    
    // Renew button click handler
    if (e.target.closest('.btn-renew')) {
        const btn = e.target.closest('.btn-renew');
        const memberId = btn.dataset.memberId;
        
        try {
            // Fetch member data to get name
            const response = await fetch(`actions/members_actions.php?action=view&member_id=${encodeURIComponent(memberId)}`);
            const data = await response.json();
            
            if (data.success) {
                const member = data.member;
                toRenewId = member.member_id;
                toRenewName = `${member.first_name} ${member.last_name}`;
                
                                 // Populate renew modal
                 document.getElementById('renew_member_name').textContent = toRenewName;
                 document.getElementById('renew_member_id').textContent = toRenewId;
                 
                 // Populate profile information
                 document.getElementById('renew_member_email').textContent = member.email || 'N/A';
                 document.getElementById('renew_member_phone').textContent = member.phone || 'N/A';
                 
                 // Calculate age from birth_date
                 if (member.birth_date) {
                     const birthDate = new Date(member.birth_date);
                     const today = new Date();
                     let age = today.getFullYear() - birthDate.getFullYear();
                     const monthDiff = today.getMonth() - birthDate.getMonth();
                     if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                         age--;
                     }
                     document.getElementById('renew_member_age').textContent = age + ' years old';
                 } else {
                     document.getElementById('renew_member_age').textContent = 'N/A';
                 }
                 
                 // Set location
                 document.getElementById('renew_member_location').textContent = member.address ? member.address.split(',')[0] + ', Philippines' : 'N/A';
                 
                 // Handle profile photo
                 const profilePhoto = document.getElementById('renew_member_photo');
                 const profileIcon = document.getElementById('renew_member_icon');
                 if (member.photo && member.photo.trim() !== '') {
                     profilePhoto.src = '../uploads/member_photos/' + member.photo;
                     profilePhoto.style.display = 'block';
                     profileIcon.style.display = 'none';
                 } else {
                     profilePhoto.style.display = 'none';
                     profileIcon.style.display = 'block';
                 }
                 
                 // Populate current membership details
                 document.getElementById('current_membership_type').textContent = member.membership_type ? member.membership_type.charAt(0).toUpperCase() + member.membership_type.slice(1) : 'N/A';
                 
                 let durationText = 'N/A';
                 if (member.membership_type === 'regular' && member.membership_duration) {
                     durationText = member.membership_duration == 12 ? '1 Year' : member.membership_duration + ' Month' + (member.membership_duration > 1 ? 's' : '');
                 } else if (member.membership_type === 'session') {
                     durationText = '1 Day';
                 }
                 document.getElementById('current_membership_duration').textContent = durationText;
                 
                 document.getElementById('current_join_date').textContent = member.join_date || 'N/A';
                 document.getElementById('current_expired_date').textContent = member.expired_date || 'N/A';
                 
                 // Reset form
                 document.getElementById('renewMembershipForm').reset();
                 document.getElementById('duration_group').style.display = 'none';
                
                // Show renew modal
                const renewModal = new bootstrap.Modal(document.getElementById('renewMembershipModal'));
                renewModal.show();
            } else {
                alert('Failed to load member data');
            }
        } catch (error) {
            console.error('Error loading member data:', error);
            alert('Failed to load member data');
        }
    }
});

// Delete confirmation handler
document.getElementById('confirmDeleteMember').addEventListener('click', async function() {
    if (!toDeleteId) return;
    
    const btn = this;
    const originalBtnText = btn.innerHTML;
    
    // Show loading state
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...
    `;
    btn.disabled = true;
    
    try {
        // Send delete request
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('member_id', toDeleteId);
        
        const response = await fetch('actions/members_actions.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Remove row from table
            const row = document.querySelector(`tr[data-member-id="${toDeleteId}"]`);
            if (row) {
                row.remove();
            }
            
            // Close modal
            const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteMemberModal'));
            deleteModal.hide();
            
            // Show success message
            alert('Member deleted successfully!');
            
            // Reload page to update statistics
            window.location.reload();
        } else {
            throw new Error(data.message || 'Delete failed');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(error.message || 'An error occurred while deleting member');
    } finally {
        btn.innerHTML = originalBtnText;
        btn.disabled = false;
    }
});

// Membership type change handler
document.getElementById('membership_type').addEventListener('change', function() {
    const durationGroup = document.getElementById('duration_group');
    const durationSelect = document.getElementById('membership_duration');
    
    if (this.value === 'regular') {
        durationGroup.style.display = 'block';
        durationSelect.required = true;
    } else {
        durationGroup.style.display = 'none';
        durationSelect.required = false;
        durationSelect.value = '';
    }
});

// Renew membership confirmation handler
document.getElementById('confirmRenewMembership').addEventListener('click', async function() {
    if (!toRenewId) return;
    
    const membershipType = document.getElementById('membership_type').value;
    const membershipDuration = document.getElementById('membership_duration').value;
    
    if (!membershipType) {
        alert('Please select a membership type');
        return;
    }
    
    if (membershipType === 'regular' && !membershipDuration) {
        alert('Please select a duration for regular membership');
        return;
    }
    
    const btn = this;
    const originalBtnText = btn.innerHTML;
    
    // Show loading state
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Renewing...
    `;
    btn.disabled = true;
    
    try {
        // Send renew request
        const formData = new FormData();
        formData.append('action', 'renew_membership');
        formData.append('member_id', toRenewId);
        formData.append('membership_type', membershipType);
        formData.append('membership_duration', membershipDuration);
        
        const response = await fetch('actions/members_actions.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
                 if (data.success) {
             // Close modal
             const renewModal = bootstrap.Modal.getInstance(document.getElementById('renewMembershipModal'));
             renewModal.hide();
             
             // Show success message with details
             const updatedMember = data.member;
             let durationText = 'N/A';
             if (updatedMember.membership_type === 'regular' && updatedMember.membership_duration) {
                 durationText = updatedMember.membership_duration == 12 ? '1 Year' : updatedMember.membership_duration + ' Month' + (updatedMember.membership_duration > 1 ? 's' : '');
             } else if (updatedMember.membership_type === 'session') {
                 durationText = '1 Day';
             }
             
             alert(`Membership renewed successfully!\n\nNew Details:\n- Type: ${updatedMember.membership_type.charAt(0).toUpperCase() + updatedMember.membership_type.slice(1)}\n- Duration: ${durationText}\n- New Join Date: ${updatedMember.join_date}\n- New Expiry Date: ${updatedMember.expired_date}`);
             
             // Reload page to update data
             window.location.reload();
         } else {
            throw new Error(data.message || 'Renewal failed');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(error.message || 'An error occurred while renewing membership');
    } finally {
        btn.innerHTML = originalBtnText;
        btn.disabled = false;
    }
});
</script>

<script src="../assets/js/admin/member.js"></script>
<?php include 'components/footer.php'; ?>