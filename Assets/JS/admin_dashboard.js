/**
* EduLend - Admin Dashboard Integration Logic
* Bridges frontend administration displays with our dynamic MySQL database engines.
*/

// Initialize all administrative monitoring modules once loaded
document.addEventListener("DOMContentLoaded", () => {
    fetchLiveAdminMetrics();
    fetchLiveVerificationQueue();
    fetchRecentLoans();
    fetchRecentTransactions();
    fetchHighRiskBorrowers();
    renderSystemLogs();
});



// 2. Fetch Aggregates from database via your php API
function fetchLiveAdminMetrics() {

    fetch("Api/get_admin_dashboard.php")
        .then(res => res.json())
        .then(data => {

            if (!data.success) return;
            const metrics = data.metrics || {};

            document.getElementById("total-liquidity").innerText =
                "₦" + Number(metrics.total_liquidity || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

            document.getElementById("active-loans").innerText =
                "₦" + Number(metrics.active_loan_volume || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

            document.getElementById("default-rate").innerText =
                Number(metrics.default_ratio || 0).toFixed(2) + "%";

            document.getElementById("pending-count").innerText =
                Number(metrics.pending_verifications_count || 0);
            document.getElementById("current-date").innerText =
                new Date().toLocaleDateString();
        })
        .catch(error => console.log(error));

}

// Fetch the list of users waiting for approval from the backend.
function fetchLiveVerificationQueue() {
    const queueTable = document.getElementById("verification-queue");
    if (!queueTable) return;

    fetch("/EduLend/Api/get_pending_queue.php")
        .then(response => response.json())
        .then(data => {
            queueTable.innerHTML = "";

            if (!data.success || !Array.isArray(data.students) || data.students.length === 0) {
                queueTable.innerHTML = '<tr><td colspan="8" class="p-8 text-center text-gray-400 italic">No pending milestone verifications remaining.</td></tr>';
                return;
            }

            data.students.forEach(student => {
                const row = document.createElement("tr");
                row.className = "border-b border-slate-800 hover:bg-slate-800/60 text-sm";

                row.innerHTML = `
                    <td class="p-4 font-semibold">${escapeHtml(student.name)}</td>
                    <td class="p-4 font-mono">${escapeHtml(student.matric_no || "N/A")}</td>
                    <td class="p-4">${escapeHtml(student.current_tier)}</td>
                    <td class="p-4">${escapeHtml(student.requested_tier)}</td>
                    <td class="p-4"><a href="${escapeHtml(student.document_path)}" target="_blank" class="text-blue-400 underline">View</a></td>
                    <td class="p-4">${escapeHtml(student.submitted_at)}</td>
                    <td class="p-4"><span class="text-amber-400 font-bold">Pending</span></td>
                    <td class="p-4 text-center">
                        <button onclick="processVerification(${Number(student.verification_id)}, 'approve')" class="bg-green-600 hover:bg-green-500 text-white px-3 py-1 rounded">Approve</button>
                        <button onclick="processVerification(${Number(student.verification_id)}, 'reject')" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1 rounded ml-2">Reject</button>
                    </td>
                `;

                queueTable.appendChild(row);
            });
        })
        .catch(error => {
            console.error("Queue rendering error:", error);
            queueTable.innerHTML = '<tr><td colspan="8" class="p-8 text-center text-rose-500 italic">Database sync error.</td></tr>';
        });
}

function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
}


// Cards 
function fetchRecentLoans() {

    fetch("Api/get_admin_dashboard.php?action=loans")
        .then(res => res.json())
        .then(data => {
            const table = document.getElementById("loan-activities");
            table.innerHTML = "";
            (data.recent_loans || []).forEach(loan => {
                table.innerHTML += `
                <tr>
                <td class="p-4">${loan.id}</td>
                <td class="p-4">${loan.borrower}</td>
                <td class="p-4">${loan.lender}</td>
                <td class="p-4">₦${loan.amount}</td>
                <td class="p-4">${loan.due_date}</td>
                <td class="p-4">
                    <span>${loan.status}</span>
                    ${loan.status === "pending" ? `
                        <div class="mt-2 flex gap-2">
                            <button onclick="processLoanReview(${Number(loan.id)}, 'approve')" class="bg-emerald-600 hover:bg-emerald-500 text-white px-2 py-1 rounded text-xs font-bold">Approve & Fund</button>
                            <button onclick="processLoanReview(${Number(loan.id)}, 'reject')" class="bg-rose-600 hover:bg-rose-500 text-white px-2 py-1 rounded text-xs font-bold">Reject</button>
                        </div>` : ""}
                </td>
                </tr>
                `;
            });
        });
}

function fetchRecentTransactions() {

    fetch("Api/get_admin_dashboard.php?action=transactions")
        .then(res => res.json())
        .then(data => {
            const table = document.getElementById("recent-transactions");
            table.innerHTML = "";
            data.transactions.forEach(transaction => {
                table.innerHTML += `
                <tr>
                <td class="p-4">${transaction.id}</td>
                <td class="p-4">${transaction.user}</td>
                <td class="p-4">${transaction.type}</td>
                <td class="p-4">₦${transaction.amount}</td>
                <td class="p-4">${transaction.created_at}</td>
                <td class="p-4">${transaction.status}</td>
                </tr>
                `;
            });
        });
}

function fetchHighRiskBorrowers() {

    fetch("Api/get_admin_dashboard.php?action=risk")
        .then(res => res.json())
        .then(data => {
            const table = document.getElementById("high-risk-users");
            table.innerHTML = "";
            (data.high_risk_users || []).forEach(user => {
                table.innerHTML += `
                <tr>
                <td class="p-4">${user.name}</td>
                <td class="p-4">${user.credit_score}</td>
                <td class="p-4">₦${user.outstanding_loan}</td>
                <td class="p-4">
                    <span class="text-red-600 font-bold">
                    ${user.status}
                    </span>
                </td>
                </tr>
                `;
            });
        });
}

// 4. Governance Action Router to change Student verification statuses
function processVerification(verificationId, operationalAction) {
    let rejectionReason = "";
    if (operationalAction === "reject") {
        rejectionReason = prompt("Please enter the reason for rejecting this document:");
        if (rejectionReason === null) return; // User cancelled prompt
        if (rejectionReason.trim() === "") {
            alert("Action cancelled: Rejection requires a valid reason feedback message.");
            return;
        }
    }

    fetch('Api/verify_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `verification_id=${encodeURIComponent(verificationId)}&action=${encodeURIComponent(operationalAction)}&reason=${encodeURIComponent(rejectionReason)}`
    })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                // Reload layout to display fresh database states
                fetchLiveAdminMetrics();
                fetchLiveVerificationQueue();
            }
        })
        .catch(error => console.error("Critical governance operation failure:", error));
}

