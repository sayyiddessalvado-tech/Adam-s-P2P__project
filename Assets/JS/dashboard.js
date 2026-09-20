/**
* EduLend - Integrated Dashboard Interactive Controller
* Handles micro-credit requests, wallet actions, credit gauge rendering,
* identity verification, peer vouchers, and automated sweep operations.
*/

document.addEventListener("DOMContentLoaded", () => {
    // Mobile Navigation Drawer Toggle
    const menuButton = document.getElementById("menuButton");
    const sidebar = document.getElementById("sidebar");

    if (menuButton && sidebar) {
        menuButton.addEventListener("click", () => {
            sidebar.classList.toggle("hidden");
        });
    }

    // Initialize Sub-Modules
    setupLoanRequests();
    initWalletActions();
    fetchTransactionHistory();
    initVerificationModal();
    initVoucherManagement();
    initAutoSweepModule();
});

// ============================================================
// 1. HELPER & UTILITY FUNCTIONS
// ============================================================

/**
 * Global CSRF Token Extractor
 */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
        || document.querySelector('input[name="csrf_token"]')?.value
        || "";
}

/**
 * Display modal or system alert feedback
 */
function showNotification(message, isSuccess = true) {
    alert((isSuccess ? "SUCCESS: " : "ERROR: ") + message);
}

/**
 * Basic HTML Sanitizer
 */
function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
}

// ============================================================
// 2. CREDIT SCORE GAUGE ANIMATION
// ============================================================

/**
 * Animates the circular SVG credit score gauge based on real database scores
 * @param {number} score - The credit rating score to render (0 - 100)
 */
function animateCreditScore(score) {
    const circle = document.getElementById("scoreCircle");
    const scoreText = document.getElementById("scoreValue");
    const scoreStatus = document.getElementById("scoreStatus");

    if (!circle || !scoreText || !scoreStatus) return;

    // SVG Circle Radius is 70. Circumference = 2 * PI * r = ~439.82
    const circumference = 2 * Math.PI * 70;
    const offset = circumference - (score / 100) * circumference;

    circle.style.strokeDasharray = `${circumference}`;
    circle.style.strokeDashoffset = offset;

    scoreText.innerText = `${score}%`;

    if (score >= 75) {
        scoreStatus.innerText = "EXCELLENT";
        scoreStatus.className = "text-xs font-black text-emerald-500";
        circle.setAttribute("class", "text-emerald-500 transition-all duration-1000 ease-out");
    } else if (score >= 50) {
        scoreStatus.innerText = "FAIR";
        scoreStatus.className = "text-xs font-black text-amber-500";
        circle.setAttribute("class", "text-amber-500 transition-all duration-1000 ease-out");
    } else {
        scoreStatus.innerText = "CRITICAL RISK";
        scoreStatus.className = "text-xs font-black text-red-500";
        circle.setAttribute("class", "text-red-500 transition-all duration-1000 ease-out");
    }
}

// ============================================================
// 3. LOAN & WALLET OPERATIONS
// ============================================================

function setupLoanRequests() {
    const requestLoanCard = document.querySelector('div[class*="hover:border-blue-600"]');

    if (requestLoanCard) {
        requestLoanCard.addEventListener("click", () => {
            const amount = parseFloat(prompt("Enter the micro-credit amount you need (₦):"));
            if (isNaN(amount) || amount <= 0) return alert("Invalid amount entered.");

            fetch('Api/request_loan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: JSON.stringify({ amount: amount })
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        window.location.reload();
                    }
                })
                .catch(err => {
                    console.error("Loan System API Error:", err);
                    alert("Could not process request. Ensure backend API node is reachable.");
                });
        });
    }
}

