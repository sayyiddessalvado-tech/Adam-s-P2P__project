 <?php
// lender_dashboard.php
session_start();

require_once 'lender_dashboard_data.php';

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

    <title>EduLend - Lender Dashboard</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50 text-gray-900">

    <!-- HEADER -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30 px-6 py-4 shadow-sm">
        <div class="max-w-7xl mx-auto flex justify-between items-center">

            <div class="flex items-center gap-3">
                <span class="text-2xl font-black text-emerald-600 tracking-tight">
                    EduLend
                </span>
            </div>

            <div class="flex items-center gap-6">

                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-gray-900">
                        <?php echo htmlspecialchars($lender_name); ?>
                    </span>

                    <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold uppercase tracking-wider">
                        <?php echo htmlspecialchars($verification_status); ?>
                    </span>
                </div>

                <a href="#notifications"
                   class="relative text-gray-600 hover:text-emerald-600">
                    <i class="fa-solid fa-bell text-lg"></i>
                </a>

                <a href="logout.php"
                   class="text-gray-600 hover:text-rose-600 text-sm font-bold flex items-center gap-1">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>

            </div>
        </div>
    </header>


    <!-- MAIN CONTENT -->
    <main class="max-w-7xl mx-auto p-6 md:p-8 space-y-10">


        <!-- SUCCESS MESSAGE -->
        <?php if (!empty($message)): ?>

            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-semibold">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->
        <?php if (!empty($error)): ?>

            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-semibold">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <!-- HERO SECTION -->
        <section class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">

                <div>

                    <h1 class="text-3xl font-black text-gray-900 mb-2">
                        Welcome back,
                        <?php echo htmlspecialchars(explode(' ', $lender_name)[0]); ?>.
                    </h1>

                    <p class="text-gray-600 text-sm">
                        Your money is helping verified students access educational loans.
                    </p>

                </div>

                <a href="#deposit-withdraw"
                   class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-xl transition shadow-sm flex items-center gap-2">

                    <i class="fa-solid fa-plus-circle"></i>

                    Deposit Funds

                </a>

            </div>

        </section>


        <!-- SUMMARY CARDS -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">


            <!-- WALLET BALANCE -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">

                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-2">
                    Wallet Balance
                </span>

                <h3 class="text-3xl font-black text-gray-900 mb-2">
                    ₦<?php echo number_format($wallet_balance, 2); ?>
                </h3>

                <p class="text-xs text-gray-500">
                    Money currently inside lender wallet.
                </p>

            </div>


            <!-- MONEY INVESTED -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">

                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-2">
                    Money Currently Invested
                </span>

                <h3 class="text-3xl font-black text-blue-600 mb-2">
                    ₦<?php echo number_format($money_invested, 2); ?>
                </h3>

                <p class="text-xs text-gray-500">
                    Money currently funding active student loans.
                </p>

            </div>


            <!-- TOTAL PROFIT -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">

                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-2">
                    Total Profit Earned
                </span>

                <h3 class="text-3xl font-black text-purple-600 mb-2">
                    ₦<?php echo number_format($profit_earned, 2); ?>
                </h3>

                <p class="text-xs text-gray-500">
                    Profit earned from completed loans.
                </p>

            </div>


            <!-- AVAILABLE WITHDRAW -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">

                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-2">
                    Available to Withdraw
                </span>

                <h3 class="text-3xl font-black text-emerald-600 mb-2">
                    ₦<?php echo number_format($available_withdraw, 2); ?>
                </h3>

                <p class="text-xs text-gray-500">
                    Funds currently available for withdrawal.
                </p>

            </div>


            <!-- STUDENTS SPONSORED -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">

                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-2">
                    Students Sponsored
                </span>

                <h3 class="text-3xl font-black text-gray-900 mb-2">
                    <?php echo $students_sponsored; ?>
                </h3>

                <p class="text-xs text-gray-500">
                    Students funded through your loans.
                </p>

            </div>


            <!-- RECOVERY RATE -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">

                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-2">
                    Loan Recovery Rate
                </span>

                <h3 class="text-3xl font-black text-emerald-600 mb-2">
                    <?php echo $recovery_rate; ?>%
                </h3>

                <p class="text-xs text-gray-500">
                    Percentage of loans successfully repaid.
                </p>

            </div>

        </section>


        <!-- DEPOSIT / WITHDRAW -->
        <section id="deposit-withdraw"
                 class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm">

            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Deposit / Withdraw
            </h2>

            <div class="flex flex-col sm:flex-row gap-4">

                <button type="button"
                        onclick="alert('Deposit integration will be connected here.')"
                        class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 rounded-xl transition shadow-sm">

                    <i class="fa-solid fa-wallet mr-2"></i>

                    Deposit Funds

                </button>

                <button type="button"
                        onclick="alert('Withdrawal integration will be connected here.')"
                        class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-bold py-4 rounded-xl transition shadow-sm">

                    <i class="fa-solid fa-money-bill-transfer mr-2"></i>

                    Withdraw Funds

                </button>

            </div>

        </section>


        <!-- INVESTMENT STATISTICS -->
        <section class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm">

            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Investment Statistics
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-center">


                <!-- TOTAL CAPITAL -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">

                    <span class="text-xs font-bold text-gray-500 uppercase block mb-1">
                        Total Capital
                    </span>

                    <p class="text-xl font-black text-gray-900">
                        ₦<?php echo number_format($total_capital, 2); ?>
                    </p>

                </div>


                <!-- TOTAL LOANS -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">

                    <span class="text-xs font-bold text-gray-500 uppercase block mb-1">
                        Total Active Loans
                    </span>

                    <p class="text-xl font-black text-gray-900">
                        <?php echo $total_loans_count; ?>
                    </p>

                </div>


                <!-- CAPITAL AVAILABLE -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">

                    <span class="text-xs font-bold text-gray-500 uppercase block mb-1">
                        Capital Available
                    </span>

                    <p class="text-xl font-black text-emerald-600">
                        ₦<?php echo number_format($capital_available, 2); ?>
                    </p>

                </div>


                <!-- AVERAGE LOAN -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">

                    <span class="text-xs font-bold text-gray-500 uppercase block mb-1">
                        Average Loan Size
                    </span>

                    <p class="text-xl font-black text-gray-900">
                        ₦<?php echo number_format($average_loan_size, 2); ?>
                    </p>

                </div>

            </div>

        </section>


        <!-- ACTIVE LOANS -->
        <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

            <div class="p-6 border-b border-gray-200">

                <h2 class="text-xl font-bold text-gray-900">
                    Active Loans
                </h2>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold">

                        <tr>

                            <th class="px-6 py-4">
                                Loan ID
                            </th>

                            <th class="px-6 py-4">
                                Borrower
                            </th>

                            <th class="px-6 py-4">
                                Amount
                            </th>

                            <th class="px-6 py-4">
                                Repayment
                            </th>

                            <th class="px-6 py-4">
                                Due Date
                            </th>

                            <th class="px-6 py-4">
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100 text-gray-700">

                        <?php if ($active_loans && $active_loans->num_rows > 0): ?>

                            <?php while ($row = $active_loans->fetch_assoc()): ?>

                                <?php

                                $status = strtolower($row['status']);

                                $badge_color = 'bg-emerald-100 text-emerald-800';

                                if ($status === 'paid') {

                                    $badge_color = 'bg-blue-100 text-blue-800';

                                } elseif ($status === 'pending') {

                                    $badge_color = 'bg-yellow-100 text-yellow-800';

                                } elseif ($status === 'defaulted') {

                                    $badge_color = 'bg-rose-100 text-rose-800';

                                }

                                ?>

                                <tr>

                                    <td class="px-6 py-4 font-mono font-bold text-xs">
                                        #LN-<?php echo $row['id']; ?>
                                    </td>

                                    <td class="px-6 py-4 font-bold text-gray-900">
                                        <?php echo htmlspecialchars($row['borrower']); ?>
                                    </td>

                                    <td class="px-6 py-4 font-bold">
                                        ₦<?php echo number_format($row['amount'], 2); ?>
                                    </td>

                                    <td class="px-6 py-4 font-bold">
                                        ₦<?php echo number_format($row['repayment_amount'], 2); ?>
                                    </td>

                                    <td class="px-6 py-4 text-xs text-gray-500">
                                        <?php echo !empty($row['due_date']) ? date("M d, Y", strtotime($row['due_date'])) : 'N/A'; ?>
                                    </td>

                                    <td class="px-6 py-4">

                                        <span class="px-3 py-1 rounded-full font-bold text-xs capitalize <?php echo $badge_color; ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="6"
                                    class="px-6 py-8 text-center text-gray-400 italic">

                                    No active loans found.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- LOAN HISTORY -->
        <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

            <div class="p-6 border-b border-gray-200">

                <h2 class="text-xl font-bold text-gray-900">
                    Loan History
                </h2>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold">

                        <tr>

                            <th class="px-6 py-4">
                                Borrower
                            </th>

                            <th class="px-6 py-4">
                                Amount
                            </th>

                            <th class="px-6 py-4">
                                Profit
                            </th>

                            <th class="px-6 py-4">
                                Completed Date
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100 text-gray-700">

                        <?php if ($loan_history && $loan_history->num_rows > 0): ?>

                            <?php while ($row = $loan_history->fetch_assoc()): ?>

                                <tr>

                                    <td class="px-6 py-4 font-bold text-gray-900">
                                        <?php echo htmlspecialchars($row['borrower']); ?>
                                    </td>

                                    <td class="px-6 py-4 font-bold">
                                        ₦<?php echo number_format($row['amount'], 2); ?>
                                    </td>

                                    <td class="px-6 py-4 font-bold text-emerald-600">
                                        +₦<?php echo number_format($row['profit'], 2); ?>
                                    </td>

                                    <td class="px-6 py-4 text-xs text-gray-500">

                                        <?php
                                        echo !empty($row['paid_date'])
                                            ? date("M d, Y", strtotime($row['paid_date']))
                                            : 'N/A';
                                        ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="4"
                                    class="px-6 py-8 text-center text-gray-400 italic">

                                    No completed loans recorded.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- TRANSACTION HISTORY -->
        <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

            <div class="p-6 border-b border-gray-200">

                <h2 class="text-xl font-bold text-gray-900">
                    Transaction History
                </h2>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold">

                        <tr>

                            <th class="px-6 py-4">
                                Reference
                            </th>

                            <th class="px-6 py-4">
                                Amount
                            </th>

                            <th class="px-6 py-4">
                                Type
                            </th>

                            <th class="px-6 py-4">
                                Date
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100 text-gray-700">

                        <?php if ($transactions && $transactions->num_rows > 0): ?>

                            <?php while ($row = $transactions->fetch_assoc()): ?>

                                <tr>

                                    <td class="px-6 py-4 font-mono text-xs font-bold text-gray-500">
                                        #<?php echo htmlspecialchars($row['reference']); ?>
                                    </td>

                                    <td class="px-6 py-4 font-bold">
                                        ₦<?php echo number_format($row['amount'], 2); ?>
                                    </td>

                                    <td class="px-6 py-4">

                                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full font-bold text-xs capitalize">
                                            <?php echo htmlspecialchars($row['type']); ?>
                                        </span>

                                    </td>

                                    <td class="px-6 py-4 text-xs text-gray-500">
                                        <?php echo date("M d, Y - H:i", strtotime($row['created_at'])); ?>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="4"
                                    class="px-6 py-8 text-center text-gray-400 italic">

                                    No wallet activity recorded.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- PORTFOLIO PERFORMANCE -->
        <section class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm">

            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Portfolio Performance
            </h2>

            <div class="h-64">

                <canvas id="investmentGrowthChart"></canvas>

            </div>

        </section>


        <!-- NOTIFICATIONS -->
        <section id="notifications"
                 class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm">

            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Notifications
            </h2>

            <div class="space-y-3">

                <?php if ($notifications && $notifications->num_rows > 0): ?>

                    <?php while ($row = $notifications->fetch_assoc()): ?>

                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">

                            <div class="flex justify-between items-start gap-4">

                                <div>

                                    <?php if (!empty($row['title'])): ?>

                                        <h3 class="text-sm font-bold text-gray-900 mb-1">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </h3>

                                    <?php endif; ?>

                                    <p class="text-sm text-gray-700">
                                        <?php echo htmlspecialchars($row['message']); ?>
                                    </p>

                                </div>

                                <span class="text-xs text-gray-400 whitespace-nowrap">
                                    <?php echo date("M d, H:i", strtotime($row['created_at'])); ?>
                                </span>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="p-4 bg-gray-50 rounded-xl text-sm text-gray-500 text-center italic">

                        No notifications available.

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- REPORTS -->
        <section class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm">

            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Reports
            </h2>

            <div class="flex flex-wrap gap-4">

                <a href="report_loans.php"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold px-5 py-3 rounded-xl transition text-sm inline-flex items-center">

                    <i class="fa-solid fa-file-pdf text-rose-600 mr-2"></i>

                    Download Statement

                </a>

                <a href="report_investments.php"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold px-5 py-3 rounded-xl transition text-sm inline-flex items-center">

                    <i class="fa-solid fa-file-pdf text-rose-600 mr-2"></i>

                    Download Investment Report

                </a>

                <a href="report_profits.php"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold px-5 py-3 rounded-xl transition text-sm inline-flex items-center">

                    <i class="fa-solid fa-file-pdf text-rose-600 mr-2"></i>

                    Download Profit Report

                </a>

            </div>

        </section>


        <!-- PROFILE AND SETTINGS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">


            <!-- PROFILE -->
            <section class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm">

                <h2 class="text-xl font-bold text-gray-900 mb-6">
                    Profile
                </h2>


                <form action="lender_dashboard.php"
                      method="POST"
                      class="space-y-4">

                    <input type="hidden"
                           name="action"
                           value="update_profile">


                    <div>

                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">
                            Full Name
                        </label>

                        <input type="text"
                               name="fullname"
                               value="<?php echo htmlspecialchars($lender_name); ?>"
                               required
                               class="w-full p-3 rounded-xl border border-gray-300 font-medium text-sm">

                    </div>


                    <div>

                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">
                            Email
                        </label>

                        <input type="email"
                               name="email"
                               value="<?php echo htmlspecialchars($lender_email); ?>"
                               required
                               class="w-full p-3 rounded-xl border border-gray-300 font-medium text-sm">

                    </div>


                    <div>

                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">
                            Phone
                        </label>

                        <input type="text"
                               name="phone"
                               value="<?php echo htmlspecialchars($lender_phone); ?>"
                               class="w-full p-3 rounded-xl border border-gray-300 font-medium text-sm">

                    </div>


                    <div>

                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">
                            Bank Account
                        </label>

                        <input type="text"
                               value="<?php echo htmlspecialchars($bank_account_display); ?>"
                               readonly
                               class="w-full p-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-500 font-medium text-sm">

                    </div>


                    <div>

                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">
                            Verification Status
                        </label>

                        <span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold capitalize">

                            <?php echo htmlspecialchars($verification_status); ?>

                        </span>

                    </div>


                    <button type="submit"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-xl transition text-sm">

                        Update Profile

                    </button>

                </form>

            </section>


            <!-- SETTINGS -->
            <section class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm">

                <h2 class="text-xl font-bold text-gray-900 mb-6">
                    Settings
                </h2>


                <div class="space-y-4">


                    <button type="button"
                            onclick="alert('Password change will be connected here.')"
                            class="w-full text-left p-4 bg-gray-50 hover:bg-gray-100 rounded-xl font-bold text-sm text-gray-800 border border-gray-100 flex justify-between items-center">

                        <span>
                            Change Password
                        </span>

                        <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>

                    </button>


                    <button type="button"
                            onclick="alert('Notification settings will be connected here.')"
                            class="w-full text-left p-4 bg-gray-50 hover:bg-gray-100 rounded-xl font-bold text-sm text-gray-800 border border-gray-100 flex justify-between items-center">

                        <span>
                            Notification Settings
                        </span>

                        <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>

                    </button>


                    <a href="logout.php"
                       class="block w-full text-left p-4 bg-rose-50 hover:bg-rose-100 rounded-xl font-bold text-sm text-rose-700 border border-rose-100 flex justify-between items-center">

                        <span>
                            Logout
                        </span>

                        <i class="fa-solid fa-right-from-bracket text-xs"></i>

                    </a>

                </div>

            </section>

        </div>

    </main>


    <!-- CHART SCRIPT -->
    <script>

        const chartCanvas = document.getElementById('investmentGrowthChart');

        if (chartCanvas) {

            const ctx = chartCanvas.getContext('2d');

            const chartLabels =
                <?php echo json_encode($monthly_labels); ?>;

            const chartData =
                <?php echo json_encode($monthly_data); ?>;


            new Chart(ctx, {

                type: 'line',

                data: {

                    labels: chartLabels,

                    datasets: [{

                        label: 'Investment Growth (₦)',

                        data: chartData,

                        borderColor: '#059669',

                        backgroundColor: 'rgba(5, 150, 105, 0.1)',

                        fill: true,

                        tension: 0.3

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

                                borderDash: [2, 4]

                            }

                        },

                        x: {

                            grid: {

                                display: false

                            }

                        }

                    }

                }

            });

        }

    </script>

</body>
</html>