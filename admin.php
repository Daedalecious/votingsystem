<?php
session_start();
if (!isset($_SESSION["number"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

$conn = mysqli_connect('localhost', 'root', '', 'voting');
if (!$conn) {
    die("Database not connected: " . mysqli_connect_error());
}

$query = "SELECT * FROM admin WHERE number = 'admin' LIMIT 1";
$result = mysqli_query($conn, $query);
if (!$result || mysqli_num_rows($result) === 0) {
   
    $password = password_hash('admin', PASSWORD_DEFAULT);
    $insertQuery = "INSERT INTO admin (number, password) VALUES ('admin', '$password')";
    $insertResult = mysqli_query($conn, $insertQuery);
    if (!$insertResult) {
        die("Error inserting admin account: " . mysqli_error($conn));
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["image"])) {
    $targetDir = "uploads/candidates/";
    $targetFile = $targetDir . basename($_FILES["image"]["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check !== false) {
        if ($_FILES["image"]["size"] > 1024000) {
            echo "<div class='message-container'><div class='error-message'>File size exceeds the limit.</div></div>";
        } else {
            $allowedFormats = array("jpg", "jpeg", "png");
            if (in_array($imageFileType, $allowedFormats)) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                    $name = $_POST["name"];
                    $description = $_POST["description"];
                    $position = $_POST["position"];

                    $insertQuery = "INSERT INTO candidates (name, description, position, image) VALUES ('$name', '$description', '$position', '$targetFile')";
                    $insertResult = mysqli_query($conn, $insertQuery);
                    if ($insertResult) {
                        echo "<div class='message-container'><div class='success-message'>Candidate added successfully.</div></div>";
                    } else {
                        echo "<div class='message-container'><div class='error-message'>Error adding candidate: " . mysqli_error($conn) . "</div></div>";
                    }
                } else {
                    echo "<div class='message-container'><div class='error-message'>Error uploading file.</div></div>";
                }
            } else {
                echo "<div class='message-container'><div class='error-message'>Invalid file format. Only JPG, JPEG, and PNG files are allowed.</div></div>";
            }
        }
    } else {
        echo "<div class='message-container'><div class='error-message'>File is not an image.</div></div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_candidate"])) {
    $candidateId = $_POST["candidate_id"];
    $name = $_POST["name"];
    $description = $_POST["description"];
    $position = $_POST["position"];

    $updateQuery = "UPDATE candidates SET name = '$name', description = '$description', position = '$position' WHERE id = $candidateId";
    $updateResult = mysqli_query($conn, $updateQuery);
    if ($updateResult) {
        echo "<div class='centered'>Candidate updated successfully.</div>";
    } else {
        echo "<div class='centered'>Error updating candidate: " . mysqli_error($conn) . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["candidate_id"])) {
    $candidateId = $_POST["candidate_id"];
    $deleteQuery = "DELETE FROM candidates WHERE id = $candidateId";
    $deleteResult = mysqli_query($conn, $deleteQuery);
    if ($deleteResult) {
        echo "<div class='message-container'><div class='success-message'>Candidate removed successfully.</div></div>";
    } else {
        echo "<div class='message-container'><div class='error-message'>" . mysqli_error($conn) . "</div></div>";
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["user_id"])) {
    $userId = $_POST["user_id"];
    $deleteQuery = "DELETE FROM users WHERE id = $userId";
    $deleteResult = mysqli_query($conn, $deleteQuery);
    if ($deleteResult) {
        echo "<div class='message-container'><div class='success-message'>User account removed successfully.</div></div>";
    } else {
        echo "<div class='message-container'><div class='error-message'>" . mysqli_error($conn) . "</div></div>";
    }
}


$query = "SELECT * FROM candidates";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Error retrieving candidates: " . mysqli_error($conn));
}
$candidates = mysqli_fetch_all($result, MYSQLI_ASSOC);