function initWalletActions() {
    // DEPOSIT HANDLER
    const depositBtn = document.getElementById("depositBtn");
    if (depositBtn) {
        depositBtn.addEventListener("click", (e) => {
            e.preventDefault();
            const amount = prompt("Enter amount to deposit (₦):");
            if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
                if (amount !== null) alert("Please enter a valid amount.");
                return;
            }

            fetch("Api/deposit_funds.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": getCsrfToken()
                },
                body: JSON.stringify({ amount: parseFloat(amount) })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message || "Deposit successful!");
                        location.reload();
                    } else {
                        showNotification(data.message || "Deposit failed.", false);
                    }
                })
                .catch(() => showNotification("Network error or API endpoint not found.", false));
        });
    }

    // WITHDRAW HANDLER
    const withdrawBtn = document.getElementById("withdrawBtn");
    if (withdrawBtn) {
        withdrawBtn.addEventListener("click", (e) => {
            e.preventDefault();
            const amount = prompt("Enter amount to withdraw (₦):");
            if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
                if (amount !== null) alert("Please enter a valid amount.");
                return;
            }

            fetch("Api/withdraw.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": getCsrfToken()
                },
                body: JSON.stringify({ amount: parseFloat(amount) })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message || "Withdrawal successful!");
                        location.reload();
                    } else {
                        showNotification(data.message || "Withdrawal failed.", false);
                    }
                })
                .catch(() => showNotification("Network error or API endpoint not found.", false));
        });
    }

    // REPAY LOAN HANDLER
    const repayBtn = document.getElementById("repayment");
    if (repayBtn) {
        repayBtn.addEventListener("click", (e) => {
            e.preventDefault();
            const amount = prompt("Enter amount to repay towards your loan (₦):");
            if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
                if (amount !== null) alert("Please enter a valid amount.");
                return;
            }

            fetch("Api/repay.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": getCsrfToken()
                },
                body: JSON.stringify({ amount: parseFloat(amount) })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message || "Repayment successful!");
                        location.reload();
                    } else {
                        showNotification(data.message || "Repayment failed.", false);
                    }
                })
                .catch(() => showNotification("Network error or API endpoint not found.", false));
        });
    }

    // PRIMARY LOAN CARD BUTTON HANDLER
    const loanBtn = document.getElementById("loan");
    if (loanBtn) {
        loanBtn.addEventListener("click", async (e) => {
            e.preventDefault();
            const amount = prompt("Enter the loan amount you need (₦):");
            if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
                if (amount !== null) alert("Please enter a valid amount.");
                return;
            }

            try {
                const response = await fetch("Api/request_loan.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-Token": getCsrfToken()
                    },
                    body: JSON.stringify({ amount: parseFloat(amount) })
                });

                const responseText = await response.text();
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (jsonErr) {
                    console.error("Invalid JSON response:", responseText);
                    showNotification("The loan API returned an invalid response.", false);
                    return;
                }

                showNotification(data.message || "Loan request processed.", data.success);
                if (data.success) location.reload();

            } catch (err) {
                console.error("Loan request error:", err);
                showNotification("Unable to connect to the loan service.", false);
            }
        });
    }
}

// ============================================================
// 4. TRANSACTION HISTORY MODULE
// ============================================================

function fetchTransactionHistory() {
    const tableBody = document.getElementById("transactionHistoryTable");
    if (!tableBody) return;

    fetch("Api/process_transaction.php")
        .then(res => res.json())
        .then(data => {
            if (data.success && Array.isArray(data.transactions) && data.transactions.length > 0) {
                tableBody.innerHTML = data.transactions.map(tx => `
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-semibold capitalize">${escapeHtml(tx.type)}</td>
                        <td class="py-3 px-4 font-bold ${tx.type === 'deposit' ? 'text-green-600' : 'text-slate-900'}">₦${parseFloat(tx.amount).toFixed(2)}</td>
                        <td class="py-3 px-4 text-xs font-mono text-slate-500">${escapeHtml(tx.reference || 'N/A')}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded-md text-xs font-bold ${tx.status === 'completed' ? 'bg-green-100 text-green-700' :
                        tx.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'
                    }">
                                ${escapeHtml(tx.status)}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-xs text-slate-500">${escapeHtml(tx.created_at)}</td>
                    </tr>
                `).join("");
            } else {
                tableBody.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-slate-400">No transaction records found.</td></tr>`;
            }
        })
        .catch(() => {
            tableBody.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-slate-400">No transactions recorded yet.</td></tr>`;
        });

    const refreshTxBtn = document.getElementById("refreshTxBtn");
    if (refreshTxBtn) {
        refreshTxBtn.onclick = fetchTransactionHistory;
    }
}

// ============================================================
// 5. VERIFICATION HANDLER
// ============================================================

