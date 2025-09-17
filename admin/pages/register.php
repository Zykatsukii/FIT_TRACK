<?php
$page_title = "Registration";
include 'components/header.php';
include '../includes/db.php';

// Database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$errors = [];
$success = '';
$activeForm = isset($_GET['form']) ? $_GET['form'] : 'member'; // Default to member form

// Debug: Check what form is active
error_log("Active form: " . $activeForm);
error_log("GET form parameter: " . ($_GET['form'] ?? 'not set'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'member') {
        // Member registration processing
        $firstName = trim($_POST['mFirstName'] ?? '');
        $lastName = trim($_POST['mLastName'] ?? '');
        $email = trim($_POST['mEmail'] ?? '');
        $phone = trim($_POST['mPhone'] ?? '');
        $password = $_POST['mPassword'] ?? '';
        $confirmPassword = $_POST['mConfirmPassword'] ?? '';
        $membershipType = $_POST['mMembershipType'] ?? '';
        $membershipDuration = $_POST['mMembershipDuration'] ?? null;
        $joinDate = $_POST['mJoinDate'] ?? date('Y-m-d');
        $address = trim($_POST['mAddress'] ?? '');
        $gender = $_POST['mGender'] ?? '';
        $withTrainees = $_POST['mWithTrainees'] ?? '';

        // Validation
        if (empty($firstName)) $errors['mFirstName'] = 'First name is required.';
        if (empty($lastName)) $errors['mLastName'] = 'Last name is required.';
        if (empty($email)) {
            $errors['mEmail'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['mEmail'] = 'Invalid email format.';
        }
        if (!empty($phone) && !preg_match('/^[0-9+\-\s]*$/', $phone)) {
            $errors['mPhone'] = 'Invalid phone number.';
        }
        if (strlen($password) < 8) {
            $errors['mPassword'] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirmPassword) {
            $errors['mConfirmPassword'] = 'Passwords do not match.';
        }
        if (!in_array($membershipType, ['regular', 'student'])) {
            $errors['mMembershipType'] = 'Please select a valid membership type.';
        }
        
        // Validate duration for regular membership
        if (empty($membershipDuration)) {
            $errors['mMembershipDuration'] = 'Please select membership duration.';
        } elseif (!in_array($membershipDuration, ['1', '3', '6', '12'])) {
            $errors['mMembershipDuration'] = 'Please select a valid duration.';
        }
        
        // Validate trainees option
        if (!in_array($withTrainees, ['with', 'without'])) {
            $errors['mWithTrainees'] = 'Please select trainees option.';
        }

        // Handle photo upload
        $photoPath = null;
        if (isset($_FILES['mPhoto']) && $_FILES['mPhoto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if ($_FILES['mPhoto']['error'] === UPLOAD_ERR_OK) {
                if (in_array($_FILES['mPhoto']['type'], $allowedTypes)) {
                    $uploadDir = '../uploads/member_photos/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileName = uniqid() . '_' . basename($_FILES['mPhoto']['name']);
                    $targetFile = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['mPhoto']['tmp_name'], $targetFile)) {
                        $photoPath = $fileName;
                    } else {
                        $errors['mPhoto'] = 'Failed to upload photo.';
                    }
                } else {
                    $errors['mPhoto'] = 'Invalid photo file type. Only JPG, PNG, GIF allowed.';
                }
            } else {
                $errors['mPhoto'] = 'Error uploading photo.';
            }
        }

        // If no errors, insert into DB
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $memberId = 'MEM-' . date('Y') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                
                // Generate QR code data
                $qrCodeData = "FIT_TRACK_MEMBER_ID:" . $memberId;
                
                // Calculate expired date
                $expiredDate = null;
                if ($membershipType === 'session') {
                    $expiredDate = date('Y-m-d', strtotime($joinDate . ' +1 day'));
                } elseif ($membershipType === 'regular' && $membershipDuration) {
                    $expiredDate = date('Y-m-d', strtotime($joinDate . ' +' . $membershipDuration . ' months'));
                } else {
                    $expiredDate = date('Y-m-d', strtotime($joinDate . ' +30 days'));
                }
                
                // Calculate payroll based on membership type and duration
                $payroll = 0;
                if ($membershipType === 'regular') {
                    switch ($membershipDuration) {
                        case '1':
                            $payroll = 1000; // 1 month - ₱1,000
                            break;
                        case '3':
                            $payroll = 2700; // 3 months - ₱2,700 (₱900/month)
                            break;
                        case '6':
                            $payroll = 4800; // 6 months - ₱4,800 (₱800/month)
                            break;
                        case '12':
                            $payroll = 8400; // 1 year - ₱8,400 (₱700/month)
                            break;
                        default:
                            $payroll = 1000;
                    }
                } elseif ($membershipType === 'student') {
                    switch ($membershipDuration) {
                        case '1':
                            $payroll = 700; // 1 month - ₱700
                            break;
                        case '3':
                            $payroll = 1800; // 3 months - ₱1,800 (₱600/month)
                            break;
                        case '6':
                            $payroll = 3000; // 6 months - ₱3,000 (₱500/month)
                            break;
                        case '12':
                            $payroll = 4800; // 1 year - ₱4,800 (₱400/month)
                            break;
                        default:
                            $payroll = 700;
                    }
                }
                
                // Add trainees fee if selected
                if ($withTrainees === 'with') {
                    $payroll += 500; // Additional ₱500 for trainees
                }
                
                $stmt = $pdo->prepare("INSERT INTO members 
                    (member_id, first_name, last_name, email, phone, password, membership_type, 
                    membership_duration, join_date, expired_date, address, photo, gender, with_trainees, qr_code_data) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $memberId,
                    $firstName,
                    $lastName,
                    $email,
                    $phone ?: null,
                    $hashedPassword,
                    $membershipType,
                    $membershipDuration,
                    $joinDate,
                    $expiredDate,
                    $address ?: null,
                    $photoPath,
                    $gender,
                    $withTrainees,
                    $qrCodeData
                ]);
                
                $memberDbId = $pdo->lastInsertId();
                
                // Insert into member_payroll table
                if ($payroll > 0) {
                    $stmt = $pdo->prepare("INSERT INTO member_payroll 
                        (member_id, membership_type, amount, payment_date, status) 
                        VALUES (?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $memberDbId,
                        $membershipType,
                        $payroll,
                        $joinDate,
                        'pending'
                    ]);
                }
                
                $pdo->commit();
                
                $success = "Member registered successfully! Member ID: $memberId - QR Code has been generated and stored. Payroll amount: ₱$payroll";
                $newMemberId = $memberDbId;
                $_POST = []; // Clear form
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                if ($e->errorInfo[1] == 1062) {
                    $errors['mEmail'] = 'Email already registered.';
                } else {
                    die("Database error: " . $e->getMessage());
                }
            }
        }
    } elseif (isset($_POST['form_type']) && $_POST['form_type'] === 'staff') {
        // Staff registration with payroll processing
        $firstName = trim($_POST['sFirstName'] ?? '');
        $lastName = trim($_POST['sLastName'] ?? '');
        $email = trim($_POST['sEmail'] ?? '');
        $phone = trim($_POST['sPhone'] ?? '');
        $password = $_POST['sPassword'] ?? '';
        $confirmPassword = $_POST['sConfirmPassword'] ?? '';
        $position = $_POST['sPosition'] ?? '';
        $hireDate = $_POST['sHireDate'] ?? date('Y-m-d');
        $address = trim($_POST['sAddress'] ?? '');
        $gender = $_POST['sGender'] ?? '';
        $salary = trim($_POST['sSalary'] ?? '');
        $bankName = trim($_POST['sBankName'] ?? '');
        $accountNumber = trim($_POST['sAccountNumber'] ?? '');
        $taxId = trim($_POST['sTaxId'] ?? '');
        $employmentType = $_POST['sEmploymentType'] ?? 'full-time';

        // Validation
        if (empty($firstName)) $errors['sFirstName'] = 'First name is required.';
        if (empty($lastName)) $errors['sLastName'] = 'Last name is required.';
        if (empty($email)) {
            $errors['sEmail'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['sEmail'] = 'Invalid email format.';
        }
        if (!empty($phone) && !preg_match('/^[0-9+\-\s]*$/', $phone)) {
            $errors['sPhone'] = 'Invalid phone number.';
        }
        if (strlen($password) < 8) {
            $errors['sPassword'] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirmPassword) {
            $errors['sConfirmPassword'] = 'Passwords do not match.';
        }
        if (empty($position)) {
            $errors['sPosition'] = 'Position is required.';
        }
        if (!empty($salary) && !is_numeric($salary)) {
            $errors['sSalary'] = 'Salary must be a valid number.';
        }

        // Handle photo upload
        $photoPath = null;
        if (isset($_FILES['sPhoto']) && $_FILES['sPhoto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if ($_FILES['sPhoto']['error'] === UPLOAD_ERR_OK) {
                if (in_array($_FILES['sPhoto']['type'], $allowedTypes)) {
                    $uploadDir = '../uploads/staff_photos/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileName = uniqid() . '_' . basename($_FILES['sPhoto']['name']);
                    $targetFile = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['sPhoto']['tmp_name'], $targetFile)) {
                        $photoPath = $fileName;
                    } else {
                        $errors['sPhoto'] = 'Failed to upload photo.';
                    }
                } else {
                    $errors['sPhoto'] = 'Invalid photo file type. Only JPG, PNG, GIF allowed.';
                }
            } else {
                $errors['sPhoto'] = 'Error uploading photo.';
            }
        }

        // If no errors, insert into DB
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Generate staff ID
                $staffId = 'STAFF-' . date('Y') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Generate QR code data
                $qrCodeData = "FIT_TRACK_STAFF_ID:" . $staffId;
                
                // Insert into staff table
                $stmt = $pdo->prepare("INSERT INTO staff 
                    (staff_id, first_name, last_name, email, phone, password, 
                    position, hire_date, address, photo, gender, qr_code_data) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $staffId,
                    $firstName,
                    $lastName,
                    $email,
                    $phone ?: null,
                    $hashedPassword,
                    $position,
                    $hireDate,
                    $address ?: null,
                    $photoPath,
                    $gender,
                    $qrCodeData
                ]);
                
                $staffDbId = $pdo->lastInsertId();
                
                // Insert into payroll table
                if (!empty($salary)) {
                    $stmt = $pdo->prepare("INSERT INTO payroll 
                        (staff_id, salary, bank_name, account_number, tax_id, employment_type) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $staffDbId,
                        $salary,
                        $bankName ?: null,
                        $accountNumber ?: null,
                        $taxId ?: null,
                        $employmentType
                    ]);
                }
                
                $pdo->commit();
                
                $success = "Staff registered successfully! Staff ID: $staffId - QR Code has been generated and stored.";
                $activeForm = 'staff';
                $_POST = []; // Clear form
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                if ($e->errorInfo[1] == 1062) {
                    $errors['sEmail'] = 'Email already registered.';
                } else {
                    die("Database error: " . $e->getMessage());
                }
            }
        } else {
            $activeForm = 'staff';
        }
    }
    
    // Ensure activeForm is set correctly after form submission
    if (isset($_POST['form_type'])) {
        $activeForm = $_POST['form_type'];
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Registration Panel</h1>
        <div class="btn-group" role="group">
            <a href="register.php?form=member" class="btn btn-<?= $activeForm === 'member' ? 'primary' : 'outline-primary' ?>">
                <i class="fas fa-user me-2"></i>Member Registration
            </a>
            <a href="register.php?form=staff" class="btn btn-<?= $activeForm === 'staff' ? 'primary' : 'outline-primary' ?>">
                <i class="fas fa-user-tie me-2"></i>Staff Registration
            </a>
        </div>
    </div>
    


    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php if (isset($memberId)): ?>
            <!-- QR Code Display Section for Members -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>Member QR Code Generated</h5>
                </div>
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="qr-code-container mb-3">
                                <div id="memberQrCode" class="qr-code-display"></div>
                                <p class="text-muted mt-2">Scan this QR code for gym access</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="member-info">
                                <h6>Member Details:</h6>
                                <p><strong>Name:</strong> <?= htmlspecialchars($firstName . ' ' . $lastName) ?></p>
                                <p><strong>Member ID:</strong> <?= htmlspecialchars($memberId) ?></p>
                                <p><strong>Membership:</strong> <?= htmlspecialchars(ucfirst($membershipType)) ?></p>
                                <p><strong>Join Date:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($joinDate))) ?></p>
                                <hr>
                                <div class="payroll-info">
                                    <h6 class="text-success"><i class="fas fa-money-bill-wave me-2"></i>Payroll Information</h6>
                                    <p><strong>Membership Fee:</strong> <span class="text-success fw-bold">₱<?= $payroll ?></span></p>
                                    <p><strong>Status:</strong> <span class="badge bg-warning">Pending Payment</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="qr-actions mt-3">
                        <button class="btn btn-primary me-2" id="downloadQrBtn">
                            <i class="fas fa-download me-2"></i>Download QR Code
                        </button>
                        <button class="btn btn-outline-primary me-2" id="printQrBtn">
                            <i class="fas fa-print me-2"></i>Print Membership Card
                        </button>
                        <button class="btn btn-success" id="emailQrBtn">
                            <i class="fas fa-envelope me-2"></i>Email to Member
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($staffId)): ?>
            <!-- QR Code Display Section for Staff -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>Staff QR Code Generated</h5>
                </div>
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="qr-code-container mb-3">
                                <div id="staffQrCode" class="qr-code-display"></div>
                                <p class="text-muted mt-2">Scan this QR code for staff access</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="staff-info">
                                <h6>Staff Details:</h6>
                                <p><strong>Name:</strong> <?= htmlspecialchars($firstName . ' ' . $lastName) ?></p>
                                <p><strong>Staff ID:</strong> <?= htmlspecialchars($staffId) ?></p>
                                <p><strong>Position:</strong> <?= htmlspecialchars($position) ?></p>
                                <p><strong>Hire Date:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($hireDate))) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="qr-actions mt-3">
                        <button class="btn btn-primary me-2" id="downloadStaffQrBtn">
                            <i class="fas fa-download me-2"></i>Download QR Code
                        </button>
                        <button class="btn btn-outline-primary me-2" id="printStaffQrBtn">
                            <i class="fas fa-print me-2"></i>Print Staff ID Card
                        </button>
                        <button class="btn btn-success" id="emailStaffQrBtn">
                            <i class="fas fa-envelope me-2"></i>Email to Staff
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <?php if ($activeForm === 'member'): ?>
                <!-- Member Registration Form -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Member Registration Form</h5>
                    </div>
                    <div class="card-body">
                        <form id="memberForm" method="POST" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="form_type" value="member">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="mFirstName" class="form-label">First Name *</label>
                                    <input type="text" class="form-control <?= isset($errors['mFirstName']) ? 'is-invalid' : '' ?>" 
                                        name="mFirstName" id="mFirstName" 
                                        value="<?= htmlspecialchars($_POST['mFirstName'] ?? '') ?>" required>
                                    <div class="invalid-feedback"><?= $errors['mFirstName'] ?? '' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="mLastName" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control <?= isset($errors['mLastName']) ? 'is-invalid' : '' ?>" 
                                        name="mLastName" id="mLastName" 
                                        value="<?= htmlspecialchars($_POST['mLastName'] ?? '') ?>" required>
                                    <div class="invalid-feedback"><?= $errors['mLastName'] ?? '' ?></div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="mEmail" class="form-label">Email *</label>
                                    <input type="email" class="form-control <?= isset($errors['mEmail']) ? 'is-invalid' : '' ?>" 
                                        name="mEmail" id="mEmail" 
                                        value="<?= htmlspecialchars($_POST['mEmail'] ?? '') ?>" required>
                                    <div class="invalid-feedback"><?= $errors['mEmail'] ?? '' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="mPhone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control <?= isset($errors['mPhone']) ? 'is-invalid' : '' ?>" 
                                        name="mPhone" id="mPhone" 
                                        value="<?= htmlspecialchars($_POST['mPhone'] ?? '') ?>">
                                    <div class="invalid-feedback"><?= $errors['mPhone'] ?? '' ?></div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="mPassword" class="form-label">Password *</label>
                                    <input type="password" class="form-control <?= isset($errors['mPassword']) ? 'is-invalid' : '' ?>" 
                                        name="mPassword" id="mPassword" required>
                                    <div class="invalid-feedback"><?= $errors['mPassword'] ?? '' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="mConfirmPassword" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control <?= isset($errors['mConfirmPassword']) ? 'is-invalid' : '' ?>" 
                                        name="mConfirmPassword" id="mConfirmPassword" required>
                                    <div class="invalid-feedback"><?= $errors['mConfirmPassword'] ?? '' ?></div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="mMembershipType" class="form-label">Membership Type *</label>
                                    <select class="form-select <?= isset($errors['mMembershipType']) ? 'is-invalid' : '' ?>" 
                                        name="mMembershipType" id="mMembershipType" required>
                                        <option value="">Select Type</option>
                                        <option value="regular" <?= (($_POST['mMembershipType'] ?? '') === 'regular') ? 'selected' : '' ?>>Regular Membership</option>
                                        <option value="student" <?= (($_POST['mMembershipType'] ?? '') === 'student') ? 'selected' : '' ?>>Student Membership</option>
                                    </select>
                                    <div class="invalid-feedback"><?= $errors['mMembershipType'] ?? '' ?></div>
                                </div>
                                <div class="col-md-4">
                                    <label for="mMembershipDuration" class="form-label">Duration *</label>
                                    <select class="form-select <?= isset($errors['mMembershipDuration']) ? 'is-invalid' : '' ?>" 
                                        name="mMembershipDuration" id="mMembershipDuration" required>
                                        <option value="">Select Duration</option>
                                        <option value="1" <?= (($_POST['mMembershipDuration'] ?? '') === '1') ? 'selected' : '' ?>>1 Month</option>
                                        <option value="3" <?= (($_POST['mMembershipDuration'] ?? '') === '3') ? 'selected' : '' ?>>3 Months</option>
                                        <option value="6" <?= (($_POST['mMembershipDuration'] ?? '') === '6') ? 'selected' : '' ?>>6 Months</option>
                                        <option value="12" <?= (($_POST['mMembershipDuration'] ?? '') === '12') ? 'selected' : '' ?>>1 Year</option>
                                    </select>
                                    <div class="invalid-feedback"><?= $errors['mMembershipDuration'] ?? '' ?></div>
                                </div>
                                <div class="col-md-3">
                                    <label for="mJoinDate" class="form-label">Join Date</label>
                                    <input type="date" class="form-control" 
                                        name="mJoinDate" id="mJoinDate" 
                                        value="<?= htmlspecialchars($_POST['mJoinDate'] ?? date('Y-m-d')) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="mGender" class="form-label">Gender</label>
                                    <select class="form-select" name="mGender" id="mGender">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?= (($_POST['mGender'] ?? '') === 'male') ? 'selected' : '' ?>>Male</option>
                                        <option value="female" <?= (($_POST['mGender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
                                        <option value="other" <?= (($_POST['mGender'] ?? '') === 'other') ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Payroll Information Section -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h6 class="alert-heading"><i class="fas fa-money-bill-wave me-2"></i>Membership Fees</h6>
                                        <div id="pricingDisplay">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Regular Membership:</strong>
                                                    <ul class="list-unstyled mt-2">
                                                        <li>1 Month: ₱1,000</li>
                                                        <li>3 Months: ₱2,700 (₱900/month)</li>
                                                        <li>6 Months: ₱4,800 (₱800/month)</li>
                                                        <li>1 Year: ₱8,400 (₱700/month)</li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Student Membership:</strong>
                                                    <ul class="list-unstyled mt-2">
                                                        <li>1 Month: ₱700</li>
                                                        <li>3 Months: ₱1,800 (₱600/month)</li>
                                                        <li>6 Months: ₱3,000 (₱500/month)</li>
                                                        <li>1 Year: ₱4,800 (₱400/month)</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="selectedPrice" class="mt-3 p-2 bg-light rounded" style="display: none;">
                                            <strong>Selected Plan:</strong> <span id="priceAmount"></span>
                                            <div id="traineesFee" class="mt-2" style="display: none;">
                                                <strong>Trainees Fee:</strong> <span class="text-warning fw-bold">+₱500</span>
                                            </div>
                                            <div id="totalPrice" class="mt-2" style="display: none;">
                                                <strong>Total Amount:</strong> <span id="totalAmount" class="text-success fw-bold"></span>
                                            </div>
                                        </div>
                                        <small class="text-muted">Fees will be automatically calculated based on the selected membership type, duration, and trainees option.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="mWithTrainees" class="form-label">Trainees Option *</label>
                                    <select class="form-select <?= isset($errors['mWithTrainees']) ? 'is-invalid' : '' ?>" 
                                        name="mWithTrainees" id="mWithTrainees" required>
                                        <option value="">Select Option</option>
                                        <option value="with" <?= (($_POST['mWithTrainees'] ?? '') === 'with') ? 'selected' : '' ?>>With Trainees</option>
                                        <option value="without" <?= (($_POST['mWithTrainees'] ?? '') === 'without') ? 'selected' : '' ?>>Without Trainees</option>
                                    </select>
                                    <div class="invalid-feedback"><?= $errors['mWithTrainees'] ?? '' ?></div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="mPhoto" class="form-label">Photo</label>
                                    <input class="form-control <?= isset($errors['mPhoto']) ? 'is-invalid' : '' ?>" 
                                        type="file" name="mPhoto" id="mPhoto" accept="image/*">
                                    <div class="invalid-feedback"><?= $errors['mPhoto'] ?? '' ?></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="mAddress" class="form-label">Address</label>
                                <textarea class="form-control" name="mAddress" id="mAddress" rows="2"><?= htmlspecialchars($_POST['mAddress'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Register Member</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Staff Registration Form -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Staff Registration Form</h5>
                    </div>
                    <div class="card-body">
                        <form id="staffForm" method="POST" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="form_type" value="staff">
                            
                            <h5 class="mb-4"><i class="fas fa-user-tie me-2"></i>Staff Information</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="sFirstName" class="form-label">First Name *</label>
                                    <input type="text" class="form-control <?= isset($errors['sFirstName']) ? 'is-invalid' : '' ?>" 
                                        name="sFirstName" id="sFirstName" 
                                        value="<?= htmlspecialchars($_POST['sFirstName'] ?? '') ?>" required>
                                    <div class="invalid-feedback"><?= $errors['sFirstName'] ?? '' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="sLastName" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control <?= isset($errors['sLastName']) ? 'is-invalid' : '' ?>" 
                                        name="sLastName" id="sLastName" 
                                        value="<?= htmlspecialchars($_POST['sLastName'] ?? '') ?>" required>
                                    <div class="invalid-feedback"><?= $errors['sLastName'] ?? '' ?></div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="sEmail" class="form-label">Email *</label>
                                    <input type="email" class="form-control <?= isset($errors['sEmail']) ? 'is-invalid' : '' ?>" 
                                        name="sEmail" id="sEmail" 
                                        value="<?= htmlspecialchars($_POST['sEmail'] ?? '') ?>" required>
                                    <div class="invalid-feedback"><?= $errors['sEmail'] ?? '' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="sPhone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control <?= isset($errors['sPhone']) ? 'is-invalid' : '' ?>" 
                                        name="sPhone" id="sPhone" 
                                        value="<?= htmlspecialchars($_POST['sPhone'] ?? '') ?>">
                                    <div class="invalid-feedback"><?= $errors['sPhone'] ?? '' ?></div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="sPassword" class="form-label">Password *</label>
                                    <input type="password" class="form-control <?= isset($errors['sPassword']) ? 'is-invalid' : '' ?>" 
                                        name="sPassword" id="sPassword" required>
                                    <div class="invalid-feedback"><?= $errors['sPassword'] ?? '' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="sConfirmPassword" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control <?= isset($errors['sConfirmPassword']) ? 'is-invalid' : '' ?>" 
                                        name="sConfirmPassword" id="sConfirmPassword" required>
                                    <div class="invalid-feedback"><?= $errors['sConfirmPassword'] ?? '' ?></div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="sPosition" class="form-label">Position *</label>
                                    <input type="text" class="form-control <?= isset($errors['sPosition']) ? 'is-invalid' : '' ?>" 
                                        name="sPosition" id="sPosition" 
                                        value="<?= htmlspecialchars($_POST['sPosition'] ?? '') ?>" required>
                                    <div class="invalid-feedback"><?= $errors['sPosition'] ?? '' ?></div>
                                </div>
                                <div class="col-md-4">
                                    <label for="sHireDate" class="form-label">Hire Date</label>
                                    <input type="date" class="form-control" 
                                        name="sHireDate" id="sHireDate" 
                                        value="<?= htmlspecialchars($_POST['sHireDate'] ?? date('Y-m-d')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="sGender" class="form-label">Gender</label>
                                    <select class="form-select" name="sGender" id="sGender">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?= (($_POST['sGender'] ?? '') === 'male') ? 'selected' : '' ?>>Male</option>
                                        <option value="female" <?= (($_POST['sGender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
                                        <option value="other" <?= (($_POST['sGender'] ?? '') === 'other') ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="sPhoto" class="form-label">Photo</label>
                                    <input class="form-control <?= isset($errors['sPhoto']) ? 'is-invalid' : '' ?>" 
                                        type="file" name="sPhoto" id="sPhoto" accept="image/*">
                                    <div class="invalid-feedback"><?= $errors['sPhoto'] ?? '' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="sEmploymentType" class="form-label">Employment Type</label>
                                    <select class="form-select" name="sEmploymentType" id="sEmploymentType">
                                        <option value="full-time" <?= (($_POST['sEmploymentType'] ?? '') === 'full-time') ? 'selected' : '' ?>>Full-time</option>
                                        <option value="part-time" <?= (($_POST['sEmploymentType'] ?? '') === 'part-time') ? 'selected' : '' ?>>Part-time</option>
                                        <option value="contract" <?= (($_POST['sEmploymentType'] ?? '') === 'contract') ? 'selected' : '' ?>>Contract</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="sAddress" class="form-label">Address</label>
                                <textarea class="form-control" name="sAddress" id="sAddress" rows="2"><?= htmlspecialchars($_POST['sAddress'] ?? '') ?></textarea>
                            </div>

                            <hr class="my-4">
                            <h5 class="mb-4"><i class="fas fa-money-bill-wave me-2"></i>Payroll Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="sSalary" class="form-label">Monthly Salary (₱)</label>
                                    <input type="number" class="form-control <?= isset($errors['sSalary']) ? 'is-invalid' : '' ?>" 
                                        name="sSalary" id="sSalary" 
                                        value="<?= htmlspecialchars($_POST['sSalary'] ?? '') ?>" step="0.01">
                                    <div class="invalid-feedback"><?= $errors['sSalary'] ?? '' ?></div>
                                </div>
                                <div class="col-md-4">
                                    <label for="sBankName" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control" 
                                        name="sBankName" id="sBankName" 
                                        value="<?= htmlspecialchars($_POST['sBankName'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="sAccountNumber" class="form-label">Account Number</label>
                                    <input type="text" class="form-control" 
                                        name="sAccountNumber" id="sAccountNumber" 
                                        value="<?= htmlspecialchars($_POST['sAccountNumber'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="sTaxId" class="form-label">Tax Identification Number</label>
                                    <input type="text" class="form-control" 
                                        name="sTaxId" id="sTaxId" 
                                        value="<?= htmlspecialchars($_POST['sTaxId'] ?? '') ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Register Staff</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- QR Code Styles -->
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
    
    .member-info, .staff-info {
        text-align: left;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 1px solid #dee2e6;
    }
    
    .member-info h6, .staff-info h6 {
        color: #495057;
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .member-info p, .staff-info p {
        margin-bottom: 8px;
        color: #6c757d;
    }
    
    .member-info strong, .staff-info strong {
        color: #495057;
    }
    
    .qr-actions {
        border-top: 1px solid #dee2e6;
        padding-top: 20px;
    }
    
    .qr-actions .btn {
        margin: 5px;
    }
</style>

<!-- QR Code Script -->
<script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Debug: Log the current form
        console.log('Current active form:', '<?= $activeForm ?>');
        console.log('URL form parameter:', '<?= $_GET['form'] ?? 'none' ?>');
        
        // Duration field is always required for regular memberships
        
        // Dynamic payroll display based on membership type and duration
        const membershipTypeSelect = document.getElementById('mMembershipType');
        const membershipDurationSelect = document.getElementById('mMembershipDuration');
        const withTraineesSelect = document.getElementById('mWithTrainees');
        const selectedPriceDiv = document.getElementById('selectedPrice');
        const priceAmountSpan = document.getElementById('priceAmount');
        const traineesFeeDiv = document.getElementById('traineesFee');
        const totalPriceDiv = document.getElementById('totalPrice');
        const totalAmountSpan = document.getElementById('totalAmount');
        
        const pricingData = {
            regular: {
                '1': 1000,
                '3': 2700,
                '6': 4800,
                '12': 8400
            },
            student: {
                '1': 700,
                '3': 1800,
                '6': 3000,
                '12': 4800
            }
        };
        
        function updatePriceDisplay() {
            const selectedType = membershipTypeSelect.value;
            const selectedDuration = membershipDurationSelect.value;
            const selectedTrainees = withTraineesSelect.value;
            
            if (selectedType && selectedDuration && pricingData[selectedType] && pricingData[selectedType][selectedDuration]) {
                const basePrice = pricingData[selectedType][selectedDuration];
                const traineesFee = selectedTrainees === 'with' ? 500 : 0;
                const totalPrice = basePrice + traineesFee;
                
                const durationText = selectedDuration === '1' ? '1 Month' : 
                                   selectedDuration === '3' ? '3 Months' : 
                                   selectedDuration === '6' ? '6 Months' : '1 Year';
                const typeText = selectedType === 'regular' ? 'Regular' : 'Student';
                
                priceAmountSpan.innerHTML = `${typeText} Membership - ${durationText}: <span class="text-success fw-bold">₱${basePrice.toLocaleString()}</span>`;
                
                // Show/hide trainees fee
                if (selectedTrainees === 'with') {
                    traineesFeeDiv.style.display = 'block';
                    totalPriceDiv.style.display = 'block';
                    totalAmountSpan.innerHTML = `₱${totalPrice.toLocaleString()}`;
                } else {
                    traineesFeeDiv.style.display = 'none';
                    totalPriceDiv.style.display = 'none';
                }
                
                selectedPriceDiv.style.display = 'block';
            } else {
                selectedPriceDiv.style.display = 'none';
            }
        }
        
        if (membershipTypeSelect && membershipDurationSelect && withTraineesSelect) {
            membershipTypeSelect.addEventListener('change', updatePriceDisplay);
            membershipDurationSelect.addEventListener('change', updatePriceDisplay);
            withTraineesSelect.addEventListener('change', updatePriceDisplay);
        }
        
        <?php if (isset($success) && isset($memberId)): ?>
        // Generate QR Code for the newly registered member
        new QRCode(document.getElementById("memberQrCode"), {
            text: "FIT_TRACK_MEMBER_ID:<?= htmlspecialchars($memberId) ?>",
            width: 180,
            height: 180,
            colorDark: "#000000ff",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        // Download QR Code
        document.getElementById('downloadQrBtn').addEventListener('click', function() {
            const canvas = document.querySelector('#memberQrCode canvas');
            const dataURL = canvas.toDataURL('image/png');
            
            const link = document.createElement('a');
            link.download = 'FIT_TRACK_Membership_<?= htmlspecialchars($memberId) ?>.png';
            link.href = dataURL;
            link.click();
        });
        
        // Print QR Code
        document.getElementById('printQrBtn').addEventListener('click', function() {
            const printBtn = this;
            const originalText = printBtn.innerHTML;
            printBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing...';
            printBtn.disabled = true;
            
            setTimeout(() => {
                printBtn.innerHTML = '<i class="fas fa-check"></i> Ready to Print';
                setTimeout(() => {
                    printBtn.innerHTML = originalText;
                    printBtn.disabled = false;
                }, 2000);
            }, 1500);
        });
        
        // Email QR Code (placeholder functionality)
        document.getElementById('emailQrBtn').addEventListener('click', function() {
            const emailBtn = this;
            const originalText = emailBtn.innerHTML;
            emailBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            emailBtn.disabled = true;
            
            setTimeout(() => {
                emailBtn.innerHTML = '<i class="fas fa-check"></i> Email Sent!';
                setTimeout(() => {
                    emailBtn.innerHTML = originalText;
                    emailBtn.disabled = false;
                }, 2000);
            }, 1500);
        });
        <?php endif; ?>
        
        <?php if (isset($success) && isset($staffId)): ?>
        // Generate QR Code for the newly registered staff
        new QRCode(document.getElementById("staffQrCode"), {
            text: "FIT_TRACK_STAFF_ID:<?= htmlspecialchars($staffId) ?>",
            width: 180,
            height: 180,
            colorDark: "#000000ff",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        // Download Staff QR Code
        document.getElementById('downloadStaffQrBtn').addEventListener('click', function() {
            const canvas = document.querySelector('#staffQrCode canvas');
            const dataURL = canvas.toDataURL('image/png');
            
            const link = document.createElement('a');
            link.download = 'FIT_TRACK_Staff_ID_<?= htmlspecialchars($staffId) ?>.png';
            link.href = dataURL;
            link.click();
        });
        
        // Print Staff QR Code
        document.getElementById('printStaffQrBtn').addEventListener('click', function() {
            const printBtn = this;
            const originalText = printBtn.innerHTML;
            printBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing...';
            printBtn.disabled = true;
            
            setTimeout(() => {
                printBtn.innerHTML = '<i class="fas fa-check"></i> Ready to Print';
                setTimeout(() => {
                    printBtn.innerHTML = originalText;
                    printBtn.disabled = false;
                }, 2000);
            }, 1500);
        });
        
        // Email Staff QR Code (placeholder functionality)
        document.getElementById('emailStaffQrBtn').addEventListener('click', function() {
            const emailBtn = this;
            const originalText = emailBtn.innerHTML;
            emailBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            emailBtn.disabled = true;
            
            setTimeout(() => {
                emailBtn.innerHTML = '<i class="fas fa-check"></i> Email Sent!';
                setTimeout(() => {
                    emailBtn.innerHTML = originalText;
                    emailBtn.disabled = false;
                }, 2000);
            }, 1500);
        });
        <?php endif; ?>
    });
</script>

<?php include 'components/footer.php'; ?>