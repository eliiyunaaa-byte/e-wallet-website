<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="../styles/profile.css">
    <script src="api.js"></script>
</head>
<body>
    <div class="profile-container">
        <!-- Header -->
        <header class="profile-header">
            <button class="back-btn" onclick="goBack()">
                <span>&lt;</span>
            </button>
            <h1>Profile</h1>
        </header>

        <!-- Profile Picture -->
        <div class="profile-pic-section">
            <div class="profile-pic">
                <img src="img/user.png" alt="User Profile">
            </div>
        </div>

        <!-- Profile Information -->
        <div class="profile-info-section">
            <div class="input-field">
                <label>Name</label>
                <input type="text" id="studentName" value="Student Name" readonly>
            </div>

            <div class="input-field">
                <label>ID Number</label>
                <input type="text" id="studentID" value="123456" readonly>
            </div>

            <div class="input-field">
                <label>Grade & Section</label>
                <input type="text" id="gradeSection" value="10-Eucharist Centered" readonly>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="change-password-btn" onclick="changePassword()">
                Change Password
            </button>
            <button class="logout-btn" onclick="logout()">
                Log Out
            </button>
        </div>
    </div>

    <script>
        // Check login
        if (!EWalletAPI.isLoggedIn()) {
            window.location.href = 'index.php';
        }

        function goBack() {
            window.location.href = 'dashboard.php';
        }

        function changePassword() {
            alert('Change Password functionality coming soon!');
            // Future: Redirect to change password page
        }

        function logout() {
            EWalletAPI.clearSession();
            window.location.href = 'index.php';
        }

        // Load user data from session
        document.addEventListener('DOMContentLoaded', function() {
            const session = EWalletAPI.getSession();
            
            document.getElementById('studentName').value = session.name || 'Student Name';
            document.getElementById('studentID').value = session.school_id || '123456';
            document.getElementById('gradeSection').value = session.grade_section || '10-Eucharist Centered';
        });
    </script>
</body>
</html>
