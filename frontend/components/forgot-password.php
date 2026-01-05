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

        <form id="forgot-password-form">
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

    <script src="api.js"></script>
    <script>
        document.getElementById('forgot-password-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const schoolId = document.getElementById('school_id').value;
            const button = this.querySelector('button');
            
            button.disabled = true;
            button.textContent = 'Sending OTP...';
            
            try {
                const result = await EWalletAPI.requestPasswordReset(schoolId);
                
                if (result.status === 'success') {
                    // Store school_id for verify page
                    localStorage.setItem('reset_school_id', schoolId);
                    
                    alert('✅ ' + result.message + '\n\nThe code will expire in 15 minutes.');
                    
                    // Redirect to verify page
                    window.location.href = 'verify.php';
                } else {
                    alert('❌ ' + result.message);
                    button.disabled = false;
                    button.textContent = 'Send Verification Code';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ An error occurred. Please try again.');
                button.disabled = false;
                button.textContent = 'Send Verification Code';
            }
        });
    </script>
</body>
</html>
