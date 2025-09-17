<?php
header('Content-Type: text/html; charset=utf-8');
require_once '../includes/db.php';

// Handle form submission
$result = '';
if ($_POST) {
    $qrData = trim($_POST['qr_data'] ?? '');
    if ($qrData) {
        // Test the QR code directly
        // Make a direct call to scan.php
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'scan.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['qr_data' => $qrData]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = $response;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple QR Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .container { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        input[type="text"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .sample { background: #e9ecef; padding: 10px; border-radius: 5px; margin: 5px 0; cursor: pointer; }
        .sample:hover { background: #dee2e6; }
    </style>
</head>
<body>
    <h1>🧪 Simple QR Code Test</h1>
    
    <div class="container info">
        <h3>Instructions</h3>
        <p>Enter a QR code data below and click "Test QR Code" to see if it works.</p>
    </div>

    <div class="container">
        <h3>Test QR Code</h3>
        <form method="POST">
            <input type="text" name="qr_data" id="qr_data" placeholder="Enter QR code data (e.g., FIT_TRACK_MEMBER_ID:MEM-2024-0001)" value="<?= htmlspecialchars($_POST['qr_data'] ?? '') ?>">
            <button type="submit">Test QR Code</button>
        </form>
    </div>

    <div class="container">
        <h3>Sample QR Codes (Click to test)</h3>
        <div class="sample" onclick="document.getElementById('qr_data').value='FIT_TRACK_MEMBER_ID:MEM-2024-0001'">
            <strong>Member QR:</strong> FIT_TRACK_MEMBER_ID:MEM-2024-0001
        </div>
        <div class="sample" onclick="document.getElementById('qr_data').value='FIT_TRACK_STAFF_ID:STAFF-2024-0001'">
            <strong>Staff QR:</strong> FIT_TRACK_STAFF_ID:STAFF-2024-0001
        </div>
        <div class="sample" onclick="document.getElementById('qr_data').value='MEM-2024-0001'">
            <strong>Raw Member:</strong> MEM-2024-0001
        </div>
        <div class="sample" onclick="document.getElementById('qr_data').value='STAFF-2024-0001'">
            <strong>Raw Staff:</strong> STAFF-2024-0001
        </div>
    </div>

    <?php if ($result): ?>
    <div class="container">
        <h3>Test Result</h3>
        <pre><?= htmlspecialchars($result) ?></pre>
        
        <?php 
        $jsonResult = json_decode($result, true);
        if ($jsonResult && isset($jsonResult['success'])): 
        ?>
            <?php if ($jsonResult['success']): ?>
                <div class="success">
                    <h4>✅ Success!</h4>
                    <p><strong>Action:</strong> <?= htmlspecialchars($jsonResult['action'] ?? 'Unknown') ?></p>
                    <p><strong>Member:</strong> <?= htmlspecialchars($jsonResult['member']['name'] ?? 'Unknown') ?></p>
                    <p><strong>Type:</strong> <?= htmlspecialchars($jsonResult['member']['type'] ?? 'Unknown') ?></p>
                    <p><strong>Time In:</strong> <?= htmlspecialchars($jsonResult['record']['time_in'] ?? 'Not set') ?></p>
                    <p><strong>Time Out:</strong> <?= htmlspecialchars($jsonResult['record']['time_out'] ?? 'Not set') ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($jsonResult['record']['status'] ?? 'Unknown') ?></p>
                </div>
            <?php else: ?>
                <div class="error">
                    <h4>❌ Error!</h4>
                    <p><strong>Message:</strong> <?= htmlspecialchars($jsonResult['message'] ?? 'Unknown error') ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="container">
        <h3>Quick Links</h3>
        <p><a href="check_database.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;">🔍 Check Database</a></p>
        <p><a href="fix_everything.php" style="background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;">🔧 Fix Everything</a></p>
        <p><a href="../admin/attendance.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;">📊 Attendance Page</a></p>
    </div>

    <script>
        // Auto-fill with sample data if empty
        document.getElementById('qr_data').addEventListener('focus', function() {
            if (!this.value) {
                this.value = 'FIT_TRACK_MEMBER_ID:MEM-2024-0001';
            }
        });
    </script>
</body>
</html>
