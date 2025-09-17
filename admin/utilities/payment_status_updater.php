<?php
/**
 * Payment Status Updater
 * This script automatically updates payment statuses based on membership expiration
 * Run this script periodically (daily) to keep payment statuses current
 */

include '../includes/db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all pending payments for expired memberships
    $query = "
        SELECT 
            mp.id,
            mp.member_id,
            mp.status,
            m.member_id as member_code,
            m.first_name,
            m.last_name,
            m.membership_type,
            m.membership_duration,
            m.join_date,
            COALESCE(m.expired_date, 
                CASE 
                    WHEN m.membership_type = 'session' THEN DATE_ADD(m.join_date, INTERVAL 1 DAY)
                    WHEN m.membership_type = 'regular' AND m.membership_duration IS NOT NULL THEN DATE_ADD(m.join_date, INTERVAL m.membership_duration MONTH)
                    ELSE DATE_ADD(m.join_date, INTERVAL 30 DAY)
                END
            ) AS expired_date
        FROM member_payroll mp
        JOIN members m ON mp.member_id = m.id
        WHERE mp.status = 'pending'
        AND COALESCE(m.expired_date, 
            CASE 
                WHEN m.membership_type = 'session' THEN DATE_ADD(m.join_date, INTERVAL 1 DAY)
                WHEN m.membership_type = 'regular' AND m.membership_duration IS NOT NULL THEN DATE_ADD(m.join_date, INTERVAL m.membership_duration MONTH)
                ELSE DATE_ADD(m.join_date, INTERVAL 30 DAY)
            END
        ) < CURDATE()
    ";
    
    $stmt = $pdo->query($query);
    $expiredPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updatedCount = 0;
    
    foreach ($expiredPayments as $payment) {
        // Update payment status to overdue
        $updateStmt = $pdo->prepare("UPDATE member_payroll SET status = 'overdue' WHERE id = ?");
        $updateStmt->execute([$payment['id']]);
        
        $updatedCount++;
        
        // Log the update
        $logMessage = "Payment ID {$payment['id']} for member {$payment['member_code']} ({$payment['first_name']} {$payment['last_name']}) marked as overdue. Expired: {$payment['expired_date']}";
        error_log($logMessage);
    }
    
    echo "Payment status updater completed.\n";
    echo "Updated {$updatedCount} payments to overdue status.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    error_log("Payment status updater error: " . $e->getMessage());
}
?>
