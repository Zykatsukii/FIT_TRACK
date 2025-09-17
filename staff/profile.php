<?php
$page_title = "Staff Profile";
include 'components/header.php';
include '../includes/db.php';

// Get staff data (this would normally come from session)
// For demo purposes, we'll get the first staff member
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT * FROM staff ORDER BY id DESC LIMIT 1");
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff) {
        echo "<div class='alert alert-warning'>No staff members found. Please register a staff member first.</div>";
        include 'components/footer.php';
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Staff Profile</h1>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Staff Information Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Personal Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <?php if ($staff['photo']): ?>
                                <img src="../<?= htmlspecialchars($staff['photo']) ?>" 
                                     class="img-profile rounded-circle" 
                                     style="width: 120px; height: 120px; object-fit: cover;">
                            <?php else: ?>
                                <div class="img-profile rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                     style="width: 120px; height: 120px;">
                                    <i class="fas fa-user fa-3x text-white"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> <?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?></p>
                                    <p><strong>Staff ID:</strong> <?= htmlspecialchars($staff['staff_id']) ?></p>
                                    <p><strong>Position:</strong> <?= htmlspecialchars($staff['position']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($staff['email']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Phone:</strong> <?= htmlspecialchars($staff['phone'] ?: 'Not provided') ?></p>
                                    <p><strong>Gender:</strong> <?= htmlspecialchars(ucfirst($staff['gender'] ?: 'Not specified')) ?></p>
                                    <p><strong>Hire Date:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($staff['hire_date']))) ?></p>
                                    <p><strong>Address:</strong> <?= htmlspecialchars($staff['address'] ?: 'Not provided') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- QR Code Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Staff QR Code</h6>
                </div>
                <div class="card-body text-center">
                    <div class="qr-code-container mb-3">
                        <div id="staffQrCode" class="qr-code-display"></div>
                        <p class="text-muted mt-2">Scan this QR code for staff access</p>
                    </div>
                    <div class="qr-actions">
                        <button class="btn btn-primary btn-sm me-2" id="downloadQrBtn">
                            <i class="fas fa-download me-1"></i>Download
                        </button>
                        <button class="btn btn-outline-primary btn-sm" id="printQrBtn">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .qr-code-container {
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 2px dashed #dee2e6;
    }
    
    .qr-code-display {
        width: 200px;
        height: 200px;
        margin: 0 auto;
        background: white;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .qr-actions {
        border-top: 1px solid #dee2e6;
        padding-top: 15px;
    }
    
    .img-profile {
        border: 3px solid #e3e6f0;
    }
</style>

<script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Generate QR Code for staff
        new QRCode(document.getElementById("staffQrCode"), {
            text: "<?= htmlspecialchars($staff['qr_code_data'] ?: 'FIT_TRACK_STAFF_ID:' . $staff['staff_id']) ?>",
            width: 180,
            height: 180,
            colorDark: "#000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        // Download QR Code
        document.getElementById('downloadQrBtn').addEventListener('click', function() {
            const canvas = document.querySelector('#staffQrCode canvas');
            const dataURL = canvas.toDataURL('image/png');
            
            const link = document.createElement('a');
            link.download = 'FIT_TRACK_Staff_ID_<?= htmlspecialchars($staff['staff_id']) ?>.png';
            link.href = dataURL;
            link.click();
        });
        
        // Print QR Code
        document.getElementById('printQrBtn').addEventListener('click', function() {
            const printBtn = this;
            const originalText = printBtn.innerHTML;
            printBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Preparing...';
            printBtn.disabled = true;
            
            setTimeout(() => {
                printBtn.innerHTML = '<i class="fas fa-check me-1"></i>Ready to Print';
                setTimeout(() => {
                    printBtn.innerHTML = originalText;
                    printBtn.disabled = false;
                }, 2000);
            }, 1500);
        });
    });
</script>

<?php include 'components/footer.php'; ?>
