<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Authentication check
if (!isset($_SESSION['member_logged_in']) || !$_SESSION['member_logged_in']) {
    header('Location: login.php');
    exit;
}

$page_title = "My Schedule";
include 'components/header.php';
?>
<link rel="stylesheet" href="../assets/css/member/schedule.css">

<div class="container-fluid px-4">
    <div class="row g-4">
        <!-- Page Header -->
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="fas fa-calendar-alt me-2 text-primary"></i>My Schedule</h2>
                    <p class="text-muted mb-0">Manage your workout routines and class schedules</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                        <i class="fas fa-plus me-2"></i>Add Schedule
                    </button>
                    <button class="btn btn-primary" onclick="exportSchedule()">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Schedule Overview Cards -->
        <div class="col-md-3">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-dumbbell fa-2x text-primary"></i>
                    </div>
                    <h4 class="mb-1">12</h4>
                    <p class="text-muted mb-0">Total Workouts</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-users fa-2x text-success"></i>
                    </div>
                    <h4 class="mb-1">8</h4>
                    <p class="text-muted mb-0">Group Classes</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-user-tie fa-2x text-warning"></i>
                    </div>
                    <h4 class="mb-1">4</h4>
                    <p class="text-muted mb-0">PT Sessions</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-clock fa-2x text-info"></i>
                    </div>
                    <h4 class="mb-1">24h</h4>
                    <p class="text-muted mb-0">Next Session</p>
                </div>
            </div>
        </div>

        <!-- Weekly Schedule -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-calendar-week me-2 text-primary"></i>Weekly Schedule</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">Time</th>
                                    <th class="border-0">Monday</th>
                                    <th class="border-0">Tuesday</th>
                                    <th class="border-0">Wednesday</th>
                                    <th class="border-0">Thursday</th>
                                    <th class="border-0">Friday</th>
                                    <th class="border-0">Saturday</th>
                                    <th class="border-0">Sunday</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold text-muted">6:00 AM</td>
                                    <td>
                                        <div class="schedule-item bg-primary text-white p-2 rounded mb-1">
                                            <small class="d-block fw-bold">Cardio</small>
                                            <small>45 min</small>
                                        </div>
                                    </td>
                                    <td></td>
                                    <td>
                                        <div class="schedule-item bg-success text-white p-2 rounded mb-1">
                                            <small class="d-block fw-bold">Yoga</small>
                                            <small>60 min</small>
                                        </div>
                                    </td>
                                    <td></td>
                                    <td>
                                        <div class="schedule-item bg-warning text-white p-2 rounded mb-1">
                                            <small class="d-block fw-bold">PT Session</small>
                                            <small>90 min</small>
                                        </div>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">7:00 AM</td>
                                    <td></td>
                                    <td>
                                        <div class="schedule-item bg-info text-white p-2 rounded mb-1">
                                            <small class="d-block fw-bold">Strength</small>
                                            <small>60 min</small>
                                        </div>
                                    </td>
                                    <td></td>
                                    <td>
                                        <div class="schedule-item bg-danger text-white p-2 rounded mb-1">
                                            <small class="d-block fw-bold">HIIT</small>
                                            <small>30 min</small>
                                        </div>
                                    </td>
                                    <td></td>
                                    <td>
                                        <div class="schedule-item bg-secondary text-white p-2 rounded mb-1">
                                            <small class="d-block fw-bold">Pilates</small>
                                            <small>45 min</small>
                                        </div>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">8:00 AM</td>
                                    <td></td>
                                    <td></td>
                                    <td>
                                        <div class="schedule-item bg-primary text-white p-2 rounded mb-1">
                                            <small class="d-block fw-bold">Swimming</small>
                                            <small>45 min</small>
                                        </div>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>
                                        <div class="schedule-item bg-success text-white p-2 rounded mb-1">
                                            <small class="d-block fw-bold">Rest Day</small>
                                            <small>Active Recovery</small>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Sessions -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-clock me-2 text-primary"></i>Upcoming Sessions</h5>
                </div>
                <div class="card-body">
                    <div class="upcoming-sessions">
                        <div class="session-item d-flex align-items-center p-3 border rounded mb-3">
                            <div class="session-time text-center me-3">
                                <div class="fw-bold text-primary">6:00</div>
                                <small class="text-muted">AM</small>
                            </div>
                            <div class="session-details flex-grow-1">
                                <h6 class="mb-1">Cardio Training</h6>
                                <p class="text-muted mb-1">Monday, Dec 16</p>
                                <span class="badge bg-primary">45 min</span>
                            </div>
                            <div class="session-actions">
                                <button class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>

                        <div class="session-item d-flex align-items-center p-3 border rounded mb-3">
                            <div class="session-time text-center me-3">
                                <div class="fw-bold text-success">7:00</div>
                                <small class="text-muted">AM</small>
                            </div>
                            <div class="session-details flex-grow-1">
                                <h6 class="mb-1">Strength Training</h6>
                                <p class="text-muted mb-1">Tuesday, Dec 17</p>
                                <span class="badge bg-success">60 min</span>
                            </div>
                            <div class="session-actions">
                                <button class="btn btn-sm btn-outline-success" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>

                        <div class="session-item d-flex align-items-center p-3 border rounded mb-3">
                            <div class="session-time text-center me-3">
                                <div class="fw-bold text-warning">6:00</div>
                                <small class="text-muted">AM</small>
                            </div>
                            <div class="session-details flex-grow-1">
                                <h6 class="mb-1">Personal Training</h6>
                                <p class="text-muted mb-1">Friday, Dec 20</p>
                                <span class="badge bg-warning">90 min</span>
                            </div>
                            <div class="session-actions">
                                <button class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Categories -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Schedule Categories</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="category-card p-4 border rounded text-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-dumbbell fa-3x text-primary"></i>
                                </div>
                                <h5>Workout Routines</h5>
                                <p class="text-muted">Personal workout schedules and routines</p>
                                <button class="btn btn-primary btn-sm">Manage</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="category-card p-4 border rounded text-center">
                                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-users fa-3x text-success"></i>
                                </div>
                                <h5>Group Classes</h5>
                                <p class="text-muted">Yoga, Pilates, HIIT, and other group sessions</p>
                                <button class="btn btn-success btn-sm">View Classes</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="category-card p-4 border rounded text-center">
                                <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-user-tie fa-3x text-warning"></i>
                                </div>
                                <h5>Personal Training</h5>
                                <p class="text-muted">One-on-one training sessions</p>
                                <button class="btn btn-warning btn-sm">Book Session</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addScheduleForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Activity Type</label>
                            <select class="form-select" name="activity" required>
                                <option value="">Select Activity</option>
                                <option value="cardio">Cardio Training</option>
                                <option value="strength">Strength Training</option>
                                <option value="yoga">Yoga</option>
                                <option value="pilates">Pilates</option>
                                <option value="hiit">HIIT</option>
                                <option value="swimming">Swimming</option>
                                <option value="pt">Personal Training</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Day of Week</label>
                            <select class="form-select" name="day" required>
                                <option value="">Select Day</option>
                                <option value="monday">Monday</option>
                                <option value="tuesday">Tuesday</option>
                                <option value="wednesday">Wednesday</option>
                                <option value="thursday">Thursday</option>
                                <option value="friday">Friday</option>
                                <option value="saturday">Saturday</option>
                                <option value="sunday">Sunday</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Time</label>
                            <input type="time" class="form-control" name="time" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" name="duration" min="15" max="180" value="60" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Any additional notes..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addScheduleForm" class="btn btn-primary">Add Schedule</button>
            </div>
        </div>
    </div>
</div>

<style>
.schedule-item {
    cursor: pointer;
    transition: all 0.3s ease;
}

.schedule-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.session-item {
    transition: all 0.3s ease;
}

.session-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.category-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.session-time {
    min-width: 60px;
}

.session-actions {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.session-item:hover .session-actions {
    opacity: 1;
}
</style>

<script src="../assets/js/member/schedule.js"></script>

<?php include 'components/footer.php'; ?>
