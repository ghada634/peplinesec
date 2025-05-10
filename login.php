<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/login.css">
    <title>Login</title>
</head>

<body>
    <?php
    session_start();
    $_SESSION["user"] = "";
    $_SESSION["usertype"] = "";
    date_default_timezone_set('Asia/Kolkata');
    $_SESSION["date"] = date('Y-m-d');

    include("connection.php");

    if ($_POST) {
        $email = $_POST['useremail'];
        $password = $_POST['userpassword'];
        $error = '<label for="promter" class="form-label"></label>';

        $result = $database->query("SELECT * FROM webuser WHERE email='$email'");
        if ($result->num_rows == 1) {
            $utype = $result->fetch_assoc()['usertype'];
            if ($utype == 'p') {
                $checker = $database->query("SELECT * FROM patient WHERE pemail='$email' AND ppassword='$password'");
                if ($checker->num_rows == 1) {
                    $_SESSION['user'] = $email;
                    $_SESSION['usertype'] = 'p';
                    header('location: patient/index.php');
                } else {
                    $error = '<label class="form-label" style="color:red;text-align:center;">Invalid email or password</label>';
                }
            } elseif ($utype == 'a') {
                $checker = $database->query("SELECT * FROM admin WHERE aemail='$email' AND apassword='$password'");
                if ($checker->num_rows == 1) {
                    $_SESSION['user'] = $email;
                    $_SESSION['usertype'] = 'a';
                    header('location: admin/index.php');
                } else {
                    $error = '<label class="form-label" style="color:red;text-align:center;">Invalid email or password</label>';
                }
            } elseif ($utype == 'd') {
                $checker = $database->query("SELECT * FROM doctor WHERE docemail='$email' AND docpassword='$password'");
                if ($checker->num_rows == 1) {
                    $_SESSION['user'] = $email;
                    $_SESSION['usertype'] = 'd';
                    header('location: doctor/index.php');
                } else {
                    $error = '<label class="form-label" style="color:red;text-align:center;">Invalid email or password</label>';
                }
            }
        } else {
            $error = '<label class="form-label" style="color:red;text-align:center;">No account found for this email</label>';
        }
    } else {
        $error = '<label for="promter" class="form-label">&nbsp;</label>';
    }
    ?>

    <center>
        <div class="container">
            <p class="header-text">Welcome Back!</p>
            <p class="sub-text">Login with your details to continue</p>

            <form action="" method="POST">
                <table border="0" style="margin: 0;padding: 0;width: 60%;">
                    <tr>
                        <td class="label-td">
                            <label for="useremail" class="form-label">Email: </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="label-td">
                            <input type="email" name="useremail" class="input-text" placeholder="Email Address" required>
                        </td>
                    </tr>
                    <tr>
                        <td class="label-td">
                            <label for="userpassword" class="form-label">Password: </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="label-td">
                            <input type="password" id="userpassword" name="userpassword" class="input-text" placeholder="Password" required>
                            <br>
                            <input type="checkbox" onclick="togglePassword()"> Afficher le mot de passe
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <br>
                            <?php echo $error; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="submit" value="Login" class="login-btn btn-primary btn">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <br>
                            <label class="sub-text" style="font-weight: 280;">Don't have an account&#63;</label>
                            <a href="signup.php" class="hover-link1 non-style-link">Sign Up</a>
                            <br><br><br>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </center>


</body>

</html>