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

$action = $_POST['action'] ?? '';
if ($action === '') {
	respond(['success' => false, 'message' => 'Missing action'], 400);
}

if ($action === 'edit_attendance') {
	$attendanceId = $_POST['attendance_id'] ?? '';
	$viewType = $_POST['view_type'] ?? '';
	$timeIn = $_POST['time_in'] ?? '';
	$timeOut = $_POST['time_out'] ?? '';
	
	if (!$attendanceId || !$timeIn) {
		respond(['success' => false, 'message' => 'Missing required fields'], 400);
	}
	
	// Validate time format
	if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $timeIn)) {
		respond(['success' => false, 'message' => 'Invalid time in format'], 400);
	}
	
	if ($timeOut && !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $timeOut)) {
		respond(['success' => false, 'message' => 'Invalid time out format'], 400);
	}
	
	// Convert datetime-local format to MySQL datetime format
	$timeInFormatted = date('Y-m-d H:i:s', strtotime($timeIn));
	$timeOutFormatted = $timeOut ? date('Y-m-d H:i:s', strtotime($timeOut)) : null;
	
	// Determine which table to update based on view type
	$updated = false;
	
	if ($viewType === 'archive') {
		// Try to update attendance_archive table first (new table)
		$sql = "UPDATE attendance_archive SET time_in = ?, time_out = ? WHERE id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param('ssi', $timeInFormatted, $timeOutFormatted, $attendanceId);
		$stmt->execute();
		
		if ($stmt->affected_rows > 0) {
			$updated = true;
		} else {
			// If no rows affected, try archived_attendance table (old table)
			$timeInTime = $timeInFormatted ? date('H:i:s', strtotime($timeInFormatted)) : null;
			$timeOutTime = $timeOutFormatted ? date('H:i:s', strtotime($timeOutFormatted)) : null;
			$sql = "UPDATE archived_attendance SET time_in = ?, time_out = ? WHERE id = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param('ssi', $timeInTime, $timeOutTime, $attendanceId);
			$stmt->execute();
			
			if ($stmt->affected_rows > 0) {
				$updated = true;
			} else {
				// If still no rows affected, try main attendance table
				$sql = "UPDATE attendance SET time_in = ?, time_out = ? WHERE id = ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param('ssi', $timeInFormatted, $timeOutFormatted, $attendanceId);
				$stmt->execute();
				
				if ($stmt->affected_rows > 0) {
					$updated = true;
				}
			}
		}
	} else {
		// Update live attendance table
		$sql = "UPDATE attendance SET time_in = ?, time_out = ? WHERE id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param('ssi', $timeInFormatted, $timeOutFormatted, $attendanceId);
		$stmt->execute();
		
		if ($stmt->affected_rows > 0) {
			$updated = true;
		}
	}
	
	if (!$updated) {
		respond(['success' => false, 'message' => 'No attendance record found with that ID'], 404);
	}
	
	$stmt->close();
	respond(['success' => true, 'message' => 'Attendance updated successfully'], 200);
}

