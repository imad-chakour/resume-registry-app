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

if (isset($_POST['save'])) {
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email']) || empty($_POST['headline']) || empty($_POST['summary'])) {
        $_SESSION['error'] = "All fields are required";
        header("Location: edit.php?profile_id=" . $_GET['profile_id']);
         return;
    } elseif (strpos($_POST['email'], '@') === false) {
        $_SESSION['error'] = "Email address must contain @";
        header("Location: edit.php?profile_id=" . $_GET['profile_id']);
        return;
    }elseif (is_string($msg1) || is_string($msg2)) {
        $_SESSION['error'] = $msg1 . "\n" . $msg2; // Concatenate error messages
        header("Location: edit.php?profile_id=" . $_GET['profile_id']);
        return;
    }else { 
        $stmt = $pdo->prepare('UPDATE profile SET first_name = :fn, last_name = :ln, email = :em, headline = :he, summary = :sm WHERE profile_id = :id AND user_id = :uid');
        $stmt->execute(array(
            ':id' => $_POST['profile_id'],
            ':uid' => $_SESSION['user_id'],
            ':fn' => $_POST['first_name'],
            ':ln' => $_POST['last_name'],
            ':em' => $_POST['email'],
            ':he' => $_POST['headline'],
            ':sm' => $_POST['summary']
        ));

        $stmt = $pdo->prepare('DELETE FROM Position WHERE profile_id=:pid');
        $stmt->execute(array( ':pid' => $_REQUEST['profile_id']));
        
        // Insert new positions
        $rank = 1;
        for($i=1; $i<=9; $i++) {
             if ( ! isset($_POST['year'.$i]) ) continue;
            if ( ! isset($_POST['desc'.$i]) ) continue;

            $year = $_POST['year'.$i];
            $desc = $_POST['desc'.$i];
            $stmt = $pdo->prepare('INSERT INTO Position
                    (profile_id, rank, year, description)
                    VALUES ( :pid, :rank, :year, :desc)');

            $stmt->execute(array(
                ':pid' => $_REQUEST['profile_id'],
                ':rank' => $rank,
                ':year' => $year,
                ':desc' => $desc)
            );
            $rank++;

        }

        $stmt = $pdo->prepare('DELETE FROM education WHERE profile_id=:pid');
        $stmt->execute(array( ':pid' => $_REQUEST['profile_id']));
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
                ':pid' => $_REQUEST['profile_id'],
                ':isid' => $institution_id,
                ':rank' => $rank,
                ':year' => $year,
            ));
            $rank++;
        }

        $_SESSION['success'] = "solutions_Profile updated";
        header("Location: index.php");
        return;
    }
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
    $_SESSION['error'] = "Profile not found";
    header("Location: index.php");
    return;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <?php require_once "bootstrap.php"; ?>
</head>
<body>
    <div class="container">
        <h1>Editing Profile for <?= htmlentities($_SESSION['name']) ?></h1>
        <?php if (isset($_SESSION['error'])) : ?>
        <p style="color: red;"><?= htmlentities($_SESSION['error']) ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
        <form method="post">
        <p>First Name:
            <input type="text" name="first_name" value="<?= htmlentities($row['first_name']) ?>" size="60"/></p>
        <p>Last Name:
            <input type="text" name="last_name" value="<?= htmlentities($row['last_name']) ?>" size="60"/></p>
        <p>Email:
            <input type="text" name="email" value="<?= htmlentities($row['email']) ?>" size="30"/></p>
        <p>Headline:<br/>
            <input type="text" name="headline" value="<?= htmlentities($row['headline']) ?>" size="80"/></p>
        <p>Summary:<br/>
            <textarea name="summary" rows="8" cols="80"><?= htmlentities($row['summary']) ?></textarea></p>

        <p>
            Education: <input type="submit" id="addEdu" value="+">
            <div id="edu_fields">
            </div>
        </p>
        <p>
            Position: <input type="submit" id="addPos" value="+">
            <div id="position_fields">
            </div>
        </p>
            <p>
            <input type="hidden" name="profile_id" value="<?= $row['profile_id'] ?>"/>
            <input type="submit" name="save" value="Save">
            <input type="submit" name="cancel" value="Cancel">
            </p>
    </form>
    </div>

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

</body>
</html>