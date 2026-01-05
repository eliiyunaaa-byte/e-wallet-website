<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siena College of Taytay - Reset Password</title>
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

    <!-- Reset Password Form Card -->
    <div class="login-card">

        <h2 class="login-title">
            Create New Password
        </h2>

        <p class="instruction-text">
            Enter your new password below
        </p>

        <form id="reset-password-form">
            <div class="input-group">
                <input type="password" name="new_password" id="new_password" placeholder="New Password" required minlength="6">
            </div>

            <div class="input-group">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" required minlength="6">
            </div>

            <div class="pt-4">
                <button type="submit" class="login-btn">Reset Password</button>
            </div>
        </form>

        <p class="forgot-pass">
            <a href="index.php">Back to Log In</a>
        </p>

    </div>

    <script src="api.js"></script>
    <script>
        // Check if school_id exists
        const schoolId = localStorage.getItem('reset_school_id');
        if (!schoolId) {
            alert('⚠️ Session expired. Please start over.');
            window.location.href = 'forgot-password.php';
        }

        document.getElementById('reset-password-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('⚠️ Passwords do not match!');
                return;
            }
            
            if (newPassword.length < 6) {
                alert('⚠️ Password must be at least 6 characters long');
                return;
            }
            
            const button = this.querySelector('button');
            button.disabled = true;
            button.textContent = 'Resetting...';
            
            try {
                const result = await EWalletAPI.resetPassword(schoolId, newPassword);
                
                if (result.status === 'success') {
                    // Clear stored school_id
                    localStorage.removeItem('reset_school_id');
                    
                    alert('✅ ' + result.message + '\\n\\nYou can now log in with your new password.');
                    window.location.href = 'index.php';
                } else {
                    alert('❌ ' + result.message);
                    button.disabled = false;
                    button.textContent = 'Reset Password';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ An error occurred. Please try again.');
                button.disabled = false;
                button.textContent = 'Reset Password';
            }
        });
    </script>

</body>
</html>
