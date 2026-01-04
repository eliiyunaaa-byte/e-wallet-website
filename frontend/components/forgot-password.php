<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siena College of Taytay - Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../styles/forgot-password.css">
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4" style="background-image: url('img/schoolBackground.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <!-- School Logo -->
    <div class="logo-card">
        <img
            src="img/schoolLogo.png"
            alt="Siena College of Taytay Logo"
            class="logo-img"
            onerror="this.onerror=null; this.src='https://placehold.co/96x96/6a040f/facc15?text=Siena';"
        />
    </div>

    <!-- Forgot Password Form Card -->
    <div class="login-card">

        <h2 class="login-title">
            Reset Your Password
        </h2>

        <p class="instruction-text">
            Enter your School ID and we'll send a verification code to your registered email.
        </p>

        <form action="verify.php" method="GET">
            <div class="input-group">
                <input type="text" name="school_id" id="school_id" placeholder="School ID" required>
            </div>

            <div class="pt-4">
                <button type="submit" class="login-btn">Send Verification Code</button>
            </div>
        </form>

        <p class="forgot-pass">
            <a href="index.php">Back to Log In</a>
        </p>

    </div>

</body>
</html>
