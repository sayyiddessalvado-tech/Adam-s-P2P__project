/**
 * EduLend - Wallet Transaction Controller
 * Routes deposits and withdrawals directly to backend processing modules.
 */

document.addEventListener("DOMContentLoaded", () => {
    setupWalletActions();
});

/**
 * Binds click events to payment gateway simulators on the UI
 */
function setupWalletActions() {
    const depositBtn = document.getElementById("btn-deposit");
    const withdrawBtn = document.getElementById("btn-withdraw");

    if (depositBtn) {
        depositBtn.addEventListener("click", () => {
            const amount = parseFloat(prompt("Enter amount to deposit (₦):"));
            if (isNaN(amount) || amount <= 0) return alert("Invalid amount entered.");

            executeTransaction('deposit', amount);
        });
    }

    if (withdrawBtn) {
        withdrawBtn.addEventListener("click", () => {
            const amount = parseFloat(prompt("Enter amount to withdraw (₦):"));
            if (isNaN(amount) || amount <= 0) return alert("Invalid amount entered.");

            // We use 'withdrawal' here to match exactly what your PHP $_POST expects
            executeTransaction('withdrawal', amount);
        });
    }
}

/**
 * Sends transaction events to your live PHP backend
 * @param {string} actionType - 'deposit' or 'withdrawal'
 * @param {number} actionAmount - Value to transaction
 */
function executeTransaction(actionType, actionAmount) {
    if (actionType === 'deposit') {
        alert(`Simulating payment gateway connection. Processing ₦${actionAmount.toLocaleString()}...`);
    }

    // Use URLSearchParams to simulate a standard HTML form submit so $_POST works in PHP
    const formData = new URLSearchParams();
    formData.append('amount', actionAmount);
    formData.append('type', actionType);

    fetch('Api/process_transaction.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded' 
        },
        body: formData.toString()
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP Error Status: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        alert(data.message);
        if (data.success) {
            // Instant reload to let PHP pull your newly updated wallet rows & transactions
            window.location.reload(); 
        }
    })
    .catch(err => {
        console.error("Transaction System Connection Fault:", err);
        alert("Transaction processing failed. Please check your database connection.");
    });
} 