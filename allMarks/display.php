
<?php
  include '../connect.php';
  include '../session.php';
  $grade = isset($_GET['grade']) ? (int) $_GET['grade'] : 9;
  $section = isset($_GET['section']) ? mysqli_real_escape_string($conn, $_GET['section']) : 'A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Performance Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="style.css">
  <style>
    @media (max-width: 992px) {
      .card,.card-body {
        padding-right: 85px;
        min-width: 100vw;
      }
    }
  </style>
</head>
<body>
<div class="dashboard-container">
  <div class="header">
    <div class="d-flex justify-content-between align-items-center">
      <h2><i class="fas fa-user-graduate me-2"></i>Student Performance Dashboard</h2>
      <div class="action-buttons">
        <a href="../home.php" class="btn btn-light no-print me-2"><i class="fas fa-arrow-left me-1"></i> Back Home</a>
        <button onclick="window.print()" class="btn btn-light no-print"><i class="fas fa-print me-1"></i> Print</button>
      </div>
    </div>
  </div>

  <!-- Grade and Section Filter -->
  <div class="filter-card no-print">
    <form method="GET" class="row g-3">
      <div class="col-md-3">
        <label for="grade" class="form-label">Grade</label>
        <select name="grade" id="grade" class="form-select" required onchange="this.form.submit()">
          <?php for ($g = 9; $g <= 12; $g++): ?>
            <option value="<?= $g ?>" <?= $grade == $g ? 'selected' : 'not student' ?>><?= $g ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label for="section" class="form-label">Section</label>
        <select name="section" id="section" class="form-select" required onchange="this.form.submit()">
          <?php foreach (range('A','Z') as $sec): ?>
            <option value="<?= $sec ?>" <?= $section == $sec ? 'selected' : 'not student' ?>><?= $sec ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <!-- <div class="col-md-3 d-flex align-items-end">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
      </div> -->
    </form>
  </div>

  <div class="card">
    <div class="card-header">
      <i class="fas fa-chart-line me-2"></i>Grade <?= $grade ?>, Section <?= htmlspecialchars($section) ?> - Performance Overview
    </div>
    <div class="card-body">
      <?php
        // Fetch subjects
        $subjects = [];
        $subject_res = mysqli_query($conn, "SELECT id, name FROM subjects ORDER BY name");
        while ($row = mysqli_fetch_assoc($subject_res)) {
          $subjects[$row['id']] = $row['name'];
        }

        // Fetch semesters (terms)
        $terms = [];
        $term_res = mysqli_query($conn, "SELECT id, name FROM semesters ORDER BY id");
        while ($row = mysqli_fetch_assoc($term_res)) {
          $terms[$row['id']] = $row['name'];
        }

        // Fetch students
        $students = [];
        $student_res = mysqli_query($conn, "SELECT id, name FROM students WHERE grade = $grade AND section = '$section' ORDER BY name");
        while ($row = mysqli_fetch_assoc($student_res)) {
          $students[$row['id']] = [
            'name' => $row['name'],
            'terms' => [],
            'term_totals' => [],
            'term_averages' => [],
            'term_ranks' => [],
            'overall_total' => 0,
            'overall_avg' => 0,
          ];
        }

        // Fetch marks - now grouping by student, subject, and term to sum all mark types
        $marks_res = mysqli_query($conn, "
          SELECT 
            sm.student_id, 
            sm.subject_id, 
            sm.semester_id, 
            SUM(sm.mark) as total_mark
          FROM student_marks sm
          JOIN students s ON sm.student_id = s.id
          WHERE s.grade = $grade AND s.section = '$section'
          GROUP BY sm.student_id, sm.subject_id, sm.semester_id 
        ");

        foreach ($marks_res as $row) {
          $sid = $row['student_id'];
          $sub = $row['subject_id'];
          $term = $row['semester_id'];
          $mark = $row['total_mark'];

          // Initialize term array if not exists
          if (!isset($students[$sid]['terms'][$term])) {
            $students[$sid]['terms'][$term] = [];
          }
          
          $students[$sid]['terms'][$term][$sub] = $mark;
          $students[$sid]['term_totals'][$term] = ($students[$sid]['term_totals'][$term] ?? 0) + $mark;
          $students[$sid]['overall_total'] += $mark;
        }

        // Calculate term averages and ranks for each term
foreach ($terms as $term_id => $term_name) {
  // Create a temporary array for this term's totals with student IDs
  $term_totals = [];
  foreach ($students as $sid => $student) {
      if (isset($student['term_totals'][$term_id])) {
          $term_totals[$sid] = $student['term_totals'][$term_id];
      }
  }
  
  // Sort students by this term's total (descending)
  arsort($term_totals);
  
  // Assign ranks for this term
  $rank = 1;
  $prev_total = null;
  $skip = 0;
  foreach ($term_totals as $sid => $total) {
      if ($total === $prev_total) {
          $skip++;
      } else {
          $rank += $skip;
          $skip = 0;
      }
      
      $students[$sid]['term_ranks'][$term_id] = $rank;
      $subject_count = count($subjects);
      $students[$sid]['term_averages'][$term_id] = $subject_count > 0 ? $students[$sid]['term_totals'][$term_id] / $subject_count : 0;
      
      $prev_total = $total;
      $rank++;
  }
}

// Then later in the display code:
foreach ($students as $student) {
  $rank_class = '';
  $term_rank = $student['term_ranks'][$term_id] ?? null;
  if ($term_rank === 1) $rank_class = 'rank-1';
  elseif ($term_rank === 2) $rank_class = 'rank-2';
  elseif ($term_rank === 3) $rank_class = 'rank-3';
}

        // Calculate overall averages and ranks
        foreach ($students as &$stu) {
          $stu['overall_avg'] = count($subjects) * count($terms) ? $stu['overall_total'] / (count($subjects) * count($terms)) : 0;
        }
        unset($stu);

        // Sort students by overall total for overall ranking
        usort($students, fn($a, $b) => $b['overall_total'] <=> $a['overall_total']);
        $overall_rank = 1;
        $prev_total = null;
        $skip = 0;
        foreach ($students as $i => &$stu) {
          if ($stu['overall_total'] === $prev_total) {
            $skip++;
          } else {
            $overall_rank += $skip;
            $skip = 0;
          }
          
          $stu['overall_rank'] = $overall_rank;
          $prev_total = $stu['overall_total'];
          $overall_rank++;
        }
        unset($stu);
      ?>

      <!-- Navigation Tabs -->
      <ul class="nav nav-pills mb-4" id="termTabs" role="tablist">
        <?php foreach ($terms as $term_id => $term_name): ?>
          <li class="nav-item" role="presentation">
            <button class="nav-link <?= $term_id === array_key_first($terms) ? 'active' : '' ?>" 
                    id="term-<?= $term_id ?>-tab" data-bs-toggle="pill" 
                    data-bs-target="#term-<?= $term_id ?>" type="button" role="tab">
              <?= htmlspecialchars($term_name) ?>
            </button>
          </li>
        <?php endforeach; ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="overall-tab" data-bs-toggle="pill" 
                  data-bs-target="#overall" type="button" role="tab">
            <i class="fas fa-star me-1"></i>Overall
          </button>
        </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content" id="termTabsContent">
        <?php foreach ($terms as $term_id => $term_name): ?>
          <div class="tab-pane fade <?= $term_id === array_key_first($terms) ? 'show active' : '' ?>" 
               id="term-<?= $term_id ?>" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th width="40px">#</th>
                    <th>Student Name</th>
                    <?php foreach ($subjects as $sub_id => $sub_name): ?>
                      <th width="100px"><?= htmlspecialchars($sub_name) ?></th>
                    <?php endforeach; ?>
                    <th width="90px">Total</th>
                    <th width="90px">Average</th>
                    <th width="80px">Rank</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($students as $student): ?>
                    <?php 
                      $rank_class = ''; 
                      $term_rank = $student['term_ranks'][$term_id] ?? null;
                      if ($term_rank == 1) $rank_class = 'rank-1';
                      elseif ($term_rank == 2) $rank_class = 'rank-2';
                      elseif ($term_rank == 3) $rank_class = 'rank-3';
                    ?>
                    <tr class="<?= $rank_class ?>">
                      <td><?= array_search($student['name'], array_column($students, 'name')) + 1 ?></td>
                      <td><?= htmlspecialchars($student['name']) ?></td>
                      <?php foreach ($subjects as $sub_id => $sub_name): ?>
                        <?php 
                          $score = $student['terms'][$term_id][$sub_id] ?? 0;
                          $score_class = '';
                          if ($score >= 80) $score_class = 'high';
                          elseif ($score >= 50) $score_class = 'medium';
                          else $score_class = 'low';
                        ?>
                        <td class="subject-score <?= $score_class ?>"><?= $score ?></td>
                      <?php endforeach; ?>
                      <td class="highlight"><?= $student['term_totals'][$term_id] ?? 0 ?></td>
                      <td class="highlight"><?= number_format($student['term_averages'][$term_id] ?? 0, 1) ?></td>
                      <td>
                        <?php if (isset($student['term_ranks'][$term_id])): ?>
                          <span class="<?= $rank_class ? 'rank-badge' : 'other-rank' ?>">
                            <?=  $student['term_ranks'][$term_id] ?>
                          </span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Overall Tab -->
        <div class="tab-pane fade" id="overall" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th width="40px">#</th>
                  <th>Student Name</th>
                  <?php foreach ($terms as $term_id => $term_name): ?>
                    <th width="90px"><?= htmlspecialchars($term_name) ?> Total</th>
                    <th width="90px"><?= htmlspecialchars($term_name) ?> Avg</th>
                  <?php endforeach; ?>
                  <th width="90px">Overall Total</th>
                  <th width="90px">Overall Avg</th>
                  <th width="80px">Rank</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($students as $student): ?>
                  <?php 
                    $rank_class = '';
                    if ($student['overall_rank'] == 1) $rank_class = 'rank-1';
                    elseif ($student['overall_rank'] == 2) $rank_class = 'rank-2';
                    elseif ($student['overall_rank'] == 3) $rank_class = 'rank-3';
                  ?>
                  <tr class="<?= $rank_class ?>">
                    <td><?= array_search($student['name'], array_column($students, 'name')) + 1 ?></td>
                    <td><?= htmlspecialchars($student['name']) ?></td>
                    <?php foreach ($terms as $term_id => $term_name): ?>
                      <td><?= $student['term_totals'][$term_id] ?? 0 ?></td>
                      <td><?= number_format($student['term_averages'][$term_id] ?? 0, 1) ?></td>
                    <?php endforeach; ?>
                    <td class="highlight"><?= $student['overall_total'] ?></td>
                    <td class="highlight"><?= number_format($student['overall_avg'], 1) ?></td>
                    <td>
                      <span class="<?= $rank_class ? 'rank-badge' : 'other-rank' ?>">
                        <?= $student['overall_rank'] ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Activate tooltips
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });
  });
</script>

</body>
</html>