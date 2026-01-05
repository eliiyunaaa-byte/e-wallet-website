<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siena College of Taytay - Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../styles/verify.css">
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4" style="background-image: url('img/schoolBackground.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">

   
    <div class="logo-card">
        <img
            src="img/schoolLogo.png"
            alt="Siena College of Taytay Logo"
            class="logo-img"
            onerror="this.onerror=null; this.src='https://placehold.co/96x96/6a040f/facc15?text=Siena';"
        />
    </div>

    
    <div class="login-card">

        <h2 class="login-title">
            Enter Verification Code
        </h2>

        <p class="instruction-text" style="font-size: 14px; color: #666; text-align: center; margin-bottom: 20px;">
            We've sent a 6-digit code to your email
        </p>

        <form id="otp-form">
            <div class="otp-container">
                <input type="text" maxlength="1" class="otp-input" required>
                <input type="text" maxlength="1" class="otp-input" required>
                <input type="text" maxlength="1" class="otp-input" required>
                <input type="text" maxlength="1" class="otp-input" required>
                <input type="text" maxlength="1" class="otp-input" required>
                <input type="text" maxlength="1" class="otp-input" required>
            </div>

            <div class="pt-4">
                <button type="submit" class="login-btn">Verify OTP</button>
            </div>
        </form>

        <p class="forgot-pass">
            <a href="forgot-password.php">Resend Code</a> | 
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

        // OTP Form submission
        document.getElementById('otp-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Get OTP from all inputs
            const inputs = document.querySelectorAll('.otp-input');
            const otp = Array.from(inputs).map(input => input.value).join('');
            
            if (otp.length !== 6) {
                alert('⚠️ Please enter all 6 digits');
                return;
            }
            
            const button = this.querySelector('button');
            button.disabled = true;
            button.textContent = 'Verifying...';
            
            try {
                const result = await EWalletAPI.verifyOTP(schoolId, otp);
                
                if (result.status === 'success') {
                    alert('✅ OTP verified! Please enter your new password.');
                    // Redirect to reset password page
                    window.location.href = 'reset-password.php';
                } else {
                    alert('❌ ' + result.message);
                    button.disabled = false;
                    button.textContent = 'Verify OTP';
                    // Clear inputs
                    inputs.forEach(input => input.value = '');
                    inputs[0].focus();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ An error occurred. Please try again.');
                button.disabled = false;
                button.textContent = 'Verify OTP';
            }
        });

        // Automatically move to next input
        const inputs = document.querySelectorAll(".otp-input");
        inputs.forEach((input, index) => {
            input.addEventListener("input", () => {
                if(input.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });
            input.addEventListener("keydown", (e) => {
                if(e.key === "Backspace" && index > 0 && input.value === "") {
                    inputs[index - 1].focus();
                }
            });
        });
    </script>

</body>
</html>