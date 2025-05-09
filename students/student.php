<?php
include '../connect.php';
$grade = isset($_GET['grade']) ? (int) $_GET['grade'] : 9; 

// Search functionality
$searchQuery = '';
if (isset($_POST['search']) && !empty(trim($_POST['search_query']))) {
    $searchQuery = trim($_POST['search_query']);
    $searchQuery = ucwords(strtolower($searchQuery));
}
$sql = "SELECT * FROM students WHERE grade= ? AND  name LIKE ?  ORDER BY name,section";
$stmt = $conn->prepare($sql);
$likeQuery = "%$searchQuery%";
$stmt->bind_param("is", $grade,$likeQuery );
$stmt->execute();
$result = $stmt->get_result();
$num = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css?v=1.1">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom CSS -->
   <style>
         /* Add to your style.css */
.modal-view .no-print-modal {
    display: none !important;
}

/* Hide certain elements in modal view */
.modal-view .student-profile {
    padding: 0;
    margin-bottom: 0;
    background: none;
    box-shadow: none;
}

.modal-view .container {
    padding: 0;
    max-width: 100%;
}

/* Overlay styles */
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.overlay.active {
    opacity: 1;
    visibility: visible;
}

.overlay-content {
    background-color: white;
    padding: 2rem;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    text-align: center;
}  
.profile-photo-container {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 20px;
    background-color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
}

.profile-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-photo {
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 1.2em;
}
   </style>
</head>

<body>
    <header class="header text-center">
        <h1>Grade <?php echo $grade ?> Student Management System  And Record</h1>
        <p class="lead mt-2">School Name Secondary School</p>
    </header>
    
    <button id="off" class="print btn btn-primary" onclick="reloadAndPrint('tt1')" title="Print">
        <i class="bi bi-printer-fill"></i>
    </button>
    
    <a href="../home.php" class="back-home-btn" id="off" title="Go Back Home">
        <i class="bi bi-house-door" style="font-size: 1.5rem;"></i>
    </a>
    <a href="../home.php" class="back-home-btn" id="off" title="ADD NEW">
        <i class="bi bi-house-door" style="font-size: 1.5rem;"></i>
    </a>

    <div class="container">
        <div class="search-box no-print mb-4">
            <form id="form2" method="POST" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="search" class="form-control" placeholder="Search students..." 
                               name="search_query" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button type="submit" class="btn btn-info" name="search">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <a href="./registerAll.php" class="btn btn-primary w-100">
                        <i class="bi bi-table"></i> To Register New student
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="../allMarks/grade9.php" class="btn btn-success w-100">
                        <i class="bi bi-table"></i> Display All Marks
                    </a>
                </div>
            </form>
        </div>

        <div id="tt1" class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Gender</th>
                        <th>Grade</th>
                        <th>Section</th>
                        <th>Contact</th>
                        <th style="width: 50px;">photo</th>
                        <th id="off" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result):
                        while ($row = mysqli_fetch_assoc($result)) :
                            $id = $row['id'];
                            $name = $row['name'];
                            $sex = $row['sex'];
                            $grade = $row['grade'];
                            $section = $row['section'];
                            $username = $row['username'];
                            $password = $row['password'];
                            $phone = $row['phone'];
                            $photo = $row['photo'];
                            $num++;
                                ?>
                            <tr>
                                <td class="num"><?= htmlspecialchars($num) ?></td>
                                <td class="uu"><?= htmlspecialchars($name) ?></td>
                                <td class="uu"><?= htmlspecialchars($sex) ?></td>
                                <td class="uu"><?= htmlspecialchars($grade) ?></td>
                                <td class="uu"><?= htmlspecialchars($section) ?></td>
                                <td class="uu"><?= htmlspecialchars($phone) ?></td>
                                <td>
                                    <div class="profile-photo-container">
                                        <?php if (!empty($photo) && file_exists($photo)): ?>
                                            <img src="<?php echo htmlspecialchars($photo); ?>" class="profile-photo" alt="Student Photo">
                                        <?php else: ?>
                                            <div class="profile-photo no-photo"><i class="bi bi-person"></i></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td id="off" class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="update.php?updateid=<?= $id?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger" onclick="showOverlay(<?=  $id ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <!-- <button class="btn btn-sm btn-info view-student-btn" data-student-id="<?= $id ?>">
                                            <i class="bi bi-info-circle"></i> Info
                                        </button> -->
                                        
                                        <a href="./info.php?id=<?= $id?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-info-circle"></i>
                                        </a> 
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="text-center">No students registered yet</td></tr>
                    <?php endif; ?>  
                </tbody>
            </table>
        </div>
    </div>

    <!-- Overlay (hidden by default) -->
    <div id="overlay" class="overlay">
        <div class="overlay-content">
            <h4>Are you sure you want to delete this student?</h4>
            <p class="text-muted">This action cannot be undone.</p>
            <div class="d-flex justify-content-center gap-3 mt-3">
                <button class="btn btn-danger px-4" onclick="deleteItem()">
                    <i class="bi bi-trash"></i> Delete
                </button>
                <button class="btn btn-secondary px-4" onclick="closeOverlay()">
                    <i class="bi bi-x"></i> Cancel
                </button>
            </div>
        </div>
    </div>
<!-- Student Info Modal -->
<div class="modal fade" id="studentInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentInfoContent">
                Loading student information...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printModalContent()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="main.js"></script>
    <script>
function printModalContent() {
    const printContent = document.getElementById('studentInfoContent').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    
    // Re-initialize any necessary scripts
    window.location.reload();
}
    </script>
    <script>
    // Overlay functions
        let currentItemId = null;
        
        function showOverlay(id) {
            currentItemId = id;
            document.getElementById('overlay').classList.add('active');
        }
        
        function closeOverlay() {
            document.getElementById('overlay').classList.remove('active');
        }
        
        // Function to handle the delete action
            function deleteItem() {
                if (currentItemId !== null) {
                    // Send the delete request using AJAX
                    const xhr = new XMLHttpRequest();
                    xhr.open("GET", "delete.php?deleteid=" + currentItemId, true);
                    xhr.onload = function() {
                        if (xhr.status == 200) {
                            // Successfully deleted, hide the overlay and reload the page
                            closeOverlay();
                            alert("Item deleted successfully!");
                            location.reload(); // Optionally reload the page to reflect changes
                        } else {
                            alert("Error deleting item.");
                        }
                    };
                    xhr.send();
                } else {
                    alert("No item selected for deletion.");
                }
            }
        </script>
</body>
</html>