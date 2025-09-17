<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../../includes/functions.php';

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action === '') {
    respond(['success' => false, 'message' => 'Missing action'], 400);
}

// Helper to load member by member_id
function getMember($conn, $memberId) {
    try {
        $stmt = $conn->prepare("SELECT * FROM members WHERE member_id = ? LIMIT 1");
        $stmt->bind_param("s", $memberId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error in getMember: " . $e->getMessage());
        return null;
    }
}

if ($action === 'view') {
    $memberId = $_GET['member_id'] ?? '';
    if ($memberId === '') respond(['success' => false, 'message' => 'Missing member_id'], 400);
    
    $member = getMember($conn, $memberId);
    if (!$member) respond(['success' => false, 'message' => 'Member not found'], 404);
    
    // Use stored expired_date if available, otherwise calculate it
    if ($member['expired_date']) {
        $expiredDate = $member['expired_date'];
    } else {
        // Calculate expired date for legacy records
        if ($member['membership_type'] === 'session') {
            $expiredDate = date('Y-m-d', strtotime($member['join_date'] . ' +1 day'));
        } elseif ($member['membership_type'] === 'regular' && $member['membership_duration']) {
            $expiredDate = date('Y-m-d', strtotime($member['join_date'] . ' +' . $member['membership_duration'] . ' months'));
        } else {
            $expiredDate = date('Y-m-d', strtotime($member['join_date'] . ' +30 days'));
        }
    }
    
    $member['expired_date'] = $expiredDate;
    respond(['success' => true, 'member' => $member]);
}

if ($action === 'update') {
    $memberId = $_POST['member_id'] ?? '';
    if ($memberId === '') respond(['success' => false, 'message' => 'Missing member_id'], 400);
    
    $member = getMember($conn, $memberId);
    if (!$member) respond(['success' => false, 'message' => 'Member not found'], 404);
    
    // Get form data
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $membershipType = $_POST['membership_type'] ?? '';
    $membershipDuration = $_POST['membership_duration'] ?? null;
    $joinDate = $_POST['join_date'] ?? '';
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($membershipType) || empty($joinDate)) {
        respond(['success' => false, 'message' => 'All fields are required'], 422);
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(['success' => false, 'message' => 'Invalid email format'], 422);
    }
    
    // Check if email already exists for another member
    $stmt = $conn->prepare("SELECT member_id FROM members WHERE email = ? AND member_id != ?");
    $stmt->bind_param("ss", $email, $memberId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        respond(['success' => false, 'message' => 'Email already exists for another member'], 422);
    }
    
    // Validate membership duration for regular membership
    if ($membershipType === 'regular' && (empty($membershipDuration) || $membershipDuration < 1)) {
        respond(['success' => false, 'message' => 'Duration is required for Regular membership (minimum 1 month)'], 422);
    }
    
    // Update member
    $stmt = $conn->prepare("UPDATE members SET first_name=?, last_name=?, email=?, phone=?, membership_type=?, membership_duration=?, join_date=? WHERE member_id=?");
    $stmt->bind_param("ssssssss", $firstName, $lastName, $email, $phone, $membershipType, $membershipDuration, $joinDate, $memberId);
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'Update failed: ' . $stmt->error], 500);
    }
    
    // Get updated member data
    $updated = getMember($conn, $memberId);
    respond(['success' => true, 'member' => $updated, 'message' => 'Member updated successfully']);
}

if ($action === 'delete') {
    $memberId = $_POST['member_id'] ?? '';
    if ($memberId === '') respond(['success' => false, 'message' => 'Missing member_id'], 400);
    
    $member = getMember($conn, $memberId);
    if (!$member) respond(['success' => false, 'message' => 'Member not found'], 404);
    
    // Delete member
    $stmt = $conn->prepare("DELETE FROM members WHERE member_id = ?");
    $stmt->bind_param("s", $memberId);
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'Delete failed: ' . $stmt->error], 500);
    }
    
    respond(['success' => true, 'message' => 'Member deleted successfully']);
}

