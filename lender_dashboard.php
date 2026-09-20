<?php
// lender_dashboard.php
session_start();

require_once 'Api/lender_dashboard_data.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lender') {
    header("Location: p2p.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lender Dashboard | EduLend</title>

    <?php require_once __DIR__ . '/Includes/csrf.php'; ?>

    <script src="assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-slate-100/70 text-slate-800 antialiased selection:bg-blue-600 selection:text-white min-h-screen relative overflow-x-hidden font-sans">

    <!-- Ambient Background Accents -->
    <div class="fixed top-0 left-1/4 w-[30rem] h-[30rem] bg-blue-500/10 rounded-full blur-3xl pointer-events-none -z-10 animate-pulse-glow"></div>
    <div class="fixed top-1/3 right-10 w-[28rem] h-[28rem] bg-emerald-500/10 rounded-full blur-3xl pointer-events-none -z-10 animate-pulse-glow" style="animation-delay: 2.5s;"></div>

    <?php require_once __DIR__ . '/Includes/header.php'; ?>


    <!-- MAIN CONTENT -->
    <?php require_once __DIR__ . '/Includes/sidebar.php'; ?>

    <main id="dashboard" class="max-w-7xl mx-auto p-4 sm:p-6 md:p-8 space-y-8 sm:space-y-10 lg:ml-72">


        <!-- SUCCESS MESSAGE -->
        <?php if (!empty($message)): ?>

            <div class="p-4 bg-emerald-50/90 backdrop-blur border border-emerald-200/80 text-emerald-800 rounded-2xl text-sm font-semibold shadow-sm flex items-center gap-3 animate-fade-in transition-all">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                <div>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->
        <?php if (!empty($error)): ?>

            <div class="p-4 bg-rose-50/90 backdrop-blur border border-rose-200/80 text-rose-800 rounded-2xl text-sm font-semibold shadow-sm flex items-center gap-3 animate-fade-in transition-all">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg"></i>
                <div>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </div>

        <?php endif; ?>


        <!-- HERO SECTION -->
        <section class="bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 p-6 sm:p-8 md:p-10 rounded-3xl border border-slate-800 text-white shadow-xl relative overflow-hidden group">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl group-hover:bg-blue-500/30 transition-all duration-500"></div>

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative z-10">

                <div class="space-y-2">

                    <span class="text-xs font-mono font-bold uppercase tracking-widest text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 rounded-full inline-block">Active Capital Allocation</span>

                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-black tracking-tight text-white">
                        Welcome back,
                        <?php echo htmlspecialchars(explode(' ', $lender_name)[0]); ?>.
                    </h1>

                    <p class="text-slate-300 text-xs sm:text-sm font-medium max-w-xl leading-relaxed">
                        Your investment capital directly enables verified university students to achieve academic milestones.
                    </p>

                </div>

                <a href="#deposit-withdraw"
                   class="bg-gradient-to-r from-blue-600 to-emerald-500 hover:from-blue-500 hover:to-emerald-400 text-white font-extrabold px-7 py-3.5 rounded-2xl transition-all duration-200 shadow-lg shadow-blue-600/30 hover:shadow-blue-600/50 hover:-translate-y-0.5 active:translate-y-0 flex items-center gap-2.5 text-sm sm:text-base group/btn shrink-0">

                    <i class="fa-solid fa-plus-circle transition-transform group-hover/btn:rotate-90"></i>

                    Deposit Funds

                </a>

            </div>

        </section>


        <!-- SUMMARY CARDS (Redesigned) -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- WALLET BALANCE -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-lg transition-all duration-300 flex items-center gap-4">
                <div class="w-16 h-16 flex items-center justify-center rounded-xl bg-slate-50 border border-slate-100">
                    <i class="fa-solid fa-wallet text-2xl text-slate-800"></i>
                </div>

                <div>
                    <div class="text-xs text-slate-400 font-black uppercase tracking-wider">Capital Contributed</div>
                    <div class="text-2xl font-black text-slate-900">₦<?php echo number_format($wallet_balance, 2); ?></div>
                    <div class="text-xs text-slate-500 mt-1">Total capital supplied to EduLend.</div>
                </div>
            </div>


            <!-- MONEY INVESTED -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-lg transition-all duration-300 flex items-center gap-4">
                <div class="w-16 h-16 flex items-center justify-center rounded-xl bg-blue-50 border border-blue-100">
                    <i class="fa-solid fa-chart-line text-2xl text-blue-600"></i>
                </div>

                <div>
                    <div class="text-xs text-slate-400 font-black uppercase tracking-wider">Capital Deployed</div>
                    <div class="text-2xl font-black text-blue-600">₦<?php echo number_format($money_invested, 2); ?></div>
                    <div class="text-xs text-slate-500 mt-1">Your share currently funding pool loans.</div>
                </div>
            </div>


            <!-- TOTAL PROFIT -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-lg transition-all duration-300 flex items-center gap-4">
                <div class="w-16 h-16 flex items-center justify-center rounded-xl bg-emerald-50 border border-emerald-100">
                    <i class="fa-solid fa-arrow-up-right-dots text-2xl text-emerald-600"></i>
                </div>

                <div>
                    <div class="text-xs text-slate-400 font-black uppercase tracking-wider">Lender Returns</div>
                    <div class="text-2xl font-black text-emerald-600">₦<?php echo number_format($profit_earned, 2); ?></div>
                    <div class="text-xs text-slate-500 mt-1">Your share of the 75% lender profit.</div>
                </div>
            </div>


            <!-- AVAILABLE WITHDRAW -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative group overflow-hidden">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500 rounded-l-full"></div>

                <div class="flex justify-between items-center mb-2">
                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider block">
                        Available to Withdraw
                    </span>
                    <i class="fa-solid fa-building-columns text-emerald-200 group-hover:text-emerald-500 transition-colors"></i>
                </div>

                <h3 class="text-2xl sm:text-3xl font-black text-emerald-600 mb-2 tracking-tight group-hover:scale-[1.02] origin-left transition-transform">
                    ₦<?php echo number_format($available_withdraw, 2); ?>
                </h3>

                <p class="text-xs text-slate-500 font-medium">
                    Liquid funds available for payout.
                </p>

            </div>


            <!-- STUDENTS SPONSORED -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative group overflow-hidden">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500 rounded-l-full"></div>

                <div class="flex justify-between items-center mb-2">
                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider block">
                        Students Sponsored
                    </span>
                    <i class="fa-solid fa-graduation-cap text-amber-200 group-hover:text-amber-500 transition-colors"></i>
                </div>

                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mb-2 tracking-tight">
                    <?php echo $students_sponsored; ?>
                </h3>

                <p class="text-xs text-slate-500 font-medium">
                    Total scholars supported.
                </p>

            </div>


            <!-- RECOVERY RATE -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative group overflow-hidden">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-teal-500 rounded-l-full"></div>

                <div class="flex justify-between items-center mb-2">
                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider block">
                        Loan Recovery Rate
                    </span>
                    <i class="fa-solid fa-shield-halved text-teal-200 group-hover:text-teal-500 transition-colors"></i>
                </div>

                <h3 class="text-2xl sm:text-3xl font-black text-emerald-600 mb-2 tracking-tight group-hover:scale-[1.02] origin-left transition-transform">
                    <?php echo $recovery_rate; ?>%
                </h3>

                <p class="text-xs text-slate-500 font-medium">
                    Historical repayment compliance.
                </p>

            </div>

        </section>


        <!-- DEPOSIT / WITHDRAW -->
        <section id="deposit-withdraw"
                 class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">

            <h2 class="text-xl font-black text-slate-900 mb-6 tracking-tight flex items-center gap-2.5">
                <span class="w-2.5 h-6 bg-blue-600 rounded-full inline-block"></span>
                Capital Actions
            </h2>

            <div class="flex flex-col sm:flex-row gap-4">

                <button type="button"
                    onclick="openTransactionModal('deposit')"
                        class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white font-extrabold py-4 rounded-2xl transition-all duration-200 shadow-md shadow-blue-600/20 hover:shadow-lg hover:shadow-blue-600/30 hover:-translate-y-0.5 active:translate-y-0 text-sm sm:text-base group">

                    <i class="fa-solid fa-wallet mr-2 transition-transform group-hover:scale-110 inline-block"></i>

                    Deposit Funds

                </button>

                <button type="button"
                    onclick="openTransactionModal('withdraw')"
                        class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-extrabold py-4 rounded-2xl transition-all duration-200 shadow-md shadow-slate-900/20 hover:shadow-lg hover:shadow-slate-900/30 hover:-translate-y-0.5 active:translate-y-0 text-sm sm:text-base group border border-slate-800">

                    <i class="fa-solid fa-money-bill-transfer mr-2 transition-transform group-hover:scale-110 inline-block"></i>

                    Withdraw Funds

                </button>

            </div>

        </section>


        <!-- INVESTMENT STATISTICS -->
        <section class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">

            <h2 class="text-xl font-black text-slate-900 mb-6 tracking-tight flex items-center gap-2.5">
                <span class="w-2.5 h-6 bg-emerald-500 rounded-full inline-block"></span>
                Portfolio Analytics
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 text-center">


                <!-- TOTAL CAPITAL -->
                <div class="p-5 bg-slate-50/80 hover:bg-slate-100/80 rounded-2xl border border-slate-200/60 transition-all duration-200 hover:-translate-y-1">

                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider block mb-1">
                        Total Capital
                    </span>

                    <p class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        ₦<?php echo number_format($total_capital, 2); ?>
                    </p>

                </div>


                <!-- TOTAL LOANS -->
                <div class="p-5 bg-slate-50/80 hover:bg-slate-100/80 rounded-2xl border border-slate-200/60 transition-all duration-200 hover:-translate-y-1">

                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider block mb-1">
                        Total Active Loans
                    </span>

                    <p class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        <?php echo $total_loans_count; ?>
                    </p>

                </div>


                <!-- CAPITAL AVAILABLE -->
                <div class="p-5 bg-slate-50/80 hover:bg-slate-100/80 rounded-2xl border border-slate-200/60 transition-all duration-200 hover:-translate-y-1">

                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider block mb-1">
                        Capital Available
                    </span>

                    <p class="text-xl sm:text-2xl font-black text-emerald-600 tracking-tight">
                        ₦<?php echo number_format($capital_available, 2); ?>
                    </p>

                </div>


                <!-- AVERAGE LOAN -->
                <div class="p-5 bg-slate-50/80 hover:bg-slate-100/80 rounded-2xl border border-slate-200/60 transition-all duration-200 hover:-translate-y-1">

                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider block mb-1">
                        Average Loan Size
                    </span>

                    <p class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        ₦<?php echo number_format($average_loan_size, 2); ?>
                    </p>

                </div>

            </div>

        </section>


        <!-- ACTIVE LOANS -->
   <!-- UNIFIED TABBED DATA SECTION -->
<section class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden hover:shadow-md transition-shadow p-6 sm:p-8">

    <!-- SELECTOR SHAPES / TABS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

        <!-- Active Loans Tab -->
        <button type="button"
                id="tab-btn-active"
                onclick="switchDashboardTab('active')"
                class="tab-selector-btn active-tab p-4 rounded-2xl border text-left transition-all duration-200 flex items-center justify-between group">
            <div>
                <span class="text-[11px] font-black uppercase tracking-wider block text-slate-400 group-[.active-tab]:text-blue-600">
                    Active Portfolio
                </span>
                <span class="text-base font-black text-slate-900">
                    Active Student Loans
                </span>
            </div>
            <i class="fa-solid fa-graduation-cap text-lg text-slate-300 group-[.active-tab]:text-blue-600"></i>
        </button>

        <!-- Historical Performance Tab -->
        <button type="button"
                id="tab-btn-history"
                onclick="switchDashboardTab('history')"
                class="tab-selector-btn p-4 rounded-2xl border border-slate-200/80 bg-slate-50/50 text-left transition-all duration-200 flex items-center justify-between group hover:bg-slate-100/80">
            <div>
                <span class="text-[11px] font-black uppercase tracking-wider block text-slate-400 group-[.active-tab]:text-teal-600">
                    History
                </span>
                <span class="text-base font-black text-slate-900">
                    Historical Performance
                </span>
            </div>
            <i class="fa-solid fa-clock-rotate-left text-lg text-slate-300 group-[.active-tab]:text-teal-600"></i>
        </button>

        <!-- Wallet Transactions Tab -->
        <button type="button"
                id="tab-btn-transactions"
                onclick="switchDashboardTab('transactions')"
                class="tab-selector-btn p-4 rounded-2xl border border-slate-200/80 bg-slate-50/50 text-left transition-all duration-200 flex items-center justify-between group hover:bg-slate-100/80">
            <div>
                <span class="text-[11px] font-black uppercase tracking-wider block text-slate-400 group-[.active-tab]:text-slate-800">
                    Audit Log
                </span>
                <span class="text-base font-black text-slate-900">
                    Wallet Transactions
                </span>
            </div>
            <i class="fa-solid fa-receipt text-lg text-slate-300 group-[.active-tab]:text-slate-800"></i>
        </button>

    </div>


    <!-- TABLE DISPLAY CONTAINER -->
    <div id="table-display-container" class="bg-white rounded-2xl shadow-sm overflow-hidden transition-all duration-300">

        <!-- HEADER WITH CANCEL SIGN -->
        <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex justify-between items-center">
            <h3 id="current-table-title" class="text-sm font-black tracking-tight uppercase flex items-center gap-2">
                <span id="title-accent-bar" class="w-2 h-4 bg-blue-500 rounded-full inline-block"></span>
                <span id="table-title-text">Active Student Loans</span>
            </h3>

            <!-- Cancel / Close Button -->
            <button type="button"
                    onclick="collapseDashboardTables()"
                    title="Collapse Table View"
                    class="p-1.5 text-slate-200 hover:text-white hover:bg-slate-800 rounded-lg transition-colors flex items-center justify-center">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- CONTENT PANE -->
        <div class="p-4">

            <!-- TAB 1: ACTIVE STUDENT LOANS -->
            <div id="tab-content-active" class="tab-content-pane overflow-x-auto">
                <div class="rounded-xl overflow-hidden border border-slate-100">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-white/95 text-slate-600 uppercase text-[11px] font-black tracking-wider sticky top-0">
                            <tr>
                                <th class="px-6 py-3">Loan ID</th>
                                <th class="px-6 py-3">Borrower</th>
                                <th class="px-6 py-3">Principal</th>
                                <th class="px-6 py-3">Repayment Target</th>
                                <th class="px-6 py-3">Principal Returned</th>
                                <th class="px-6 py-3">Due Date</th>
                                <th class="px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                    <?php if ($active_loans && $active_loans->num_rows > 0): ?>
                        <?php while ($row = $active_loans->fetch_assoc()): ?>
                            <?php
                            $status = strtolower($row['status']);
                            $badge_color = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            if ($status === 'paid') {
                                $badge_color = 'bg-blue-50 text-blue-700 border-blue-200';
                            } elseif ($status === 'pending') {
                                $badge_color = 'bg-amber-50 text-amber-700 border-amber-200';
                            } elseif ($status === 'defaulted') {
                                $badge_color = 'bg-rose-50 text-rose-700 border-rose-200';
                            }
                            ?>
                            <tr class="hover:bg-slate-50/80 transition-colors duration-150">
                                <td class="px-6 py-4 font-mono font-bold text-xs text-slate-500">#LN-<?php echo $row['id']; ?></td>
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    <?php echo htmlspecialchars($row['borrower']); ?>
                                </td>
                                <td class="px-6 py-4 font-black text-slate-900">
                                    ₦<?php echo number_format((float)$row['amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 font-black text-blue-600">
                                    ₦<?php echo number_format((float)$row['repayment_amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 font-black text-emerald-600">
                                    ₦<?php echo number_format((float)($row['principal_returned'] ?? 0), 2); ?>
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-slate-500">
                                    <?php echo !empty($row['due_date']) ? date("M d, Y", strtotime($row['due_date'])) : 'N/A'; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full border font-bold text-[11px] capitalize tracking-wide shadow-xs inline-block <?php echo $badge_color; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-400 italic font-medium">No active pool-funded loans found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <!-- TAB 2: HISTORICAL PERFORMANCE -->
        <div id="tab-content-history" class="tab-content-pane hidden overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100 text-slate-600 uppercase text-[11px] font-black tracking-wider border-b border-slate-200/80">
                    <tr>
                        <th class="px-6 py-4">Borrower</th>
                        <th class="px-6 py-4">Principal Funded</th>
                        <th class="px-6 py-4">Profit Yield</th>
                        <th class="px-6 py-4">Completion Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if ($loan_history && $loan_history->num_rows > 0): ?>
                        <?php while ($row = $loan_history->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors duration-150">
                                <td class="px-6 py-4 font-bold text-slate-900"><?php echo htmlspecialchars($row['borrower']); ?></td>
                                <td class="px-6 py-4 font-black text-slate-900">₦<?php echo number_format($row['amount'], 2); ?></td>
                                <td class="px-6 py-4 font-black text-emerald-600">+₦<?php echo number_format($row['profit'], 2); ?></td>
                                <td class="px-6 py-4 text-xs font-semibold text-slate-500">
                                    <?php echo !empty($row['paid_date']) ? date("M d, Y", strtotime($row['paid_date'])) : 'N/A'; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-400 italic font-medium">No completed loans recorded.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <!-- TAB 3: WALLET TRANSACTION AUDIT -->
        <div id="tab-content-transactions" class="tab-content-pane hidden overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100 text-slate-600 uppercase text-[11px] font-black tracking-wider border-b border-slate-200/80">
                    <tr>
                        <th class="px-6 py-4">Reference Code</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4">Transaction Type</th>
                        <th class="px-6 py-4">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if ($transactions && $transactions->num_rows > 0): ?>
                        <?php while ($row = $transactions->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors duration-150">
                                <td class="px-6 py-4 font-mono text-xs font-bold text-slate-500">#<?php echo htmlspecialchars($row['reference']); ?></td>
                                <td class="px-6 py-4 font-black text-slate-900">₦<?php echo number_format($row['amount'], 2); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-slate-100 border border-slate-200 text-slate-700 rounded-full font-bold text-[11px] capitalize tracking-wide inline-block">
                                        <?php echo htmlspecialchars($row['type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-slate-500">
                                    <?php echo date("M d, Y - H:i", strtotime($row['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-400 italic font-medium">No wallet activity recorded.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
                </div>
            </div>

        </div>

    </div>

</section>

        <!-- PORTFOLIO PERFORMANCE -->
        <section class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">

            <h2 class="text-xl font-black text-slate-900 mb-6 tracking-tight flex items-center gap-2.5">
                <span class="w-2.5 h-6 bg-blue-600 rounded-full inline-block"></span>
                Yield & Growth Chart
            </h2>

            <div class="h-64 sm:h-72">

                <canvas id="investmentGrowthChart"></canvas>

            </div>

        </section>

        <!-- THREE SUMMARY PANELS (Chart + two extra cards) -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Chart panel (same canvas above acts as primary) -->
            <div class="col-span-1 lg:col-span-1 bg-gradient-to-b from-slate-50 to-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                <div class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2">Yield & Growth</div>
                <div class="h-44">
                    <canvas id="investmentGrowthChartMini"></canvas>
                </div>
            </div>

            <!-- Extra Card A -->
            <div class="col-span-1 bg-gradient-to-r from-amber-50 to-amber-100 p-5 rounded-2xl border border-amber-200 shadow-md flex flex-col justify-between">
                <div>
                    <div class="text-[11px] font-black text-amber-600 uppercase tracking-wider">Recent Yield</div>
                    <div class="text-2xl font-extrabold text-amber-700 mt-2">₦<?php echo number_format($recent_yield ?? 0, 2); ?></div>
                    <p class="text-xs text-amber-600 mt-1">Latest realized yield across matured loans.</p>
                </div>
                <div class="mt-4 text-sm text-amber-700 font-semibold">View details</div>
            </div>

            <!-- Extra Card B -->
            <div class="col-span-1 bg-gradient-to-r from-emerald-50 to-emerald-100 p-5 rounded-2xl border border-emerald-200 shadow-md flex flex-col justify-between">
                <div>
                    <div class="text-[11px] font-black text-emerald-600 uppercase tracking-wider">Risk Exposure</div>
                    <div class="text-2xl font-extrabold text-emerald-700 mt-2"><?php echo $risk_exposure ?? 'Low'; ?></div>
                    <p class="text-xs text-emerald-600 mt-1">Current allocation exposure across cohorts.</p>
                </div>
                <div class="mt-4 text-sm text-emerald-700 font-semibold">Manage allocation</div>
            </div>

        </section>


        <!-- NOTIFICATIONS -->
        <section id="notificationsContainer"
             class="hidden bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">

            <h2 class="text-xl font-black text-slate-900 mb-6 tracking-tight flex items-center gap-2.5">
                <span class="w-2.5 h-6 bg-amber-500 rounded-full inline-block"></span>
                Notifications & Broadcasts
            </h2>

            <div class="space-y-3">

                <?php if ($notifications && $notifications->num_rows > 0): ?>

                    <?php while ($row = $notifications->fetch_assoc()): ?>

                        <div class="p-4 bg-slate-50/80 hover:bg-slate-100/80 rounded-2xl border border-slate-200/60 transition-all duration-200 hover:translate-x-1">

                            <div class="flex justify-between items-start gap-4">

                                <div class="space-y-1">

                                    <?php if (!empty($row['title'])): ?>

                                        <h3 class="text-sm font-black text-slate-900">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </h3>

                                    <?php endif; ?>

                                    <p class="text-sm text-slate-600 font-medium">
                                        <?php echo htmlspecialchars($row['message']); ?>
                                    </p>

                                </div>

                                <span class="text-[11px] font-bold text-slate-400 whitespace-nowrap bg-white px-2.5 py-1 rounded-full border border-slate-200/60 shadow-xs">
                                    <?php echo date("M d, H:i", strtotime($row['created_at'])); ?>
                                </span>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="p-6 bg-slate-50/80 rounded-2xl text-sm text-slate-400 text-center italic font-medium border border-slate-100">

                        No notifications available.

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- REPORTS -->
        <section id="reports" class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">

            <h2 class="text-xl font-black text-slate-900 mb-6 tracking-tight flex items-center gap-2.5">
                <span class="w-2.5 h-6 bg-rose-600 rounded-full inline-block"></span>
                Statement & Report Exports
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <a href="report_loans.php" class="block p-4 rounded-2xl border border-slate-100 hover:shadow-md transition-all bg-slate-50">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-file-pdf text-rose-600 text-2xl"></i>
                        <div>
                            <div class="font-bold text-slate-900">Download Statement</div>
                            <div class="text-xs text-slate-500">Comprehensive loan statements (PDF)</div>
                        </div>
                    </div>
                </a>

                <a href="report_investments.php" class="block p-4 rounded-2xl border border-slate-100 hover:shadow-md transition-all bg-slate-50">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-file-pdf text-rose-600 text-2xl"></i>
                        <div>
                            <div class="font-bold text-slate-900">Investment Report</div>
                            <div class="text-xs text-slate-500">Capital allocation and performance</div>
                        </div>
                    </div>
                </a>

                <a href="report_profits.php" class="block p-4 rounded-2xl border border-slate-100 hover:shadow-md transition-all bg-slate-50">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-file-pdf text-rose-600 text-2xl"></i>
                        <div>
                            <div class="font-bold text-slate-900">Profit Report</div>
                            <div class="text-xs text-slate-500">Realized and unrealized profits</div>
                        </div>
                    </div>
                </a>

            </div>

        </section>


        <!-- PROFILE AND SETTINGS -->
        <div id="settings" class="grid grid-cols-1 md:grid-cols-2 gap-8">


            <!-- PROFILE -->
            <section class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">

                <h2 class="text-xl font-black text-slate-900 mb-6 tracking-tight flex items-center gap-2.5">
                    <span class="w-2.5 h-6 bg-emerald-500 rounded-full inline-block"></span>
                    Profile Credentials
                </h2>


                    <form action="lender_dashboard.php"
                        method="POST"
                        class="space-y-4 grid grid-cols-1 md:grid-cols-2 gap-4">

                    <input type="hidden"
                           name="action"
                           value="update_profile">


                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Full Name</label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($lender_name); ?>" required class="w-full p-3.5 rounded-2xl border border-slate-200 bg-slate-50/50 font-semibold text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all">
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($lender_email); ?>" required class="w-full p-3.5 rounded-2xl border border-slate-200 bg-slate-50/50 font-semibold text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all">
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($lender_phone); ?>" class="w-full p-3.5 rounded-2xl border border-slate-200 bg-slate-50/50 font-semibold text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all">
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Linked Bank Account</label>
                        <input type="text" value="<?php echo htmlspecialchars($bank_account_display); ?>" readonly class="w-full p-3.5 bg-slate-100/80 rounded-2xl border border-slate-200/60 text-slate-500 font-semibold text-sm cursor-not-allowed">
                    </div>

                    <div class="col-span-1 md:col-span-2 flex items-center justify-between">
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-2">Verification Status</label>
                            <span class="inline-block px-3 py-1 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-full text-xs font-bold capitalize shadow-xs"><?php echo htmlspecialchars($verification_status); ?></span>
                        </div>

                        <div>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-extrabold px-7 py-3.5 rounded-2xl transition-all duration-200 text-sm shadow-md">Update Profile</button>
                        </div>
                    </div>

                </form>

            </section>


            <!-- SETTINGS -->
            <section class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">

                <h2 class="text-xl font-black text-slate-900 mb-6 tracking-tight flex items-center gap-2.5">
                    <span class="w-2.5 h-6 bg-slate-900 rounded-full inline-block"></span>
                    Account Preferences
                </h2>


                <div class="grid grid-cols-1 gap-4">

                    <button type="button" onclick="alert('Password change will be connected here.')" class="w-full text-left p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-900">Change Security Password</div>
                                <div class="text-xs text-slate-500">Update your login and transaction password</div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-xs text-slate-400"></i>
                        </div>
                    </button>

                    <button type="button" onclick="alert('Notification settings will be connected here.')" class="w-full text-left p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-900">Notification Preferences</div>
                                <div class="text-xs text-slate-500">Control email and app notifications</div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-xs text-slate-400"></i>
                        </div>
                    </button>

                    <a href="logout.php" class="block w-full text-left p-4 bg-rose-50 rounded-2xl border border-rose-100 hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-bold text-rose-700">Logout</div>
                                <div class="text-xs text-rose-500">End your session securely</div>
                            </div>
                            <i class="fa-solid fa-right-from-bracket text-xs text-rose-600"></i>
                        </div>
                    </a>

                </div>

            </section>

        </div>

    </main>


    <!-- CHART SCRIPT -->
    <script>

        const chartCanvas = document.getElementById('investmentGrowthChart');

        if (chartCanvas && typeof Chart !== 'undefined') {

            const ctx = chartCanvas.getContext('2d');

            const chartLabels =
                <?php echo json_encode($monthly_labels); ?>;

            const chartData =
                <?php echo json_encode($monthly_data); ?>;


            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
            gradient.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

            new Chart(ctx, {

                type: 'line',

                data: {

                    labels: chartLabels,

                    datasets: [{

                        label: 'Investment Growth (₦)',

                        data: chartData,

                        borderColor: '#2563eb',

                        borderWidth: 3,

                        pointBackgroundColor: '#2563eb',

                        pointBorderColor: '#ffffff',

                        pointBorderWidth: 2,

                        pointRadius: 4,

                        pointHoverRadius: 6,

                        backgroundColor: gradient,

                        fill: true,

                        tension: 0.4

                    }]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    plugins: {

                        legend: {

                            display: false

                        }

                    },

                    scales: {

                        y: {

                            beginAtZero: true,

                            grid: {

                                borderDash: [4, 4],

                                color: '#e2e8f0'

                            },

                            ticks: {

                                font: {

                                    family: "system-ui, -apple-system, sans-serif",

                                    weight: 'bold'

                                },

                                color: '#64748b'

                            }

                        },

                        x: {

                            grid: {

                                display: false

                            },

                            ticks: {

                                font: {

                                    family: "system-ui, -apple-system, sans-serif",

                                    weight: 'bold'

                                },

                                color: '#64748b'

                            }

                        }

                    }

                }

            });

        }

        /**
         * Deposit / Withdraw handlers
         */

        function openTransactionModal(type) {
            const modal = document.getElementById('transactionModal');
            const title = document.getElementById('transactionModalTitle');
            const submitBtn = document.getElementById('transactionSubmit');
            const amountInput = document.getElementById('transactionAmount');

            amountInput.value = '';
            modal.dataset.type = type;

            if (type === 'deposit') {
                title.textContent = 'Deposit Funds';
                submitBtn.textContent = 'Confirm Deposit';
                submitBtn.dataset.api = 'Api/deposit_funds.php';
                submitBtn.classList.remove('bg-slate-900');
                submitBtn.classList.add('bg-blue-600');
            } else {
                title.textContent = 'Withdraw Funds';
                submitBtn.textContent = 'Confirm Withdrawal';
                submitBtn.dataset.api = 'Api/withdraw.php';
                submitBtn.classList.remove('bg-blue-600');
                submitBtn.classList.add('bg-slate-900');
            }

            modal.classList.remove('hidden');
        }

        function closeTransactionModal() {
            const modal = document.getElementById('transactionModal');
            modal.classList.add('hidden');
        }

        async function performTransaction() {
            const modal = document.getElementById('transactionModal');
            const amountInput = document.getElementById('transactionAmount');
            const submitBtn = document.getElementById('transactionSubmit');
            const feedback = document.getElementById('transactionFeedback');

            const raw = amountInput.value.trim();
            const amount = parseFloat(raw.replace(/[,\s]/g, ''));
            if (isNaN(amount) || amount <= 0) {
                feedback.textContent = 'Enter a valid amount greater than zero.';
                feedback.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            feedback.classList.add('hidden');

            try {
                const api = submitBtn.dataset.api;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const resp = await fetch(api, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ amount: amount })
                });

                const data = await resp.json();

                if (data.success) {
                    alert(data.message || 'Transaction successful.');
                    window.location.reload();
                } else {
                    feedback.textContent = data.message || 'Transaction failed.';
                    feedback.classList.remove('hidden');
                }

            } catch (err) {
                console.error(err);
                feedback.textContent = 'An error occurred. Please try again.';
                feedback.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = submitBtn.dataset.api && submitBtn.dataset.api.includes('deposit') ? 'Confirm Deposit' : 'Confirm Withdrawal';
            }
        }

        /**
         * Lender Dashboard Tab Switcher Logic
         */

function switchDashboardTab(targetTab) {
    const container = document.getElementById('table-display-container');
    const titleEl = document.getElementById('current-table-title');
    const accentEl = document.getElementById('title-accent-bar');

    if (!container) return;

    // 1. Reveal container if collapsed
    container.classList.remove('hidden');

    // 2. Hide all content panes
    const panes = document.querySelectorAll('.tab-content-pane');
    panes.forEach(pane => pane.classList.add('hidden'));

    // 3. Reset selector button styles
    const buttons = document.querySelectorAll('.tab-selector-btn');
    buttons.forEach(btn => {
        btn.classList.remove('active-tab', 'bg-blue-50/80', 'border-blue-500', 'ring-2', 'ring-blue-500/20');
        btn.classList.add('bg-slate-50/50', 'border-slate-200/80');
    });

    // 4. Activate target tab and pane
    const activeBtn = document.getElementById(`tab-btn-${targetTab}`);
    const activePane = document.getElementById(`tab-content-${targetTab}`);

    if (activePane) {
        activePane.classList.remove('hidden');
    }

    if (activeBtn) {
        activeBtn.classList.add('active-tab', 'bg-blue-50/80', 'border-blue-500', 'ring-2', 'ring-blue-500/20');
        activeBtn.classList.remove('bg-slate-50/50', 'border-slate-200/80');
    }

    // 5. Update header dynamic text & accent color
    const titleTextEl = document.getElementById('table-title-text');

    if (targetTab === 'active') {
        titleTextEl.textContent = 'Active Student Loans';
        accentEl.className = 'w-2 h-4 bg-blue-500 rounded-full inline-block';
    } else if (targetTab === 'history') {
        titleTextEl.textContent = 'Historical Performance';
        accentEl.className = 'w-2 h-4 bg-teal-500 rounded-full inline-block';
    } else if (targetTab === 'transactions') {
        titleTextEl.textContent = 'Wallet Transaction Audit';
        accentEl.className = 'w-2 h-4 bg-slate-400 rounded-full inline-block';
    }
}

// Initialize small mini-chart if present
const miniCanvas = document.getElementById('investmentGrowthChartMini');
if (miniCanvas && typeof Chart !== 'undefined') {
    const mctx = miniCanvas.getContext('2d');
    new Chart(mctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($monthly_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($monthly_data); ?>,
                borderColor: '#f59e0b',
                borderWidth: 2,
                pointRadius: 0,
                fill: false,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { display: false }, y: { display: false } }
        }
    });
}

// Show notifications container when invoked from sidebar
function showNotifications() {
    const container = document.getElementById('notificationsContainer');
    if (!container) return;
    container.classList.remove('hidden');
    container.scrollIntoView({behavior: 'smooth', block: 'start'});
}

/**
 * Collapse Table Container via 'X' Cancel Sign
 */
function collapseDashboardTables() {
    const container = document.getElementById('table-display-container');
    if (container) {
        container.classList.add('hidden');
    }

    // Remove active highlight state from selector tabs
    const buttons = document.querySelectorAll('.tab-selector-btn');
    buttons.forEach(btn => {
        btn.classList.remove('active-tab', 'bg-blue-50/80', 'border-blue-500', 'ring-2', 'ring-blue-500/20');
        btn.classList.add('bg-slate-50/50', 'border-slate-200/80');
    });
}

    </script>

    <!-- Transaction Modal -->
    <div id="transactionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 hidden">
        <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 id="transactionModalTitle" class="text-lg font-black">Deposit Funds</h3>
                <button onclick="closeTransactionModal()" class="text-slate-500 hover:text-slate-900">✕</button>
            </div>

            <div class="space-y-3">
                <label class="text-sm font-bold text-slate-500">Amount (NGN)</label>
                <input id="transactionAmount" type="text" placeholder="0.00" class="w-full p-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-200">

                <p id="transactionFeedback" class="text-rose-600 text-sm hidden"></p>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <button onclick="closeTransactionModal()" class="px-4 py-2 rounded-xl bg-slate-100 font-bold">Cancel</button>
                    <button id="transactionSubmit" onclick="performTransaction()" data-api="Api/deposit_funds.php" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-black">Confirm Deposit</button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
