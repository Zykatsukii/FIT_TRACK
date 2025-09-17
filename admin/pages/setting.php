<?php
$page_title = "System Settings";
include 'components/header.php';

// Simulate existing settings (in a real app, these would come from a database)
$settings = [
    'dark_mode' => false,
    'email_notifications' => true,
    'maintenance_mode' => false,
    'auto_logout' => 30,
    'language' => 'en',
    'date_format' => 'Y-m-d',
    'results_per_page' => 25
];
?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">System Settings</h1>
        <div>
            <button class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2" id="resetSettingsBtn">
                <i class="fas fa-undo fa-sm text-white-50"></i> Reset to Default
            </button>
            <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" id="saveSettingsBtn">
                <i class="fas fa-save fa-sm text-white-50"></i> Save Settings
            </button>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="settingsToast" class="toast position-fixed" style="top: 20px; right: 20px; z-index: 9999; display: none;">
        <div class="toast-header">
            <strong class="mr-auto" id="toastTitle">Notification</strong>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>

    <form id="settingsForm">
        <div class="row">
            <!-- General Settings -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">General Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-weight-bold">Dark Mode</h6>
                                <small class="text-muted">Enable dark theme for the system</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="darkModeSwitch" name="dark_mode" <?= $settings['dark_mode'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-weight-bold">Email Notifications</h6>
                                <small class="text-muted">Receive email alerts for important events</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="emailNotificationsSwitch" name="email_notifications" <?= $settings['email_notifications'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-weight-bold">Maintenance Mode</h6>
                                <small class="text-muted">Temporarily disable system access</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="maintenanceModeSwitch" name="maintenance_mode" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label for="languageSelect" class="form-label font-weight-bold">Language</label>
                            <select class="form-control" id="languageSelect" name="language">
                                <option value="en" <?= $settings['language'] === 'en' ? 'selected' : '' ?>>English</option>
                                <option value="es" <?= $settings['language'] === 'es' ? 'selected' : '' ?>>Spanish</option>
                                <option value="fr" <?= $settings['language'] === 'fr' ? 'selected' : '' ?>>French</option>
                                <option value="de" <?= $settings['language'] === 'de' ? 'selected' : '' ?>>German</option>
                            </select>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label for="dateFormatSelect" class="form-label font-weight-bold">Date Format</label>
                            <select class="form-control" id="dateFormatSelect" name="date_format">
                                <option value="Y-m-d" <?= $settings['date_format'] === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                                <option value="m/d/Y" <?= $settings['date_format'] === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                                <option value="d/m/Y" <?= $settings['date_format'] === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Security Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Two-Factor Authentication</h6>
                            <small class="text-muted">Add extra security to your account</small>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-primary">Enable 2FA</button>
                                <span class="badge badge-success ml-2" id="twoFactorStatus">Active</span>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Auto Logout</h6>
                            <small class="text-muted">Automatically logout after period of inactivity</small>
                            <div class="mt-2 d-flex align-items-center">
                                <input type="range" class="form-range flex-grow-1 mr-3" id="autoLogoutRange" min="5" max="120" step="5" value="<?= $settings['auto_logout'] ?>" name="auto_logout">
                                <span class="badge badge-info" id="autoLogoutValue"><?= $settings['auto_logout'] ?> minutes</span>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Password Update</h6>
                            <small class="text-muted">Last changed 3 months ago</small>
                            <button type="button" class="btn btn-sm btn-primary mt-2" data-toggle="modal" data-target="#changePasswordModal">Change Password</button>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Login Activity</h6>
                            <small class="text-muted">View recent login attempts</small>
                            <button type="button" class="btn btn-sm btn-info mt-2" data-toggle="modal" data-target="#loginActivityModal">View Activity</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Data Settings -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Data Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="resultsPerPage" class="form-label font-weight-bold">Results Per Page</label>
                            <select class="form-control" id="resultsPerPage" name="results_per_page">
                                <option value="10" <?= $settings['results_per_page'] == 10 ? 'selected' : '' ?>>10</option>
                                <option value="25" <?= $settings['results_per_page'] == 25 ? 'selected' : '' ?>>25</option>
                                <option value="50" <?= $settings['results_per_page'] == 50 ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= $settings['results_per_page'] == 100 ? 'selected' : '' ?>>100</option>
                            </select>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Data Export</h6>
                            <small class="text-muted">Export your data in various formats</small>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary mr-2">CSV</button>
                                <button type="button" class="btn btn-sm btn-outline-primary mr-2">JSON</button>
                                <button type="button" class="btn btn-sm btn-outline-primary">XML</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="col-lg-6">
                <div class="card shadow mb-4 border-left-danger">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-danger">Danger Zone</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Clear Cache</h6>
                            <small class="text-muted">Remove temporary system files</small>
                            <button class="btn btn-sm btn-warning mt-2" id="clearCacheBtn">
                                <i class="fas fa-broom fa-sm fa-fw mr-1"></i> Clear Cache
                            </button>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <h6 class="font-weight-bold">Delete Account</h6>
                            <small class="text-muted">Permanently remove your account and all data</small>
                            <button class="btn btn-sm btn-danger mt-2" type="button" data-toggle="modal" data-target="#deleteAccountModal">
                                <i class="fas fa-trash-alt fa-sm fa-fw mr-1"></i> Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="currentPassword" class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="currentPassword" placeholder="Enter current password">
                </div>
                <div class="mb-3">
                    <label for="newPassword" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="newPassword" placeholder="Enter new password">
                    <div class="progress mt-2" style="height: 5px;">
                        <div id="passwordStrengthBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small id="passwordStrengthText" class="form-text"></small>
                </div>
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm new password">
                    <small id="passwordMatchText" class="form-text"></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updatePasswordBtn">Update Password</button>
            </div>
        </div>
    </div>
</div>

<!-- Login Activity Modal -->
<div class="modal fade" id="loginActivityModal" tabindex="-1" aria-labelledby="loginActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="loginActivityModalLabel">Login Activity</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>IP Address</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>2023-06-15 14:32:18</td>
                                <td>192.168.1.1</td>
                                <td>New York, USA</td>
                                <td><span class="badge badge-success">Success</span></td>
                            </tr>
                            <tr>
                                <td>2023-06-14 09:15:47</td>
                                <td>192.168.1.1</td>
                                <td>New York, USA</td>
                                <td><span class="badge badge-success">Success</span></td>
                            </tr>
                            <tr>
                                <td>2023-06-12 22:05:13</td>
                                <td>103.216.88.15</td>
                                <td>Tokyo, Japan</td>
                                <td><span class="badge badge-danger">Failed</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAccountModalLabel">Confirm Account Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> This action is irreversible!
                </div>
                <p>Are you sure you want to delete your account? This action cannot be undone. All your data will be permanently removed from our systems.</p>
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Enter your password to confirm:</label>
                    <input type="password" class="form-control" id="confirmPassword" placeholder="Your password">
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="confirmDeletion">
                    <label class="form-check-label text-danger" for="confirmDeletion">I understand that this action cannot be undone</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="deleteAccountBtn" disabled>Delete Account</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto logout slider update
    $('#autoLogoutRange').on('input', function() {
        $('#autoLogoutValue').text($(this).val() + ' minutes');
    });

    // Password strength checker
    $('#newPassword').on('input', function() {
        const password = $(this).val();
        let strength = 0;
        let message = '';
        
        if (password.length > 0) {
            if (password.length < 6) {
                strength = 20;
                message = 'Too short';
                $('#passwordStrengthBar').removeClass('bg-warning bg-success').addClass('bg-danger');
            } else {
                if (/[a-z]/.test(password)) strength += 20;
                if (/[A-Z]/.test(password)) strength += 20;
                if (/[0-9]/.test(password)) strength += 20;
                if (/[^a-zA-Z0-9]/.test(password)) strength += 20;
                
                if (strength <= 40) {
                    message = 'Weak';
                    $('#passwordStrengthBar').removeClass('bg-success bg-danger').addClass('bg-warning');
                } else if (strength <= 80) {
                    message = 'Medium';
                    $('#passwordStrengthBar').removeClass('bg-success bg-danger').addClass('bg-warning');
                } else {
                    message = 'Strong';
                    $('#passwordStrengthBar').removeClass('bg-warning bg-danger').addClass('bg-success');
                }
            }
        } else {
            $('#passwordStrengthBar').removeClass('bg-warning bg-success bg-danger');
        }
        
        $('#passwordStrengthBar').css('width', strength + '%');
        $('#passwordStrengthText').text(message);
    });

    // Password confirmation check
    $('#confirmPassword').on('input', function() {
        const newPassword = $('#newPassword').val();
        const confirmPassword = $(this).val();
        
        if (confirmPassword.length > 0) {
            if (newPassword === confirmPassword) {
                $('#passwordMatchText').text('Passwords match').removeClass('text-danger').addClass('text-success');
            } else {
                $('#passwordMatchText').text('Passwords do not match').removeClass('text-success').addClass('text-danger');
            }
        } else {
            $('#passwordMatchText').text('');
        }
    });

    // Delete account confirmation
    $('#confirmDeletion').change(function() {
        $('#deleteAccountBtn').prop('disabled', !this.checked);
    });

    // Show toast notification
    function showToast(title, message, type = 'info') {
        $('#toastTitle').text(title);
        $('#toastMessage').text(message);
        $('#settingsToast').removeClass('bg-info bg-success bg-danger bg-warning');
        
        switch(type) {
            case 'success':
                $('#settingsToast').addClass('bg-success text-white');
                break;
            case 'error':
                $('#settingsToast').addClass('bg-danger text-white');
                break;
            case 'warning':
                $('#settingsToast').addClass('bg-warning text-dark');
                break;
            default:
                $('#settingsToast').addClass('bg-info text-white');
        }
        
        $('#settingsToast').toast({ delay: 3000 });
        $('#settingsToast').toast('show');
    }

    // Save settings functionality
    $('#saveSettingsBtn').click(function() {
        const formData = $('#settingsForm').serialize();
        
        // Show loading state
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin fa-sm text-white-50"></i> Saving...');
        
        // Simulate API call
        setTimeout(function() {
            // In a real application, you would use AJAX to send the data to the server
            // $.post('api/save-settings.php', formData, function(response) {
            //     if (response.success) {
            //         showToast('Success', 'Settings saved successfully!', 'success');
            //     } else {
            //         showToast('Error', 'Failed to save settings: ' + response.message, 'error');
            //     }
            // }).fail(function() {
            //     showToast('Error', 'An error occurred while saving settings', 'error');
            // }).always(function() {
            //     $('#saveSettingsBtn').prop('disabled', false).html('<i class="fas fa-save fa-sm text-white-50"></i> Save Settings');
            // });
            
            // For demo purposes, we'll just show a success message
            showToast('Success', 'Settings saved successfully!', 'success');
            $('#saveSettingsBtn').prop('disabled', false).html('<i class="fas fa-save fa-sm text-white-50"></i> Save Settings');
        }, 1000);
    });

    // Reset settings functionality
    $('#resetSettingsBtn').click(function() {
        if (confirm('Are you sure you want to reset all settings to their default values?')) {
            // In a real application, you would make an AJAX call to reset settings
            // For demo purposes, we'll just show a message
            showToast('Settings Reset', 'All settings have been reset to default values.', 'success');
            
            // Reset form values (in a real app, you'd fetch defaults from server)
            $('#darkModeSwitch').prop('checked', false);
            $('#emailNotificationsSwitch').prop('checked', true);
            $('#maintenanceModeSwitch').prop('checked', false);
            $('#languageSelect').val('en');
            $('#dateFormatSelect').val('Y-m-d');
            $('#autoLogoutRange').val(30);
            $('#autoLogoutValue').text('30 minutes');
            $('#resultsPerPage').val(25);
        }
    });

    // Clear cache functionality
    $('#clearCacheBtn').click(function() {
        // Simulate API call
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin fa-sm fa-fw mr-1"></i> Clearing...');
        
        setTimeout(function() {
            showToast('Cache Cleared', 'Temporary files have been removed.', 'success');
            $('#clearCacheBtn').prop('disabled', false).html('<i class="fas fa-broom fa-sm fa-fw mr-1"></i> Clear Cache');
        }, 1500);
    });
});
</script>

<?php include 'components/footer.php'; ?>