if ($action === 'renew_membership') {
    $memberId = $_POST['member_id'] ?? '';
    $newMembershipType = $_POST['membership_type'] ?? '';
    $newMembershipDuration = $_POST['membership_duration'] ?? null;
    
    if ($memberId === '') respond(['success' => false, 'message' => 'Missing member_id'], 400);
    if ($newMembershipType === '') respond(['success' => false, 'message' => 'Missing membership_type'], 400);
    
    $member = getMember($conn, $memberId);
    if (!$member) respond(['success' => false, 'message' => 'Member not found'], 404);
    
    // Validate membership duration for regular membership
    if ($newMembershipType === 'regular' && (empty($newMembershipDuration) || $newMembershipDuration < 1)) {
        respond(['success' => false, 'message' => 'Duration is required for Regular membership (minimum 1 month)'], 422);
    }
    
    // Set membership duration to null for session type
    if ($newMembershipType === 'session') {
        $newMembershipDuration = null;
    }
    
    // Update membership with new join date (current date)
    $newJoinDate = date('Y-m-d');
    
    // Calculate new expired date
    $newExpiredDate = null;
    if ($newMembershipType === 'session') {
        $newExpiredDate = date('Y-m-d', strtotime($newJoinDate . ' +1 day'));
    } elseif ($newMembershipType === 'regular' && $newMembershipDuration) {
        $newExpiredDate = date('Y-m-d', strtotime($newJoinDate . ' +' . $newMembershipDuration . ' months'));
    } else {
        $newExpiredDate = date('Y-m-d', strtotime($newJoinDate . ' +30 days'));
    }
    
    $stmt = $conn->prepare("UPDATE members SET membership_type=?, membership_duration=?, join_date=?, expired_date=? WHERE member_id=?");
    $stmt->bind_param("sssss", $newMembershipType, $newMembershipDuration, $newJoinDate, $newExpiredDate, $memberId);
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'Membership renewal failed: ' . $stmt->error], 500);
    }
    
    // Get updated member data
    $updated = getMember($conn, $memberId);
    
    // Calculate new expired date
    $expiredDate = null;
    if ($updated['membership_type'] === 'session') {
        $expiredDate = date('Y-m-d', strtotime($updated['join_date'] . ' +1 day'));
    } elseif ($updated['membership_type'] === 'regular' && $updated['membership_duration']) {
        $expiredDate = date('Y-m-d', strtotime($updated['join_date'] . ' +' . $updated['membership_duration'] . ' months'));
    } else {
        $expiredDate = date('Y-m-d', strtotime($updated['join_date'] . ' +30 days'));
    }
    
    $updated['expired_date'] = $newExpiredDate;
    
    // Calculate payment amount based on membership type
    $paymentAmount = 0;
    if ($newMembershipType === 'regular') {
        $paymentAmount = 1000;
    } elseif ($newMembershipType === 'student') {
        $paymentAmount = 700;
    }
    
    // For renewal, update existing payment record instead of creating new one
    if ($paymentAmount > 0) {
        try {
            // Check if there's an existing pending payment for this member
            $stmt = $conn->prepare("SELECT id FROM member_payroll WHERE member_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param("i", $updated['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingPayment = $result->fetch_assoc();
            
            if ($existingPayment) {
                // Update existing payment record
                $stmt = $conn->prepare("UPDATE member_payroll SET 
                    membership_type = ?, 
                    amount = ?, 
                    payment_date = ?, 
                    notes = ? 
                    WHERE id = ?");
                
                $renewalNotes = "Membership renewal - {$newMembershipType} membership for {$newMembershipDuration} months";
                
                $stmt->bind_param("ssssi", $newMembershipType, $paymentAmount, $newJoinDate, $renewalNotes, $existingPayment['id']);
                $stmt->execute();
            } else {
                // Only create new payment record if no existing pending payment
                $stmt = $conn->prepare("INSERT INTO member_payroll 
                    (member_id, membership_type, amount, payment_date, status, notes) 
                    VALUES (?, ?, ?, ?, 'pending', ?)");
                
                $renewalNotes = "Membership renewal - {$newMembershipType} membership for {$newMembershipDuration} months";
                
                $stmt->bind_param("issss", $updated['id'], $newMembershipType, $paymentAmount, $newJoinDate, $renewalNotes);
                $stmt->execute();
            }
        } catch (Exception $e) {
            // Log error but don't fail the renewal
            error_log("Failed to update payment record for renewal: " . $e->getMessage());
        }
    }
    
    // Log the membership renewal
    $logMessage = "Membership renewed for member ID: {$memberId}, Type: {$newMembershipType}, Duration: {$newMembershipDuration}, New Join Date: {$newJoinDate}, New Expiry: {$newExpiredDate}, Payment Amount: ₱{$paymentAmount}";
    error_log($logMessage);
    
    respond(['success' => true, 'member' => $updated, 'message' => 'Membership renewed successfully. Payment record created with amount: ₱' . number_format($paymentAmount, 2)]);
}

// If no action matches
respond(['success' => false, 'message' => 'Invalid action'], 400);
?> 