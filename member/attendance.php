<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Attendance Logs";

include '../includes/auth.php';
requireMemberAuth();

include 'components/header.php';
include '../includes/db.php';

$member_id = getCurrentMemberId();

// Debug: Check if member_id is valid
if (!$member_id) {
    error_log("Member ID not found in session: " . print_r($_SESSION, true));
    header('Location: login.php');
    exit;
}

// Get member info for display
$member_sql = "SELECT first_name, last_name, member_id, membership_type FROM members WHERE id = ?";
$member_stmt = $conn->prepare($member_sql);
if ($member_stmt) {
    $member_stmt->bind_param('i', $member_id);
    $member_stmt->execute();
    $member_info = $member_stmt->get_result()->fetch_assoc();
    $member_stmt->close();
} else {
    error_log("Database error in member info: " . $conn->error);
    $member_info = null;
}

// Get attendance records for the logged-in member
$attendance_records = [];

// Query to get attendance records
$sql = "SELECT 
            a.id,
            a.date,
            a.time_in,
            a.time_out,
            'current' as source
        FROM attendance a 
        WHERE a.member_id = ? 
        ORDER BY a.date DESC, a.time_in DESC
        LIMIT 100";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('i', $member_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $attendance_records[] = $row;
    }
    $stmt->close();
} else {
    error_log("Database error in member attendance: " . $conn->error);
}

// Check for archived records
$archive_tables = ['attendance_archive', 'archived_attendance'];
$archive_records = [];

foreach ($archive_tables as $table_name) {
    $table_check = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($table_check && $table_check->num_rows > 0) {
        if ($table_name === 'attendance_archive') {
            $archive_sql = "SELECT 
                                aa.id,
                                aa.archive_date as date,
                                aa.time_in,
                                aa.time_out,
                                'archive' as source
                            FROM attendance_archive aa 
                            WHERE aa.member_id = ? 
                            ORDER BY aa.archive_date DESC, aa.time_in DESC
                            LIMIT 50";
        } else {
            $archive_sql = "SELECT 
                                aa.id,
                                aa.date,
                                aa.time_in,
                                aa.time_out,
                                'archive' as source
                            FROM archived_attendance aa 
                            WHERE aa.member_id = ? 
                            ORDER BY aa.date DESC, aa.time_in DESC
                            LIMIT 50";
        }

        $archive_stmt = $conn->prepare($archive_sql);
        if ($archive_stmt) {
            $archive_stmt->bind_param('i', $member_id);
            $archive_stmt->execute();
            $archive_result = $archive_stmt->get_result();

            while ($row = $archive_result->fetch_assoc()) {
                $archive_records[] = $row;
            }
            $archive_stmt->close();
            break;
        }
    }
}

// Merge and sort all records
$attendance_records = array_merge($attendance_records, $archive_records);
usort($attendance_records, function($a, $b) {
    $date_a = strtotime($a['date'] . ' ' . ($a['time_in'] ?? '00:00:00'));
    $date_b = strtotime($b['date'] . ' ' . ($b['time_in'] ?? '00:00:00'));
    return $date_b - $date_a;
});

// Calculate statistics
$total_sessions = count($attendance_records);
$completed_sessions = count(array_filter($attendance_records, function($record) {
    return !empty($record['time_out']);
}));
$active_sessions = $total_sessions - $completed_sessions;

$total_hours = 0;
$this_month_sessions = 0;
$this_week_sessions = 0;

$current_month = date('Y-m');
$current_week_start = date('Y-m-d', strtotime('monday this week'));

foreach ($attendance_records as $record) {
    if ($record['time_in'] && $record['time_out']) {
        $time_in = new DateTime($record['time_in']);
        $time_out = new DateTime($record['time_out']);
        $interval = $time_in->diff($time_out);
        $total_hours += $interval->h + ($interval->i / 60);
    }
    
    // Count this month's sessions
    if (date('Y-m', strtotime($record['date'])) === $current_month) {
        $this_month_sessions++;
    }
    
    // Count this week's sessions
    if ($record['date'] >= $current_week_start) {
        $this_week_sessions++;
    }
}

// Check today's attendance status
$today = date('Y-m-d');
$today_status = 'Not Checked In';
$today_class = 'secondary';
$today_icon = 'fa-calendar-times';
$today_record = null;