function initVerificationModal() {
    const manageVerificationBtn = document.getElementById("manageVerificationBtn");
    const verificationModal = document.getElementById("verificationModal");
    const closeVerificationBtn = document.getElementById("closeVerificationBtn");
    const verificationForm = document.getElementById("verificationForm");

    if (manageVerificationBtn && verificationModal) {
        manageVerificationBtn.addEventListener("click", () => verificationModal.classList.remove("hidden"));
    }

    if (closeVerificationBtn && verificationModal) {
        closeVerificationBtn.addEventListener("click", () => verificationModal.classList.add("hidden"));
    }

    if (verificationModal) {
        verificationModal.addEventListener("click", (e) => {
            if (e.target === verificationModal) verificationModal.classList.add("hidden");
        });
    }

    if (verificationForm) {
        verificationForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById("submitVerificationBtn");
            const fileInput = document.getElementById("verificationDocument");

            if (!fileInput || !fileInput.files.length) {
                alert("Please select your verification document.");
                return;
            }

            const formData = new FormData(verificationForm);
            submitBtn.disabled = true;
            submitBtn.textContent = "Submitting...";

            fetch("Api/submit_verification.php", {
                method: "POST",
                headers: { "X-CSRF-Token": getCsrfToken() },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || "Verification submitted successfully.");
                        verificationModal.classList.add("hidden");
                        location.reload();
                    } else {
                        alert(data.message || "Verification submission failed.");
                    }
                })
                .catch(err => {
                    console.error("Verification error:", err);
                    alert("Unable to submit verification. Please try again.");
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = "Submit Verification";
                });
        });
    }
}

// ============================================================
// 6. VOUCHER MANAGEMENT MODULE
// ============================================================

function initVoucherManagement() {
    const voucherBtn = document.getElementById("voucher");
    const voucherModal = document.getElementById("voucherModal");
    const closeVoucherModal = document.getElementById("closeVoucherModal");

    if (voucherBtn) {
        voucherBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (voucherModal) {
                voucherModal.classList.remove("hidden");
                loadVoucherData();
            }
        });
    }

    if (closeVoucherModal) {
        closeVoucherModal.addEventListener("click", () => voucherModal?.classList.add("hidden"));
    }

    if (voucherModal) {
        voucherModal.addEventListener("click", (e) => {
            if (e.target === voucherModal) voucherModal.classList.add("hidden");
        });
    }
}

function loadVoucherData() {
    const guarantorList = document.getElementById("guarantorList");
    const voucherCurrentStatus = document.getElementById("voucherCurrentStatus");
    const voucherTier1Message = document.getElementById("voucherTier1Message");
    const voucherTier2Content = document.getElementById("voucherTier2Content");
    const voucherTier3Message = document.getElementById("voucherTier3Message");
    const voucherSummary = document.getElementById("voucherSummary");

    fetch("Api/voucher.php?action=my_status")
        .then(async res => ({ ...(await res.json()), httpStatus: res.status }))
        .then(data => {
            if (!data.success) {
                if (voucherCurrentStatus) {
                    voucherCurrentStatus.textContent = data.message || `Voucher service HTTP ${data.httpStatus}`;
                }
                return;
            }

            // TIER 3 LOGIC
            if (data.trust_tier >= 3) {
                if (voucherTier1Message) voucherTier1Message.classList.add("hidden");
                if (voucherTier2Content) voucherTier2Content.classList.add("hidden");
                if (voucherTier3Message) voucherTier3Message.classList.remove("hidden");

                const incomingContent = document.getElementById("incomingVoucherContent");
                if (incomingContent) incomingContent.classList.remove("hidden");
                loadIncomingRequests();
                return;
            }

            // TIER 1 LOGIC
            if (data.trust_tier < 2) {
                if (voucherTier1Message) voucherTier1Message.classList.remove("hidden");
                if (voucherTier2Content) voucherTier2Content.classList.add("hidden");
                if (voucherTier3Message) voucherTier3Message.classList.add("hidden");
                return;
            }

            // TIER 2 LOGIC
            if (voucherTier1Message) voucherTier1Message.classList.add("hidden");
            if (voucherTier3Message) voucherTier3Message.classList.add("hidden");
            if (voucherTier2Content) voucherTier2Content.classList.remove("hidden");

            const status = data.voucher_status || "No voucher request";
            if (voucherCurrentStatus) voucherCurrentStatus.textContent = status.replace("_", " ").toUpperCase();
            if (voucherSummary) voucherSummary.textContent = "Voucher: " + status.replace("_", " ");

            if (status === "pending" || status === "approved") {
                loadIncomingGuarantors(false);
            } else {
                loadIncomingGuarantors(true);
            }
        })
        .catch(() => {
            if (voucherCurrentStatus) voucherCurrentStatus.textContent = "Unable to connect to voucher service.";
        });
}

