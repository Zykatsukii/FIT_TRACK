<?php
// Set timezone to Philippines for correct time display
date_default_timezone_set('Asia/Manila');

$page_title = "Attendance Management";
include 'components/header.php';
include '../includes/db.php';

// Get parameters
$view = $_GET['view'] ?? 'today';
$dateParam = $_GET['date'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'members';
$filter = $_GET['filter'] ?? 'all'; // all, active, completed

// Debug: Log the parameters
error_log("Attendance Debug - View: $view, Date: $dateParam, Type: $type, Filter: $filter");

// Initialize arrays
$memberAttendance = [];
$staffAttendance = [];
$stats = [
    'total' => 0,
    'active' => 0,
    'completed' => 0,
    'members' => 0,
    'staff' => 0
];

// Function to get attendance data
function getAttendanceData($conn, $view, $dateParam, $type) {
    $data = [];
    
    if ($view === 'archive') {
        // For archive view, check all possible tables in order of preference
        // First check attendance_archive table (new table)
        $table_check = $conn->query("SHOW TABLES LIKE 'attendance_archive'");
        if ($table_check && $table_check->num_rows > 0) {
            error_log("Attendance archive table exists, searching for date: $dateParam");
            
            $sql = "SELECT aa.id AS attendance_id,
                    aa.member_id AS member_pk,
                    aa.member_code,
                    aa.time_in,
                    aa.time_out,
                    aa.date,
                    m.first_name,
                    m.last_name,
                    m.membership_type,
                    m.photo,
                    'member' as user_type
                FROM attendance_archive aa
                JOIN members m ON m.id = aa.member_id
                WHERE aa.date = ?
                ORDER BY COALESCE(aa.time_out, aa.time_in) DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $dateParam);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) { $data[] = $row; }
            $stmt->close();
            
            error_log("Found " . count($data) . " records in attendance_archive for date: $dateParam");
        }
        
        // If no data found, check archived_attendance table (old table)
        if (empty($data)) {
            $table_check = $conn->query("SHOW TABLES LIKE 'archived_attendance'");
            if ($table_check && $table_check->num_rows > 0) {
                error_log("Old archived_attendance table exists, searching for date: $dateParam");
                
                $sql = "SELECT aa.id AS attendance_id,
                        aa.member_id AS member_pk,
                        aa.member_code,
                        CONCAT(aa.date, ' ', aa.time_in) as time_in,
                        CONCAT(aa.date, ' ', aa.time_out) as time_out,
                        aa.date,
                        aa.first_name,
                        aa.last_name,
                        aa.membership_type,
                        aa.photo,
                        'member' as user_type
                    FROM archived_attendance aa
                    WHERE aa.date = ?
                    ORDER BY COALESCE(aa.time_out, aa.time_in) DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('s', $dateParam);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) { $data[] = $row; }
                $stmt->close();
                
                error_log("Found " . count($data) . " records in archived_attendance for date: $dateParam");
            }
        }
        
        // Only check main attendance table if no archive data was found
        if (empty($data)) {
            error_log("No archive data found, checking main attendance table for date: $dateParam");
            $sql = "SELECT a.id AS attendance_id,
                    a.member_id AS member_pk,
                    a.member_code,
                    a.time_in,
                    a.time_out,
                    a.date,
                    m.first_name,
                    m.last_name,
                    m.membership_type,
                    m.photo,
                    'member' as user_type
                FROM attendance a
                JOIN members m ON m.id = a.member_id
                WHERE a.date = ?
                ORDER BY COALESCE(a.time_out, a.time_in) DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $dateParam);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) { $data[] = $row; }
            $stmt->close();
            
            error_log("Found " . count($data) . " records in main attendance table for date: $dateParam");
        } else {
            error_log("Archive data found, skipping main attendance table to avoid duplicates");
        }
    } else {
        // Today's data from live table
        $sql = "SELECT a.id AS attendance_id,
                a.member_id AS member_pk,
                a.member_code,
                a.time_in,
                a.time_out,
                a.date,
                m.first_name,
                m.last_name,
                m.membership_type,
                m.photo,
                'member' as user_type
            FROM attendance a
            JOIN members m ON m.id = a.member_id
            WHERE a.date = CURDATE()
            ORDER BY COALESCE(a.time_out, a.time_in) DESC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
    }
    
    return $data;
}

