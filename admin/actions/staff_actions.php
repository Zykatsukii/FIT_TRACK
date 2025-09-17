<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../includes/db.php';

// Database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'view':
        $staffId = $_GET['staff_id'] ?? '';
        if (empty($staffId)) {
            echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    s.staff_id, s.first_name, s.last_name, s.email, s.phone, s.position, s.hire_date,
                    s.address, s.gender, s.photo, s.qr_code_data,
                    p.salary, p.employment_type, p.bank_name, p.account_number, p.tax_id
                FROM staff s
                LEFT JOIN payroll p ON s.id = p.staff_id
                WHERE s.staff_id = ?
            ");
            $stmt->execute([$staffId]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($staff) {
                echo json_encode(['success' => true, 'staff' => $staff]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Staff not found']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'edit':
        $staffId = $_POST['staff_id'] ?? '';
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $hireDate = $_POST['hire_date'] ?? '';
        $employmentType = $_POST['employment_type'] ?? 'full-time';
        $salary = $_POST['salary'] ?? '';
        $address = trim($_POST['address'] ?? '');
        $gender = $_POST['gender'] ?? '';
        
        if (empty($staffId) || empty($firstName) || empty($lastName) || empty($email) || empty($position)) {
            echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Update staff table
            $stmt = $pdo->prepare("
                UPDATE staff 
                SET first_name = ?, last_name = ?, email = ?, phone = ?, position = ?, 
                    hire_date = ?, address = ?, gender = ?
                WHERE staff_id = ?
            ");
            $stmt->execute([
                $firstName, $lastName, $email, $phone, $position, 
                $hireDate, $address ?: null, $gender ?: null, $staffId
            ]);
            
            // Get staff database ID
            $stmt = $pdo->prepare("SELECT id FROM staff WHERE staff_id = ?");
            $stmt->execute([$staffId]);
            $staffDbId = $stmt->fetchColumn();
            
            if ($staffDbId) {
                // Check if payroll record exists
                $stmt = $pdo->prepare("SELECT id FROM payroll WHERE staff_id = ?");
                $stmt->execute([$staffDbId]);
                $payrollExists = $stmt->fetchColumn();
                
                if ($payrollExists) {
                    // Update existing payroll record
                    $stmt = $pdo->prepare("
                        UPDATE payroll 
                        SET salary = ?, employment_type = ?
                        WHERE staff_id = ?
                    ");
                    $stmt->execute([
                        $salary ?: null, 
                        $employmentType, 
                        $staffDbId
                    ]);
                } else {
                    // Create new payroll record
                    $stmt = $pdo->prepare("
                        INSERT INTO payroll (staff_id, salary, employment_type)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([
                        $staffDbId, 
                        $salary ?: null, 
                        $employmentType
                    ]);
                }
            }
            
            $pdo->commit();
            
            // Return updated staff data
            $stmt = $pdo->prepare("
                SELECT 
                    s.staff_id, s.first_name, s.last_name, s.email, s.phone, s.position, s.hire_date,
                    s.address, s.gender, s.photo, s.qr_code_data,
                    p.salary, p.employment_type, p.bank_name, p.account_number
                FROM staff s
                LEFT JOIN payroll p ON s.id = p.staff_id
                WHERE s.staff_id = ?
            ");
            $stmt->execute([$staffId]);
            $updatedStaff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'staff' => $updatedStaff]);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'delete':
        $staffId = $_POST['staff_id'] ?? '';
        if (empty($staffId)) {
            echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Get staff database ID
            $stmt = $pdo->prepare("SELECT id FROM staff WHERE staff_id = ?");
            $stmt->execute([$staffId]);
            $staffDbId = $stmt->fetchColumn();
            
            if ($staffDbId) {
                // Delete from payroll first (due to foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM payroll WHERE staff_id = ?");
                $stmt->execute([$staffDbId]);
                
                // Delete from staff
                $stmt = $pdo->prepare("DELETE FROM staff WHERE staff_id = ?");
                $stmt->execute([$staffId]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Staff deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Staff not found']);
            }
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
