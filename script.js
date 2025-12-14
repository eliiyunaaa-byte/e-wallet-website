// Load dashboard data automatically
document.addEventListener("DOMContentLoaded", () => {
    const name = localStorage.getItem("userName") || "User";
    if (document.getElementById("userName")) {
        document.getElementById("userName").textContent = name;
    }

    if (document.getElementById("balanceDisplay")) {
        updateBalance();
        updateLastTransaction();
        updateWeeklySpending();
    }
});

// Redirect to another page
function goTo(page) {
    window.location.href = page;
}

// Logout
function logout() {
    localStorage.clear();
    window.location.href = "login.html";
}

// BALANCE
function updateBalance() {
    let balance = Number(localStorage.getItem("balance")) || 0;
    document.getElementById("balanceDisplay").textContent =
        "₱" + balance.toFixed(2);
}

// LAST TRANSACTION
function updateLastTransaction() {
    let last = localStorage.getItem("lastTransaction");

    if (!last) {
        document.getElementById("lastTransaction").textContent = "—";
        return;
    }

    document.getElementById("lastTransaction").textContent = last;
}

// WEEKLY SPENDING
function updateWeeklySpending() {
    let week = Number(localStorage.getItem("weeklySpending")) || 0;
    document.getElementById("weeklySpending").textContent = "₱" + week;
}