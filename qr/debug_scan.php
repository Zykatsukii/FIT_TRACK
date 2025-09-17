<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

function respond($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Log function for debugging
function debugLog($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data) {
        $log .= " - " . json_encode($data);
    }
    error_log($log . "\n", 3, '../logs/scan_debug.log');
}

// Create logs directory if it doesn't exist
if (!is_dir('../logs')) {
    mkdir('../logs', 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debugLog("Invalid request method", $_SERVER['REQUEST_METHOD']);
    respond(['success' => false, 'message' => 'Invalid request method'], 405);
}

$qrData = trim($_POST['qr_data'] ?? '');
debugLog("Received QR data", $qrData);

if ($qrData === '') {
    debugLog("Missing QR data");
    respond(['success' => false, 'message' => 'Missing QR data'], 400);
}

// Expected formats:
// 1) FIT_TRACK_MEMBER_ID:MEM-YYYY-XXXX
// 2) FIT_TRACK_STAFF_ID:STAFF-YYYY-XXXX
// 3) Raw member code like MEM-YYYY-XXXX
// 4) Raw staff code like STAFF-YYYY-XXXX
$memberCode = $qrData;
$staffCode = $qrData;
$isStaff = false;

$memberPrefix = 'FIT_TRACK_MEMBER_ID:';
$staffPrefix = 'FIT_TRACK_STAFF_ID:';

if (stripos($qrData, $memberPrefix) === 0) {
    $memberCode = substr($qrData, strlen($memberPrefix));
    $isStaff = false;
    debugLog("Detected member QR with prefix", $memberCode);
} elseif (stripos($qrData, $staffPrefix) === 0) {
    $staffCode = substr($qrData, strlen($staffPrefix));
    $isStaff = true;
    debugLog("Detected staff QR with prefix", $staffCode);
} else {
    // Check if it's a raw staff code
    if (stripos($qrData, 'STAFF-') === 0) {
        $staffCode = $qrData;
        $isStaff = true;
        debugLog("Detected raw staff code", $staffCode);
    } else {
        $memberCode = $qrData;
        $isStaff = false;
        debugLog("Detected raw member code", $memberCode);
    }
}

$memberCode = trim($memberCode);
$staffCode = trim($staffCode);

// Check if attendance table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'attendance'");
if ($tableExists->num_rows == 0) {
    debugLog("Attendance table does not exist, creating it");
    $createSql = "CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        member_code VARCHAR(50) NOT NULL,
        date DATE NOT NULL,
        time_in DATETIME DEFAULT NULL,
        time_out DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_member_date (member_id, date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$conn->query($createSql)) {
        debugLog("Failed to create attendance table", $conn->error);
        respond(['success' => false, 'message' => 'Failed to create attendance table: ' . $conn->error], 500);
    }
}

// Find member or staff by ID or qr_code_data
if ($isStaff) {
    debugLog("Searching for staff", ['staff_code' => $staffCode]);
    $stmt = $conn->prepare("SELECT id, staff_id as member_id, first_name, last_name, position as membership_type FROM staff WHERE staff_id = ? OR qr_code_data = ? LIMIT 1");
    if (!$stmt) {
        debugLog("DB error preparing staff query", $conn->error);
        respond(['success' => false, 'message' => 'DB error: ' . $conn->error], 500);
    }
    $stmt->bind_param('ss', $staffCode, $staffCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();
    $stmt->close();
    
    if (!$member) {
        debugLog("Staff not found", $staffCode);
        respond(['success' => false, 'message' => 'Staff not found for code: ' . $staffCode], 404);
    }
    debugLog("Staff found", $member);
} else {
    debugLog("Searching for member", ['member_code' => $memberCode]);
    $stmt = $conn->prepare("SELECT id, member_id, first_name, last_name, membership_type FROM members WHERE member_id = ? OR qr_code_data = ? LIMIT 1");
    if (!$stmt) {
        debugLog("DB error preparing member query", $conn->error);
        respond(['success' => false, 'message' => 'DB error: ' . $conn->error], 500);
    }
    $stmt->bind_param('ss', $memberCode, $memberCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();
    $stmt->close();
    
    if (!$member) {
        debugLog("Member not found", $memberCode);
        respond(['success' => false, 'message' => 'Member not found for code: ' . $memberCode], 404);
    }
    debugLog("Member found", $member);
}

$today = date('Y-m-d');
debugLog("Processing for date", $today);

// Check if there is an active attendance record today (no time_out yet)
$stmt = $conn->prepare("SELECT id, time_in, time_out FROM attendance WHERE member_id = ? AND date = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param('is', $member['id'], $today);
$stmt->execute();
$last = $stmt->get_result()->fetch_assoc();
$stmt->close();

debugLog("Last attendance record", $last);

$action = '';
$timeIn = null;
$timeOut = null;

if ($last && empty($last['time_out'])) {
    // Time out
    debugLog("Processing time out");
    $update = $conn->prepare("UPDATE attendance SET time_out = NOW() WHERE id = ?");
    $update->bind_param('i', $last['id']);
    if (!$update->execute()) {
        debugLog("Failed to log time out", $update->error);
        respond(['success' => false, 'message' => 'Failed to log time out'], 500);
    }
    $update->close();

    $action = 'time_out';
    $timeIn = $last['time_in'];
    // Fetch updated record
    $q = $conn->prepare("SELECT time_out FROM attendance WHERE id = ?");
    $q->bind_param('i', $last['id']);
    $q->execute();
    $updated = $q->get_result()->fetch_assoc();
    $q->close();
    $timeOut = $updated['time_out'];
    debugLog("Time out recorded", ['time_out' => $timeOut]);
} else {
    // Time in (create new record)
    debugLog("Processing time in");
    $insert = $conn->prepare("INSERT INTO attendance (member_id, member_code, date, time_in) VALUES (?, ?, ?, NOW())");
    $insert->bind_param('iss', $member['id'], $member['member_id'], $today);
    if (!$insert->execute()) {
        debugLog("Failed to log time in", $insert->error);
        respond(['success' => false, 'message' => 'Failed to log time in'], 500);
    }
    $insert->close();
    $action = 'time_in';
    // Fetch inserted values
    $q = $conn->prepare("SELECT time_in FROM attendance WHERE id = LAST_INSERT_ID()");
    $q->execute();
    $inserted = $q->get_result()->fetch_assoc();
    $q->close();
    $timeIn = $inserted['time_in'];
    debugLog("Time in recorded", ['time_in' => $timeIn, 'insert_id' => $conn->insert_id]);
}

$name = $member['first_name'] . ' ' . $member['last_name'];
$type = ucfirst($member['membership_type'] ?? ($isStaff ? 'Staff' : 'Member'));
$status = ($action === 'time_out') ? 'Completed' : 'Active';

$response = [
    'success' => true,
    'action' => $action,
    'user_type' => $isStaff ? 'staff' : 'member',
    'member' => [
        'id' => (int)$member['id'],
        'code' => $member['member_id'],
        'name' => $name,
        'type' => $type
    ],
    'record' => [
        'id' => $action === 'time_in' ? $conn->insert_id : $last['id'],
        'time_in' => $timeIn,
        'time_out' => $timeOut,
        'status' => $status
    ]
];

debugLog("Success response", $response);
respond($response);
?>
