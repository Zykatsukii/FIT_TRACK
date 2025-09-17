<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

include '../includes/auth.php';
requireMemberAuth();
include '../includes/db.php';

$member_id = getCurrentMemberId();

if (!$member_id) {
    echo json_encode(['success' => false, 'message' => 'Member not authenticated']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action !== 'archive_completed') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // First, ensure archive table exists
    $create_archive_sql = "CREATE TABLE IF NOT EXISTS attendance_archive (
        id INT(11) NOT NULL AUTO_INCREMENT,
        member_id INT(11) NOT NULL,
        member_code VARCHAR(50) NOT NULL,
        archive_date DATE NOT NULL,
        time_in DATETIME DEFAULT NULL,
        time_out DATETIME DEFAULT NULL,
        archived_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY member_id (member_id),
        KEY idx_member_date (member_id, archive_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!$conn->query($create_archive_sql)) {
        throw new Exception("Failed to create archive table: " . $conn->error);
    }
    
    // Get completed attendance records (those with time_out)
    $select_sql = "SELECT * FROM attendance WHERE member_id = ? AND time_out IS NOT NULL ORDER BY date ASC";
    $select_stmt = $conn->prepare($select_sql);
    $select_stmt->bind_param('i', $member_id);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    
    $records_to_archive = [];
    while ($row = $result->fetch_assoc()) {
        $records_to_archive[] = $row;
    }
    $select_stmt->close();
    
    if (empty($records_to_archive)) {
        echo json_encode(['success' => false, 'message' => 'No completed attendance records found to archive']);
        $conn->rollback();
        exit;
    }
    
    // Insert records into archive
    $insert_archive_sql = "INSERT INTO attendance_archive (member_id, member_code, archive_date, time_in, time_out) VALUES (?, ?, ?, ?, ?)";
    $insert_archive_stmt = $conn->prepare($insert_archive_sql);
    
    foreach ($records_to_archive as $record) {
        $insert_archive_stmt->bind_param('issss', 
            $record['member_id'], 
            $record['member_code'], 
            $record['date'], 
            $record['time_in'], 
            $record['time_out']
        );
        
        if (!$insert_archive_stmt->execute()) {
            throw new Exception("Failed to insert into archive: " . $insert_archive_stmt->error);
        }
    }
    $insert_archive_stmt->close();
    
    // Delete completed records from current table
    $delete_sql = "DELETE FROM attendance WHERE member_id = ? AND time_out IS NOT NULL";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param('i', $member_id);
    
    if (!$delete_stmt->execute()) {
        throw new Exception("Failed to delete from current table: " . $delete_stmt->error);
    }
    $delete_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Completed attendance records archived successfully',
        'archived_count' => count($records_to_archive)
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in archive_attendance: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
