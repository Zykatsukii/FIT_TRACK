<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Staff Management";
include 'components/header.php';
include '../includes/db.php';

// Database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Query: All Staff
$allStaff = $pdo->query("
    SELECT 
        s.staff_id, s.first_name, s.last_name, s.email, s.phone, s.position, s.hire_date,
        s.address, s.gender, s.photo, s.qr_code_data,
        p.salary, p.employment_type, p.bank_name, p.account_number
    FROM staff s
    LEFT JOIN payroll p ON s.id = p.staff_id
    ORDER BY s.hire_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Query: Active Staff (employed within last 2 years)
$activeStaff = $pdo->query("
    SELECT 
        s.staff_id, s.first_name, s.last_name, s.email, s.phone, s.position, s.hire_date,
        s.address, s.gender, s.photo, s.qr_code_data,
        p.salary, p.employment_type, p.bank_name, p.account_number
    FROM staff s
    LEFT JOIN payroll p ON s.id = p.staff_id
    WHERE s.hire_date >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
    ORDER BY s.hire_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Query: Long-term Staff (employed for more than 2 years)
$longTermStaff = $pdo->query("
    SELECT 
        s.staff_id, s.first_name, s.last_name, s.email, s.phone, s.position, s.hire_date,
        s.address, s.gender, s.photo, s.qr_code_data,
        p.salary, p.employment_type, p.bank_name, p.account_number
    FROM staff s
    LEFT JOIN payroll p ON s.id = p.staff_id
    WHERE s.hire_date < DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
    ORDER BY s.hire_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Function to render table rows
function renderStaffRows($staff) {
    if (!$staff) {
        return "<tr><td colspan='8' class='text-center text-muted'>No staff found.</td></tr>";
    }
    $rows = "";
    foreach ($staff as $s) {
        $badge = match(strtolower($s['employment_type'] ?? 'full-time')) {
            'full-time' => 'primary',
            'part-time' => 'success',
            'contract'  => 'warning',
            default     => 'secondary'
        };
        $fullName = htmlspecialchars($s['first_name'] . ' ' . $s['last_name']);
        $staffId = htmlspecialchars($s['staff_id']);
        $email = htmlspecialchars($s['email']);
        $phone = htmlspecialchars($s['phone']);
        $position = htmlspecialchars($s['position']);
        $hireDate = htmlspecialchars($s['hire_date']);
        $employmentType = htmlspecialchars(ucfirst($s['employment_type'] ?? 'Full-time'));
        $salary = $s['salary'] ? '₱' . number_format($s['salary'], 2) : '-';
        
        $rows .= "
        <tr data-staff-id='{$staffId}'>
            <td>{$staffId}</td>
            <td>{$fullName}</td>
            <td>{$email}</td>
            <td>{$phone}</td>
            <td>{$position}</td>
            <td><span class='badge bg-{$badge}'><i class='fas fa-briefcase me-1'></i> {$employmentType}</span></td>
            <td>{$hireDate}</td>
            <td>{$salary}</td>
            <td>
                <button class='btn btn-sm btn-info btn-view' data-staff-id='{$staffId}' title='View Staff'><i class='fas fa-eye'></i></button>
                <button class='btn btn-sm btn-warning btn-edit' data-staff-id='{$staffId}' title='Edit Staff'><i class='fas fa-edit'></i></button>
                <button class='btn btn-sm btn-danger btn-delete' data-staff-id='{$staffId}' title='Delete Staff'><i class='fas fa-trash'></i></button>
            </td>
        </tr>";
    }
    return $rows;
}
?>

<!-- Custom Styles -->
<link rel="stylesheet" href="../assets/css/admin/staff.css">

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-<?= $_SESSION['type'] ?? 'success' ?> alert-dismissible fade show" role="alert">
    <?= $_SESSION['message'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['message'], $_SESSION['type']); endif; ?>

<div class="card shadow mb-4">
    <div class="card-header pb-0 border-bottom-0">
        <ul class="nav nav-tabs card-header-tabs" id="staffTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-staff" type="button" role="tab">All Staff</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-staff" type="button" role="tab">Active Staff</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="longterm-tab" data-bs-toggle="tab" data-bs-target="#longterm-staff" type="button" role="tab">Long-term Staff</button>
            </li>
        </ul>
    </div>

    <div class="card-body tab-content" id="staffTabsContent">
        <!-- All Staff -->
        <div class="tab-pane fade show active" id="all-staff" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="staffTable">
                    <thead class="table-light">
                        <tr>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Position</th>
                            <th>Employment</th>
                            <th>Hire Date</th>
                            <th>Salary</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?= renderStaffRows($allStaff) ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Active Staff -->
        <div class="tab-pane fade" id="active-staff" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Position</th>
                            <th>Employment</th>
                            <th>Hire Date</th>
                            <th>Salary</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?= renderStaffRows($activeStaff) ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Long-term Staff -->
        <div class="tab-pane fade" id="longterm-staff" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Position</th>
                            <th>Employment</th>
                            <th>Hire Date</th>
                            <th>Salary</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?= renderStaffRows($longTermStaff) ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Staff Modal -->
<div class="modal fade" id="viewStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Staff Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="staffDetails">
                    <!-- populated by JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editStaffForm">
                <div class="modal-body">
                    <input type="hidden" name="staff_id" id="edit_staff_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="edit_phone">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" name="position" id="edit_position" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hire Date</label>
                            <input type="date" class="form-control" name="hire_date" id="edit_hire_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employment Type</label>
                            <select class="form-select" name="employment_type" id="edit_employment_type">
                                <option value="full-time">Full-time</option>
                                <option value="part-time">Part-time</option>
                                <option value="contract">Contract</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Salary (₱)</label>
                            <input type="number" class="form-control" name="salary" id="edit_salary" step="0.01">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="edit_address" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" id="edit_gender">
                                <option value="">Not set</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="delete_staff_name"></strong> (<code id="delete_staff_id"></code>)?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteStaff">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize modals
const viewModal = new bootstrap.Modal(document.getElementById('viewStaffModal'));
const editModal = new bootstrap.Modal(document.getElementById('editStaffModal'));
const deleteModal = new bootstrap.Modal(document.getElementById('deleteStaffModal'));

// View Staff
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-view')) {
        const btn = e.target.closest('.btn-view');
        const staffId = btn.dataset.staffId;
        
        // Show loading state
        document.getElementById('staffDetails').innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        viewModal.show();
        
        // Fetch staff data
        fetch(`staff_actions.php?action=view&staff_id=${encodeURIComponent(staffId)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const s = data.staff;
                    document.getElementById('staffDetails').innerHTML = `
                        <dl class="row">
                            <dt class="col-sm-4">Staff ID</dt>
                            <dd class="col-sm-8">${s.staff_id}</dd>
                            
                            <dt class="col-sm-4">Name</dt>
                            <dd class="col-sm-8">${s.first_name} ${s.last_name}</dd>
                            
                            <dt class="col-sm-4">Email</dt>
                            <dd class="col-sm-8">${s.email}</dd>
                            
                            <dt class="col-sm-4">Phone</dt>
                            <dd class="col-sm-8">${s.phone || '-'}</dd>
                            
                            <dt class="col-sm-4">Position</dt>
                            <dd class="col-sm-8">${s.position}</dd>
                            
                            <dt class="col-sm-4">Hire Date</dt>
                            <dd class="col-sm-8">${s.hire_date}</dd>
                            
                            <dt class="col-sm-4">Employment Type</dt>
                            <dd class="col-sm-8">${s.employment_type || 'Full-time'}</dd>
                            
                            <dt class="col-sm-4">Salary</dt>
                            <dd class="col-sm-8">${s.salary ? '₱' + parseFloat(s.salary).toLocaleString() : '-'}</dd>
                            
                            <dt class="col-sm-4">Address</dt>
                            <dd class="col-sm-8">${s.address || '-'}</dd>
                            
                            <dt class="col-sm-4">Gender</dt>
                            <dd class="col-sm-8">${s.gender || '-'}</dd>
                        </dl>
                    `;
                } else {
                    document.getElementById('staffDetails').innerHTML = `
                        <div class="alert alert-danger">${data.message || 'Failed to load staff data'}</div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('staffDetails').innerHTML = `
                    <div class="alert alert-danger">An error occurred while loading staff data</div>
                `;
            });
    }
});

