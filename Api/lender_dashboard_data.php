<?php
// lender_dashboard_data.php

session_start();

require_once 'Includes_dynamics/dataB.php';

// ============================================================
// AUTHENTICATION
// ============================================================

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'lender') {
    header("Location: p2p.html");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$message = '';
$error = '';


// ============================================================
// PROFILE UPDATE
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'update_profile'
) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullname === '' || $email === '') {
        $error = 'Full Name and Email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check whether another user already has this email.
            $email_check = $conn->prepare(
                "SELECT id
                 FROM users
                 WHERE email = ?
                 AND id <> ?
                 LIMIT 1"
            );

            $email_check->bind_param(
                "si",
                $email,
                $user_id
            );

            $email_check->execute();

            $email_exists = $email_check
                ->get_result()
                ->fetch_assoc();

            if ($email_exists) {
                $error = 'That email address is already in use.';
            } else {

                if ($password !== '') {

                    $password_hash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $stmt = $conn->prepare(
                        "UPDATE users
                         SET fullname = ?,
                             email = ?,
                             phone = ?,
                             password_hash = ?
                         WHERE id = ?"
                    );

                    $stmt->bind_param(
                        "ssssi",
                        $fullname,
                        $email,
                        $phone,
                        $password_hash,
                        $user_id
                    );

                } else {

                    $stmt = $conn->prepare(
                        "UPDATE users
                         SET fullname = ?,
                             email = ?,
                             phone = ?
                         WHERE id = ?"
                    );

                    $stmt->bind_param(
                        "sssi",
                        $fullname,
                        $email,
                        $phone,
                        $user_id
                    );
                }

                if ($stmt->execute()) {
                    $message = 'Profile updated successfully.';
                } else {
                    $error = 'Unable to update your profile.';
                }
            }

        } catch (Throwable $e) {
            $error = 'Unable to update your profile right now.';
        }
    }
}


// ============================================================
// DEFAULT VALUES
// ============================================================

$lender_name = 'Lender';
$lender_email = '';
$lender_phone = 'Not provided';

$verification_status = 'pending';

$wallet_balance = 0.00;
$money_invested = 0.00;
$profit_earned = 0.00;
$available_withdraw = 0.00;

$students_sponsored = 0;
$recovery_rate = 0;

$total_capital = 0.00;
$total_loans_count = 0;
$capital_available = 0.00;
$average_loan_size = 0.00;

$active_loans = null;
$loan_history = null;
$transactions = null;
$notifications = null;


// ============================================================
// FETCH LENDER DASHBOARD DATA
// ============================================================

