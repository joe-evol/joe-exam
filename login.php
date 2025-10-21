<?php
// login.php - Handle login
session_start();

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password required';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM joe_users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            margin-bottom: 30px;
            color: #2c3e50;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #3498db;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
        }
        button:hover {
            background: #2980b9;
        }
        .error {
            background: #e74c3c;
            color: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>

    <div class="login-link">
        Don't have an account? <a href="register.php">Register</a>
    </div>

    </div>
</body>
</html>