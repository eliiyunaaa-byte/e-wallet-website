<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siena College of Taytay - Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../styles/verify.css">
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

    <!-- School Logo -->
    <div class="logo-card">
        <img
            src="img/schoolLogo.png"
            alt="Siena College of Taytay Logo"
            class="logo-img"
            onerror="this.onerror=null; this.src='https://placehold.co/96x96/6a040f/facc15?text=Siena';"
        />
    </div>

    <!-- Verification Form Card -->
    <div class="login-card">

        <h2 class="login-title">
            Enter Verification Code
        </h2>

        <form action="dashboard.php" method="GET" id="otp-form">
            <div class="otp-container">
                <input type="text" maxlength="1" class="otp-input" required>
                <input type="text" maxlength="1" class="otp-input" required>
                <input type="text" maxlength="1" class="otp-input" required>
                <input type="text" maxlength="1" class="otp-input" required>
                <input type="text" maxlength="1" class="otp-input" required>
                <input type="text" maxlength="1" class="otp-input" required>
            </div>

            <div class="pt-4">
                <button type="submit" class="login-btn">Verify</button>
            </div>
        </form>

        <p class="forgot-pass">
            <a href="index.php">Back to Log In</a>
        </p>

    </div>

    <script>
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