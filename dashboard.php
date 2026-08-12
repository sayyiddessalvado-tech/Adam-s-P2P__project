 <?php
// 1. Start Session & Require Authentication
session_start();
require_once 'Includes_dynamics/dataB.php'; 

// 2. Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: p2p.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// 3. Fetch Student User & Wallet data dynamically from DB
try {
    // Get User Details
    $user_stmt = $conn->prepare("SELECT fullname, FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result()->fetch_assoc();
    $student_name = $user_res['fullname'] ?? 'Student';
     
    // Get Wallet Details
    $wallet_stmt = $conn->prepare("SELECT balance, debt, trust_score, crf_status, is_defaulted FROM wallets WHERE user_id = ?");
    $wallet_stmt->bind_param("i", $user_id);
    $wallet_stmt->execute();
    $wallet = $wallet_stmt->get_result()->fetch_assoc();

    $balance = $wallet['balance'] ?? 0.00;
    $outstanding_debt = $wallet['debt'] ?? 0.00;
    $trust_score = $wallet['trust_score'] ?? 80;
    $crf_status = $wallet['crf_status'] ?? 'unsubmitted';
    $is_defaulted = $wallet['is_defaulted'] ?? 0;

} catch (Exception $e) {
    die("System connection fault: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | EduLend</title>
    <script type="text/javascript" src="./assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="assets/p2p.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans min-h-screen">

    <div id="wallet-status-banner" class="<?php echo $is_defaulted ? '' : 'hidden'; ?> bg-red-600 text-white text-center py-2 text-sm font-bold animate-pulse">
        ⚠️ ACCOUNT LOCKED: Deposits will automatically go toward clearing your unpaid debt balance.
    </div>

    <nav class="bg-white shadow-sm py-4 px-8 flex justify-between items-center sticky top-0 z-30">
        <div class="flex items-center gap-2">
            <div class="bg-blue-600 text-white p-2 rounded-lg">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <h1 class="text-xl font-bold text-gray-900 tracking-tight">EduLend</h1>
        </div>
        <div class="flex items-center gap-6">
            <div class="text-right hidden md:block">
                <p class="text-sm font-bold text-gray-900" id="userName"><?php echo htmlspecialchars($student_name); ?></p>
                <p class="text-xs text-gray-500">Verified Account (<?php echo htmlspecialchars($matric_no); ?>)</p>
            </div>
            <button onclick="localStorage.removeItem('edulend_session'); window.location.href='logout.php';"
                class="text-gray-400 hover:text-red-500 transition text-xl">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6 grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center">
                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-6">Credit Trust Rating</h3>
                <div class="relative inline-flex items-center justify-center">
                    <svg class="w-40 h-40 transform -rotate-90">
                        <circle cx="80" cy="80" r="70" stroke="currentColor" stroke-width="12" fill="transparent" class="text-gray-100" />
                        <circle id="scoreCircle" cx="80" cy="80" r="70" stroke="currentColor" stroke-width="12"
                            fill="transparent" stroke-dasharray="440" stroke-dashoffset="440"
                            class="text-blue-600 transition-all duration-1000 ease-out" />
                    </svg>
                    <div class="absolute flex flex-col items-center">
                        <span class="text-4xl font-black text-gray-900" id="scoreValue"><?php echo $trust_score; ?>%</span>
                        <span class="text-xs font-bold text-emerald-500" id="scoreStatus">EXCELLENT</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-6 leading-relaxed">
                    Rating drops to <span class="text-red-500 font-bold">20%</span> immediately upon missed milestones.
                </p>
            </div>

            <div class="bg-blue-600 p-6 rounded-2xl shadow-lg text-white relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-blue-100 text-xs font-bold uppercase tracking-widest mb-1">Available Balance</p>
                    <h2 class="text-4xl font-black mb-6" id="wallet-balance">
                        ₦<?php echo number_format($balance, 2); ?>
                    </h2>
                    <div class="flex gap-3">
                        <button id="btn-deposit" class="flex-1 bg-white text-blue-600 py-2.5 rounded-xl font-bold text-sm hover:bg-blue-50 transition">
                            <i class="fa-solid fa-plus mr-2"></i> Deposit
                        </button>
                        <button id="btn-withdraw" class="flex-1 bg-blue-500 text-white py-2.5 rounded-xl font-bold text-sm hover:bg-blue-400 transition">
                            Withdraw
                        </button>
                    </div>
                </div>
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-blue-500 rounded-full opacity-50"></div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-100 flex justify-between items-center">
                <span class="text-sm font-bold text-gray-500">Unpaid Debt:</span>
                <span id="wallet-debt" class="text-lg font-black text-rose-600">
                    ₦<?php echo number_format($outstanding_debt, 2); ?>
                </span>
            </div>
        </div>

        <div class="lg:col-span-8 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mt-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="font-bold text-gray-900 flex items-center gap-2">
                            <i class="fa-solid fa-file-invoice text-blue-600"></i> Semester Academic Verification (Tier 3)
                        </h4>
                        <p class="text-xs text-gray-500 mt-0.5">Upload your stamped Course Registration Form to drop peer voucher demands.</p>
                    </div>
                    <span class="text-[10px] bg-amber-100 text-amber-800 font-bold px-2.5 py-1 rounded-full border border-amber-200 uppercase tracking-wider">
                        Verification: <?php echo htmlspecialchars(ucfirst($crf_status)); ?>
                    </span>
                </div>
                <form id="crfUploadForm" action="Api/verify_user.php" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3 mt-2">
                    <input type="hidden" name="action" value="submit_crf_tier3">
                    <input type="file" name="crf_pdf" accept=".pdf,.jpg,.jpeg" required class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-100 rounded-xl p-1.5 bg-gray-50/50"/>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-5 py-3 rounded-xl transition shadow-sm whitespace-nowrap">
                        Submit Document
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
                <div class="flex items-center gap-2 mb-2 text-rose-600">
                    <i class="fa-solid fa-bullhorn text-sm"></i>
                    <h4 class="font-bold text-gray-900">Institutional Public Risk Registry</h4>
                </div>
                <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                    Profiles displayed below have exceeded their structured micro-loan maturity timelines:
                </p>
                <div class="overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-rose-50 text-rose-800 uppercase font-bold text-[10px] tracking-wider">
                            <tr>
                                <th class="p-3">Student Identifier</th>
                                <th class="p-3">Ecosystem Status</th>
                                <th class="p-3 text-right">Reputation Degradation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                            <?php
                            $flagged_query = "SELECT users.matric_no, wallets.trust_score FROM wallets JOIN users ON wallets.user_id = users.id WHERE wallets.is_defaulted = 1";
                            $flagged_result = $conn->query($flagged_query);
                            if ($flagged_result && $flagged_result->num_rows > 0):
                                while($row = $flagged_result->fetch_assoc()):
                            ?>
                                <tr class="bg-rose-50/10 hover:bg-rose-50/20 transition">
                                    <td class="p-3 font-mono font-bold text-gray-900"><?php echo htmlspecialchars($row['matric_no']); ?></td>
                                    <td class="p-3"><span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 font-bold text-[9px] uppercase tracking-wide">FLAGGED DEFAULT</span></td>
                                    <td class="p-3 text-right text-rose-600 font-bold font-mono"><?php echo $row['trust_score']; ?>% Trust Floor</td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="3" class="p-4 text-center text-gray-400 italic bg-gray-50/50">No profiles currently flagged for default in this institution block.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900">Transaction History</h3>
                    <button class="text-blue-600 text-sm font-bold hover:underline">View All</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Description</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php
                            // Fetch user's real transactions from database
                            $tx_stmt = $conn->prepare("SELECT description, tx_id, created_at, amount, type FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 5");
                            $tx_stmt->bind_param("i", $user_id);
                            $tx_stmt->execute();
                            $tx_res = $tx_stmt->get_result();

                            if ($tx_res && $tx_res->num_rows > 0):
                                while ($tx = $tx_res->fetch_assoc()):
                                    $is_credit = ($tx['type'] === 'credit' || $tx['type'] === 'deposit' || $tx['type'] === 'disbursement');
                                    $amount_color = $is_credit ? 'text-emerald-500' : 'text-rose-500';
                                    $prefix = $is_credit ? '+' : '-';
                            ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($tx['description']); ?></p>
                                        <p class="text-xs text-gray-500">ID: #<?php echo htmlspecialchars($tx['tx_id']); ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo date("M d, Y", strtotime($tx['created_at'])); ?></td>
                                    <td class="px-6 py-4 text-sm font-bold <?php echo $amount_color; ?> text-right">
                                        <?php echo $prefix; ?>₦<?php echo number_format($tx['amount'], 2); ?>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-gray-400 italic">No transactions recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/dashboard.js" defer></script>
    <script src="assets/js/wallet.js" defer></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Smoothly render SVG arc animation based on DB trust score
            const realTrustScore = <?php echo intval($trust_score); ?>;
            
            const circle = document.getElementById('scoreCircle');
            if (circle) {
                const radius = circle.r.baseVal.value;
                const circumference = 2 * Math.PI * radius;
                const offset = circumference - (realTrustScore / 100) * circumference;
                circle.style.strokeDasharray = `${circumference} ${circumference}`;
                circle.style.strokeDashoffset = offset;
            }

            // Adjust trust text badge
            const statusText = document.getElementById('scoreStatus');
            if(statusText) {
                if(realTrustScore >= 80) statusText.innerText = "EXCELLENT";
                else if(realTrustScore >= 50) statusText.innerText = "AVERAGE";
                else statusText.innerText = "RISK ALERT";
            }
        });
    </script>
</body>
</html>