if ($action === 'get_details') {
	$attendanceId = $_POST['attendance_id'] ?? '';
	
	if (!$attendanceId) {
		respond(['success' => false, 'message' => 'Missing attendance ID'], 400);
	}
	
	// Try to get from all possible tables
	$attendance = null;
	
	// First try main attendance table
	$sql = "SELECT a.*, m.first_name, m.last_name, m.membership_type, 'member' as user_type
			FROM attendance a
			JOIN members m ON m.id = a.member_id
			WHERE a.id = ?
			UNION
			SELECT a.*, s.first_name, s.last_name, s.position as membership_type, 'staff' as user_type
			FROM attendance a
			JOIN staff s ON s.id = a.member_id
			WHERE a.id = ?";
	
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('ii', $attendanceId, $attendanceId);
	$stmt->execute();
	$result = $stmt->get_result();
	
	if ($result->num_rows > 0) {
		$attendance = $result->fetch_assoc();
	} else {
		// Try attendance_archive table (new table)
		$sql = "SELECT aa.id, aa.member_id, aa.member_code, 
				aa.time_in, aa.time_out, aa.date,
				m.first_name, m.last_name, m.membership_type, 'member' as user_type
				FROM attendance_archive aa
				JOIN members m ON m.id = aa.member_id
				WHERE aa.id = ?
				UNION
				SELECT aa.id, aa.member_id, aa.member_code, 
				aa.time_in, aa.time_out, aa.date,
				s.first_name, s.last_name, s.position as membership_type, 'staff' as user_type
				FROM attendance_archive aa
				JOIN staff s ON s.id = aa.member_id
				WHERE aa.id = ?";
		
		$stmt = $conn->prepare($sql);
		$stmt->bind_param('ii', $attendanceId, $attendanceId);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if ($result->num_rows > 0) {
			$attendance = $result->fetch_assoc();
		} else {
			// Try archived_attendance table (old table)
			$sql = "SELECT aa.id, aa.member_id, aa.member_code, 
					CONCAT(aa.date, ' ', aa.time_in) as time_in,
					CONCAT(aa.date, ' ', aa.time_out) as time_out,
					aa.date, aa.first_name, aa.last_name, aa.membership_type, 'member' as user_type
					FROM archived_attendance aa
					WHERE aa.id = ?";
			
			$stmt = $conn->prepare($sql);
			$stmt->bind_param('i', $attendanceId);
			$stmt->execute();
			$result = $stmt->get_result();
			
			if ($result->num_rows > 0) {
				$attendance = $result->fetch_assoc();
			}
		}
	}
	
	if (!$attendance) {
		respond(['success' => false, 'message' => 'Attendance record not found'], 404);
	}
	$stmt->close();
	
	// Calculate duration
	$duration = '-';
	if ($attendance['time_in'] && $attendance['time_out']) {
		$timeInObj = new DateTime($attendance['time_in']);
		$timeOutObj = new DateTime($attendance['time_out']);
		$diff = $timeInObj->diff($timeOutObj);
		$duration = $diff->format('%H hours %i minutes');
	} elseif ($attendance['time_in']) {
		$timeInObj = new DateTime($attendance['time_in']);
		$now = new DateTime();
		$diff = $timeInObj->diff($now);
		$duration = $diff->format('%H hours %i minutes');
	}
	
	// Generate HTML for details
	$html = '
	<div class="row">
		<div class="col-md-6">
			<h6 class="text-primary mb-3">Personal Information</h6>
			<table class="table table-sm table-borderless text-white-50">
				<tr>
					<td><strong>Name:</strong></td>
					<td>' . htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']) . '</td>
				</tr>
				<tr>
					<td><strong>ID:</strong></td>
					<td>' . htmlspecialchars($attendance['member_code']) . '</td>
				</tr>
				<tr>
					<td><strong>Type:</strong></td>
					<td><span class="badge bg-' . ($attendance['user_type'] === 'staff' ? 'info' : 'success') . '">' . ucfirst($attendance['user_type']) . '</span></td>
				</tr>
				<tr>
					<td><strong>Category:</strong></td>
					<td>' . htmlspecialchars(ucfirst($attendance['membership_type'])) . '</td>
				</tr>
			</table>
		</div>
		<div class="col-md-6">
			<h6 class="text-primary mb-3">Attendance Details</h6>
			<table class="table table-sm table-borderless text-white-50">
				<tr>
					<td><strong>Date:</strong></td>
					<td>' . date('M d, Y', strtotime($attendance['date'])) . '</td>
				</tr>
				<tr>
					<td><strong>Time In:</strong></td>
					<td>' . ($attendance['time_in'] ? date('h:i A', strtotime($attendance['time_in'])) : 'Not recorded') . '</td>
				</tr>
				<tr>
					<td><strong>Time Out:</strong></td>
					<td>' . ($attendance['time_out'] ? date('h:i A', strtotime($attendance['time_out'])) : 'Not recorded') . '</td>
				</tr>
				<tr>
					<td><strong>Duration:</strong></td>
					<td>' . $duration . '</td>
				</tr>
				<tr>
					<td><strong>Status:</strong></td>
					<td><span class="badge ' . ($attendance['time_out'] ? 'bg-success' : 'bg-warning') . '">' . ($attendance['time_out'] ? 'Completed' : 'Active') . '</span></td>
				</tr>
			</table>
		</div>
	</div>';
	
	respond(['success' => true, 'html' => $html], 200);
}

