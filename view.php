<?php
require_once "pdo.php";
session_start();

if (!isset($_SESSION['name'])) {
    die('ACCESS DENIED');
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

$stmt = $pdo->prepare("SELECT * FROM position WHERE profile_id = :id");
$stmt->execute(array(':id' => $_GET['profile_id']));
$positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM education JOIN institution ON education.institution_id = institution.institution_id WHERE profile_id = :id");
$stmt->execute(array(':id' => $_GET['profile_id']));
$educations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chakour Imad's Profile information</title>
    <?php require_once "bootstrap.php"; ?>
    <link rel="stylesheet" type="text/css" href="css/profile.css">
</head>
<body>
    <div class="container">
        <h1>Profile information</h1>
        <p>First Name : <?= htmlentities($row['first_name']) ?></p>
        <p>Last Name : <?= htmlentities($row['last_name']) ?></p>
        <p>Email : <?= htmlentities($row['email']) ?></p>
        <p>Headline : <?= htmlentities($row['headline']) ?></p>
        <p>Summary : <?= htmlentities($row['summary']) ?></p>
        <p>Education</p>
        <ul>
            <?php foreach ($educations as $education): ?>
                <li><?= htmlentities($education['year']) ?>: <?= htmlentities($education['name']) ?></li>
            <?php endforeach; ?>
        </ul>
        <p>Position</p>
        <ul>
            <?php foreach ($positions as $position): ?>
                <li><?= htmlentities($position['year']) ?>: <?= htmlentities($position['description']) ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="index.php">Done</a>
    </div>
</body>
</html>