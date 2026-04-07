<?php
// Include the config.php to connect to the database
session_start();
include('includes/config.php');

$error_message = ''; // Variable to hold error messages

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if username and password are not empty
    if (!empty($username) && !empty($password)) {
        try {
            // Prepare SQL query to check if user exists
            $query = "SELECT * FROM users WHERE username = :username";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if user exists and password is correct
            if ($user && password_verify($password, $user['password'])) {
                // Start session and store user info
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; // Store the user's role in the session

                // Redirect users to their respective pages based on their roles
                if ($user['role'] === 'admin') {
                    header("Location: index.php"); // Admin dashboard
                } elseif ($user['role'] === 'cashier') {
                    header("Location: pos/index.php"); // Cashier POS page
                } elseif ($user['role'] === 'barista') {
                    header("Location: kitchen.php"); // Barista kitchen page
                } else {
                    $error_message = "Invalid role assigned to the user.";
                }
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } else {
        $error_message = "Please enter both username and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KUSSO Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('assets/cafebg.jpg');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            opacity: 0.6;
            z-index: -1;
        }

        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .login-container h3 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: bold;
            color: #3f2305;
        }

        .login-container .form-control {
            border-radius: 10px;
            padding: 10px;
        }

        .login-container .btn-primary {
            background-color: #3f2305;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .login-container .btn-primary:hover {
            background-color: #5a3820;
        }

        .login-container .forgot-pass {
            color: #3f2305;
            text-decoration: none;
            font-size: 14px;
        }

        .login-container .forgot-pass:hover {
            text-decoration: underline;
        }

        .login-container .logo {
            display: block;
            margin: 0 auto 20px;
            max-width: 100px;
        }

        .login-container .form-text {
            text-align: center;
            margin-top: 15px;
        }

        .login-container .form-text a {
            color: #3f2305;
            text-decoration: none;
        }

        .login-container .form-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="assets/img/kalicafe_logo.jpg" alt="Kali Cafe Logo" class="logo">
        <h3>Login to your account</h3>
        <?php if (!empty($error_message)): ?>
            <p class="text-danger text-center"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>