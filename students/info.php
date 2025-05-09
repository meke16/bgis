<?php
include_once '../connect.php';

// Get the student ID from URL parameter
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("Invalid student ID");
}

// Get student information
$stu_info = $conn->query("SELECT * FROM students WHERE id='$id'")->fetch_assoc();

if (!$stu_info) {
    die("Student not found");
}

// Get marks information
$marksData = $conn->query("SELECT * FROM marks WHERE student_id='$id'")->fetch_assoc();

// Calculate averages if marks exist
if ($marksData) {
    $subjects = ['sub1', 'sub2', 'sub3', 'sub4', 'sub5', 'sub6', 'sub7', 'sub8', 'sub9', 'sub10', 'sub11', 'sub12'];
    $sem1_total = 0;
    $sem2_total = 0;
    $subject_count = 0;
    
    foreach ($subjects as $subject) {
        $sem1 = $marksData[$subject] ?? 0;
        $sem2 = $marksData[$subject . '_second_semester'] ?? 0;
        
        $sem1_total += $sem1;
        $sem2_total += $sem2;
        $subject_count++;
    }
    
    $total_mark = ($sem1_total + $sem2_total) / 2;
    $sem1_avg = $subject_count > 0 ? $sem1_total / $subject_count : 0;
    $sem2_avg = $subject_count > 0 ? $sem2_total / $subject_count : 0;
    $overall_avg = ($sem1_avg + $sem2_avg) / 2;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Information - <?php echo htmlspecialchars($stu_info['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .student-profile { background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .info-list { list-style-type: none; padding: 0; }
        .info-list li { padding: 8px 0; border-bottom: 1px solid #eee; }
        .table-responsive { overflow-x: auto; }
        .print-btn { margin-top: 20px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: white; }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Student Report Card</h1>
        
        <!-- Student Profile Section -->
        <div class="student-profile p-4 mb-4">
            <div class="row">
                <!-- Photo Column -->
                <div class="col-md-4 text-center">
                    <?php if (!empty($stu_info['photo'])): ?>
                        <img src="<?php echo htmlspecialchars($stu_info['photo']); ?>" 
                             class="img-fluid rounded-circle mb-3" 
                             style="width: 200px; height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3"
                             style="width: 200px; height: 200px; margin: 0 auto;">
                            <span class="text-muted">No Photo</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Information Column -->
                <div class="col-md-8">
                    <h3 class="mb-4">Student Information</h3>
                    <ul class="info-list">
                        <li><strong>Full Name:</strong> <?php echo htmlspecialchars($stu_info['name']); ?></li>
                        <li><strong>Gender:</strong> <?php echo htmlspecialchars($stu_info['sex']); ?></li>
                        <li><strong>Grade:</strong> <?php echo htmlspecialchars($stu_info['grade']); ?></li>
                        <li><strong>Section:</strong> <?php echo htmlspecialchars($stu_info['section']); ?></li>
                        <li class="no-print"><strong>Student ID:</strong> <?php echo htmlspecialchars($stu_info['id']); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Academic Performance Section -->
        <?php if ($marksData): ?>
        <div class="marks-table p-4 mb-4 bg-white rounded">
            <h4 class="mb-4 text-primary">Academic Performance</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Semester 1</th>
                            <th>Semester 2</th>
                            <th>Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subjects = ['sub1', 'sub2', 'sub3', 'sub4', 'sub5', 'sub6','sub7','sub8','sub9','sub10','sub11','sub12'];
                        foreach ($subjects as $subject) {
                            $sem1 = $marksData[$subject] ?? 0;
                            $sem2 = $marksData[$subject . '_second_semester'] ?? 0;
                            $avg = ($sem1 + $sem2) / 2;
                        ?>
                            <tr>
                                <td><?= ucwords(str_replace('_', ' ', $subject)) ?></td>
                                <td><?= $sem1 ?></td>
                                <td><?= $sem2 ?></td>
                                <td><?= number_format($avg, 2) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot class="table-group-divider">
                        <tr style="font-weight: bold; background-color: #f8f9fa;">
                            <td>Total</td>
                            <td><?= $sem1_total ?? 0 ?></td>
                            <td><?= $sem2_total ?? 0 ?></td>
                            <td><?= number_format($total_mark ?? 0, 2) ?></td>
                        </tr>
                        <tr style="font-weight: bold; background-color: #f8f9fa;">
                            <td>Average</td>
                            <td><?= number_format($sem1_avg ?? 0, 2) ?></td>
                            <td><?= number_format($sem2_avg ?? 0, 2) ?></td>
                            <td><?= number_format($overall_avg ?? 0, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php else: ?>
            <div class="alert alert-info">No academic records found for this student.</div>
        <?php endif; ?>
        
        <!-- Print Button -->
        <div class="text-center mb-5 no-print">
            <button onclick="window.print()" class="btn btn-primary print-btn">
                Print Report
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>