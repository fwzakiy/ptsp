<?php
session_start();

// Jika sudah login, langsung arahkan sesuai role
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] == 'admin') {
        header("Location: report.php"); // Admin diarahkan ke report dulu (opsional)
    } else {
        header("Location: admin.php");
    }
    exit;
}

$error = '';

// Proses Login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // --- KONFIGURASI USERNAME & PASSWORD DI SINI ---
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['user_role'] = 'admin';
        header("Location: admin.php"); // Admin juga butuh akses panel panggil
        exit;
    } elseif ($username === 'loket' && $password === 'loket') {
        $_SESSION['user_role'] = 'loket';
        header("Location: admin.php");
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PTSP Kemenag</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            border-top: 5px solid var(--kemenag-green);
        }
        .login-box img { height: 80px; margin-bottom: 20px; }
        .login-box h2 { margin: 0 0 20px 0; color: var(--text-dark); }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input {
            width: 100%; padding: 10px; border: 1px solid #ddd;
            border-radius: 5px; box-sizing: border-box; font-size: 1rem;
        }
        .btn-login {
            width: 100%; padding: 12px; background-color: var(--kemenag-green);
            color: white; border: none; border-radius: 5px; font-size: 1.1rem;
            cursor: pointer; font-weight: bold;
        }
        .btn-login:hover { background-color: #004a11; }
        .error-msg { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-box">
        <img src="https://upload.wikimedia.org/wikipedia/commons/9/9a/Kementerian_Agama_new_logo.png" alt="Logo">
        <h2>Login Petugas</h2>
        
        <?php if($error): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Masuk</button>
        </form>
    </div>
</body>
</html>