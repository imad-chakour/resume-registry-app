<?php
session_start();
require_once "pdo.php";

unset($_SESSION['name']);
unset($_SESSION['user_id']);

$salt = 'XyZzy12*_';
$stored_hash = '1a52e17fa899cf40fb04cfc42e6352f1'; // Replace this with your hashed password

if (isset($_POST['cancel'])) {
    header("Location: index.php");
    return;
}

if (isset($_POST['email']) && isset($_POST['pass'])) {
    if (strlen($_POST['email']) < 1 || strlen($_POST['pass']) < 1) {
        $_SESSION['error'] = "Both email and password are required";
        header("Location: login.php");
        return;
    }
        $check = hash('md5', $salt . $_POST['pass']);
        $stmt = $pdo->prepare("SELECT user_id, name FROM users where email = :em and password = :pw ");
        $stmt->execute(array(':em' => $_POST['email'], ':pw' => $check ));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            $_SESSION['name'] = $row['name'];
            $_SESSION['user_id'] = $row['user_id'];
            header("Location: index.php");
            return;
        } else {
            $_SESSION['error'] = "Invalid password";
            header("Location: login.php");
            return;
        }
    }

?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once "bootstrap.php"; ?>
    <title>Chakour Imad's Login Page - 10adf645</title>
</head>
<body>
<div class="container">
    <h1>Please Log In</h1>
    <?php
    if (isset($_SESSION['error'])) {
        echo('<p style="color: red;">'.htmlentities($_SESSION['error'])."</p>\n");
        unset($_SESSION['error']);
    }
    ?>
    <form method="POST">
        <label for="email">User Name</label>
        <input type="text" name="email" id="email">
        <br>
        <label for="pass">Password</label>
        <input type="password" name="pass" id="id_1723">
        <br>
        <input type="submit" onclick="return doValidate();" value="Log In">
        <input type="button" value="Cancel">
    </form>

    <p>
        For a password hint, view source and find a password hint
        in the HTML comments.
        <!-- Hint: The password is 'php' (all lower case) followed by 123. -->
    </p>
</div>

<script>
function doValidate() {
         console.log('Validating...');
         try {
             pw = document.getElementById('id_1723').value;
             console.log("Validating pw="+pw);
             if (pw == null || pw == "") {
                 alert("Both fields must be filled out");
                 return false;
             }
             return true;
         } catch(e) {
             return false;
         }
         return false;
     }
</script>
</body>
</html>