// Edit Staff - Open Modal
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-edit')) {
        const btn = e.target.closest('.btn-edit');
        const staffId = btn.dataset.staffId;
        const submitBtn = document.querySelector('#editStaffForm button[type="submit"]');
        
        // Show loading state
        submitBtn.innerHTML = `
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...
        `;
        submitBtn.disabled = true;
        
        editModal.show();
        
        // Fetch staff data
        fetch(`staff_actions.php?action=view&staff_id=${encodeURIComponent(staffId)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const s = data.staff;
                    document.getElementById('edit_staff_id').value = s.staff_id;
                    document.getElementById('edit_first_name').value = s.first_name || '';
                    document.getElementById('edit_last_name').value = s.last_name || '';
                    document.getElementById('edit_email').value = s.email || '';
                    document.getElementById('edit_phone').value = s.phone || '';
                    document.getElementById('edit_position').value = s.position || '';
                    document.getElementById('edit_hire_date').value = s.hire_date || '';
                    document.getElementById('edit_employment_type').value = s.employment_type || 'full-time';
                    document.getElementById('edit_salary').value = s.salary || '';
                    document.getElementById('edit_address').value = s.address || '';
                    document.getElementById('edit_gender').value = s.gender || '';
                } else {
                    alert(data.message || 'Failed to load staff data');
                    editModal.hide();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while loading staff data');
                editModal.hide();
            })
            .finally(() => {
                submitBtn.innerHTML = 'Save Changes';
                submitBtn.disabled = false;
            });
    }
});

// Edit Staff - Form Submission
document.getElementById('editStaffForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...
    `;
    submitBtn.disabled = true;
    
    // Prepare form data
    const formData = new FormData(form);
    formData.append('action', 'edit');
    
    // Send request
    fetch('staff_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update table row
            const s = data.staff;
            const row = document.querySelector(`tr[data-staff-id="${s.staff_id}"]`);
            if (row) {
                // Update all cells in the row
                const badge = s.employment_type === 'full-time' ? 'primary' : 
                             s.employment_type === 'part-time' ? 'success' : 
                             s.employment_type === 'contract' ? 'warning' : 'secondary';
                
                row.cells[1].textContent = `${s.first_name} ${s.last_name}`;
                row.cells[2].textContent = s.email;
                row.cells[3].textContent = s.phone || '-';
                row.cells[4].textContent = s.position;
                row.cells[5].innerHTML = `<span class='badge bg-${badge}'><i class='fas fa-briefcase me-1'></i> ${s.employment_type.charAt(0).toUpperCase() + s.employment_type.slice(1)}</span>`;
                row.cells[6].textContent = s.hire_date;
                row.cells[7].textContent = s.salary ? '₱' + parseFloat(s.salary).toLocaleString() : '-';
            }
            
            // Close modal
            editModal.hide();
            
            // Show success message
            alert('Staff updated successfully!');
        } else {
            throw new Error(data.message || 'Update failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'An error occurred while updating staff');
    })
    .finally(() => {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    });
});

