/**
* EduLend - Wallet Transaction Logic
* Handles user balances, system deposits, withdrawals, and debt auto-recovery.
*/

// Global wallet state initialized from LocalStorage (or defaults)
let userWallet = JSON.parse(localStorage.getItem("edulend_wallet")) || {
    balance: 5000.00,       // Current available balance in Naira
    debt: 0.00,             // Outstanding unpaid loans
    isDefaulted: false      // System lock status
};

document.addEventListener("DOMContentLoaded", () => {
    updateWalletUI();
    setupWalletActions();
});

/**
 * Updates all wallet metrics across the dashboard interface
 */
function updateWalletUI() {
    localStorage.setItem("edulend_wallet", JSON.stringify(userWallet));

    if (document.getElementById("wallet-balance")) {
        document.getElementById("wallet-balance").innerText = `₦${userWallet.balance.toLocaleString()}`;
    }
    if (document.getElementById("wallet-debt")) {
        document.getElementById("wallet-debt").innerText = `₦${userWallet.debt.toLocaleString()}`;
    }

    // Visual warning banner if user is locked
    const warningBanner = document.getElementById("wallet-status-banner");
    if (warningBanner) {
        if (userWallet.isDefaulted) {
            warningBanner.classList.remove("hidden");
            warningBanner.innerText = "⚠️ ACCOUNT LOCKED: Deposits will automatically go toward clearing your unpaid debt balance.";
        } else {
            warningBanner.classList.add("hidden");
        }
    }
}

/**
 * Binds the click handlers to the deposit and withdrawal forms/buttons
 */
function setupWalletActions() {
    const depositBtn = document.getElementById("btn-deposit");
    const withdrawBtn = document.getElementById("btn-withdraw");

    if (depositBtn) {
        depositBtn.addEventListener("click", () => {
            const amount = parseFloat(prompt("Enter amount to deposit (₦):"));
            if (isNaN(amount) || amount <= 0) return alert("Invalid amount entered.");

            handleDeposit(amount);
        });
    }

    if (withdrawBtn) {
        withdrawBtn.addEventListener("click", () => {
            // Guard clause: Prevent defaulted users from drawing platform capital
            if (userWallet.isDefaulted) {
                alert("Action Blocked: You cannot withdraw funds while your account is in default.");
                return;
            }

            const amount = parseFloat(prompt("Enter amount to withdraw to your bank account (₦):"));
            if (isNaN(amount) || amount <= 0) return alert("Invalid amount entered.");
            if (amount > userWallet.balance) return alert("Insufficient funds available.");

            userWallet.balance -= amount;
            alert(`Withdrawal of ₦${amount.toLocaleString()} processed successfully to your registered bank account.`);
            updateWalletUI();
        });
    }
}

/**
 * Core business logic engine for incoming wallet capital
 */
function handleDeposit(amount) {
    alert(`Simulating secure payment gateway payment of ₦${amount.toLocaleString()}...`);

    // Rule: If they are defaulted, the money pays down debt first!
    if (userWallet.isDefaulted && userWallet.debt > 0) {
        if (amount >= userWallet.debt) {
            // Deposit completely covers debt
            const remainder = amount - userWallet.debt;
            alert(`Success! ₦${userWallet.debt.toLocaleString()} went to pay off your debt. Your account is now ACTIVE.`);
            userWallet.debt = 0;
            userWallet.isDefaulted = false;
            userWallet.balance += remainder;
        } else {
            // Deposit partially covers debt
            userWallet.debt -= amount;
            alert(`Partial payment successful. ₦${amount.toLocaleString()} paid toward debt. Remaining debt: ₦${userWallet.debt.toLocaleString()}`);
        }
    } else {
        // Standard user behavior
        userWallet.balance += amount;
        alert(`₦${amount.toLocaleString()} successfully credited to your main wallet balance.`);
    }

    updateWalletUI();
}