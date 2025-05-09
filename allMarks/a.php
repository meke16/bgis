<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Performance Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    :root {
      --primary-color: #4361ee;
      --secondary-color: #3f37c9;
      --accent-color: #4cc9f0;
      --success-color: #4ad66d;
      --danger-color: #f72585;
      --light-color: #f8f9fa;
      --dark-color: #212529;
      --border-radius: 8px;
      --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s ease;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f7fb;
      color: var(--dark-color);
      line-height: 1.6;
    }

    .dashboard-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 20px;
    }

    .header {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      padding: 20px;
      border-radius: var(--border-radius);
      margin-bottom: 25px;
      box-shadow: var(--box-shadow);
    }

    .header h2 {
      margin: 0;
      font-weight: 600;
    }

    .filter-card {
      background-color: white;
      border-radius: var(--border-radius);
      padding: 20px;
      margin-bottom: 25px;
      box-shadow: var(--box-shadow);
    }

    .card {
      background-color: white;
      border-radius: var(--border-radius);
      border: none;
      box-shadow: var(--box-shadow);
      transition: var(--transition);
      margin-bottom: 20px;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .card-header {
      background-color: var(--primary-color);
      color: white;
      border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
      padding: 15px 20px;
      font-weight: 600;
    }

    .nav-pills .nav-link {
      color: var(--dark-color);
      font-weight: 500;
      border-radius: 5px;
      margin-right: 5px;
      transition: var(--transition);
    }

    .nav-pills .nav-link.active {
      background-color: var(--primary-color);
      color: white;
    }

    .table-responsive {
      border-radius: var(--border-radius);
      overflow: hidden;
    }

    .table {
      margin-bottom: 0;
    }

    .table th {
      background-color: var(--primary-color);
      color: white;
      font-weight: 500;
      padding: 12px 15px;
      vertical-align: middle;
    }

    .table td {
      padding: 12px 15px;
      vertical-align: middle;
    }

    .table-hover tbody tr:hover {
      background-color: rgba(67, 97, 238, 0.05);
    }

    .rank-1 {
      background-color: rgba(255, 215, 0, 0.2) !important;
    }

    .rank-2 {
      background-color: rgba(192, 192, 192, 0.2) !important;
    }

    .rank-3 {
      background-color: rgba(205, 127, 50, 0.2) !important;
    }

    .rank-badge {
      display: inline-block;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      text-align: center;
      line-height: 24px;
      font-weight: bold;
      font-size: 12px;
    }

    .rank-1 .rank-badge {
      background-color: gold;
      color: #856404;
    }

    .rank-2 .rank-badge {
      background-color: silver;
      color: #343a40;
    }

    .rank-3 .rank-badge {
      background-color: #cd7f32;
      color: white;
    }

    .other-rank .rank-badge {
      background-color: #e9ecef;
      color: var(--dark-color);
    }

    .highlight {
      font-weight: 600;
      color: var(--primary-color);
    }

    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .btn-primary:hover {
      background-color: var(--secondary-color);
      border-color: var(--secondary-color);
    }

    .btn-outline-primary {
      color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .btn-outline-primary:hover {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .badge-success {
      background-color: var(--success-color);
    }

    .badge-danger {
      background-color: var(--danger-color);
    }

    .badge-warning {
      background-color: #ffc107;
    }

    .progress {
      height: 8px;
      border-radius: 4px;
    }

    .progress-bar {
      background-color: var(--primary-color);
    }

    .action-buttons .btn {
      margin-right: 5px;
    }

    .subject-score {
      font-weight: 500;
    }

    .subject-score.high {
      color: var(--success-color);
    }

    .subject-score.medium {
      color: #ffc107;
    }

    .subject-score.low {
      color: var(--danger-color);
    }

    @media print {
      .no-print, .action-buttons, .filter-card {
        display: none !important;
      }

      body {
        background: white;
        padding: 0;
      }

      .dashboard-container {
        padding: 0;
      }

      .header {
        background: white !important;
        color: black !important;
        box-shadow: none !important;
        padding: 10px 0 !important;
      }

      .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
      }

      .table th {
        background-color: white !important;
        color: black !important;
        border-bottom: 2px solid #ddd !important;
      }
    }
  </style>
</head>
<body>

<?php
  include '../connect.php';

  $grade = isset($_GET['grade']) ? (int) $_GET['grade'] : 9;
  $section = isset($_GET['section']) ? mysqli_real_escape_string($conn, $_GET['section']) : 'A';
?>

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
        <select name="grade" id="grade" class="form-select">
          <?php for ($g = 1; $g <= 12; $g++): ?>
            <option value="<?= $g ?>" <?= $grade == $g ? 'selected' : '' ?>><?= $g ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label for="section" class="form-label">Section</label>
        <select name="section" id="section" class="form-select">
          <?php foreach (['A', 'B', 'C', 'D'] as $sec): ?>
            <option value="<?= $sec ?>" <?= $section == $sec ? 'selected' : '' ?>><?= $sec ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
      </div>
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

        // Fetch marks
        $marks_res = mysqli_query($conn, "
          SELECT sm.student_id, sm.subject_id, sm.semester_id, sm.mark
          FROM student_marks sm
          JOIN students s ON sm.student_id = s.id
          WHERE s.grade = $grade AND s.section = '$section'
        ");

        foreach ($marks_res as $row) {
          $sid = $row['student_id'];
          $sub = $row['subject_id'];
          $term = $row['semester_id'];
          $mark = $row['mark'];

          $students[$sid]['terms'][$term][$sub] = $mark;
          $students[$sid]['term_totals'][$term] = ($students[$sid]['term_totals'][$term] ?? 0) + $mark;
          $students[$sid]['overall_total'] += $mark;
        }

        // Calculate term averages and ranks for each term
        foreach ($terms as $term_id => $term_name) {
          // Create a temporary array for this term's totals
          $term_totals = [];
          foreach ($students as $sid => $student) {
            $term_totals[$sid] = $student['term_totals'][$term_id] ?? 0;
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
            $students[$sid]['term_averages'][$term_id] = count($subjects) ? $students[$sid]['term_totals'][$term_id] / count($subjects) : 0;
            
            $prev_total = $total;
            $rank++;
          }
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
                      if ($student['term_ranks'][$term_id] == 1) $rank_class = 'rank-1';
                      elseif ($student['term_ranks'][$term_id] == 2) $rank_class = 'rank-2';
                      elseif ($student['term_ranks'][$term_id] == 3) $rank_class = 'rank-3';
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
                        <span class="<?= $rank_class ? 'rank-badge' : 'other-rank' ?>">
                          <?= $student['term_ranks'][$term_id] ?? '-' ?>
                        </span>
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