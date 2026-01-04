<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <script src="api.js"></script>
</head>
<body>

<div class="dashboard-container">

    <!-- HEADER -->
    <div class="profile-card">
        <div class="profile-info">
            <img src="img/user.png" alt="User Icon">
            <h2>Welcome, <span id="userName">User</span></h2>
        </div>
        <button class="logout-btn" onclick="logout()">Log Out</button>
    </div>

    <!-- CURRENT BALANCE -->
    <div class="balance-card">
        <p>Your Balance:</p>
        <h1 id="balanceDisplay">₱0.00</h1>
    </div>

    <!-- BIG ACTION BUTTONS -->
    <div class="action-row">
        <button class="big-btn yellow-btn" onclick="goTo('cashin.php')">Cash In</button>
        <button class="big-btn white-btn" onclick="goTo('transaction.php')">View Transactions</button>
    </div>

    <!-- INFO CARDS -->
    <div class="bottom-row">
        <div class="small-card">
            <p><strong>Last Transaction:</strong></p>
            <p id="lastTransaction">—</p>
        </div>

        <div class="small-card">
            <p><strong>This Week’s Spending:</strong></p>
            <p id="weekSpending">₱0</p>
        </div>
    </div>

    <!-- FULL WIDTH BUTTON -->
    <button class="profile-btn" onclick="goTo('profile.php')" style="width: 100%; margin-top: 1rem;">
        Go to Profile
    </button>

</div>

</div>

<script>
    // Check login
    if (!EWalletAPI.isLoggedIn()) {
        window.location.href = 'index.php';
    }

    const session = EWalletAPI.getSession();
    const student_id = session.student_id;

    // Load dashboard data
    async function loadDashboard() {
        // Get name
        document.getElementById('userName').textContent = session.name || 'User';

        // Get balance
        const balanceRes = await EWalletAPI.getBalance(student_id);
        if (balanceRes.status === 'success') {
            document.getElementById('balanceDisplay').textContent = '₱' + balanceRes.balance.toFixed(2);
            localStorage.setItem('balance', balanceRes.balance);
        }

        // Get last transaction
        const transRes = await EWalletAPI.getTransactions(student_id, 1);
        if (transRes.status === 'success' && transRes.transactions.length > 0) {
            const lastTrans = transRes.transactions[0];
            document.getElementById('lastTransaction').textContent = 
                lastTrans.item_name + ' ₱' + lastTrans.amount + ' (' + lastTrans.transaction_date + ')';
            localStorage.setItem('lastTransaction', lastTrans.item_name);
        }

        // Get weekly spending
        const spendingRes = await EWalletAPI.getWeeklySpending(student_id);
        if (spendingRes.status === 'success') {
            document.getElementById('weekSpending').textContent = '₱' + spendingRes.weekly_spending.toFixed(2);
            localStorage.setItem('weeklySpending', spendingRes.weekly_spending);
        }
    }

    function goTo(page) {
        window.location.href = page;
    }

    function logout() {
        EWalletAPI.clearSession();
        window.location.href = "index.php";
    }

    // Check if returning from successful payment
    async function checkPaymentSuccess() {
        const urlParams = new URLSearchParams(window.location.search);
        const paymentStatus = urlParams.get('payment');
        const amount = localStorage.getItem('pending_cashin_amount');
        
        if (paymentStatus === 'success' && amount && student_id) {
            // Show processing message
            alert('Processing your cash-in of ₱' + amount + '...');
            
            // Process cash-in (simulate webhook)
            try {
                const response = await fetch(`../../backend/webhooks/manual_cashin.php?student_id=${student_id}&amount=${amount}`);
                const result = await response.json();
                
                console.log('💰 CASH-IN RESPONSE:', result);
                
                if (result.status === 'success') {
                    // Log notification results
                    if (result.notifications) {
                        console.log('📧 EMAIL STATUS:', result.notifications.email || 'Not sent');
                        console.log('📱 SMS STATUS:', result.notifications.sms || 'Not sent');
                        
                        if (result.notifications.email?.status === 'success') {
                            console.log('✅ Email sent to:', result.notifications.email.to);
                        } else if (result.notifications.email?.status === 'disabled') {
                            console.log('⚠️ Email disabled in config');
                        } else if (result.notifications.email?.status === 'error') {
                            console.log('❌ Email failed:', result.notifications.email.message);
                        }
                        
                        if (result.notifications.sms?.status === 'success') {
                            console.log('✅ SMS sent to:', result.notifications.sms.phone);
                            console.log('   Message ID:', result.notifications.sms.message_id);
                        } else if (result.notifications.sms?.status === 'disabled') {
                            console.log('⚠️ SMS disabled in config');
                        } else if (result.notifications.sms?.status === 'error') {
                            console.log('❌ SMS failed:', result.notifications.sms.message);
                            console.log('   API Response:', result.notifications.sms.response);
                        }
                    }
                    
                    // Clear pending amount
                    localStorage.removeItem('pending_cashin_amount');
                    
                    // Reload dashboard to show new balance
                    await loadDashboard();
                    
                    alert('✅ Cash-in successful! Your balance has been updated.');
                    
                    // Clean URL
                    window.history.replaceState({}, document.title, 'dashboard.php');
                } else {
                    alert('⚠️ Payment received but balance update failed. Contact admin.');
                }
            } catch (error) {
                console.error('Error processing payment:', error);
                alert('⚠️ Error processing payment. Contact admin.');
            }
        } else if (paymentStatus === 'success') {
            // Clean URL even if no amount
            window.history.replaceState({}, document.title, 'dashboard.php');
        }
    }

    // Load on page load
    document.addEventListener('DOMContentLoaded', async function() {
        await checkPaymentSuccess();
        await loadDashboard();
    });
</script>

</body>
</html>