<?php
session_start();

// Check if logged in as staff
if (!isset($_SESSION['staff_logged_in']) || !$_SESSION['staff_logged_in']) {
    header('Location: login.php');
    exit;
}

$page_title = "My Attendance";
include 'components/header.php';
include '../includes/db.php';

// Get current staff ID
$currentStaffId = $_SESSION['staff_id'] ?? null;
$currentStaffName = $_SESSION['staff_name'] ?? 'Staff Member';

// Get parameters
$view = $_GET['view'] ?? 'today';
$dateParam = $_GET['date'] ?? date('Y-m-d');

// Get staff attendance data
$staffAttendance = [];
$stats = [
    'total' => 0,
    'active' => 0,
    'completed' => 0
];

if ($view === 'archive') {
    // Check attendance_archive table for staff
    $table_check = $conn->query("SHOW TABLES LIKE 'attendance_archive'");
    if ($table_check && $table_check->num_rows > 0) {
        $sql = "SELECT aa.id AS attendance_id,
                aa.member_id AS member_pk,
                aa.member_code,
                aa.time_in,
                aa.time_out,
                aa.date,
                s.first_name,
                s.last_name,
                s.position as membership_type,
                'staff' as user_type
            FROM attendance_archive aa
            JOIN staff s ON s.id = aa.member_id
            WHERE aa.date = ? AND aa.member_id = ?
            ORDER BY COALESCE(aa.time_out, aa.time_in) DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $dateParam, $currentStaffId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $staffAttendance[] = $row; }
        $stmt->close();
    }
    
    // If no data found, check main attendance table for staff
    if (empty($staffAttendance)) {
        $sql = "SELECT a.id AS attendance_id,
                a.member_id AS member_pk,
                a.member_code,
                a.time_in,
                a.time_out,
                a.date,
                s.first_name,
                s.last_name,
                s.position as membership_type,
                'staff' as user_type
            FROM attendance a
            JOIN staff s ON s.id = a.member_id
            WHERE a.date = ? AND a.member_id = ?
            ORDER BY COALESCE(a.time_out, a.time_in) DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $dateParam, $currentStaffId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $staffAttendance[] = $row; }
        $stmt->close();
    }
} else {
    // Today's staff data from live table
    $sql = "SELECT a.id AS attendance_id,
            a.member_id AS member_pk,
            a.member_code,
            a.time_in,
            a.time_out,
            a.date,
            s.first_name,
            s.last_name,
            s.position as membership_type,
            'staff' as user_type
        FROM attendance a
        JOIN staff s ON s.id = a.member_id
        WHERE a.date = CURDATE() AND a.member_id = ?
        ORDER BY COALESCE(a.time_out, a.time_in) DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $currentStaffId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $staffAttendance[] = $row; }
    $stmt->close();
}

// Calculate statistics
$stats['total'] = count($staffAttendance);
foreach ($staffAttendance as $record) {
    if ($record['time_in'] && !$record['time_out']) {
        $stats['active']++;
    } elseif ($record['time_in'] && $record['time_out']) {
        $stats['completed']++;
    }
}

// Include attendance-specific CSS
echo '<link rel="stylesheet" href="../assets/css/staff/attendance.css">';
echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">';
?>

<!-- Date Picker -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Welcome Message -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-dark border-light">
            <div class="card-body text-center">
                <h3 class="text-primary mb-2">Welcome, <?= htmlspecialchars($currentStaffName) ?>!</h3>
                <p class="text-muted mb-0">Track your daily attendance and working hours</p>
            </div>
        </div>
    </div>
</div>

