/**
* EduLend - Admin Dashboard Logic (Fixed & Aligned)
* Handles milestone verification using real school documents.
*/

// Move this outside the function so we can remove students dynamically when clicked!
let pendingStudents = [
    { id: "STU042", name: "Sayyid Adam", milestone: "Official Admission Letter", requestedTier: "Tier 1 Access", document: "admission_letter.pdf" },
    { id: "STU109", name: "Des Salvador", milestone: "Course Registration Form (Sem 1)", requestedTier: "Tier 2 Access", document: "course_form_signed.png" }
];

// REMOVED THE DOMContentLoaded WRAPPER SO IT RUNS IMMEDIATELY AT THE BOTTOM OF YOUR HTML
checkAdminAuth();
renderMetrics();
renderVerificationQueue();
renderSystemLogs();


function checkAdminAuth() {
    // 1. Look for the passport card saved by p2p.html
    const currentUser = JSON.parse(localStorage.getItem("edulend_session"));

    // 2. If no card exists, or if the card role is NOT 'admin', deny access instantly!
    if (!currentUser || currentUser.role !== "admin") {
        alert("Access Denied: Administrative privileges required.");
        window.location.href = "p2p.html"; // Boot them back to login page
    }

    // If it reaches here, the user is a valid admin, and the dashboard loads smoothly!
    console.log("Authentication successful. Welcome, Admin.");
}



function renderMetrics() {
    const metrics = {
        totalLiquidity: "₦4,500,000.00",
        activeLoans: "₦1,250,000.00",
        defaultRate: "1.2%",
        pendingVerifications: pendingStudents.length
    };

    if (document.getElementById("total-liquidity")) {
        document.getElementById("total-liquidity").innerText = metrics.totalLiquidity;
        document.getElementById("active-loans").innerText = metrics.activeLoans;
        document.getElementById("default-rate").innerText = metrics.defaultRate;
        document.getElementById("pending-count").innerText = metrics.pendingVerifications;
    }
}

function renderVerificationQueue() {
    const queueTable = document.getElementById("verification-queue");
    if (!queueTable) return;

    queueTable.innerHTML = "";

    if (pendingStudents.length === 0) {
        queueTable.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-gray-400 italic">No pending verifications remaining.</td></tr>`;
        return;
    }

    pendingStudents.forEach(student => {
        const row = document.createElement("tr");
        row.className = "border-b border-gray-200 hover:bg-gray-50 text-sm";

        row.innerHTML = `
            <td class="p-4 font-medium text-gray-900">${student.id}</td>
            <td class="p-4 text-gray-700">${student.name}</td>
            <td class="p-4 text-gray-600"><span class="bg-amber-100 text-amber-800 px-2 py-1 rounded text-xs font-semibold">${student.milestone}</span></td>
            <td class="p-4 text-gray-700">${student.requestedTier}</td>
            <td class="p-4">
                <button class="text-indigo-600 hover:underline font-medium text-xs view-doc-btn"><i class="fa-solid fa-file-pdf mr-1"></i> ${student.document}</button>
            </td>
            <td class="p-4 flex gap-2">
                <button class="approve-btn bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1 rounded text-xs font-semibold shadow-sm transition">Approve & Upgrade</button>
                <button class="reject-btn bg-rose-500 hover:bg-rose-600 text-white px-3 py-1 rounded text-xs font-semibold shadow-sm transition">Reject</button>
            </td>
        `;

        row.querySelector(".approve-btn").addEventListener("click", () => verifyMilestone(student.id, true));
        row.querySelector(".reject-btn").addEventListener("click", () => verifyMilestone(student.id, false));
        row.querySelector(".view-doc-btn").addEventListener("click", () => alert(`Viewing document: ${student.document}`));

        queueTable.appendChild(row);
    });
}

function verifyMilestone(studentId, isApproved) {
    if (isApproved) {
        alert(`Document verified! Student ${studentId} has been upgraded to their next borrowing tier.`);
    } else {
        const reason = prompt("Enter reason for document rejection:");
        if (reason === null) return;
        if (reason.trim() === "") {
            alert("Action cancelled: A valid rejection reason is required.");
            return;
        }
        alert(`Document rejected. Notification sent to student: "${reason}"`);
    }

    pendingStudents = pendingStudents.filter(student => student.id !== studentId);

    renderVerificationQueue();
    renderMetrics();
}

function renderSystemLogs() {
    const logContainer = document.getElementById("system-logs");
    if (!logContainer) return;

    const logs = [
        "[INFO] System verified Escrow matching protocols.",
        "[CRITICAL] Student STU091 missed repayment date. Account locked to DEFAULT state.",
        "[INFO] New Admission Letter upload detected from User STU114."
    ];
    logContainer.innerHTML = logs.map(log => `<p class="font-mono text-xs text-emerald-400 my-1 opacity-90">${log}</p>`).join("");
}