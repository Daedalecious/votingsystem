<?php

$conn = mysqli_connect('localhost', 'root', '', 'voting');
if (!$conn) {
    die("Database not connected: " . mysqli_connect_error());
}


$query = "SELECT candidates.id, candidates.name, candidates.position, candidates.image, IFNULL(COUNT(votes.id), 0) AS vote_count
          FROM candidates
          LEFT JOIN votes ON candidates.id = votes.candidate_id
          GROUP BY candidates.id";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Error retrieving updated results: " . mysqli_error($conn));
}
$candidateResults = mysqli_fetch_all($result, MYSQLI_ASSOC);


echo json_encode($candidateResults);
?>