// Function to get staff attendance data
function getStaffAttendanceData($conn, $view, $dateParam) {
    $data = [];
    
    if ($view === 'archive') {
        // First check attendance_archive table (new table) for staff
        $table_check = $conn->query("SHOW TABLES LIKE 'attendance_archive'");
        if ($table_check && $table_check->num_rows > 0) {
            error_log("Checking attendance_archive for staff on date: $dateParam");
            
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
                WHERE aa.date = ?
                ORDER BY COALESCE(aa.time_out, aa.time_in) DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $dateParam);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) { $data[] = $row; }
            $stmt->close();
            
            error_log("Found " . count($data) . " staff records in attendance_archive for date: $dateParam");
        }
        
        // Only check main attendance table if no archive data was found
        if (empty($data)) {
            error_log("No archive data found, checking main attendance table for staff on date: $dateParam");
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
                WHERE a.date = ?
                ORDER BY COALESCE(a.time_out, a.time_in) DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $dateParam);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) { $data[] = $row; }
            $stmt->close();
            
            error_log("Found " . count($data) . " staff records in main attendance table for date: $dateParam");
        } else {
            error_log("Archive data found, skipping main attendance table to avoid duplicates");
        }
    } else {
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
            WHERE a.date = CURDATE()
            ORDER BY COALESCE(a.time_out, a.time_in) DESC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
    }
    
    return $data;
}

// Get attendance data
try {
    $memberAttendance = getAttendanceData($conn, $view, $dateParam, 'members');
    $staffAttendance = getStaffAttendanceData($conn, $view, $dateParam);
    
    // Debug: Log the data for troubleshooting
    if ($view === 'archive') {
        error_log("Archive view - Date: $dateParam, Members: " . count($memberAttendance) . ", Staff: " . count($staffAttendance));
        
        // Additional debugging
        if (empty($memberAttendance) && empty($staffAttendance)) {
            error_log("No archive data found for date: $dateParam");
            
            // Check what tables exist and have data
            $tables = ['attendance_archive', 'archived_attendance', 'attendance'];
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->num_rows > 0) {
                    $count_result = $conn->query("SELECT COUNT(*) as count FROM $table WHERE date = '$dateParam'");
                    if ($count_result) {
                        $count = $count_result->fetch_assoc()['count'];
                        error_log("Table $table has $count records for date $dateParam");
                    }
                } else {
                    error_log("Table $table does not exist");
                }
            }
        }
    }
} catch (Exception $e) {
    // Log error and set empty arrays
    error_log("Error getting attendance data: " . $e->getMessage());
    $memberAttendance = [];
    $staffAttendance = [];
}

// Calculate statistics
$allAttendance = array_merge($memberAttendance, $staffAttendance);
$stats['total'] = count($allAttendance);
$stats['active'] = count(array_filter($allAttendance, function($a) { return empty($a['time_out']); }));
$stats['completed'] = count(array_filter($allAttendance, function($a) { return !empty($a['time_out']); }));
$stats['members'] = count($memberAttendance);
$stats['staff'] = count($staffAttendance);

// Filter attendance based on type and filter
$attendance = ($type === 'staff') ? $staffAttendance : $memberAttendance;

if ($filter === 'active') {
    $attendance = array_filter($attendance, function($a) { return empty($a['time_out']); });
} elseif ($filter === 'completed') {
    $attendance = array_filter($attendance, function($a) { return !empty($a['time_out']); });
}

$attendance = array_values($attendance); // Re-index array
?>

<!-- Custom Styles -->
<style>
.attendance-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 15px;
    transition: all 0.3s ease;
}

.attendance-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
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

