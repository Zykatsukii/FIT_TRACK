<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

function respond($data, $status = 200) {
	http_response_code($status);
	echo json_encode($data);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	respond(['success' => false, 'message' => 'Invalid request method'], 405);
}

action:
$action = $_POST['action'] ?? '';
if ($action === '') {
	respond(['success' => false, 'message' => 'Missing action'], 400);
}

// Ensure archive table exists
$createArchive = "CREATE TABLE IF NOT EXISTS attendance_archive (
	id INT AUTO_INCREMENT PRIMARY KEY,
	archive_date DATE NOT NULL,
	member_id INT NOT NULL,
	member_code VARCHAR(50) NOT NULL,
	time_in DATETIME DEFAULT NULL,
	time_out DATETIME DEFAULT NULL,
	status ENUM('Active','Completed') NOT NULL,
	saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_archive_date (archive_date),
	INDEX idx_member_archive (member_id, archive_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($createArchive)) {
	respond(['success' => false, 'message' => 'Failed to create archive table: ' . $conn->error], 500);
}

$today = date('Y-m-d');

if ($action === 'save_today') {
	// Insert today's attendance into archive
	$sql = "INSERT INTO attendance_archive (archive_date, member_id, member_code, time_in, time_out, status)
		SELECT a.date AS archive_date, a.member_id, a.member_code, a.time_in, a.time_out,
			CASE WHEN a.time_out IS NULL THEN 'Active' ELSE 'Completed' END AS status
		FROM attendance a WHERE a.date = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('s', $today);
	if (!$stmt->execute()) {
		respond(['success' => false, 'message' => 'Failed to save today\'s attendance: ' . $stmt->error], 500);
	}
	$affected = $stmt->affected_rows;
	$stmt->close();
	respond(['success' => true, 'message' => "Saved $affected record(s) to archive."], 200);
}

if ($action === 'reset_day') {
	$conn->begin_transaction();
	try {
		// Save first
		$sql = "INSERT INTO attendance_archive (archive_date, member_id, member_code, time_in, time_out, status)
			SELECT a.date AS archive_date, a.member_id, a.member_code, a.time_in, a.time_out,
				CASE WHEN a.time_out IS NULL THEN 'Active' ELSE 'Completed' END AS status
			FROM attendance a WHERE a.date = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param('s', $today);
		$stmt->execute();
		$saved = $stmt->affected_rows;
		$stmt->close();

		// Then clear today's rows from live attendance
		$del = $conn->prepare("DELETE FROM attendance WHERE date = ?");
		$del->bind_param('s', $today);
		$del->execute();
		$deleted = $del->affected_rows;
		$del->close();

		$conn->commit();
		respond(['success' => true, 'message' => "Archived $saved and reset $deleted record(s)."], 200);
	} catch (Throwable $e) {
		$conn->rollback();
		respond(['success' => false, 'message' => 'Reset failed: ' . $e->getMessage()], 500);
	}
}

respond(['success' => false, 'message' => 'Unknown action'], 400); 