$countQuery = "SELECT COUNT(DISTINCT user_id) AS total FROM votes";
$countResult = mysqli_query($conn, $countQuery);
if (!$countResult) {
    die("Error counting voted users: " . mysqli_error($conn));
}
$row = mysqli_fetch_assoc($countResult);
$votedUsers = $row['total'];
if (isset($_POST["logout"])) {
    session_destroy();

    header("Location: index.php");
    exit();
}
$positions = array();
$query = "SELECT position, MAX(votes) AS max_votes FROM candidates GROUP BY position";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Error retrieving winners: " . mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($result)) {
    $position = $row['position'];
    $maxVotes = $row['max_votes'];

    $winnerQuery = "SELECT * FROM candidates WHERE position = '$position' AND votes = $maxVotes";
    $winnerResult = mysqli_query($conn, $winnerQuery);
    if (!$winnerResult) {
        die("Error retrieving winner: " . mysqli_error($conn));
    }
    $winnerRow = mysqli_fetch_assoc($winnerResult);

    $winner = array(
        "candidate_id" => $winnerRow['id'],
        "candidate_name" => $winnerRow['name'],
        "votes" => $maxVotes
    );

    $positions[$position] = $winner;
}


$resultsQuery = "SELECT c.position, c.name AS candidate_name, COUNT(v.candidate_id) AS votes
                 FROM candidates AS c
                 LEFT JOIN votes AS v ON c.id = v.candidate_id
                 GROUP BY c.position, c.name";
$resultsResult = mysqli_query($conn, $resultsQuery);
if (!$resultsResult) {
    die("Error retrieving voting results: " . mysqli_error($conn));
}
$results = array();
while ($row = mysqli_fetch_assoc($resultsResult)) {
    $results[] = $row;

}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["number"], $_POST["password"], $_POST["email"], $_POST["fullname"], $_POST["course"])) {
    $number = $_POST["number"];
    $password = $_POST["password"];
    $email = $_POST["email"];
    $fullname = $_POST["fullname"];
    $course = $_POST["course"];

    $checkQuery = "SELECT id FROM users WHERE number = ?";
    $stmt = mysqli_prepare($conn, $checkQuery);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $number);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Student ID already exists.";
        }

        mysqli_stmt_close($stmt);
    }

    if (isset($error)) { ?>
        <div class="message-container">
            <p class="error-message"><?php echo $error; ?></p>
        </div>
    <?php } else {
        $insertQuery = "INSERT INTO users (fullname, number, password, email, course) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insertQuery);

        mysqli_stmt_bind_param($stmt, "sssss", $fullname, $number, $password, $email, $course);

        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $success = "Voter registration processed!";
        } else {
            $error = "Failed to register. Please try again later.";
        }

        if (isset($error)) { ?>
            <div class="message-container">
                <p class="error-message"><?php echo $error; ?></p>
            </div>
        <?php } elseif (isset($success)) { ?>
            <div class="message-container">
                <p class="success-message"><?php echo $success; ?></p>
            </div>
        <?php }

            mysqli_stmt_close($stmt);
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>iVOTE Admin Dashboard</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/admin.css">
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Menu</h2>
            <ul>
            <ul>
          
                <li><a href="#addCandidate">Add Candidates</a></li>
                <li><a href="#editCandidate">Edit candidates</a></li>
                <li><a href="#removeUser">User Details</a></li>
                <li><a href="#addnewuser">Add Users</a></li>
                <li><a href="#votedUsers">Voted Users</a></li>
                <li><a href="#results">Results</a></li>
                
            </ul>
        </div>
        <div class="content">
            <h1>Welcome, Admin!</h1>
      <form method="POST" action="">
                    <button class="logout" type="submit" name="logout">Logout</button>
                </form>
                <section id="addCandidate">
    <form class="add-candidate-form" method="POST" action="" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Candidate Name" required>
        <input type="text" name="description" placeholder="Description" required>
        <select name="position" required>
            <option value="">Position</option>
            <option value="President">President</option>
            <option value="Vice-President">Vice President</option>
            <option value="Secretary">Secretary</option>
            <option value="Treasurer">Treasurer</option>
        </select>
        <input type="file" name="image" accept="image/*" required>
        <button type="submit">Add Candidate</button>
    </form>