foreach ($attendance_records as $record) {
    if ($record['date'] === $today) {
        $today_record = $record;
        if (empty($record['time_out'])) {
            $today_status = 'Currently Active';
            $today_class = 'warning';
            $today_icon = 'fa-clock';
        } else {
            $today_status = 'Completed Today';
            $today_class = 'success';
            $today_icon = 'fa-check-circle';
        }
        break;
    }
}
?>

<div class="container-fluid px-4">
    <!-- Member Profile Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h4 class="mb-1">
                                <?= htmlspecialchars($member_info['first_name'] . ' ' . $member_info['last_name']) ?>
                            </h4>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-id-card me-1"></i>
                                Member ID: <?= htmlspecialchars($member_info['member_id']) ?> | 
                                <i class="fas fa-crown me-1"></i>
                                <?= htmlspecialchars(ucfirst($member_info['membership_type'])) ?> Member
                            </p>
                        </div>
                        <div class="col-auto">
                            <div class="text-end">
                                <h6 class="mb-1">Today's Status</h6>
                                <span class="badge bg-<?= $today_class ?> fs-6">
                                    <i class="fas <?= $today_icon ?> me-1"></i>
                                    <?= $today_status ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-calendar-check fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0"><?= $total_sessions ?></h3>
                            <small>Total Sessions</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-check-circle fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0"><?= $completed_sessions ?></h3>
                            <small>Completed</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-clock fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0"><?= number_format($total_hours, 1) ?></h3>
                            <small>Total Hours</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-calendar-week fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0"><?= $this_week_sessions ?></h3>
                            <small>This Week</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Status Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-<?= $today_class ?>">
                <div class="card-header bg-<?= $today_class ?> bg-opacity-10 border-<?= $today_class ?>">
                    <h5 class="mb-0">
                        <i class="fas <?= $today_icon ?> me-2"></i>
                        Today's Attendance - <?= date('l, F d, Y') ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="text-<?= $today_class ?> mb-2">
                                <i class="fas <?= $today_icon ?> me-1"></i>
                                <?= $today_status ?>
                            </h6>
                            <?php if ($today_record): ?>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Time In:</small><br>
                                        <strong><?= date('h:i A', strtotime($today_record['time_in'])) ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Time Out:</small><br>
                                        <strong>
                                            <?= $today_record['time_out'] ? date('h:i A', strtotime($today_record['time_out'])) : 'Not yet' ?>
                                        </strong>
                                    </div>
                                </div>
                                <?php if ($today_record['time_in'] && $today_record['time_out']): ?>
                                    <?php
                                    $time_in = new DateTime($today_record['time_in']);
                                    $time_out = new DateTime($today_record['time_out']);
                                    $duration = $time_in->diff($time_out);
                                    ?>
                                    <div class="mt-2">
                                        <small class="text-muted">Duration:</small><br>
                                        <strong class="text-success">
                                            <?= $duration->format('%Hh %Im') ?>
                                        </strong>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">No attendance record for today yet.</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-primary" onclick="refreshAttendance()" id="refreshBtn">
                                    <i class="fas fa-sync-alt me-1"></i>Refresh
                                </button>
                                <?php if ($today_record && empty($today_record['time_out'])): ?>
                                    <button class="btn btn-outline-success" onclick="completeTodaySession()" id="completeBtn">
                                        <i class="fas fa-check me-1"></i>Complete Session
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Records Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Attendance History
                    </h5>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-secondary" onclick="exportAttendance()" id="exportBtn">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="printAttendance()" id="printBtn">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($attendance_records)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No attendance records found</h5>
                            <p class="text-muted">Your attendance logs will appear here once you check in using the QR code scanner.</p>
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-home me-1"></i>Go to Dashboard
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="attendanceTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_records as $record): ?>
                                        <?php
                                        $date = date('M d, Y', strtotime($record['date']));
                                        $time_in = $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-';
                                        $time_out = $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-';
                                        
                                        // Calculate duration
                                        $duration = '-';
                                        if ($record['time_in'] && $record['time_out']) {
                                            $time_in_obj = new DateTime($record['time_in']);
                                            $time_out_obj = new DateTime($record['time_out']);
                                            $interval = $time_in_obj->diff($time_out_obj);
                                            $duration = $interval->format('%Hh %Im');
                                        }
                                        
                                        // Determine status
                                        $status = $record['time_out'] ? 'Completed' : 'Active';
                                        $status_class = $record['time_out'] ? 'success' : 'warning';
                                        
                                        // Check if it's today's record
                                        $is_today = $record['date'] === $today;
                                        ?>
                                        <tr class="<?= $is_today ? 'table-warning' : '' ?>">
                                            <td>
                                                <strong><?= $date ?></strong>
                                                <?php if ($is_today): ?>
                                                    <span class="badge bg-warning ms-1">Today</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $time_in ?></td>
                                            <td><?= $time_out ?></td>
                                            <td>
                                                <?php if ($duration !== '-'): ?>
                                                    <span class="text-success"><?= $duration ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted"><?= $duration ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $status_class ?>">
                                                    <i class="fas fa-<?= $status === 'Completed' ? 'check' : 'clock' ?> me-1"></i>
                                                    <?= $status ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-info btn-sm" 
                                                            onclick="viewDetails(<?= $record['id'] ?>)" 
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($is_today && empty($record['time_out'])): ?>
                                                        <button class="btn btn-outline-success btn-sm" 
                                                                onclick="completeTodaySession()" 
                                                                title="Complete Session">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Showing your latest <?= count($attendance_records) ?> attendance records
                            </small>
                            <small class="text-muted">
                                Last updated: <?= date('M d, Y h:i A') ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attendance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card {
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.table th {
    border-top: none;
    font-weight: 600;
}

.btn-group .btn {
    border-radius: 8px;
}

.badge {
    font-size: 0.8em;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

@media print {
    .btn-group, .modal, .card-header .btn-group {
        display: none !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }
}
</style>

<script>
let isProcessing = false;

// Show loading state
function showLoading(buttonId, loadingText) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i>${loadingText}`;
        button.disabled = true;
        isProcessing = true;
    }
}

// Reset button state
function resetButton(buttonId, originalText) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.innerHTML = originalText;
        button.disabled = false;
        isProcessing = false;
    }
}

// Show notification
function showNotification(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, type === 'success' ? 5000 : 8000);
}

// Refresh attendance
function refreshAttendance() {
    if (isProcessing) return;
    
    showLoading('refreshBtn', 'Refreshing...');
    setTimeout(() => {
        location.reload();
    }, 500);
}

// Complete today's session
function completeTodaySession() {
    if (isProcessing) return;
    
    if (confirm('Are you sure you want to complete today\'s session? This will set your time out to now.')) {
        showLoading('completeBtn', 'Completing...');
        
        fetch('complete_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'complete_today'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Session completed successfully!');
                location.reload();
            } else {
                showNotification(data.message || 'Failed to complete session', 'danger');
                resetButton('completeBtn', '<i class="fas fa-check me-1"></i>Complete Session');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while completing session', 'danger');
            resetButton('completeBtn', '<i class="fas fa-check me-1"></i>Complete Session');
        });
    }
}

// View attendance details
function viewDetails(recordId) {
    // For now, just show a simple modal with basic info
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    document.getElementById('detailsModalBody').innerHTML = `
        <div class="text-center">
            <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
            <p>Detailed view for attendance record #${recordId}</p>
            <p class="text-muted">This feature will show more detailed information about the attendance record.</p>
        </div>
    `;
    modal.show();
}

// Export attendance data
function exportAttendance() {
    if (isProcessing) return;
    
    showLoading('exportBtn', 'Exporting...');
    
    // Create CSV content
    const table = document.getElementById('attendanceTable');
    const rows = table.querySelectorAll('tbody tr');
    let csv = 'Date,Time In,Time Out,Duration,Status\n';
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const date = cells[0].textContent.trim().split('Today')[0].trim();
        const timeIn = cells[1].textContent.trim();
        const timeOut = cells[2].textContent.trim();
        const duration = cells[3].textContent.trim();
        const status = cells[4].textContent.trim();
        
        csv += `"${date}","${timeIn}","${timeOut}","${duration}","${status}"\n`;
    });
    
    // Download CSV file
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `attendance_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
    
    resetButton('exportBtn', '<i class="fas fa-download me-1"></i>Export');
    showNotification('Attendance data exported successfully!');
}

// Print attendance
function printAttendance() {
    if (isProcessing) return;
    
    showLoading('printBtn', 'Preparing...');
    
    setTimeout(() => {
        window.print();
        resetButton('printBtn', '<i class="fas fa-print me-1"></i>Print');
    }, 500);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(event) {
    if ((event.ctrlKey && event.key === 'r') || event.key === 'F5') {
        event.preventDefault();
        refreshAttendance();
    }
});

// Auto-refresh every 5 minutes
setInterval(() => {
    if (!isProcessing) {
        location.reload();
    }
}, 300000); // 5 minutes

// Initialize tooltips
if (typeof bootstrap !== 'undefined') {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
  }
  </script>