.time-display {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #10b981;
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

.scanner-btn {
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 30px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.scanner-btn:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    color: white;
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

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-3">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-clock me-2"></i>Attendance Management
        </h1>
        
        <!-- View Toggle -->
        <div class="btn-group" role="group">
                            <a href="index.php?page=attendance&view=today&type=<?= $type ?>&filter=<?= $filter ?>" 
               class="btn btn-sm <?= $view==='today' ? 'btn-primary' : 'btn-outline-primary' ?>">
                <i class="fas fa-calendar-day me-1"></i>Today
            </a>
                            <a href="index.php?page=attendance&view=archive&date=<?= htmlspecialchars($dateParam) ?>&type=<?= $type ?>&filter=<?= $filter ?>" 
               class="btn btn-sm <?= $view==='archive' ? 'btn-primary' : 'btn-outline-primary' ?>">
                <i class="fas fa-archive me-1"></i>History
            </a>
        </div>
        
        <!-- Type Toggle -->
        <div class="btn-group" role="group">
                            <a href="index.php?page=attendance&view=<?= $view ?>&type=members&filter=<?= $filter ?><?= $view==='archive' ? '&date='.htmlspecialchars($dateParam) : '' ?>" 
               class="btn btn-sm <?= $type==='members' ? 'btn-success' : 'btn-outline-success' ?>">
                <i class="fas fa-users me-1"></i>Members
            </a>
                            <a href="index.php?page=attendance&view=<?= $view ?>&type=staff&filter=<?= $filter ?><?= $view==='archive' ? '&date='.htmlspecialchars($dateParam) : '' ?>" 
               class="btn btn-sm <?= $type==='staff' ? 'btn-info' : 'btn-outline-info' ?>">
                <i class="fas fa-user-tie me-1"></i>Staff
            </a>
        </div>
    </div>
    

</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card p-3">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-users fa-2x text-primary"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="text-white-50 small">Total <?= ucfirst($type) ?></div>
                    <div class="text-white h5 mb-0"><?= $stats[$type] ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card p-3">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-clock fa-2x text-warning"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="text-white-50 small">Active</div>
                    <div class="text-white h5 mb-0"><?= $stats['active'] ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card p-3">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle fa-2x text-success"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="text-white-50 small">Completed</div>
                    <div class="text-white h5 mb-0"><?= $stats['completed'] ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card p-3">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-chart-line fa-2x text-info"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="text-white-50 small">Total Records</div>
                    <div class="text-white h5 mb-0"><?= $stats['total'] ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($view === 'today'): ?>
<!-- Scanner Status Alert -->
<div class="alert alert-info alert-dismissible fade show" id="scannerStatusAlert" style="display: none;">
    <i class="fas fa-info-circle me-2"></i>
    <span id="scannerStatusMessage">Scanner is ready</span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($view === 'archive'): ?>
<!-- Date Filter -->
<div class="attendance-card p-3 mb-4">
    <form class="row g-3 align-items-end" method="get" action="index.php">
        <input type="hidden" name="page" value="attendance">
        <input type="hidden" name="view" value="archive">
        <input type="hidden" name="type" value="<?= $type ?>">
        <input type="hidden" name="filter" value="<?= $filter ?>">
        <div class="col-md-4">
            <label for="archiveDate" class="form-label text-white">Select Date</label>
            <input type="date" class="form-control" id="archiveDate" name="date" value="<?= htmlspecialchars($dateParam) ?>">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary btn-custom" type="submit">
                <i class="fas fa-search me-1"></i>Load
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Filter Options -->
<div class="attendance-card p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div class="btn-group" role="group">
                            <a href="index.php?page=attendance&view=<?= $view ?>&type=<?= $type ?>&filter=all<?= $view==='archive' ? '&date='.htmlspecialchars($dateParam) : '' ?>" 
               class="btn btn-sm <?= $filter==='all' ? 'btn-secondary' : 'btn-outline-secondary' ?>">All</a>
                          <a href="index.php?page=attendance&view=<?= $view ?>&type=<?= $type ?>&filter=active<?= $view==='archive' ? '&date='.htmlspecialchars($dateParam) : '' ?>" 
               class="btn btn-sm <?= $filter==='active' ? 'btn-warning' : 'btn-outline-warning' ?>">Active</a>
                          <a href="index.php?page=attendance&view=<?= $view ?>&type=<?= $type ?>&filter=completed<?= $view==='archive' ? '&date='.htmlspecialchars($dateParam) : '' ?>" 
               class="btn btn-sm <?= $filter==='completed' ? 'btn-success' : 'btn-outline-success' ?>">Completed</a>
        </div>
        
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" onclick="refreshAttendance()">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
            <?php if ($view === 'today'): ?>
            <button class="btn btn-outline-success btn-sm" onclick="saveToday()">
                <i class="fas fa-save me-1"></i>Save Today
            </button>
            <button class="btn btn-outline-danger btn-sm" onclick="resetDay()">
                <i class="fas fa-archive me-1"></i>Archive & Reset
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Attendance Table -->
<div class="attendance-card">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-<?= $type==='staff' ? 'user-tie' : 'users' ?> me-2"></i>
            <?= $view==='today' ? "Today's Attendance" : ('Historical Records — ' . date('M d, Y', strtotime($dateParam))) ?>
            <span class="badge bg-<?= $type==='staff' ? 'info' : 'success' ?> ms-2"><?= ucfirst($type) ?></span>
        </h6>
        <div class="text-white-50">
            Showing <?= count($attendance) ?> of <?= $stats[$type] ?> records
        </div>
    </div>
    
    <div class="card-body p-0">
        <?php if (!empty($attendance)): ?>
        <div class="table-responsive">
            <table class="table table-custom mb-0" id="attendanceTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $row): ?>
                    <?php
                        $name = htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
                        if ($name === '') { $name = htmlspecialchars($row['member_code']); }
                        $type = htmlspecialchars(ucfirst($row['membership_type'] ?? ($row['user_type'] === 'staff' ? 'Staff' : 'Member')));
                        $timeIn = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-';
                        $timeOut = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '-';
                        $status = $row['time_out'] ? 'Completed' : 'Active';
                        $badge = $row['time_out'] ? 'bg-success' : 'bg-warning';
                        $attendanceId = $row['attendance_id'] ?? 0;
                        $userType = $row['user_type'] ?? 'member';
                    ?>
                    <tr data-member-id="<?= (int)($row['member_pk'] ?? 0) ?>" data-attendance-id="<?= (int)$attendanceId ?>" data-user-type="<?= $userType ?>">
                        <td>
                            <span class="badge bg-secondary"><?= htmlspecialchars($row['member_code']) ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-3">
                                    <?php 
                                    $photoPath = '';
                                    if (!empty($row['photo'])) {
                                        $photoPath = '../uploads/member_photos/' . $row['photo'];
                                        if (file_exists($photoPath)) {
                                            echo '<img src="' . htmlspecialchars($photoPath) . '" alt="Profile Picture" class="rounded-circle" width="40" height="40" style="object-fit: cover;">';
                                        } else {
                                            echo '<i class="fas fa-' . ($userType === 'staff' ? 'user-tie' : 'user') . ' fa-lg text-' . ($userType === 'staff' ? 'info' : 'success') . '"></i>';
                                        }
                                    } else {
                                        echo '<i class="fas fa-' . ($userType === 'staff' ? 'user-tie' : 'user') . ' fa-lg text-' . ($userType === 'staff' ? 'info' : 'success') . '"></i>';
                                    }
                                    ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-white"><?= $name ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-<?= $userType === 'staff' ? 'info' : 'success' ?>">
                                <i class="fas fa-<?= $userType === 'staff' ? 'user-tie' : 'user' ?> me-1"></i>
                                <?= $type ?>
                            </span>
                        </td>
                        <td class="time-in">
                            <span class="time-display"><?= $timeIn ?></span>
                        </td>
                        <td class="time-out">
                            <span class="time-display"><?= $timeOut ?></span>
                        </td>
                        <td class="status">
                            <span class="badge <?= $badge ?> status-badge">
                                <i class="fas fa-<?= $status === 'Completed' ? 'check-circle' : 'clock' ?> me-1"></i>
                                <?= $status ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <button class='btn btn-danger btn-delete-attendance' data-attendance-id='<?= (int)$attendanceId ?>' title='Delete Attendance' data-bs-toggle="tooltip">
                                    <i class='fas fa-trash'></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-<?= $type==='staff' ? 'user-tie' : 'users' ?>"></i>
            <h5>No <?= $type ?> attendance records found</h5>
            <p class="text-white-50">No <?= $type ?> have checked in for this date yet.</p>

        </div>
        <?php endif; ?>
    </div>
</div>



<!-- Delete Attendance Modal -->
<div class="modal fade" id="deleteAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-danger">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2 text-danger"></i>Delete Attendance Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                    <h5>Are you sure?</h5>
                </div>
                <p>You are about to delete the attendance record for <strong id="delete_attendance_name"></strong> (<code id="delete_attendance_id"></code>).</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone and will permanently remove the attendance record from the system.
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteAttendance">
                    <i class="fas fa-trash me-1"></i>Delete Record
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to open scanner in new window
function openScanner() {
    const scannerUrl = `scanner.php?type=<?= $type ?>&redirect=${encodeURIComponent(window.location.href)}`;
    window.open(scannerUrl, 'scanner', 'width=800,height=600,scrollbars=yes,resizable=yes');
}

// Auto-refresh functionality
function refreshAttendance() {
    location.reload();
}

function saveToday() {
    if (confirm('Are you sure you want to save today\'s attendance records?')) {
        fetch('attendance_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=save_today'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showScannerStatus(data.message, 'success');
            } else {
                showScannerStatus('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showScannerStatus('Error saving attendance', 'danger');
        });
    }
}

function resetDay() {
    if (confirm('Are you sure you want to archive and reset today\'s attendance? This action cannot be undone.')) {
        fetch('attendance_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=reset_day'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showScannerStatus(data.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showScannerStatus('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showScannerStatus('Error resetting day', 'danger');
        });
    }
}

// Function to show scanner status messages
function showScannerStatus(message, type = 'info') {
    const alert = document.getElementById('scannerStatusAlert');
    const messageSpan = document.getElementById('scannerStatusMessage');
    
    if (alert && messageSpan) {
        messageSpan.textContent = message;
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    }
}

// Listen for messages from scanner window
window.addEventListener('message', function(event) {
    if (event.data.type === 'scanResult') {
        if (event.data.success) {
            showScannerStatus(`Successfully recorded ${event.data.action} for ${event.data.member.name}`, 'success');
        } else {
            showScannerStatus(event.data.message, 'danger');
        }
    }
});

// Check if we should refresh (when coming back from scanner)
if (sessionStorage.getItem('refreshAttendance')) {
    sessionStorage.removeItem('refreshAttendance');
    location.reload();
}

// Delete Attendance Functionality
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteAttendanceModal'));
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Variables for delete functionality
    let toDeleteAttendanceId = null;
    let toDeleteAttendanceName = '';
    
    // Delete Attendance - Open Modal
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-delete-attendance')) {
            const btn = e.target.closest('.btn-delete-attendance');
            const attendanceId = btn.dataset.attendanceId;
            const row = btn.closest('tr');
            
            // Get member name from the row
            const nameCell = row.querySelector('td:nth-child(2) .fw-bold');
            const memberName = nameCell ? nameCell.textContent.trim() : 'Unknown Member';
            
            toDeleteAttendanceId = attendanceId;
            toDeleteAttendanceName = memberName;
            
            // Populate delete modal
            document.getElementById('delete_attendance_name').textContent = toDeleteAttendanceName;
            document.getElementById('delete_attendance_id').textContent = toDeleteAttendanceId;
            
            deleteModal.show();
        }
    });
    
    
    
    // Delete confirmation handler
    document.getElementById('confirmDeleteAttendance').addEventListener('click', async function() {
        if (!toDeleteAttendanceId) return;
        
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
            formData.append('action', 'delete_attendance');
            formData.append('attendance_id', toDeleteAttendanceId);
            formData.append('view_type', '<?= $view ?>');
            
            const response = await fetch('attendance_actions.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Remove row from table
                const row = document.querySelector(`tr[data-attendance-id="${toDeleteAttendanceId}"]`);
                if (row) {
                    row.remove();
                }
                
                // Close modal
                deleteModal.hide();
                
                // Show success message
                showScannerStatus('Attendance record deleted successfully!', 'success');
                
                // Reload page to update statistics
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(data.message || 'Delete failed');
            }
        } catch (error) {
            console.error('Error:', error);
            showScannerStatus(error.message || 'An error occurred while deleting attendance record', 'danger');
        } finally {
            btn.innerHTML = originalBtnText;
            btn.disabled = false;
        }
    });
    
    // Auto-refresh every 30 seconds for today's view
    <?php if ($view === 'today'): ?>
    setInterval(() => {
        location.reload();
    }, 30000);
    <?php endif; ?>
});
</script>

<?php include 'components/footer.php'; ?>
