<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Health Tracker";
include 'components/header.php';
?>

<!-- Main Page Content -->
<div class="container-fluid px-4">
    <div class="row g-4">
        <!-- Page Header -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-heartbeat me-2 text-danger"></i>Health Tracker</h5>
                </div>
                <div class="card-body">
                    <p>Track your fitness progress and set goals to stay motivated.</p>
                </div>
            </div>
        </div>

        <!-- Health Tracker Form -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Update Your Progress</h6>
                </div>
                <div class="card-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Current Weight (kg)</label>
                            <input type="number" class="form-control" placeholder="Enter current weight">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target Weight (kg)</label>
                            <input type="number" class="form-control" placeholder="Enter target weight">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Progress Notes</label>
                            <textarea class="form-control" rows="3" placeholder="Write notes about your progress"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Progress
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="col-lg-6">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fas fa-chart-line fa-2x mb-3 text-success"></i>
                    <h5 class="card-title">Current Progress</h5>
                    <p class="fs-4 fw-bold text-success">-2.5 kg</p>
                    <p class="text-muted">From your last update</p>
                </div>
            </div>
        </div>
    </div>
</div>

 