</section>

            <section id="editCandidate">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Position</th>
                            <th>Image</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidates as $candidate) { ?>
                            <tr>
                                <td><?php echo $candidate["name"]; ?></td>
                                <td><?php echo $candidate["description"]; ?></td>
                                <td><?php echo $candidate["position"]; ?></td>
                                <td><img src="<?php echo $candidate["image"]; ?>" width="50" height="50"></td>
                                <td>
                                    <a href="edit_candidate.php?id=<?php echo $candidate["id"]; ?>">Edit</a>
                                    <form method="POST" action="">
                                    <form method="POST" action="" onsubmit="event.preventDefault();">
                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate["id"]; ?>">
                                        <button type="submit" name="remove_candidate">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </section>
            <section id="removeUser">
    <table>
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Student ID</th>
                <th>Email</th>
                <th>Password</th>
                <th>Department</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $userQuery = "SELECT * FROM users";
            $userResult = mysqli_query($conn, $userQuery);
            if (!$userResult) {
                die("Error retrieving users: " . mysqli_error($conn));
            }
            $users = mysqli_fetch_all($userResult, MYSQLI_ASSOC);
            foreach ($users as $user) {
                ?>
                <tr>
                    <td><?php echo $user["fullname"]; ?></td>
                    <td><?php echo $user["number"]; ?></td>
                    <td><?php echo isset($user["email"]) ? $user["email"] : ""; ?></td>
                    <td><?php echo $user["password"]; ?></td>
                    <td><?php echo $user["course"]; ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="user_id" value="<?php echo $user["id"]; ?>">
                            <button type="submit" name="remove_user">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>
<section id="addnewuser">

    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="addUserForm">
        <input type="number" name="number" placeholder="Student ID" required>
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="email" name="email" placeholder="Email" required>
        <select name="course" required>
            <option value="">Select Department</option>
            <option value="BSIT">BSIT</option>
            <option value="BSHM">BSHM</option>
            <option value="BSED">BSED</option>
            <option value="POLSCI">POLSCI</option>
        </select>
        <button type="submit">Add</button>
    </form>
</section>
<section id="votedUsers">
    <div class="voted-users">
        <?php
        $sql = "SELECT DISTINCT u.number, u.fullname, u.course 
                FROM users u
                INNER JOIN votes v ON u.id = v.user_id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo '<table>';
            echo '<tr><th>Student ID</th><th>Student Name</th><th>Department</th></tr>';

            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row["number"] . '</td>';
                echo '<td>' . $row["fullname"] . '</td>';
                echo '<td>' . $row["course"] . '</td>';
                echo '</tr>';
            }

            echo '</table>';
            echo '<span class="label">Total voted users: ' . $result->num_rows . '</span>';
        } else {
            echo "No voted users found.";
        }

        $conn->close();
        ?>
    </div>
</section>

<section id="results">
    <table>
        <thead>
            <tr>
                <th>Position</th>
                <th>Candidate Name</th>
                <th>Total Votes</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $positionVotes = array();
            $positionTotalVotes = array();
            
            // Initialize positionVotes and positionTotalVotes arrays
            foreach ($results as $result) {
                $position = $result['position'];
                $positionVotes[$position] = 0;
                $positionTotalVotes[$position] = 0;
            }
            
            // Calculate position-wise total votes
            foreach ($results as $result) {
                $position = $result['position'];
                $positionTotalVotes[$position] += $result['votes'];
            }
            
            // Calculate and display results
            foreach ($results as $result) {
                $position = $result['position'];
                $votes = $result['votes'];
                $percentage = ($votes / $positionTotalVotes[$position]) * 100;
                $positionVotes[$position] += $votes;
            ?>
                <tr>
                    <td><?php echo $position; ?></td>
                    <td><?php echo $result['candidate_name']; ?></td>
                    <td><?php echo $votes; ?></td>
                    <td><?php echo round($percentage, 2); ?>%</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>



        </div>
    </div>
        </div>
    </div>
    <script src="admin.js"></script>
    
</body>
</html>