function loadIncomingGuarantors(showSelection = true) {
    const guarantorList = document.getElementById("guarantorList");
    if (!guarantorList) return;

    if (!showSelection) {
        guarantorList.innerHTML = `<p class="text-sm text-slate-500">You already have a pending or approved voucher.</p>`;
        return;
    }

    guarantorList.innerHTML = `<p class="text-sm text-slate-500">Loading eligible guarantors...</p>`;

    fetch("Api/voucher.php?action=eligible_guarantors")
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                guarantorList.innerHTML = `<p class="text-sm text-red-500">${data.message || "Unable to load guarantors."}</p>`;
                return;
            }

            if (!Array.isArray(data.guarantors) || data.guarantors.length === 0) {
                guarantorList.innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                        <p class="font-semibold text-yellow-700">No eligible guarantors found</p>
                        <p class="text-sm text-yellow-600 mt-1">There are currently no Tier 3 peer guarantors available.</p>
                    </div>`;
                return;
            }

            guarantorList.innerHTML = data.guarantors.map(person => `
                <div class="border border-slate-200 rounded-xl p-4 flex items-center justify-between gap-4">
                    <div>
                        <p class="font-bold text-slate-900">${escapeHtml(person.fullname)}</p>
                        <p class="text-sm text-slate-500">Credit Score: <strong>${person.credit_score}%</strong></p>
                        <p class="text-xs text-green-600 mt-1">${person.successful_repayments} clean repayments</p>
                    </div>
                    <button type="button" class="requestVoucherBtn bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold" data-guarantor-id="${person.id}">
                        Request
                    </button>
                </div>
            `).join("");

            document.querySelectorAll(".requestVoucherBtn").forEach(button => {
                button.addEventListener("click", function () {
                    requestVoucher(this.dataset.guarantorId);
                });
            });
        })
        .catch(() => {
            guarantorList.innerHTML = `<p class="text-sm text-red-500">Unable to load eligible guarantors.</p>`;
        });
}

function requestVoucher(guarantorId) {
    if (!guarantorId || !confirm("Send a voucher request to this student?")) return;

    const formData = new FormData();
    formData.append("guarantor_id", guarantorId);

    fetch("Api/voucher.php?action=request", {
        method: "POST",
        headers: { "X-CSRF-Token": getCsrfToken() },
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            alert(data.message || (data.success ? "Voucher request sent." : "Voucher request failed."));
            if (data.success) loadVoucherData();
        })
        .catch(() => alert("Unable to connect to the voucher service."));
}

function loadIncomingRequests() {
    const incomingList = document.getElementById("incomingVoucherList");
    if (!incomingList) return;

    incomingList.innerHTML = '<p class="text-sm text-slate-500">Loading incoming requests...</p>';

    fetch("Api/voucher.php?action=incoming")
        .then(res => res.json())
        .then(data => {
            if (!data.success || !Array.isArray(data.requests) || data.requests.length === 0) {
                incomingList.innerHTML = '<p class="text-sm text-slate-500">No pending voucher requests.</p>';
                return;
            }

            incomingList.innerHTML = data.requests.map(request => `
                <div class="border border-slate-200 rounded-xl p-4 flex items-center justify-between gap-4">
                    <div>
                        <p class="font-bold text-slate-900">${escapeHtml(request.requester_name)}</p>
                        <p class="text-xs text-slate-500 mt-1">Requests you as a peer guarantor.</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="approveVoucherBtn bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-semibold" data-voucher-id="${request.id}">Approve</button>
                        <button type="button" class="rejectVoucherBtn bg-slate-200 hover:bg-slate-300 text-slate-700 px-3 py-2 rounded-lg text-sm font-semibold" data-voucher-id="${request.id}">Reject</button>
                    </div>
                </div>
            `).join("");

            incomingList.querySelectorAll(".approveVoucherBtn").forEach(button => {
                button.addEventListener("click", () => respondToVoucher(button.dataset.voucherId, "approve"));
            });

            incomingList.querySelectorAll(".rejectVoucherBtn").forEach(button => {
                button.addEventListener("click", () => respondToVoucher(button.dataset.voucherId, "reject"));
            });
        })
        .catch(() => {
            incomingList.innerHTML = '<p class="text-sm text-red-500">Unable to load incoming voucher requests.</p>';
        });
}

function respondToVoucher(voucherId, decision) {
    fetch("Api/voucher.php?action=respond", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": getCsrfToken()
        },
        body: JSON.stringify({ voucher_id: Number(voucherId), decision })
    })
        .then(res => res.json())
        .then(data => {
            alert(data.message || "Voucher response submitted.");
            if (data.success) loadIncomingRequests();
        })
        .catch(() => alert("Unable to respond to the voucher request."));
}

// ============================================================
// 7. AUTO SWEEP MODULE
// ============================================================

function initAutoSweepModule() {
    const sweepBtn = document.getElementById("triggerAutoSweepBtn");
    const toggleInput = document.getElementById("autosweepToggle");
    const quickActionCard = document.querySelector('a#autosweep[href="#"]');

    // Load initial status
    fetchAutoSweepStatus();

    // Event Listener: Execution
    if (sweepBtn) {
        sweepBtn.addEventListener("click", (e) => {
            e.preventDefault();
            executeAutoSweep();
        });
    }

    // Event Listener: Toggle
    if (toggleInput) {
        toggleInput.addEventListener("change", (e) => {
            toggleAutoSweep(e.target.checked);
        });
    }

    // Smooth Scroll for quick action card
    if (quickActionCard) {
        quickActionCard.addEventListener("click", (e) => {
            e.preventDefault();
            const section = document.getElementById("wallet");
            if (section) section.scrollIntoView({ behavior: "smooth", block: "center" });
        });
    }
}

function fetchAutoSweepStatus() {
    const toggleInput = document.getElementById("autosweepToggle");
    const statusText = document.getElementById("autosweepStatusText");

    fetch("Includes_dynamics/autosweep.php?action=status", { method: "GET" })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (toggleInput) toggleInput.checked = data.auto_sweep_enabled;
                if (statusText) {
                    statusText.textContent = data.auto_sweep_enabled
                        ? "Status: Active (Automatic debt deduction enabled)"
                        : "Status: Inactive (Automatic debt deduction paused)";
                }
            } else if (statusText) {
                statusText.textContent = data.message || "Failed to load status.";
            }
        })
        .catch(err => console.error("Auto Sweep status fetch error:", err));
}

function toggleAutoSweep(enabled) {
    const statusText = document.getElementById("autosweepStatusText");
    const formData = new FormData();
    formData.append("action", "toggle");
    formData.append("enabled", enabled ? "true" : "false");
    formData.append("csrf_token", getCsrfToken());

    fetch("Includes_dynamics/autosweep.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (statusText) {
                    statusText.textContent = data.auto_sweep_enabled
                        ? "Status: Active (Automatic debt deduction enabled)"
                        : "Status: Inactive (Automatic debt deduction paused)";
                }
                showAutoSweepBanner(data.message || `Auto Sweep ${enabled ? "enabled" : "disabled"}.`, "success");
            } else {
                const toggleInput = document.getElementById("autosweepToggle");
                if (toggleInput) toggleInput.checked = !enabled;
                showAutoSweepBanner(data.message || "Failed to update status.", "error");
            }
        })
        .catch(err => {
            console.error("Auto Sweep toggle error:", err);
            showAutoSweepBanner("Server communication error.", "error");
        });
}

function executeAutoSweep() {
    const sweepBtn = document.getElementById("triggerAutoSweepBtn");
    if (!sweepBtn) return;

    sweepBtn.disabled = true;
    sweepBtn.innerHTML = `<span class="inline-block animate-spin mr-1">↻</span> Processing...`;

    const formData = new FormData();
    formData.append("action", "execute");
    formData.append("csrf_token", getCsrfToken());

    fetch("Includes_dynamics/autosweep.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAutoSweepBanner(data.message, "success");
                if (data.amount_swept && data.amount_swept > 0) {
                    setTimeout(() => location.reload(), 1500);
                }
            } else {
                showAutoSweepBanner(data.message || "Auto Sweep failed to process.", "error");
            }
        })
        .catch(err => {
            console.error("Auto Sweep execution error:", err);
            showAutoSweepBanner("An unexpected error occurred during execution.", "error");
        })
        .finally(() => {
            sweepBtn.disabled = false;
            sweepBtn.innerHTML = `<span>Run Auto Sweep Now</span><span class="text-lg">→</span>`;
        });
}

function showAutoSweepBanner(message, type) {
    const banner = document.getElementById("autosweepBanner");
    if (!banner) return;

    banner.classList.remove("hidden", "bg-green-100", "text-green-800", "bg-red-100", "text-red-800");
    banner.classList.add(type === "success" ? "bg-green-100" : "bg-red-100");
    banner.classList.add(type === "success" ? "text-green-800" : "text-red-800");
    banner.textContent = message;

    setTimeout(() => {
        banner.classList.add("hidden");
    }, 5000);
}