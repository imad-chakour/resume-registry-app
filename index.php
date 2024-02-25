<?php
require_once "pdo.php";
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Imad Chakour - Resume Registry</title>
    <?php require_once "bootstrap.php"; ?>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Imad Chakour's Resume Registry</h1>

    <?php if (isset($_SESSION['name'])) : ?>
    <p><a href="logout.php">Logout</a></p>
    <p><a href="add.php">Add New Entry</a></p>
    <?php else : ?>
    <p><a href="login.php">Please log in</a></p>
    <?php endif; ?>

    <?php
    if (isset($_SESSION['success'])) {
        echo '<p style="color: green;">' . htmlentities($_SESSION['success']) . "</p>\n";
        unset($_SESSION['success']);
    }

    $stmt = $pdo->query("SELECT profile_id, user_id, CONCAT(first_name, ' ', last_name) AS name, email, headline, summary FROM profile");
    
    // Check if there are any rows
    if ($stmt->rowCount() > 0) {
        // Start the table and define the table header
        echo "<table>\n";
        echo "<thead><tr><th>Name</th><th>Headline</th>";
        if (isset($_SESSION['name'])) {
          echo "<th>Action</th>";
        }
        echo "</tr></thead>\n";

        // Fetch and display data from the database
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>";
            echo '<a href="view.php?profile_id='.$row['profile_id'].'">'.htmlentities($row['name']).'</a>  ';
            echo "</td>";
            echo "<td>" . htmlentities($row['headline']) . "</td>";
            if (isset($_SESSION['name'])) {
                echo "<td>";
                echo '<a href="edit.php?profile_id='.$row['profile_id'].'">Edit</a>  ';
                echo '<a href="delete.php?profile_id='.$row['profile_id'].'">Delete</a>';
                echo "</td>";
            }
            echo "</tr>";
        }
        // Close the table
        echo "</table>\n";
    } else {
        // If no rows found, display a message
        echo "<p>No resumes found</p>";
    }
    ?>
</div>
</body>
</html>
