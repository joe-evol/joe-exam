<?php
// register.php - User registration
session_start();

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password required';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $pdo = getDB();
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM joe_users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            // Create user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO joe_users (email, password_hash) VALUES (:email, :password_hash)");
            $stmt->execute([
                'email' => $email,
                'password_hash' => $passwordHash
            ]);
            
            // Auto login
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['email'] = $email;
            
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
        .register-container {
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
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
        }
        button:hover {
            background: #229954;
        }
        .error {
            background: #e74c3c;
            color: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
        }
        .login-link a {
            color: #3498db;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>Register</h1>
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
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">Register</button>
        </form>
        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>