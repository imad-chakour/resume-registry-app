<?php
require_once "pdo.php";
session_start();

if (!isset($_SESSION['name'])) {
    die('ACCESS DENIED');
}

if (isset($_POST['cancel'])) {
    header("Location: index.php");
    return;
}

if (isset($_POST['delete']) && isset($_POST['profile_id'])) {
    $stmt = $pdo->prepare('DELETE FROM profile WHERE profile_id = :id');
    $stmt->execute(array(':id' => $_POST['profile_id']));
    $_SESSION['success'] = "Record deleted";
    header("Location: index.php");
    return;
}

if (!isset($_GET['profile_id']) || !is_numeric($_GET['profile_id'])) {
    $_SESSION['error'] = "Invalid request";
    header("Location: index.php");
    return;
}

$stmt = $pdo->prepare("SELECT * FROM profile WHERE profile_id = :id");
$stmt->execute(array(':id' => $_GET['profile_id']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row === false) {
    $_SESSION['error'] = "Record not found";
    header("Location: index.php");
    return;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Chakour Imad's Delete Profile</title>
    <?php require_once "bootstrap.php"; ?>
    <link rel="stylesheet" type="text/css" href="css/delete.css">
</head>
<body>
    <div class="container">
        <h1>Deleteing Profile</h1>
        <p>First Name : <?= htmlentities($row['first_name']) ?></p>
        <p>Last Name : <?= htmlentities($row['last_name']) ?></p>
        <form method="post">
            <input type="hidden" name="profile_id" value="<?= $row['profile_id'] ?>">
            <input type="submit" name="delete" value="Delete">
            <input type="submit" name="cancel" value="Cancel">
        </form>
    </div>
</body>
</html>
