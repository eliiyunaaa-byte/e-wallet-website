<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <link rel="stylesheet" href="../styles/transaction.css">
    <script src="api.js"></script>
</head>
<body>
    <div class="transaction-container">
        <!-- Header -->
        <header class="transaction-header">
            <button class="back-btn" onclick="goBack()">
                <span>&lt;</span>
            </button>
            <h1>Transactions</h1>
        </header>

        <!-- Search and Filter -->
        <div class="search-section">
            <input type="text" id="searchInput" placeholder="Search transactions..." class="search-input">
            <button class="filter-btn" onclick="toggleFilter()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <!-- Transaction Table -->
        <div class="table-container">
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th>Item</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody id="transactionTableBody">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem;">Loading transactions...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Insights Button -->
        <button class="insights-btn" onclick="getInsights()">
            Get Spending Insights ✨
        </button>
    </div>

    <script>
        // Check login
        if (!EWalletAPI.isLoggedIn()) {
            window.location.href = 'index.php';
        }

        const session = EWalletAPI.getSession();
        const student_id = session.student_id;
        let allTransactions = [];

        function goBack() {
            window.location.href = 'dashboard.php';
        }

        function toggleFilter() {
            alert('Filter functionality coming soon!');
        }

        function getInsights() {
            alert('Spending insights feature coming soon!');
        }

        // Load transactions from API
        async function loadTransactions() {
            const response = await EWalletAPI.getTransactions(student_id, 100);
            
            if (response.status === 'success') {
                allTransactions = response.transactions;
                renderTransactions(allTransactions);
            } else {
                console.error('Failed to load transactions:', response.message);
            }
        }

        // Render transactions in table
        function renderTransactions(transactions) {
            const tbody = document.getElementById('transactionTableBody');
            tbody.innerHTML = '';

            if (transactions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;">No transactions found</td></tr>';
                return;
            }

            transactions.forEach(trans => {
                // Format date
                const date = new Date(trans.transaction_date);
                const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                
                // Format time (convert 24hr to 12hr)
                const [hours, minutes] = trans.transaction_time.split(':');
                const hour = parseInt(hours);
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const hour12 = hour % 12 || 12;
                const formattedTime = `${hour12}:${minutes} ${ampm}`;
                
                const row = `
                    <tr>
                        <td>${formattedDate}</td>
                        <td>${formattedTime}</td>
                        <td>${trans.location || '—'}</td>
                        <td>${trans.item_name || '—'}</td>
                        <td>₱${parseFloat(trans.amount).toFixed(2)}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            const filtered = allTransactions.filter(trans => {
                const text = (trans.item_name + trans.location + trans.transaction_date).toLowerCase();
                return text.includes(searchTerm);
            });

            renderTransactions(filtered);
        });

        // Load on page load
        document.addEventListener('DOMContentLoaded', loadTransactions);
    </script>
</body>
</html>
