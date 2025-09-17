<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication check
if (!isset($_SESSION['staff_logged_in']) || !$_SESSION['staff_logged_in']) {
    echo "Not logged in as staff!";
    exit;
}

require_once '../includes/db.php';

echo "<h1>Staff Attendance Debug Page</h1>";
echo "<p>Current date: " . date('Y-m-d') . "</p>";

// Check database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>Database connection failed: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>Database connected successfully!</p>";
}

// Check if attendance table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'attendance'");
if ($tableExists->num_rows == 0) {
    echo "<p style='color: red;'>Attendance table does NOT exist!</p>";
    
    // Try to create the table
    echo "<p>Attempting to create attendance table...</p>";
    $createTable = "CREATE TABLE IF NOT EXISTS attendance (
        id INT(11) NOT NULL AUTO_INCREMENT,
        member_id INT(11) NOT NULL,
        member_code VARCHAR(50) NOT NULL,
        date DATE NOT NULL,
        time_in DATETIME DEFAULT NULL,
        time_out DATETIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY member_id (member_id),
        KEY idx_member_date (member_id, date),
        KEY idx_date (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createTable)) {
        echo "<p style='color: green;'>Attendance table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>Failed to create attendance table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>Attendance table exists!</p>";
}

// Check if members table exists
$membersExists = $conn->query("SHOW TABLES LIKE 'members'");
if ($membersExists->num_rows == 0) {
    echo "<p style='color: red;'>Members table does NOT exist!</p>";
} else {
    echo "<p style='color: green;'>Members table exists!</p>";
    
    // Count members
    $memberCount = $conn->query("SELECT COUNT(*) as count FROM members");
    $count = $memberCount->fetch_assoc()['count'];
    echo "<p>Total members: $count</p>";
}

// Check today's attendance
$today = date('Y-m-d');
echo "<h2>Today's Attendance Check</h2>";
echo "<p>Checking for date: $today</p>";

$todayAttendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today'");
if ($todayAttendance) {
    $attendanceCount = $todayAttendance->fetch_assoc()['count'];
    echo "<p>Today's attendance records: $attendanceCount</p>";
    
    if ($attendanceCount > 0) {
        echo "<h3>Recent Attendance Records:</h3>";
        $recentAttendance = $conn->query("SELECT * FROM attendance WHERE date = '$today' ORDER BY id DESC LIMIT 5");
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Member ID</th><th>Member Code</th><th>Time In</th><th>Time Out</th></tr>";
        while ($row = $recentAttendance->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['member_id']}</td>";
            echo "<td>{$row['member_code']}</td>";
            echo "<td>{$row['time_in']}</td>";
            echo "<td>" . ($row['time_out'] ?: 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>Error checking attendance: " . $conn->error . "</p>";
}

// Test the attendance query
echo "<h2>Testing Attendance Query</h2>";
try {
    $sql = "SELECT 
                a.id AS attendance_id,
                a.member_id,
                a.member_code,
                a.time_in,
                a.time_out,
                a.date,
                m.first_name,
                m.last_name,
                m.membership_type,
                m.photo,
                m.phone
            FROM attendance a
            JOIN members m ON m.id = a.member_id
            WHERE DATE(a.date) = ?
            ORDER BY a.time_in DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "<p style='color: red;'>Error preparing statement: " . $conn->error . "</p>";
    } else {
        $stmt->bind_param('s', $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<p>Query executed successfully!</p>";
        echo "<p>Number of results: " . $result->num_rows . "</p>";
        
        if ($result->num_rows > 0) {
            echo "<h3>Query Results:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Attendance ID</th><th>Member Name</th><th>Member Code</th><th>Time In</th><th>Time Out</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['attendance_id']}</td>";
                echo "<td>{$row['first_name']} {$row['last_name']}</td>";
                echo "<td>{$row['member_code']}</td>";
                echo "<td>{$row['time_in']}</td>";
                echo "<td>" . ($row['time_out'] ?: 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        $stmt->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
}

// Check for any recent attendance records
echo "<h2>All Recent Attendance Records</h2>";
$allAttendance = $conn->query("SELECT * FROM attendance ORDER BY id DESC LIMIT 10");
if ($allAttendance && $allAttendance->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Member ID</th><th>Member Code</th><th>Date</th><th>Time In</th><th>Time Out</th></tr>";
    while ($row = $allAttendance->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['member_id']}</td>";
        echo "<td>{$row['member_code']}</td>";
        echo "<td>{$row['date']}</td>";
        echo "<td>{$row['time_in']}</td>";
        echo "<td>" . ($row['time_out'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No attendance records found in the database.</p>";
}

// Check database structure
echo "<h2>Database Structure</h2>";
$tables = $conn->query("SHOW TABLES");
echo "<p>Available tables:</p>";
echo "<ul>";
while ($table = $tables->fetch_array()) {
    echo "<li>{$table[0]}</li>";
}
echo "</ul>";

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