// Delete Staff - Open Confirmation
let toDeleteId = null;
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-delete')) {
        const btn = e.target.closest('.btn-delete');
        const staffId = btn.dataset.staffId;
        const staffName = btn.closest('tr').querySelector('td:nth-child(2)').textContent;
        
        toDeleteId = staffId;
        document.getElementById('delete_staff_name').textContent = staffName;
        document.getElementById('delete_staff_id').textContent = staffId;
        
        deleteModal.show();
    }
});

// Delete Staff - Confirm
document.getElementById('confirmDeleteStaff').addEventListener('click', function() {
    if (!toDeleteId) return;
    
    const btn = this;
    const originalBtnText = btn.innerHTML;
    
    // Show loading state
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...
    `;
    btn.disabled = true;
    
    // Prepare data
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('staff_id', toDeleteId);
    
    // Send request
    fetch('staff_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove row from table
            const row = document.querySelector(`tr[data-staff-id="${toDeleteId}"]`);
            if (row) row.remove();
            
            // Close modal
            deleteModal.hide();
            
            // Show success message
            alert('Staff deleted successfully!');
        } else {
            throw new Error(data.message || 'Delete failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'An error occurred while deleting staff');
    })
    .finally(() => {
        btn.innerHTML = originalBtnText;
        btn.disabled = false;
        toDeleteId = null;
    });
});
</script>

<!-- Link JS file -->
<script src="../assets/js/admin/staff.js"></script>

<?php include 'components/footer.php'; ?>
