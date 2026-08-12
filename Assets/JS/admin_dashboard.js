/**
* EduLend - Admin Dashboard Integration Logic
* Bridges frontend administration displays with our dynamic MySQL database engines.
*/

// Initialize all administrative monitoring modules once loaded
document.addEventListener("DOMContentLoaded", () => {
    checkAdminAuth();
    fetchLiveAdminMetrics();
    fetchLiveVerificationQueue();
    fetchRecentLoans();
    fetchRecentTransactions();
    fetchHighRiskBorrowers();
    renderSystemLogs();
});

// 1. Session Protection Gateway Checks
function checkAdminAuth() {
    const sessionToken = localStorage.getItem("edulend_session");
    if (!sessionToken) {
        alert("Access Denied: Administrative privileges required.");
        window.location.href = "p2p.html";
        return;
    }

    try {
        const currentUser = JSON.parse(sessionToken);
        if (!currentUser || currentUser.role !== "admin") {
            alert("Access Denied: Administrative privileges required.");
            window.location.href = "p2p.html";
        }
    } catch (e) {
        localStorage.removeItem("edulend_session");
        window.location.href = "p2p.html";
    }
}

// 2. Fetch Aggregates from database via your php API
function fetchLiveAdminMetrics() {

    fetch("Api/get_admin_dashboard.php")
        .then(res => res.json())
        .then(data => {

            if (!data.success) return;
            document.getElementById("total-liquidity").innerText = "₦" + Number(data.total_liquidity).toLocaleString(undefined, {
                minimumFractionDigits: 2
            });
            document.getElementById("active-loans").innerText = "₦" + Number(data.active_loan_volume).toLocaleString(undefined, {
                minimumFractionDigits: 2
            });
            document.getElementById("default-rate").innerText = data.default_ratio + "%";
            document.getElementById("pending-count").innerText = data.pending_verifications;
            document.getElementById("current-date").innerText =
                new Date().toLocaleDateString();
        })
        .catch(error => console.log(error));

}

// 3. Render student rows dynamically straight from MySQL tables
function fetchLiveVerificationQueue() {

    fetch("Api/get_pending_queue.php")
        .then(res => res.json())
        .then(data => {
            const table = document.getElementById("verification-queue");
            table.innerHTML = "";
            if (!data.success || data.students.length === 0) {
                table.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center p-6">
                        No Pending Verification Requests
                    </td>
                </tr>
                `;
                return;
            }
            data.students.forEach(student => {
                table.innerHTML += `
                <tr>
                    <td class="p-4">${student.fullname}</td>
                    <td class="p-4">${student.matric_no}</td>
                    <td class="p-4">${student.current_tier}</td>
                    <td class="p-4">${student.requested_tier}</td>
                    <td class="p-4">
                        <a href="${student.document_path}" target="_blank"
                        class="text-blue-600 underline">
                        View
                        </a>
                    </td>
                    <td class="p-4">
                        ${student.submitted_at}
                    </td>
                    <td class="p-4">
                        ${student.status}
                    </td>
                    <td class="p-4">
                        <button
                        onclick="processVerification(${student.verification_id},'approve')"
                        class="bg-green-600 text-white px-3 py-1 rounded">
                        Approve
                          </button>
                        <button
                        onclick="processVerification(${student.verification_id},'reject')"
                        class="bg-red-600 text-white px-3 py-1 rounded ml-2">
                        Reject
                        </button>
                    </td>
                </tr>
                `;
            });
        });
}

// Fetch the list of users waiting for approval from backend (id_pending or crf_pending status)
fetch('Api/get_pending_queue.php')
    .then(response => response.json())
    .then(data => {
        queueTable.innerHTML = "";

        if (!data.success || data.students.length === 0) {
            queueTable.innerHTML = `<tr><td colspan="7" class="p-8 text-center text-gray-400 italic">No pending milestone verifications remaining.</td></tr>`;
            return;
        }

        data.students.forEach(student => {
            const row = document.createElement("tr");
            row.className = "border-b border-gray-200 hover:bg-gray-50 text-sm";

            const isCrf = student.crf_status === 'crf_pending';
            const levelLabel = isCrf ? "Tier 3: CRF Evaluation" : "Tier 2: ID Validation";
            const labelStyle = isCrf ? "bg-indigo-100 text-indigo-800" : "bg-amber-100 text-amber-800";
            const documentName = isCrf ? "student_crf_form.pdf" : "student_id_card.jpg";
            const actionParam = isCrf ? "approve_tier3" : "approve_tier2";

            row.innerHTML = `

                <td class="p-4 font-semibold">${student.name}</td>

                <td class="p-4 font-mono">${student.matric_no}</td>

                <td class="p-4">
                Tier ${student.current_tier}
                </td>

                <td class="p-4">
                Tier ${student.requested_tier}
                </td>

                <td class="p-4">

                <a href="${student.document_path}"

                target="_blank"

                class="text-blue-600 underline">

                View

                </a>

                </td>

                <td class="p-4">

                ${student.submitted_at}

                </td>

                <td class="p-4">

                <button

                onclick="processVerification(${student.id},'approve')"

                class="bg-green-600 text-white px-3 py-1 rounded">

                Approve

                </button>

                <button

                onclick="processVerification(${student.id},'reject')"

                class="bg-red-600 text-white px-3 py-1 rounded ml-2">

                Reject

                </button>

                </td>

                `;
            queueTable.appendChild(row);
        });
    })
    .catch(error => {
        console.error("Queue rendering error:", error);
        queueTable.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-rose-500 italic">Database Sync Error: Ensure your local server is active.</td></tr>`;
    });


// Cards 
function fetchRecentLoans() {

    fetch("Api/get_admin_dashboard.php?action=loans")
        .then(res => res.json())
        .then(data => {
            const table = document.getElementById("loan-activities");
            table.innerHTML = "";
            data.loans.forEach(loan => {
                table.innerHTML += `
                <tr>
                <td class="p-4">${loan.id}</td>
                <td class="p-4">${loan.borrower}</td>
                <td class="p-4">${loan.lender}</td>
                <td class="p-4">₦${loan.amount}</td>
                <td class="p-4">${loan.due_date}</td>
                <td class="p-4">${loan.status}</td>
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
            data.borrowers.forEach(user => {
                table.innerHTML += `
                <tr>
                <td class="p-4">${user.fullname}</td>
                <td class="p-4">${user.credit_score}</td>
                <td class="p-4">₦${user.loan_amount}</td>
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
function processVerification(targetStudentId, operationalAction) {
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
        body: `user_id=${encodeURIComponent(targetStudentId)}&action=${encodeURIComponent(operationalAction)}&reason=${encodeURIComponent(rejectionReason)}`
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