<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siena College of Taytay - Log In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../styles/index.css">
    <script src="api.js"></script>
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

    <!-- Login Form Card -->
    <div class="login-card">

        <h2 class="login-title">
            Siena College of Taytay
        </h2>

        <form id="loginForm" onsubmit="handleLogin(event)">
            <div class="input-group">
                <input type="text" name="school_id" id="school_id" placeholder="School ID" required>
                <input type="password" name="password" id="password" placeholder="Password" required>
            </div>

            <div class="pt-4">
                <button type="submit" class="login-btn" id="loginBtn">Log In</button>
                <p id="errorMsg" style="color: #dc2626; text-align: center; margin-top: 0.5rem; display: none;"></p>
            </div>
        </form>

        <p class="forgot-pass">
            <a href="forgot-password.php">Forgot Password?</a>
        </p>

    </div>

    <script>
        // Check if already logged in
        if (EWalletAPI.isLoggedIn()) {
            window.location.href = 'dashboard.php';
        }

        // Handle login form submission
        async function handleLogin(event) {
            event.preventDefault();

            const school_id = document.getElementById('school_id').value;
            const password = document.getElementById('password').value;
            const errorMsg = document.getElementById('errorMsg');
            const loginBtn = document.getElementById('loginBtn');

            // Show loading state
            loginBtn.disabled = true;
            loginBtn.textContent = 'Logging in...';

            // Call API
            const response = await EWalletAPI.login(school_id, password);

            if (response.status === 'success') {
                // Check if admin or student
                if (response.user_type === 'admin') {
                    // Save admin session
                    localStorage.setItem('admin_id', response.data.admin_id);
                    localStorage.setItem('admin_username', response.data.username);
                    localStorage.setItem('admin_role', response.data.role);
                    localStorage.setItem('is_admin', 'true');
                    
                    // Redirect to admin dashboard
                    window.location.href = 'admin-dashboard.php';
                } else {
                    // Save student session
                    EWalletAPI.saveSession(response.data);
                    
                    // Redirect to student dashboard
                    window.location.href = 'dashboard.php';
                }
            } else {
                // Show error
                errorMsg.textContent = response.message;
                errorMsg.style.display = 'block';
                loginBtn.disabled = false;
                loginBtn.textContent = 'Log In';
            }
        }
    </script>

</body>
</html>