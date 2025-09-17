<?php
$page_title = "Dashboard Debug";
include '../includes/functions.php';
include 'components/header.php';

echo "<div class='container mt-4'>";
echo "<h2>Dashboard Debug Information</h2>";

// Get dashboard data
$totalMembers = getTotalMembers();
$totalStaff = getTotalStaff();
$todayAttendance = getTodayAttendance();
$activeNow = getActiveNow();
$activeStaffNow = getActiveStaffNow();

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<h4>Function Results:</h4>";
echo "<ul>";
echo "<li>Total Members: $totalMembers</li>";
echo "<li>Total Staff: $totalStaff</li>";
echo "<li>Today's Attendance: $todayAttendance</li>";
echo "<li>Active Members Now: $activeNow</li>";
echo "<li>Active Staff Now: $activeStaffNow</li>";
echo "<li>Total Active Users: " . ($activeNow + $activeStaffNow) . "</li>";
echo "</ul>";
echo "</div>";

echo "<div class='col-md-6'>";
echo "<h4>Database Information:</h4>";

global $conn;
$today = date('Y-m-d');

// Check if attendance table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'attendance'");
if ($tableExists && $tableExists->num_rows > 0) {
    echo "<p><strong>✓ Attendance table exists</strong></p>";
    
    // Check table structure
    $structure = $conn->query("DESCRIBE attendance");
    if ($structure) {
        echo "<h5>Table Structure:</h5>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr></thead>";
        echo "<tbody>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    }
} else {
    echo "<p><strong>✗ Attendance table does not exist!</strong></p>";
}

// Check total records
$totalRecords = $conn->query("SELECT COUNT(*) as total FROM attendance");
if ($totalRecords && $totalRecords->num_rows > 0) {
    $total = $totalRecords->fetch_assoc()['total'];
    echo "<p><strong>Total records in attendance table: $total</strong></p>";
}

// Check today's records
$todayRecords = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE DATE(date) = '$today'");
if ($todayRecords && $todayRecords->num_rows > 0) {
    $todayCount = $todayRecords->fetch_assoc()['total'];
    echo "<p><strong>Records for today ($today): $todayCount</strong></p>";
}

// Check active records
$activeRecords = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE DATE(date) = '$today' AND time_out IS NULL");
if ($activeRecords && $activeRecords->num_rows > 0) {
    $activeCount = $activeRecords->fetch_assoc()['total'];
    echo "<p><strong>Total active records for today: $activeCount</strong></p>";
}

// Check active members only
$activeMembers = $conn->query("SELECT COUNT(*) as total FROM attendance a 
                               JOIN members m ON a.member_id = m.id 
                               WHERE DATE(a.date) = '$today' AND a.time_out IS NULL");
if ($activeMembers && $activeMembers->num_rows > 0) {
    $activeMemberCount = $activeMembers->fetch_assoc()['total'];
    echo "<p><strong>Active members for today: $activeMemberCount</strong></p>";
}

// Check active staff only
$activeStaff = $conn->query("SELECT COUNT(*) as total FROM attendance a 
                             JOIN staff s ON a.member_id = s.id 
                             WHERE DATE(a.date) = '$today' AND a.time_out IS NULL");
if ($activeStaff && $activeStaff->num_rows > 0) {
    $activeStaffCount = $activeStaff->fetch_assoc()['total'];
    echo "<p><strong>Active staff for today: $activeStaffCount</strong></p>";
}

// Show sample data
echo "<h5>Sample Attendance Records:</h5>";
$sampleData = $conn->query("SELECT * FROM attendance ORDER BY date DESC, time_in DESC LIMIT 5");
if ($sampleData && $sampleData->num_rows > 0) {
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>ID</th><th>Member ID</th><th>Date</th><th>Time In</th><th>Time Out</th></tr></thead>";
    echo "<tbody>";
    while ($row = $sampleData->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['member_id']}</td>";
        echo "<td>{$row['date']}</td>";
        echo "<td>{$row['time_in']}</td>";
        echo "<td>{$row['time_out']}</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>No attendance records found.</p>";
    
    // Check if we have members to create test data
    $memberCount = $conn->query("SELECT COUNT(*) as total FROM members")->fetch_assoc()['total'];
    if ($memberCount > 0) {
        echo "<h5>Create Test Data:</h5>";
        echo "<form method='post'>";
        echo "<button type='submit' name='create_test_data' class='btn btn-primary'>Create Test Attendance Data</button>";
        echo "</form>";
        
        if (isset($_POST['create_test_data'])) {
            // Get first member
            $member = $conn->query("SELECT id, member_id FROM members LIMIT 1")->fetch_assoc();
            if ($member) {
                $today = date('Y-m-d');
                $now = date('Y-m-d H:i:s');
                
                // Insert test attendance record
                $insertSql = "INSERT INTO attendance (member_id, member_code, date, time_in) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insertSql);
                $stmt->bind_param('isss', $member['id'], $member['member_id'], $today, $now);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success mt-3'>Test attendance record created successfully!</div>";
                    echo "<script>location.reload();</script>";
                } else {
                    echo "<div class='alert alert-danger mt-3'>Error creating test data: " . $stmt->error . "</div>";
                }
                $stmt->close();
            }
        }
    }
}

echo "</div>";
echo "</div>";

echo "<div class='row mt-4'>";
echo "<div class='col-12'>";
echo "<h4>Manual SQL Queries:</h4>";
echo "<p><strong>Today's Date:</strong> $today</p>";
echo "<p><strong>Query for Today's Attendance:</strong> SELECT COUNT(*) as total FROM attendance WHERE DATE(date) = '$today'</p>";
echo "<p><strong>Query for Active Now:</strong> SELECT COUNT(*) as total FROM attendance WHERE DATE(date) = '$today' AND time_out IS NULL</p>";
echo "</div>";
echo "</div>";

echo "</div>";

include 'components/footer.php';
?>