<!-- Date Controls -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-dark border-light">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="input-group" style="width: 200px;">
                        <input type="text" id="datePicker" class="form-control" placeholder="Select Date" value="<?= $dateParam ?>">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?view=today&date=<?= date('Y-m-d') ?>" 
                           class="btn <?= $view === 'today' ? 'btn-primary' : 'btn-outline-light' ?>">
                            <i class="fas fa-calendar-day"></i> Today
                        </a>
                        <a href="?view=archive&date=<?= $dateParam ?>" 
                           class="btn <?= $view === 'archive' ? 'btn-primary' : 'btn-outline-light' ?>">
                            <i class="fas fa-archive"></i> Archive
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary border-0">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-2x text-white mb-2"></i>
                <h4 class="text-white"><?= $stats['total'] ?></h4>
                <p class="text-white-50 mb-0">Total Records</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success border-0">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x text-white mb-2"></i>
                <h4 class="text-white"><?= $stats['completed'] ?></h4>
                <p class="text-white-50 mb-0">Completed</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning border-0">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x text-white mb-2"></i>
                <h4 class="text-white"><?= $stats['active'] ?></h4>
                <p class="text-white-50 mb-0">Active</p>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Records -->
<div class="row">
    <div class="col-12">
        <div class="card bg-dark border-light">
            <div class="card-header bg-secondary border-light">
                <h5 class="mb-0">
                    <i class="fas fa-user-tie me-2"></i>
                    My Attendance Records
                    <?php if ($view === 'today'): ?>
                        <span class="badge bg-primary ms-2">Today</span>
                    <?php else: ?>
                        <span class="badge bg-info ms-2"><?= date('M d, Y', strtotime($dateParam)) ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($staffAttendance)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staffAttendance as $record): ?>
                                <tr class="<?= ($currentStaffId && $record['member_pk'] == $currentStaffId) ? 'table-primary' : '' ?>">
                                    <td>
                                        <strong><?= date('M d, Y', strtotime($record['date'])) ?></strong>
                                        <?php if (date('Y-m-d', strtotime($record['date'])) === date('Y-m-d')): ?>
                                            <span class="badge bg-warning ms-2">Today</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($record['time_in']): ?>
                                            <span class="text-success">
                                                <i class="fas fa-sign-in-alt me-1"></i>
                                                <?= date('H:i', strtotime($record['time_in'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($record['time_out']): ?>
                                            <span class="text-info">
                                                <i class="fas fa-sign-out-alt me-1"></i>
                                                <?= date('H:i', strtotime($record['time_out'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($record['time_in'] && $record['time_out']): ?>
                                            <?php
                                            $timeIn = new DateTime($record['time_in']);
                                            $timeOut = new DateTime($record['time_out']);
                                            $duration = $timeIn->diff($timeOut);
                                            echo '<span class="text-success">' . $duration->format('%H:%I') . '</span>';
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($record['time_in'] && !$record['time_out']): ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i>Active
                                            </span>
                                        <?php elseif ($record['time_in'] && $record['time_out']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Completed
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-question me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Attendance Records</h4>
                        <p class="text-muted">
                            <?php if ($view === 'today'): ?>
                                No attendance records found for today. 
                                Make sure to scan your QR code when you arrive.
                            <?php else: ?>
                                No attendance records found for <?= date('M d, Y', strtotime($dateParam)) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Info -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card bg-dark border-light">
            <div class="card-body">
                <h6 class="text-primary mb-3">
                    <i class="fas fa-info-circle me-2"></i>How it works
                </h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-qrcode text-success me-2"></i>
                            <span>Scan your QR code when you arrive</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-clock text-warning me-2"></i>
                            <span>Your time-in will be recorded</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-sign-out-alt text-info me-2"></i>
                            <span>Scan again when you leave</span>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Note:</strong> Your attendance is automatically recorded when you scan your QR code 
                    through the admin scanner. You can view your records here anytime.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize date picker
    flatpickr("#datePicker", {
        dateFormat: "Y-m-d",
        defaultDate: "<?= $dateParam ?>",
        onChange: function(selectedDates, dateStr) {
            window.location.href = `?view=archive&date=${dateStr}`;
        }
    });

    // Auto-refresh every 30 seconds for today's view
    <?php if ($view === 'today'): ?>
    setInterval(function() {
        location.reload();
    }, 30000);
    <?php endif; ?>
</script>

<?php include 'components/footer.php'; ?>
