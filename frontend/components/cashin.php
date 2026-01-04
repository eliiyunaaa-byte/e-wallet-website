<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash In</title>
    <link rel="stylesheet" href="../styles/cashin.css">
    <script src="api.js"></script>
</head>
<body>
    <div class="card-container">
        <header class="card-header">
            <span class="back-arrow">&lt;</span>
            <h1>Cash In</h1>
        </header>
        
        <main class="card-content">
            <div class="qr-section">
                <div id="successMessage" class="success-message" style="display: none; padding: 10px; margin-bottom: 20px; border-radius: 8px; text-align: center; font-weight: bold;"></div>
                
                <div id="instructionsDiv">
                    <ol class="instructions">
                        <li>Enter amount to cash in (₱10 minimum)</li>
                        <li>Click "Create Payment Link"</li>
                        <li>You will be redirected to PayMongo</li>
                        <li>Select payment method (GCash, Maya, Card, etc.)</li>
                        <li>Complete the payment</li>
                        <li>Your balance will update automatically</li>
                    </ol>
                </div>
            </div>
            
            <div class="input-container">
                <input type="number" id="amountInput" placeholder="Enter amount (e.g., 50)" class="reference-input" min="10" step="1" value="">
                <button class="confirm-button" id="generateQRBtn">Create Payment Link</button>
            </div>
            
        </main>
    </div>

  
    <script>
        // Check login
        if (!EWalletAPI.isLoggedIn()) {
            window.location.href = 'index.php';
        }

        const session = EWalletAPI.getSession();
        const student_id = session.student_id;

        document.addEventListener('DOMContentLoaded', function() {
            const backArrow = document.querySelector('.back-arrow');
            backArrow.addEventListener('click', () => {
                window.location.href = 'dashboard.php';
            });

            document.getElementById('generateQRBtn').addEventListener('click', generatePaymentLink);
        });

        // Generate PayMongo Payment Link
        async function generatePaymentLink() {
            const amount = parseFloat(document.getElementById('amountInput').value);
            const errorMsg = document.getElementById('successMessage');
            const generateBtn = document.getElementById('generateQRBtn');

            // Validate amount
            if (!amount || amount < 10) {
                errorMsg.textContent = "❌ Minimum amount is ₱10";
                errorMsg.style.display = "block";
                errorMsg.style.color = "#dc2626";
                return;
            }

            generateBtn.disabled = true;
            generateBtn.textContent = 'Creating Payment Link...';
            errorMsg.textContent = "⏳ Setting up payment...";
            errorMsg.style.display = "block";
            errorMsg.style.color = "#2563eb";

            // Save amount to localStorage for later processing
            localStorage.setItem('pending_cashin_amount', amount);

            // Call backend to create PayMongo payment link
            const response = await EWalletAPI.createPaymentLink(student_id, amount);

            if (response.status === 'success') {
                // Redirect to PayMongo checkout
                window.location.href = response.checkout_url;
            } else {
                errorMsg.textContent = "❌ Error: " + response.message;
                errorMsg.style.display = "block";
                errorMsg.style.color = "#dc2626";
                generateBtn.disabled = false;
                generateBtn.textContent = 'Generate Payment Link';
            }
        }

        // Check if page was redirected back from payment
        window.addEventListener('load', function() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('payment') === 'success') {
                const errorMsg = document.getElementById('successMessage');
                errorMsg.textContent = "✅ Payment successful! Your balance will update shortly.";
                errorMsg.style.display = "block";
                errorMsg.style.color = "#16a34a";
                
                // Reset form
                document.getElementById('amountInput').value = '';
                document.getElementById('generateQRBtn').textContent = 'Generate Payment Link';
                document.getElementById('generateQRBtn').disabled = false;
                
                // Redirect to dashboard after 3 seconds
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 3000);
            } else if (params.get('payment') === 'cancelled') {
                const errorMsg = document.getElementById('successMessage');
                errorMsg.textContent = "⚠️ Payment was cancelled. Please try again.";
                errorMsg.style.display = "block";
                errorMsg.style.color = "#f59e0b";
                document.getElementById('generateQRBtn').disabled = false;
                document.getElementById('generateQRBtn').textContent = 'Generate Payment Link';
            }
        });
    </script>
</body>
</html>