//Governance and Compliance 
// Tab Switching Controller for Admin Console
function switchAdminTab(targetTabId, btnElement) {
    //  Hide all tab panes
    const panes = document.querySelectorAll('.tab-pane');
    panes.forEach(pane => pane.classList.add('hidden'));

    //  Display the selected tab pane
    const activePane = document.getElementById(targetTabId);
    if (activePane) {
        activePane.classList.remove('hidden');
    }

    // Special handling if switching to verifications from sidebar
    if (targetTabId === 'tab-verifications') {
        const verifPane = document.getElementById('tab-overview');
        if (verifPane) verifPane.classList.remove('hidden');
    }

    //  Reset active button styling
    const buttons = document.querySelectorAll('.admin-tab-btn');
    buttons.forEach(btn => {
        btn.classList.remove('bg-slate-800', 'text-white', 'border', 'border-slate-700');
        btn.classList.add('text-slate-400');
    });

    //  Highlight current button if passed
    if (btnElement && btnElement.classList.contains('admin-tab-btn')) {
        btnElement.classList.remove('text-slate-400');
        btnElement.classList.add('bg-slate-800', 'text-white', 'border', 'border-slate-700');
    }
}

// 5. System Log Rendering
function renderSystemLogs() {
    const logContainer = document.getElementById("system-logs");
    if (!logContainer) return;

    const logs = [

        "[INFO] Admin authenticated successfully.",
        "[INFO] Investment pool synchronized.",
        "[INFO] Loan monitoring service online.",
        "[INFO] Transaction ledger synchronized.",
        "[INFO] Verification engine active.",
        "[INFO] Risk assessment engine active.",
        "[INFO] EduLend platform operational."
    ];

    logContainer.innerHTML = logs.map(log => `<p class="font-mono text-xs text-emerald-400 my-1 opacity-90">${log}</p>`).join("");
}

async function processLoanReview(loanId, operationalAction) {
    let reason = '';
    if (operationalAction === 'reject') {
        reason = prompt('Reason for rejecting this loan request:');
        if (reason === null || reason.trim() === '') return;
    }

    try {
        const csrfResponse = await fetch('Api/get_csrf.php');
        const csrfData = await csrfResponse.json();
        const response = await fetch('Api/review_loan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfData.csrf_token || ''
            },
            body: JSON.stringify({
                loan_id: loanId,
                action: operationalAction,
                reason: reason
            })
        });
        const data = await response.json();
        alert(data.message || 'Loan review completed.');
        if (data.success) {
            fetchLiveAdminMetrics();
            fetchRecentLoans();
        }
    } catch (error) {
        console.error('Loan review failed:', error);
        alert('Unable to complete the loan review.');
    }
}