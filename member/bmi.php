<?php include 'components/header.php'; ?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-md-6">

      <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white text-center">
          <h4 class="mb-0">BMI Calculator</h4>
        </div>
        <div class="card-body">

          <form method="post">
            <div class="mb-3">
              <label for="height" class="form-label">Height (cm)</label>
              <input type="number" name="height" id="height" class="form-control" min="1" required>
            </div>

            <div class="mb-3">
              <label for="weight" class="form-label">Weight (kg)</label>
              <input type="number" name="weight" id="weight" class="form-control" min="1" required>
            </div>

            <div class="d-grid">
              <button class="btn btn-success" name="calc">Calculate BMI</button>
            </div>
          </form>

          <?php
          if (isset($_POST['calc'])) {
            $height = floatval($_POST['height']);
            $weight = floatval($_POST['weight']);

            if ($height > 0 && $weight > 0) {
              $hMeters = $height / 100;
              $bmi = $weight / ($hMeters * $hMeters);
              $bmi = round($bmi, 2);

              // BMI Category
              $status = '';
              if ($bmi < 18.5) {
                $status = "Underweight";
              } elseif ($bmi < 24.9) {
                $status = "Normal weight";
              } elseif ($bmi < 29.9) {
                $status = "Overweight";
              } else {
                $status = "Obese";
              }

              echo "<div class='alert alert-info mt-4 text-center'>
                      <h5>Your BMI is <strong>$bmi</strong></h5>
                      <p class='mb-0'>Category: <strong>$status</strong></p>
                    </div>";
            } else {
              echo "<div class='alert alert-danger mt-4'>Please enter valid height and weight.</div>";
            }
          }
          ?>

        </div>
      </div>

    </div>
  </div>
</div>

 
