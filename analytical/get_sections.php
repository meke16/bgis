<?php
include ("db_connect.php");

header('Content-Type: application/json');

if (!isset($_GET['grade'])) {
    die(json_encode([]));
}

$grade = $_GET['grade'];
$report = new MarksReport($pdo);
$sections = $report->getAvailableSections($grade);

echo json_encode($sections);
?>