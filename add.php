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

function validatePos() {
    for ($i = 1; $i <= 9; $i++) {
        if (!isset($_POST['year' . $i]) || !isset($_POST['desc' . $i])) {
            continue; // Skip if year or desc is not set
        }

        $year = $_POST['year' . $i];
        $desc = $_POST['desc' . $i];

        if (empty($year) || empty($desc)) {
            return "All fields are required";
        }

        if (!is_numeric($year)) {
            return "Position year must be numeric";
        }
    }
    return true;
}

function validateEdu() {
    for ($i = 1; $i <= 9; $i++) {
        if (!isset($_POST['yearE' . $i]) || !isset($_POST['edu_school' . $i])) {
            continue; // Skip if year or desc is not set
        }

        $year = $_POST['yearE' . $i];
        $edu_school = $_POST['edu_school' . $i];

        if (empty($year) || empty($edu_school)) {
            return "All fields are required";
        }

        if (!is_numeric($year)) {
            return "Education year must be numeric";
        }
    }
    return true;
}

$msg1 = validatePos();
$msg2 = validateEdu();

if (isset($_POST['Add'])) {
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email']) || empty($_POST['headline']) || empty($_POST['summary'])) {
        $_SESSION['error'] = "All fields are required";
        header("Location: add.php");
        return;
    } elseif (strpos($_POST['email'], '@') === false) {
        $_SESSION['error'] = "Email address must contain @";
        header("Location: add.php");
        return;
    } elseif (is_string($msg1) || is_string($msg2)) {
        $_SESSION['error'] = $msg1 . "\n" . $msg2; // Concatenate error messages
        header("Location: add.php");
        return;
    } else {
        try {
            $pdo->beginTransaction();
            // Insert into Profile table
            $stmt = $pdo->prepare('INSERT INTO Profile (user_id, first_name, last_name, email, headline, summary) VALUES (:uid, :fn, :ln, :em, :he, :su)');
            $stmt->execute(array(
                ':uid' => $_SESSION['user_id'],
                ':fn' => $_POST['first_name'],
                ':ln' => $_POST['last_name'],
                ':em' => $_POST['email'],
                ':he' => $_POST['headline'],
                ':su' => $_POST['summary']
            ));
            $profile_id = $pdo->lastInsertId();

            // Insert into Position table
            $rank = 1;
            for ($i = 1; $i <= 9; $i++) {
                if (!isset($_POST['year' . $i]) || !isset($_POST['desc' . $i])) {
                    continue; // Skip if year or desc is not set
                }
                $year = $_POST['year' . $i];
                $desc = $_POST['desc' . $i];
                $stmt = $pdo->prepare('INSERT INTO Position (profile_id, rank, year, description) VALUES (:pid, :rank, :year, :desc)');
                $stmt->execute(array(
                    ':pid' => $profile_id,
                    ':rank' => $rank,
                    ':year' => $year,
                    ':desc' => $desc
                ));
                $rank++;
            }

            // Insert into Education table
            $rank = 1;
            for ($i = 1; $i <= 9; $i++) {
                if (!isset($_POST['yearE' . $i]) || !isset($_POST['edu_school' . $i])) {
                    continue; // Skip if year or school is not set
                }
                $year = $_POST['yearE' . $i];
                $edu_school = $_POST['edu_school' . $i];
                
                // Check if institution already exists
                $stmt = $pdo->prepare('SELECT institution_id FROM Institution WHERE name = :name');
                $stmt->execute(array(':name' => $edu_school));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $institution_id = $row['institution_id'];
                } else {
                    // Insert new institution
                    $stmt = $pdo->prepare('INSERT INTO Institution (name) VALUES (:name)');
                    $stmt->execute(array(':name' => $edu_school));
                    $institution_id = $pdo->lastInsertId();
                }
                
                // Insert into Education table
                $stmt = $pdo->prepare('INSERT INTO Education (profile_id, institution_id, rank, year) VALUES (:pid, :isid, :rank, :year)');
                $stmt->execute(array(
                    ':pid' => $profile_id,
                    ':isid' => $institution_id,
                    ':rank' => $rank,
                    ':year' => $year,
                ));
                $rank++;
            }

            $pdo->commit();
            $_SESSION['success'] = "Record added";
            header("Location: index.php");
            return;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error: " . $e->getMessage();
            header("Location: add.php");
            return;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chakour Imad's Adding Profile 10adf645</title>
    <?php require_once "bootstrap.php"; ?>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" type="text/css" href="css/add.css">
</head>
<body>
<div class="container">
    <h1>Adding Profile for <?= htmlentities($_SESSION['name']) ?></h1>
    <?php if (isset($_SESSION['error'])) : ?>
        <p style="color: red;"><?= htmlentities($_SESSION['error']) ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <form method="post">
        <p>First Name:
            <input type="text" name="first_name" /></p>
        <p>Last Name:
            <input type="text" name="last_name" /></p>
        <p>Email:
            <input type="text" name="email" /></p>
        <p>Headline:
            <input type="text" name="headline" /></p>
        <p>Summary:
            <textarea name="summary" rows="8"></textarea></p>
        <p>
            Education: <input type="submit" id="addEdu" value="+">
            <div id="edu_fields"></div>
        </p>
        <p>
            Position: <input type="submit" id="addPos" value="+">
            <div id="position_fields"></div>
        </p>
        <p>
            <input type="submit" name="Add" value="Add">
            <input type="submit" name="cancel" value="Cancel">
        </p>
    </form>
</div>
</body>
</html>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
countPos = 0;
countEdu = 0;
$(document).ready(function() {
    $('#addPos').click(function(event) {
        event.preventDefault();
        if (countPos >= 9) {
            alert("Maximum of nine position entries exceeded");
            return;
        }
        countPos++;
        $('#position_fields').append(
            '<div id="position' + countPos + '">' +
            '<p>Year: <input type="text" name="year' + countPos + '" value="" />' +
            '<input type="button" value="-" onclick="$(\'#position' + countPos + '\').remove();return false;"></p>' +
            '<textarea name="desc' + countPos + '" rows="8" cols="80"></textarea>' +
            '</div>');
    });
});

$(document).ready(function() {
    $('#addEdu').click(function(event){
        event.preventDefault();
        if ( countEdu >= 9 ) {
            alert("Maximum of nine education entries exceeded");
            return;
        }
        countEdu++;
        window.console && console.log("Adding education "+countEdu);

        $('#edu_fields').append(
            '<div id="edu'+countEdu+'"> \
            <p>Year: <input type="text" name="yearE'+countEdu+'" value="" /> \
            <input type="button" value="-" onclick="$(\'#edu'+countEdu+'\').remove();return false;"><br>\
            <p>School: <input type="text" size="80" name="edu_school'+countEdu+'" class="school" value="" />\
            </p></div>'
        );

        $('.school').autocomplete({
            source: "fetch_un.php"
        });

    });

});

</script>