if ($action === 'save_today') {
	// Save today's attendance to attendance_archive table (new table)
	$sql = "INSERT INTO attendance_archive (member_id, member_code, date, time_in, time_out, archive_date)
			SELECT a.member_id, a.member_code, a.date, a.time_in, a.time_out, CURDATE()
			FROM attendance a
			WHERE a.date = CURDATE()";
	
	if ($conn->query($sql)) {
		respond(['success' => true, 'message' => 'Today\'s attendance saved to archive'], 200);
	} else {
		respond(['success' => false, 'message' => 'Failed to save attendance: ' . $conn->error], 500);
	}
}

if ($action === 'reset_day') {
	// Archive today's data and clear the table
	$conn->begin_transaction();
	
	try {
		// First save to attendance_archive (new table)
		$sql = "INSERT INTO attendance_archive (member_id, member_code, date, time_in, time_out, archive_date)
				SELECT a.member_id, a.member_code, a.date, a.time_in, a.time_out, CURDATE()
				FROM attendance a
				WHERE a.date = CURDATE()";
		$conn->query($sql);
		
		// Then delete from main table
		$sql = "DELETE FROM attendance WHERE date = CURDATE()";
		$conn->query($sql);
		
		$conn->commit();
		respond(['success' => true, 'message' => 'Day reset successfully'], 200);
	} catch (Exception $e) {
		$conn->rollback();
		respond(['success' => false, 'message' => 'Failed to reset day: ' . $e->getMessage()], 500);
	}
}

if ($action === 'delete_attendance') {
	$attendanceId = $_POST['attendance_id'] ?? '';
	$viewType = $_POST['view_type'] ?? '';
	
	if (!$attendanceId) {
		respond(['success' => false, 'message' => 'Missing attendance ID'], 400);
	}
	
	// Determine which table to delete from based on view type
	$deleted = false;
	
	if ($viewType === 'archive') {
		// Try to delete from attendance_archive table first (new table)
		$sql = "DELETE FROM attendance_archive WHERE id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param('i', $attendanceId);
		$stmt->execute();
		
		if ($stmt->affected_rows > 0) {
			$deleted = true;
		} else {
			// If no rows affected, try archived_attendance table (old table)
			$sql = "DELETE FROM archived_attendance WHERE id = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param('i', $attendanceId);
			$stmt->execute();
			
			if ($stmt->affected_rows > 0) {
				$deleted = true;
			} else {
				// If still no rows affected, try main attendance table
				$sql = "DELETE FROM attendance WHERE id = ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param('i', $attendanceId);
				$stmt->execute();
				
				if ($stmt->affected_rows > 0) {
					$deleted = true;
				}
			}
		}
	} else {
		// Delete from live attendance table
		$sql = "DELETE FROM attendance WHERE id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param('i', $attendanceId);
		$stmt->execute();
		
		if ($stmt->affected_rows > 0) {
			$deleted = true;
		}
	}
	
	if (!$deleted) {
		respond(['success' => false, 'message' => 'No attendance record found with that ID'], 404);
	}
	
	$stmt->close();
	respond(['success' => true, 'message' => 'Attendance record deleted successfully'], 200);
}

respond(['success' => false, 'message' => 'Unknown action'], 400);
?>

