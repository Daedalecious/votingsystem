<?php
session_start();

if (isset($_SESSION["number"])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $number = $_POST["number"];
    $password = $_POST["password"];

    $conn = mysqli_connect('localhost', 'root', '', 'voting');
    if (!$conn) {
        die("Database not connected: " . mysqli_connect_error());
    }

    $query = "SELECT * FROM users WHERE number = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        if ($password === $row["password"]) {
            $_SESSION["number"] = $row["number"];
            $_SESSION["role"] = $row["role"];
            $_SESSION["user_id"] = $row["id"];

            if ($row["role"] === "admin") {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        }
    }

    $adminQuery = "SELECT * FROM admin WHERE number = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $adminQuery);
    mysqli_stmt_bind_param($stmt, "s", $number);
    mysqli_stmt_execute($stmt);
    $adminResult = mysqli_stmt_get_result($stmt);

    if ($adminResult && mysqli_num_rows($adminResult) === 1) {
        $adminRow = mysqli_fetch_assoc($adminResult);

        if ($password === $adminRow["password"]) {
            $_SESSION["number"] = $adminRow["number"];
            $_SESSION["role"] = "admin";
            header("Location: admin.php");
            exit();
        }
    }

    $error = "Invalid ID number or password";
}

if (isset($_POST["vote"])) {
    $candidateID = $_POST["candidate"];

    if (isset($_SESSION["number"])) {
        $userID = $_SESSION["user_id"];

        $voteQuery = "INSERT INTO votes (user_id, candidate_id) VALUES ('$userID', '$candidateID')";
        mysqli_query($conn, $voteQuery);
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Welcom to iVote</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
</head>
<body>
    <div class="container">
        <img src="logo/CTU_new_logo.png" alt="Logo" class="logo" style="width: 140px; height: 140px;"> 
        <h2>Login</h2>
        
        <?php if (isset($error)) { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>
        <form method="POST" action="">
            <input type="number" name="number" placeholder="Student ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