try {

    // ========================================================
    // LENDER PROFILE
    // ========================================================

    $u_stmt = $conn->prepare(
        "SELECT fullname, email, phone
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $u_stmt->bind_param(
        "i",
        $user_id
    );

    $u_stmt->execute();

    $user_data = $u_stmt
        ->get_result()
        ->fetch_assoc();

    if (!$user_data) {
        session_destroy();

        header("Location: p2p.html");
        exit();
    }

    $lender_name = $user_data['fullname'] ?? 'Lender';
    $lender_email = $user_data['email'] ?? '';
    $lender_phone = $user_data['phone'] ?? 'Not provided';


    // ========================================================
    // VERIFICATION STATUS
    // ========================================================

    $ver_stmt = $conn->prepare(
        "SELECT status
         FROM verifications
         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 1"
    );

    $ver_stmt->bind_param(
        "i",
        $user_id
    );

    $ver_stmt->execute();

    $ver_data = $ver_stmt
        ->get_result()
        ->fetch_assoc();

    $verification_status =
        $ver_data['status'] ?? 'pending';


    // ========================================================
    // WALLET BALANCE
    // ========================================================

    $wal_stmt = $conn->prepare(
        "SELECT balance
         FROM wallets
         WHERE user_id = ?
         LIMIT 1"
    );

    $wal_stmt->bind_param(
        "i",
        $user_id
    );

    $wal_stmt->execute();

    $wal_data = $wal_stmt
        ->get_result()
        ->fetch_assoc();

    $wallet_balance = (float)(
        $wal_data['balance'] ?? 0.00
    );


    // ========================================================
    // MONEY CURRENTLY INVESTED
    // ========================================================

    $inv_stmt = $conn->prepare(
        "SELECT COALESCE(SUM(amount), 0) AS total
         FROM loans
         WHERE lender_id = ?
         AND status = 'active'"
    );

    $inv_stmt->bind_param(
        "i",
        $user_id
    );

    $inv_stmt->execute();

    $money_invested = (float)(
        $inv_stmt
            ->get_result()
            ->fetch_assoc()['total'] ?? 0.00
    );


    // ========================================================
    // TOTAL PROFIT EARNED
    // ========================================================

    $prof_stmt = $conn->prepare(
        "SELECT COALESCE(
                    SUM(repayment_amount - amount),
                    0
                ) AS profit
         FROM loans
         WHERE lender_id = ?
         AND status = 'paid'"
    );

    $prof_stmt->bind_param(
        "i",
        $user_id
    );

    $prof_stmt->execute();

    $profit_earned = (float)(
        $prof_stmt
            ->get_result()
            ->fetch_assoc()['profit'] ?? 0.00
    );


    // ========================================================
    // AVAILABLE TO WITHDRAW
    // ========================================================

    $available_withdraw = max(
        0,
        $wallet_balance
    );


    // ========================================================
    // STUDENTS SPONSORED
    // ========================================================

    $stu_stmt = $conn->prepare(
        "SELECT COUNT(DISTINCT borrower_id) AS cnt
         FROM loans
         WHERE lender_id = ?"
    );

    $stu_stmt->bind_param(
        "i",
        $user_id
    );

    $stu_stmt->execute();

    $students_sponsored = (int)(
        $stu_stmt
            ->get_result()
            ->fetch_assoc()['cnt'] ?? 0
    );


    // ========================================================
    // LOAN RECOVERY RATE
    // ========================================================

    $tot_l_stmt = $conn->prepare(
        "SELECT
            COUNT(*) AS total,
            COALESCE(
                SUM(
                    CASE
                        WHEN status = 'paid'
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS repaid
         FROM loans
         WHERE lender_id = ?"
    );

    $tot_l_stmt->bind_param(
        "i",
        $user_id
    );

    $tot_l_stmt->execute();

    $loan_stats = $tot_l_stmt
        ->get_result()
        ->fetch_assoc();

    $total_loans = (int)(
        $loan_stats['total'] ?? 0
    );

    $repaid_loans = (int)(
        $loan_stats['repaid'] ?? 0
    );

    $recovery_rate = ($total_loans > 0)
        ? round(
            ($repaid_loans / $total_loans) * 100
        )
        : 0;


    // ========================================================
    // TOTAL CAPITAL
    // ========================================================

    $tot_cap_stmt = $conn->query(
        "SELECT COALESCE(SUM(balance), 0) AS total
         FROM wallets
         WHERE user_id IN (
             SELECT id
             FROM users
             WHERE role = 'lender'
         )"
    );

    $total_capital = (float)(
        $tot_cap_stmt
            ->fetch_assoc()['total'] ?? 0.00
    );


    // ========================================================
    // TOTAL ACTIVE LOANS AND AVERAGE LOAN SIZE
    // ========================================================

    $tot_loans_stmt = $conn->query(
        "SELECT
            COUNT(*) AS cnt,
            COALESCE(AVG(amount), 0) AS avg_amt
         FROM loans
         WHERE status = 'active'"
    );

    $tot_loans_data = $tot_loans_stmt
        ->fetch_assoc();

    $total_loans_count = (int)(
        $tot_loans_data['cnt'] ?? 0
    );

    $average_loan_size = (float)(
        $tot_loans_data['avg_amt'] ?? 0.00
    );


    // ========================================================
    // CAPITAL AVAILABLE
    // ========================================================

    $cap_avail_stmt = $conn->query(
        "SELECT COALESCE(SUM(w.balance), 0) AS total
         FROM wallets w
         INNER JOIN users u
             ON w.user_id = u.id
         WHERE u.role = 'lender'"
    );

    $capital_available = (float)(
        $cap_avail_stmt
            ->fetch_assoc()['total'] ?? 0.00
    );


    // ========================================================
    // ACTIVE / ONGOING LOANS
    // ========================================================

    $act_stmt = $conn->prepare(
        "SELECT
            l.id,
            u.fullname AS borrower,
            l.amount,
            l.repayment_amount,
            l.due_date,
            l.status
         FROM loans l
         INNER JOIN users u
             ON l.borrower_id = u.id
         WHERE l.lender_id = ?
         AND l.status IN (
             'pending',
             'approved',
             'active',
             'defaulted'
         )
         ORDER BY l.id DESC"
    );

    $act_stmt->bind_param(
        "i",
        $user_id
    );

    $act_stmt->execute();

    $active_loans = $act_stmt->get_result();


    // ========================================================
    // COMPLETED LOAN HISTORY
    // ========================================================

    $hist_stmt = $conn->prepare(
        "SELECT
            u.fullname AS borrower,
            l.amount,
            (l.repayment_amount - l.amount) AS profit,
            l.paid_date
         FROM loans l
         INNER JOIN users u
             ON l.borrower_id = u.id
         WHERE l.lender_id = ?
         AND l.status = 'paid'
         ORDER BY l.id DESC"
    );

    $hist_stmt->bind_param(
        "i",
        $user_id
    );

    $hist_stmt->execute();

    $loan_history = $hist_stmt->get_result();


    // ========================================================
    // TRANSACTION HISTORY
    // ========================================================
    //
    // transactions contains:
    // id
    // user_id
    // loan_id
    // type
    // amount
    // reference
    // created_at
    //
    // There is no status column.
    // ========================================================

    $tx_stmt = $conn->prepare(
        "SELECT
            reference,
            amount,
            type,
            created_at
         FROM transactions
         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 20"
    );

    $tx_stmt->bind_param(
        "i",
        $user_id
    );

    $tx_stmt->execute();

    $transactions = $tx_stmt->get_result();


    // ========================================================
    // NOTIFICATIONS
    // ========================================================

    $notif_stmt = $conn->prepare(
        "SELECT
            title,
            message,
            created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 5"
    );

    $notif_stmt->bind_param(
        "i",
        $user_id
    );

    $notif_stmt->execute();

    $notifications = $notif_stmt->get_result();

} catch (Throwable $e) {

    $error = 'Some dashboard information could not be loaded.';
}


// ============================================================
// PORTFOLIO PERFORMANCE CHART
// ============================================================

$monthly_labels = [];
$monthly_data = [];

try {

    $chart_stmt = $conn->prepare(
        "SELECT
            DATE_FORMAT(created_at, '%b') AS month_name,
            MONTH(created_at) AS month_num,
            COALESCE(
                SUM(
                    CASE
                        WHEN type IN (
                            'deposit',
                            'repayment'
                        )
                        THEN amount

                        WHEN type IN (
                            'withdrawal',
                            'loan_disbursement',
                            'debt_sweep'
                        )
                        THEN -amount

                        ELSE 0
                    END
                ),
                0
            ) AS monthly_net

         FROM transactions

         WHERE user_id = ?
         AND YEAR(created_at) = YEAR(CURRENT_DATE())

         GROUP BY
             month_num,
             month_name

         ORDER BY
             month_num ASC"
    );

    $chart_stmt->bind_param(
        "i",
        $user_id
    );

    $chart_stmt->execute();

    $chart_res = $chart_stmt->get_result();

    $running_total = 0.00;

    while ($row = $chart_res->fetch_assoc()) {

        $monthly_labels[] =
            $row['month_name'];

        $running_total += (float)(
            $row['monthly_net'] ?? 0
        );

        $monthly_data[] =
            round($running_total, 2);
    }

} catch (Throwable $e) {

    $monthly_labels = [];
    $monthly_data = [];
}


// ============================================================
// DEFAULT CHART DATA
// ============================================================

if (empty($monthly_labels)) {

    $monthly_labels = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun'
    ];

    $monthly_data = [
        0,
        0,
        0,
        0,
        0,
        0
    ];
}
?>
