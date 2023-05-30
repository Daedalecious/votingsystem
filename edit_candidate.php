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

if (!isset($_GET["id"])) {
    header("Location: admin.php");
    exit();
}

$candidateId = $_GET["id"];

$query = "SELECT * FROM candidates WHERE id = $candidateId";
$result = mysqli_query($conn, $query);
if (!$result || mysqli_num_rows($result) === 0) {
    echo "Candidate not found.";
    exit();
}

$candidate = mysqli_fetch_assoc($result);


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"];
    $description = $_POST["description"];
    $position = $_POST["position"];

 
    if (isset($_FILES["image"]) && $_FILES["image"]["size"] > 0) {
        $targetDir = "uploads/candidates/";
        $targetFile = $targetDir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

 
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {

            if ($_FILES["image"]["size"] > 1024000) {
                echo "File size exceeds the limit.";
                exit();
            }

            
            $allowedFormats = array("jpg", "jpeg", "png");
            if (!in_array($imageFileType, $allowedFormats)) {
                echo "Invalid file format. Only JPG, JPEG, and PNG files are allowed.";
                exit();
            }

          
            if (file_exists($candidate["image"])) {
                unlink($candidate["image"]);
            }

            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                
                $updateQuery = "UPDATE candidates SET name = '$name', description = '$description', position = '$position', image = '$targetFile' WHERE id = $candidateId";
                $updateResult = mysqli_query($conn, $updateQuery);
                if ($updateResult) {
                    echo "<div class='success-message'>Candidate updated successfully.</div>";
                    $candidate["image"] = $targetFile;
                } else {
                    echo "<div class='error-message'>Error updating candidate: " . mysqli_error($conn) . "</div>";
                }
            } else {
                echo "<div class='error-message'>Error uploading file.</div>";
                exit();
            }
        } else {
            echo "<div class='error-message'>File is not an image.</div>";
            exit();
        }
    } else {
        
        $updateQuery = "UPDATE candidates SET name = '$name', description = '$description', position = '$position' WHERE id = $candidateId";
        $updateResult = mysqli_query($conn, $updateQuery);
        if ($updateResult) {
            echo "<div class='success-message'>Candidate updated successfully.</div>";
        } else {
            echo "<div class='error-message'>Error updating candidate: " . mysqli_error($conn) . "</div>";
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Candidate</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/edit_candidate.css">
</head>
<body>
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="name">Name:</label>
        <input type="text" name="name" value="<?php echo $candidate["name"]; ?>" required>

        <label for="description">Description:</label>
        <textarea name="description" rows="5" required><?php echo $candidate["description"]; ?></textarea>

        <select name="position" required>
            <option value="">Position</option>
            <option value="President">President</option>
            <option value="Vice-President">Vice President</option>
            <option value="Secretary">Secretary</option>
            <option value="Treasurer">Treasurer</option>
        </select>

        <label for="image">Image:</label>
        <input type="file" name="image">

        <?php if (!empty($candidate["image"])): ?>
            <img src="<?php echo $candidate["image"]; ?>" alt="Candidate Image">
        <?php endif; ?>

        <input type="submit" value="Update">
    </form>
    <button onclick="goBack()">Back</button>

    <script>
        function goBack() {
            window.location.href = "admin.php";
        }

    </script>

</body>
</html>
