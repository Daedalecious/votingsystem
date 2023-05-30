<?php
session_start();


if (!isset($_SESSION["number"])) {
    header("Location: login.php");
    exit();
}


if (!isset($_POST["candidate"])) {
    header("Location: dashboard.php");
    exit();
}


$candidateID = $_POST["candidate"];


$userID = $_SESSION["user_id"];


$conn = mysqli_connect('localhost', 'root', '', 'voting');
if (!$conn) {
    die("Database not connected: " . mysqli_connect_error());
}


$checkQuery = "SELECT * FROM votes WHERE user_id = '$userID' AND candidate_id = '$candidateID'";
$checkResult = mysqli_query($conn, $checkQuery);

if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    
    header("Location: dashboard.php");
    exit();
}


$voteQuery = "INSERT INTO votes (user_id, candidate_id) VALUES ('$userID', '$candidateID')";
mysqli_query($conn, $voteQuery);


header("Location: dashboard.php");
exit();
?>
