<?php
session_start();
$conn = mysqli_connect('localhost', 'root', '', 'voting');
if (!$conn) {
    die("Database not connected: " . mysqli_connect_error());
}

if (!isset($_SESSION['number'])) {
    header("Location: login.php");
    exit();
}
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM candidates";
$result = $conn->query($sql);

$candidates = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $candidates[] = $row;
    }
}

if (isset($_POST['vote'])) {
    $userId = $_SESSION['user_id'];
    $hasVotedQuery = "SELECT COUNT(*) AS count FROM votes WHERE user_id = $userId";
    $hasVotedResult = $conn->query($hasVotedQuery);
    $hasVotedRow = $hasVotedResult->fetch_assoc();
    $hasVoted = $hasVotedRow['count'] > 0;

    if ($hasVoted) {
        $errorMessage = "You have already voted. Thank you for your participation!";
    } else {
        if (!isset($_POST['candidates']) || empty($_POST['candidates'])) {
            die("Candidate IDs not provided.");
        }

        $selectedCandidateIds = $_POST['candidates'];

        $deleteSql = "DELETE FROM votes WHERE user_id = $userId";
        if (!$conn->query($deleteSql)) {
            die("Error deleting previous votes: " . $conn->error);
        }

        $insertSql = "INSERT INTO votes (user_id, candidate_id) VALUES ";
        $values = array();
        foreach ($selectedCandidateIds as $candidateId) {
            $values[] = "($userId, $candidateId)";
        }
        $insertSql .= implode(",", $values);

        if ($conn->query($insertSql)) {
            $successMessage = "Vote recorded successfully!";
        } else {
            $errorMessage = "Error recording vote: " . $conn->error;
        }
    }
}
$sql = "SELECT c.id, c.name, c.position, c.image, COUNT(v.id) AS vote_count
        FROM candidates c
        LEFT JOIN votes v ON c.id = v.candidate_id
        GROUP BY c.id, c.name, c.position, c.image
        ORDER BY vote_count DESC";
$result = $conn->query($sql);
$candidateResults = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $candidateResults[] = $row;
    }
}
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
$number = $_SESSION['number'];
$fullname = "";
$sql = "SELECT fullname FROM users WHERE number = '$number'";
$result = mysqli_query($conn, $sql);

if ($result) {
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $fullname = $row['fullname'];
    } else {
        echo "No full name found for the user.";
    }
} else {
    echo "Error executing query: " . mysqli_error($conn);
}
$conn->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>iVOTE Voter Portal</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/dashboard.css">
</head>
<body>
<div class="parent-container">
  <div class="container">
        <div class="sidebar">
            <h2>Menu</h2>
            <ul>
                <li><a href="#candidates">Candidates</a></li>
                <li><a href="#results">Results</a></li>
            </ul>
        </div>
        <div class="content">
            <h1>Welcome, <?php echo $fullname !== "" ? $fullname : "Guest"; ?>!</h1>
            <form method="POST" action="">
                <button class="logout" type="submit" name="logout">Logout</button>
            </form>
            <section id="candidates">
                <div class="candidates">
                    <?php
                    if (!empty($candidates)) {
                        $positions = array(
                            "President" => "PRESIDENTS ",
                            "Vice-President" => "VICE PRESIDENTS ",
                            "Secretary" => "SECRETARIES ",
                            "Treasurer" => "TREASURERS "
                        );
                        $sortedCandidates = array();
                        $positionHeaders = array();
                        foreach ($candidates as $candidate) {
                            $position = $candidate["position"];
                            if (isset($positions[$position])) {
                                $header = $positions[$position];
                                $positionHeaders[$position] = $header;
                            }
                            $sortedCandidates[$position][] = $candidate;
                        }
                        ?>
                        <form method="POST" action="">
                            <?php
                            $hasVoted = isset($_SESSION['hasVoted']) && $_SESSION['hasVoted'];
                            if (!$hasVoted) {
                                foreach ($positionHeaders as $position => $header) {
                                    ?>
                                    <h2><?php echo $header; ?></h2>
                                    <div class="grid">
                                        <?php foreach ($sortedCandidates[$position] as $candidate) { ?>
                                            <div class="candidate">
                                                <img src="<?php echo $candidate["image"]; ?>" alt="<?php echo $candidate["name"]; ?>" class="candidate-image">
                                                <h3><?php echo $candidate["name"]; ?></h3>
                                                <p><?php echo $candidate["description"]; ?></p>
                                                <input type="checkbox" name="candidates[<?php echo $position; ?>]" value="<?php echo $candidate["id"]; ?>">
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <?php
                                }
                                ?>
                                <button class="votebttn" type="submit" name="vote">Vote</button>
                                <?php
                            } else {
                                ?>
                                <p>You have already voted. Thank you for your participation!</p>
                                <?php
                            }
                            ?>
                        </form>
                    <?php } else { ?>
                        <p>No candidates found.</p>
                    <?php } ?>
                </div>
            </section>
            <section id="results">
    <table>
        <thead>
            <tr>
                <th>Position</th>
                <th>Vote Count</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($candidateResults)) {
                $positions = array(
                    "President" => "PRESIDENTS LIST",
                    "Vice-President" => "VICE PRESIDENTS LIST",
                    "Secretary" => "SECRETARIES LIST",
                    "Treasurer" => "TREASURERS LIST"
                );
                
                $sortedResults = array();
                foreach ($positions as $position => $header) {
                    foreach ($candidateResults as $result) {
                        if ($result["position"] === $position) {
                            $sortedResults[$position][] = $result;
                        }
                    }
                }
                
                foreach ($positions as $position => $header) { 
                    if (isset($sortedResults[$position])) { ?>
                        <tr>
                            <th colspan="3"><?php echo $header; ?></th>
                        </tr>
                        <?php 
                            $totalVotes = 0;
                            foreach ($sortedResults[$position] as $result) {
                                $totalVotes += $result["vote_count"];
                            }
                        ?>
                        <?php foreach ($sortedResults[$position] as $result) { ?>
                            <tr>
                                <td><?php echo $result["name"]; ?></td>
                                <td><?php echo $result["vote_count"]; ?></td>
                                <td><?php echo round(($result["vote_count"] / $totalVotes) * 100, 2) . "%"; ?></td>
                            </tr>
                        <?php }
                    }
                }
            } else { ?>
                <tr>
                    <td colspan="3">No candidate results found.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>

            <?php if (isset($errorMessage)) { ?>
                <div class="message error"><?php echo $errorMessage; ?></div>
            <?php } ?>

            <?php if (isset($successMessage)) { ?>
                <div class="message success"><?php echo $successMessage; ?></div>
            <?php } ?>
        </div>
    </div>
    <script src="dashboard.js"></script>
</body>
</html>
