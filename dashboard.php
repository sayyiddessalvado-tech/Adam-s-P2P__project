 ```php
<?php
// dashboard.php

session_start();

require_once 'Includes_dynamics/dataB.php';

// ============================================================
// SESSION AND ROLE PROTECTION
// ============================================================

if (!isset($_SESSION['user_id'])) {
    header("Location: p2p.html");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: p2p.html");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// ============================================================
// STUDENT PROFILE AND WALLET DATA
// ============================================================

$stmt = $conn->prepare("
    SELECT
        u.id,
        u.fullname,
        u.email,
        w.balance,
        w.debt,
        w.credit_score,
        w.trust_tier,
        w.successful_repayments,
        w.is_defaulted,
        w.crf_status
    FROM users u
    INNER JOIN wallets w ON u.id = w.user_id
    WHERE u.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_destroy();

    header("Location: p2p.html");
    exit();
}

$student = $result->fetch_assoc();

$fullname = $student['fullname'];
$email = $student['email'];

$balance = (float) $student['balance'];
$debt = (float) $student['debt'];

$credit_score = (int) $student['credit_score'];
$trust_tier = (int) $student['trust_tier'];

$successful_repayments = (int) $student['successful_repayments'];
$is_defaulted = (int) $student['is_defaulted'];

$crf_status = $student['crf_status'];

// ============================================================
// CREDIT SCORE STATUS
// ============================================================

if ($credit_score >= 80) {
    $credit_status = "Excellent";
    $credit_color = "text-green-600";
    $credit_bg = "bg-green-100";
} elseif ($credit_score >= 60) {
    $credit_status = "Good";
    $credit_color = "text-blue-600";
    $credit_bg = "bg-blue-100";
} elseif ($credit_score >= 40) {
    $credit_status = "Fair";
    $credit_color = "text-yellow-600";
    $credit_bg = "bg-yellow-100";
} else {
    $credit_status = "Needs Improvement";
    $credit_color = "text-red-600";
    $credit_bg = "bg-red-100";
}

// ============================================================
// ACCOUNT STATUS
// ============================================================

if ($is_defaulted === 1) {
    $account_status = "Defaulted";
    $account_color = "text-red-600";
    $account_bg = "bg-red-100";
} elseif ($debt > 0) {
    $account_status = "Active Loan";
    $account_color = "text-blue-600";
    $account_bg = "bg-blue-100";
} else {
    $account_status = "Good Standing";
    $account_color = "text-green-600";
    $account_bg = "bg-green-100";
}

// ============================================================
// CRF STATUS DISPLAY
// ============================================================

$crf_labels = [
    "unsubmitted" => "Not submitted",
    "id_pending" => "Student ID under review",
    "crf_pending" => "CRF under review",
    "approved" => "CRF approved",
    "rejected" => "Needs resubmission"
];

$crf_display = $crf_labels[$crf_status] ?? ucwords(str_replace('_', ' ', $crf_status));

if (in_array($crf_status, ["id_pending", "crf_pending"], true)) {
    $crf_color = "text-blue-600";
    $crf_bg = "bg-blue-100";
} elseif ($crf_status === "approved") {
    $crf_color = "text-green-600";
    $crf_bg = "bg-green-100";
} elseif ($crf_status === "rejected") {
    $crf_color = "text-red-600";
    $crf_bg = "bg-red-100";
} else {
    $crf_color = "text-yellow-600";
    $crf_bg = "bg-yellow-100";
}

// ============================================================
// DASHBOARD NUMBERS
// ============================================================

$available_balance = number_format($balance, 2);

$total_debt = number_format($debt, 2);

$student_initials = "";

$name_parts = explode(" ", trim($fullname));

foreach ($name_parts as $part) {
    if (!empty($part)) {
        $student_initials .= strtoupper(substr($part, 0, 1));
    }

    if (strlen($student_initials) >= 2) {
        break;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | EduLend</title>

    <?php require_once __DIR__ . '/Includes/csrf.php'; ?>

    <!-- CSRF Meta Tag for JavaScript -->
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

    <script src="assets/js/tailwind.js"></script>
    <script src="assets/js/dashboard.js" defer></script>

    <style>
        body {
            background: #f4f7fb;
        }

        main > div {
            max-width: 1500px;
            margin: 0 auto;
        }

        main > div > section {
            margin-bottom: 1.25rem !important;
        }

        main > div > section:first-child {
            margin-bottom: 1.5rem !important;
        }

        main > div > section > div,
        main > div > section > a {
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.045);
        }

        main > div > section > div:hover,
        main > div > section > a:hover {
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        main > div > section.grid {
            align-items: stretch;
        }

        @media (min-width: 1024px) {
            main > div {
                padding: 2rem 2.5rem !important;
            }
        }

        @media (max-width: 640px) {
            main > div {
                padding: 1rem !important;
            }

            main > div > section > div,
            main > div > section > a {
                border-radius: 1rem;
            }
        }
    </style>

</head>

<body class="bg-slate-100 text-slate-800">

    <!-- ========================================================
         MOBILE TOP BAR
    ========================================================= -->

    <div class="lg:hidden bg-white border-b border-slate-200 px-5 py-4 flex items-center justify-between">

        <div class="flex items-center gap-3">

            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-lg">
                E
            </div>

            <div>

                <h1 class="font-extrabold text-slate-900">
                    Edu<span class="text-blue-600">Lend</span>
                </h1>

                <p class="text-xs text-slate-500">
                    Student Portal
                </p>

            </div>

        </div>

        <button
            id="menuButton"
            type="button"
            class="text-2xl text-slate-700">

            ☰

        </button>

    </div>


    <!-- ========================================================
         MAIN LAYOUT
    ========================================================= -->

    <div class="min-h-screen flex">


        <!-- ====================================================
             SIDEBAR
        ===================================================== -->

        <aside
            id="sidebar"
            class="hidden lg:flex lg:w-72 bg-slate-950 text-white flex-col fixed inset-y-0 left-0 z-40">

            <!-- LOGO -->

            <div class="px-7 py-7 border-b border-slate-800">

                <div class="flex items-center gap-3">

                    <div class="w-11 h-11 rounded-xl bg-blue-600 flex items-center justify-center text-xl font-extrabold">

                        E

                    </div>

                    <div>

                        <h1 class="text-xl font-extrabold">

                            Edu<span class="text-blue-400">Lend</span>

                        </h1>

                        <p class="text-xs text-slate-400 mt-1">

                            Student Portal

                        </p>

                    </div>

                </div>

            </div>


            <!-- NAVIGATION -->

            <nav class="flex-1 px-4 py-6 space-y-2">

                <a
                    href="dashboard.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl bg-blue-600 text-white font-semibold">

                    <span>▦</span>

                    Dashboard

                </a>


                <a
                    href="#loan"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-slate-900 hover:text-white transition">

                    <span>₦</span>

                    Loans

                </a>


                <a
                    href="#repayment"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-slate-900 hover:text-white transition">

                    <span>↻</span>

                    Repayments

                </a>


                <a
                    href="#voucher"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-slate-900 hover:text-white transition">

                    <span>🎟</span>

                    Vouchers

                </a>


                <a
                    href="#autosweep"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-slate-900 hover:text-white transition">

                    <span>↗</span>

                    Auto Sweep

                </a>


                <a
                    href="#wallet"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-slate-900 hover:text-white transition">

                    <span>◉</span>

                    Wallet

                </a>


                <a
                    href="#profile"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-slate-900 hover:text-white transition">

                    <span>◯</span>

                    Profile

                </a>

            </nav>


            <!-- STUDENT ACCOUNT -->

            <div class="p-4 border-t border-slate-800">

                <div class="flex items-center gap-3 px-3 py-3">

                    <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center font-bold">

                        <?php echo htmlspecialchars($student_initials); ?>

                    </div>

                    <div class="min-w-0">

                        <p class="font-semibold text-sm truncate">

                            <?php echo htmlspecialchars($fullname); ?>

                        </p>

                        <p class="text-xs text-slate-400">

                            Student Account

                        </p>

                    </div>

                </div>


                <a
                    href="logout.php"
                    class="mt-2 flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition">

                    <span>↪</span>

                    Logout

                </a>

            </div>

        </aside>


        <!-- ====================================================
             DASHBOARD CONTENT
        ===================================================== -->

        <main class="flex-1 lg:ml-72">


            <!-- =================================================
                 TOP HEADER
            ================================================== -->

            <header class="hidden lg:flex bg-white border-b border-slate-200 px-8 py-5 items-center justify-between">

                <div>

                    <h2 class="text-2xl font-extrabold text-slate-900">

                        Student Dashboard

                    </h2>

                    <p class="text-sm text-slate-500 mt-1">

                        Manage your EduLend financial activities.

                    </p>

                </div>


                <div class="flex items-center gap-4">

                    <div class="text-right">

                        <p class="font-semibold text-sm text-slate-900">

                            <?php echo htmlspecialchars($fullname); ?>

                        </p>

                        <p class="text-xs text-slate-500">

                            <?php echo htmlspecialchars($email); ?>

                        </p>

                    </div>


                    <div class="w-11 h-11 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">

                        <?php echo htmlspecialchars($student_initials); ?>

                    </div>

                </div>

            </header>


            <!-- =================================================
                 PAGE CONTENT
            ================================================== -->

            <div class="p-5 lg:p-8">


                <!-- WELCOME SECTION -->

                <section class="mb-8">

                    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-3xl p-7 lg:p-9 text-white relative overflow-hidden">

                        <div class="relative z-10">

                            <p class="text-blue-100 text-sm">

                                Welcome back,

                            </p>

                            <h2 class="text-2xl lg:text-3xl font-extrabold mt-2">

                                <?php echo htmlspecialchars($fullname); ?>

                            </h2>

                            <p class="text-blue-100 mt-3 max-w-2xl leading-7">

                                Monitor your wallet, credit score, trust tier,
                                loans, repayments, vouchers and other EduLend
                                financial activities from one place.

                            </p>


                            <div class="flex flex-wrap gap-3 mt-6">

                                <a
                                    href="#loan"
                                    class="px-5 py-3 rounded-xl bg-white text-blue-600 font-bold hover:bg-blue-50 transition">

                                    Request a Loan

                                </a>


                                <a
                                    href="#wallet"
                                    class="px-5 py-3 rounded-xl border border-white/40 text-white font-bold hover:bg-white/10 transition">

                                    View Wallet

                                </a>

                            </div>

                        </div>


                        <div class="absolute -right-10 -bottom-16 w-64 h-64 rounded-full bg-white/10"></div>

                    </div>

                </section>


                <!-- =================================================
                     FINANCIAL SUMMARY
                ================================================== -->

                <section class="grid sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">


                    <!-- WALLET BALANCE -->

                    <div class="bg-white border border-slate-200 rounded-2xl p-5">

                        <div class="flex items-center justify-between">

                            <p class="text-sm text-slate-500">

                                Wallet Balance

                            </p>

                            <span class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">

                                ₦

                            </span>

                        </div>

                        <h3 class="text-2xl font-extrabold text-slate-900 mt-5">

                            ₦<?php echo $available_balance; ?>

                        </h3>

                        <p class="text-sm text-slate-500 mt-2">

                            Available funds

                        </p>

                    </div>


                    <!-- TOTAL DEBT -->

                    <div class="bg-white border border-slate-200 rounded-2xl p-5">

                        <div class="flex items-center justify-between">

                            <p class="text-sm text-slate-500">

                                Current Debt

                            </p>

                            <span class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">

                                ₦

                            </span>

                        </div>

                        <h3 class="text-2xl font-extrabold text-slate-900 mt-5">

                            ₦<?php echo $total_debt; ?>

                        </h3>

                        <p class="text-sm text-slate-500 mt-2">

                            Outstanding loan amount

                        </p>

                    </div>


                    <!-- CREDIT SCORE -->

                    <div class="bg-white border border-slate-200 rounded-2xl p-5">

                        <div class="flex items-center justify-between">

                            <p class="text-sm text-slate-500">

                                Credit Score

                            </p>

                            <span class="w-10 h-10 rounded-xl bg-green-100 text-green-600 flex items-center justify-center">

                                ★

                            </span>

                        </div>

                        <div class="flex items-end gap-3 mt-5">

                            <h3 class="text-2xl font-extrabold text-slate-900">

                                <?php echo $credit_score; ?>

                            </h3>

                            <span class="<?php echo $credit_bg; ?> <?php echo $credit_color; ?> px-2 py-1 rounded-md text-xs font-bold mb-1">

                                <?php echo $credit_status; ?>

                            </span>

                        </div>

                        <div class="w-full h-2 bg-slate-100 rounded-full mt-4 overflow-hidden">

                            <div
                                class="h-full bg-blue-600 rounded-full"
                                style="width: <?php echo min(100, max(0, $credit_score)); ?>%;">
                            </div>

                        </div>

                    </div>


                    <!-- TRUST TIER -->

                    <div class="bg-white border border-slate-200 rounded-2xl p-5">

                        <div class="flex items-center justify-between">

                            <p class="text-sm text-slate-500">

                                Trust Tier

                            </p>

                            <span class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">

                                ↑

                            </span>

                        </div>

                        <h3 class="text-2xl font-extrabold text-slate-900 mt-5">

                            Tier <?php echo $trust_tier; ?>

                        </h3>

                        <p class="text-sm text-slate-500 mt-2">

                            <?php echo $successful_repayments; ?>

                            successful repayments

                        </p>

                    </div>

                </section>


                <!-- =================================================
                     MAIN INFORMATION GRID
                ================================================== -->

                <section class="grid xl:grid-cols-3 gap-6 mb-8">


                    <!-- CREDIT AND TRUST -->

                    <div class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl p-6">

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">

                            <div>

                                <h3 class="text-lg font-bold text-slate-900">

                                    Financial Trust Profile

                                </h3>

                                <p class="text-sm text-slate-500 mt-1">

                                    Your EduLend credit and account standing.

                                </p>

                            </div>


                            <span class="<?php echo $account_bg; ?> <?php echo $account_color; ?> px-4 py-2 rounded-full text-sm font-bold">

                                <?php echo $account_status; ?>

                            </span>

                        </div>


                        <div class="grid sm:grid-cols-3 gap-5 mt-7">


                            <div class="rounded-xl bg-slate-50 p-5">

                                <p class="text-sm text-slate-500">

                                    Credit Score

                                </p>

                                <p class="text-2xl font-extrabold text-slate-900 mt-2">

                                    <?php echo $credit_score; ?>

                                </p>

                            </div>


                            <div class="rounded-xl bg-slate-50 p-5">

                                <p class="text-sm text-slate-500">

                                    Trust Level

                                </p>

                                <p class="text-2xl font-extrabold text-slate-900 mt-2">

                                    Tier <?php echo $trust_tier; ?>

                                </p>

                            </div>


                            <div class="rounded-xl bg-slate-50 p-5">

                                <p class="text-sm text-slate-500">

                                    Successful Repayments

                                </p>

                                <p class="text-2xl font-extrabold text-slate-900 mt-2">

                                    <?php echo $successful_repayments; ?>

                                </p>

                            </div>

                        </div>

                    </div>


                    <!-- CRF STATUS -->

                    <div class="bg-white border border-slate-200 rounded-2xl p-6">

                        <div class="flex items-center justify-between">

                            <div>

                                <h3 class="text-lg font-bold text-slate-900">

                                    CRF Status

                                </h3>

                                <p class="text-sm text-slate-500 mt-1">

                                    Credit request information

                                </p>

                            </div>

                            <span class="text-xl">

                                ✓

                            </span>

                        </div>


                        <div class="mt-8 text-center">

                            <span class="inline-flex <?php echo $crf_bg; ?> <?php echo $crf_color; ?> px-4 py-2 rounded-full text-sm font-bold">

                                <?php echo htmlspecialchars($crf_display); ?>

                            </span>


                            <p class="text-sm text-slate-500 mt-5 leading-6">

                                <?php if ($trust_tier < 2): ?>
                                    Submit your Student ID to unlock Tier 2 borrowing and voucher requests.
                                <?php elseif ($trust_tier === 2): ?>
                                    Tier 2 students can request a voucher and upload a CRF to become Tier 3.
                                <?php else: ?>
                                    Your CRF has been approved. You are verified as a Tier 3 student.
                                <?php endif; ?>

                            </p>

                        </div>

                        <?php if ($trust_tier < 3): ?>
                            <button
                                id="manageVerificationBtn"
                                type="button"
                                class="w-full mt-6 px-4 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 transition">
                                <?php echo $trust_tier < 2 ? "Upload Student ID" : "Upload CRF"; ?>
                            </button>
                        <?php else: ?>
                            <div class="w-full mt-6 px-4 py-3 rounded-xl bg-green-50 text-green-700 text-center font-bold">
                                Tier 3 verification complete
                            </div>
                        <?php endif; ?>

                    </div>

                </section>

  <!-- =================================================
     TRANSACTION HISTORY SECTION
================================================== -->
<section id="transactions" class="bg-white border border-slate-200 rounded-2xl p-6 mb-8 w-full overflow-hidden">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-900">Transaction History</h3>
            <p class="text-sm text-slate-500 mt-1">Recent wallet deposits, withdrawals, and repayments.</p>
        </div>
        <button id="refreshTxBtn" type="button" class="self-start sm:self-auto px-4 py-2 text-sm rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition">
            ↻ Refresh
        </button>
    </div>

    <div class="overflow-x-auto w-full">
        <table class="w-full min-w-[600px] text-left text-sm border-collapse">
            <thead>
                <tr class="border-b border-slate-200 text-slate-400 font-medium">
                    <th class="pb-3 px-4">Type</th>
                    <th class="pb-3 px-4">Amount</th>
                    <th class="pb-3 px-4">Reference</th>
                    <th class="pb-3 px-4">Status</th>
                    <th class="pb-3 px-4">Date</th>
                </tr>
            </thead>
            <tbody id="transactionHistoryTable" class="divide-y divide-slate-100 text-slate-700">
                <tr>
                    <td colspan="5" class="py-6 text-center text-slate-400">Loading transaction history...</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

                <!-- =================================================
                     QUICK ACTIONS
                ================================================== -->

                <section class="bg-white border border-slate-200 rounded-2xl p-6 mb-8">

                    <div>

                        <h3 class="text-lg font-bold text-slate-900">

                            Quick Actions

                        </h3>

                        <p class="text-sm text-slate-500 mt-1">

                            Access important EduLend services.

                        </p>

                    </div>


                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">


                        <a
                            id="loan"
                            href="#"
                            class="group p-5 rounded-xl border border-slate-200 hover:border-blue-400 hover:shadow-md transition">

                            <div class="w-11 h-11 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">

                                ₦

                            </div>

                            <h4 class="font-bold text-slate-900 mt-4">

                                Request Loan

                            </h4>

                            <p class="text-sm text-slate-500 mt-1">

                                Apply for educational financing.

                            </p>

                        </a>


                        <a
                            id="repayment"
                            href="#"
                            class="group p-5 rounded-xl border border-slate-200 hover:border-green-400 hover:shadow-md transition">

                            <div class="w-11 h-11 rounded-xl bg-green-100 text-green-600 flex items-center justify-center">

                                ↻

                            </div>

                            <h4 class="font-bold text-slate-900 mt-4">

                                Repay Loan

                            </h4>

                            <p class="text-sm text-slate-500 mt-1">

                                Manage loan repayments.

                            </p>

                        </a>


                     <a id="voucher" href="#"
    class="group p-5 rounded-xl border border-slate-200 hover:border-purple-400 hover:shadow-md transition">

    <div class="w-11 h-11 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
        🎟
    </div>

    <h4 class="font-bold text-slate-900 mt-4">
        Vouchers
    </h4>

    <?php if ($trust_tier >= 3): ?>

        <p class="text-sm text-green-600 mt-1 font-semibold">
            Tier 3 — Voucher not required
        </p>

    <?php elseif ($trust_tier == 2): ?>

        <p id="voucherSummary"
           class="text-sm text-slate-500 mt-1">
            Loading voucher status...
        </p>

    <?php else: ?>

        <p class="text-sm text-slate-500 mt-1">
            Complete Student ID verification to access vouchers.
        </p>

    <?php endif; ?>

</a>


                        <a
                            id="autosweep"
                            href="#"
                            class="group p-5 rounded-xl border border-slate-200 hover:border-orange-400 hover:shadow-md transition">

                            <div class="w-11 h-11 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">

                                ↗

                            </div>

                            <h4 class="font-bold text-slate-900 mt-4">

                                Auto Sweep

                            </h4>

                            <p class="text-sm text-slate-500 mt-1">

                                Manage automatic fund rules.

                            </p>

                        </a>

                    </div>

                </section>


                <!-- =================================================
                     WALLET SECTION
                ================================================== -->

                <section
                    id="wallet"
                    class="grid lg:grid-cols-2 gap-6 mb-8">


         <!-- WALLET CARD -->
<div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-7 text-white">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-slate-400 text-sm">EduLend Digital Wallet</p>
            <h3 class="text-3xl font-extrabold mt-3">
                ₦<?php echo $available_balance; ?>
            </h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-xl">◉</div>
    </div>

    <div class="flex gap-3 mt-8">
        <button id="depositBtn" type="button" class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 font-bold transition">
            Deposit
        </button>

        <button id="withdrawBtn" type="button" class="flex-1 py-3 rounded-xl bg-white/10 hover:bg-white/20 font-bold transition">
            Withdraw
        </button>
    </div>
</div>
 
<!-- =================================================
     AUTO SWEEP CARD
================================================== -->
    <section>
<div class="bg-white border border-slate-200 rounded-2xl p-7">

    <div class="flex items-center justify-between">

        <div>
            <h3 class="text-lg font-bold text-slate-900">
                Auto Sweep
            </h3>

            <p id="autosweepStatusText" class="text-sm text-slate-500 mt-1">
                Loading Auto Sweep status...
            </p>
        </div>

        <!-- Interactive Toggle Switch -->
        <label for="autosweepToggle" class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" id="autosweepToggle" class="sr-only peer">
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
        </label>

    </div>

    <p class="text-sm text-slate-600 leading-7 mt-6">
        Auto Sweep automatically deducts available wallet balances to repay active loan debts sequentially, returning principal and profit back to the central lending pool.
    </p>

    <div class="flex items-center gap-4 mt-6">
        <button
            id="triggerAutoSweepBtn"
            type="button"
            class="text-blue-600 font-bold hover:text-blue-700 flex items-center gap-2 transition disabled:opacity-50">
            <span>Run Auto Sweep Now</span>
            <span class="text-lg">→</span>
        </button>
    </div>

    <!-- UI Alert Banner -->
    <div id="autosweepBanner" class="hidden mt-4 p-3 rounded-xl text-sm font-medium"></div>

</div>
    </section>


                <!-- =================================================
                     PROFILE
                ================================================== -->

                <section
                    id="profile"
                    class="bg-white border border-slate-200 rounded-2xl p-6 mb-8">

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">

                        <div class="flex items-center gap-4">

                            <div class="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center text-xl font-bold">

                                <?php echo htmlspecialchars($student_initials); ?>

                            </div>


                            <div>

                                <h3 class="text-lg font-bold text-slate-900">

                                    <?php echo htmlspecialchars($fullname); ?>

                                </h3>

                                <p class="text-sm text-slate-500 mt-1">

                                    <?php echo htmlspecialchars($email); ?>

                                </p>

                                <p class="text-sm text-blue-600 font-semibold mt-2">

                                    EduLend Student Account

                                </p>

                            </div>

                        </div>


                        <button
                            type="button"
                            class="px-5 py-3 rounded-xl border border-slate-300 text-slate-700 font-bold hover:border-blue-500 hover:text-blue-600 transition">

                            Edit Profile

                        </button>

                    </div>

                </section>


                <!-- =================================================
                     DASHBOARD NOTICE
                ================================================== -->

                <section class="bg-blue-50 border border-blue-100 rounded-2xl p-6">

                    <div class="flex gap-4">

                        <div class="w-11 h-11 shrink-0 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold">

                            i

                        </div>


                        <div>

                            <h3 class="font-bold text-slate-900">

                                EduLend Student Financial Centre

                            </h3>

                            <p class="text-sm text-slate-600 leading-7 mt-2">

                                Your dashboard currently displays information
                                connected directly to your EduLend user session
                                and wallet. Loan requests, repayments, vouchers,
                                transactions and Auto Sweep actions can now be
                                connected to their respective backend operations
                                as we continue building and testing the platform.

                            </p>

                        </div>

                    </div>

                </section>

            </div>

        </main>

    </div>

 <!-- VERIFICATION MODAL -->
<div
    id="verificationModal"
    class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-5">

    <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-slate-900">
                    Account Verification
                </h3>

                <p class="text-sm text-slate-500 mt-1">
                    Complete your EduLend verification.
                </p>
            </div>

            <button
                id="closeVerificationBtn"
                type="button"
                class="text-slate-400 hover:text-slate-700 text-2xl">
                &times;
            </button>
        </div>

        <form
            id="verificationForm"
            enctype="multipart/form-data">

            <label class="block text-sm font-semibold text-slate-700 mb-2">
                Verification Type
            </label>

            <select
                id="verificationType"
                name="verification_type"
                required
                class="w-full border border-slate-300 rounded-xl px-4 py-3 mb-5 focus:outline-none focus:ring-2 focus:ring-blue-500">

                <?php if ($trust_tier < 2): ?>
                    <option value="student_id">
                        Student ID - Tier 1 to Tier 2
                    </option>
                <?php endif; ?>

                <?php if ($trust_tier >= 2 && $trust_tier < 3): ?>
                    <option value="course_registration_form">
                        Course Registration Form - Tier 2 to Tier 3
                    </option>
                <?php endif; ?>

            </select>

            <label class="block text-sm font-semibold text-slate-700 mb-2">
                Upload Document
            </label>

            <input
                type="file"
                id="verificationDocument"
                name="document"
                accept=".jpg,.jpeg,.png,.pdf"
                required
                class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm">

            <p class="text-xs text-slate-500 mt-2">
                Accepted formats: JPG, JPEG, PNG, PDF. Maximum size: 5 MB.
            </p>

            <button
                type="submit"
                id="submitVerificationBtn"
                class="w-full mt-6 px-4 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 transition">

                Submit Verification
            </button>

        </form>

    </div>
</div>
   
<!-- =========================================================
     VOUCHER MODAL
========================================================= -->

<div id="voucherModal"
     class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">

    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between p-6 border-b">

            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Peer Voucher
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Request a guarantor for your Tier 2 loan.
                </p>
            </div>

            <button
                type="button"
                id="closeVoucherModal"
                class="text-slate-400 hover:text-slate-700 text-2xl">
                &times;
            </button>

        </div>

        <div class="p-6">

            <!-- TIER 3 MESSAGE -->

            <div id="voucherTier3Message"
                 class="hidden bg-green-50 border border-green-200 rounded-xl p-4">

                <p class="font-semibold text-green-700">
                    Voucher not required
                </p>

                <p class="text-sm text-green-600 mt-1">
                    You are a Tier 3 verified student. You can request
                    eligible loans without a peer guarantor.
                </p>

            </div>

            <!-- INCOMING TIER 3 REQUESTS -->

            <div id="incomingVoucherContent"
                 class="hidden bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">

                <h3 class="font-bold text-blue-900 mb-3">
                    Incoming voucher requests
                </h3>

                <div id="incomingVoucherList" class="space-y-3">
                    <p class="text-sm text-slate-500">
                        Loading incoming requests...
                    </p>
                </div>

            </div>

            <!-- TIER 1 MESSAGE -->

            <div id="voucherTier1Message"
                 class="hidden bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">

                <p class="font-semibold text-yellow-700">
                    Voucher access is not available yet
                </p>

                <p class="text-sm text-yellow-600 mt-1">
                    Complete and receive approval for Student ID verification to move to Tier 2.
                </p>

            </div>


            <!-- TIER 2 CONTENT -->

            <div id="voucherTier2Content">

                <!-- CURRENT STATUS -->

                <div class="bg-slate-50 rounded-xl p-4 mb-6">

                    <p class="text-sm text-slate-500">
                        Current voucher status
                    </p>

                    <p id="voucherCurrentStatus"
                       class="font-bold text-slate-900 mt-1">
                        Checking...
                    </p>

                </div>


                <!-- GUARANTOR LIST -->

                <div id="guarantorSection">

                    <h3 class="font-bold text-slate-900 mb-3">
                        Select a Tier 3 guarantor
                    </h3>

                    <div id="guarantorList"
                         class="space-y-3">

                        <p class="text-sm text-slate-500">
                            Loading eligible guarantors...
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="assets/js/dashboard.js"></script>
</body>

</html>
