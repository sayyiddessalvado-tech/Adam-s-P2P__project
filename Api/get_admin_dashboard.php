 <?php
header("Content-Type: application/json");
require_once("../Includes_dynamics/dataB.php");

try {

    /* =====================================================
       DASHBOARD METRICS
    ====================================================== */

    // Current Investment Pool
    $pool = $conn->query("
        SELECT IFNULL(SUM(balance),0) AS total_pool
        FROM wallets
    ");
    $poolBalance = $pool->fetch_assoc()['total_pool'];

    // Money currently on loan
    $loan = $conn->query("
        SELECT IFNULL(SUM(amount),0) AS total_loans
        FROM loans
        WHERE status='active'
    ");
    $loanVolume = $loan->fetch_assoc()['total_loans'];

    // Defaulted borrowers
    $defaulted = $conn->query("
        SELECT COUNT(*) total
        FROM wallets
        WHERE is_defaulted=1
    ");
    $defaultCount = $defaulted->fetch_assoc()['total'];

    // Total borrowers
    $users = $conn->query("
        SELECT COUNT(*) total
        FROM wallets
    ");
    $totalUsers = $users->fetch_assoc()['total'];

    $defaultRate = ($totalUsers > 0)
        ? round(($defaultCount / $totalUsers) * 100,2)
        : 0;

    // Pending Verifications
    $pending = $conn->query("
        SELECT COUNT(*) total
        FROM verifications
        WHERE status='pending'
    ");
    $pendingCount = $pending->fetch_assoc()['total'];



    /* =====================================================
       RECENT LOANS
    ====================================================== */

    $loanResult = $conn->query("
        SELECT

        loans.id,
        borrower.fullname AS borrower,
        lender.fullname AS lender,
        loans.amount,
        loans.due_date,
        loans.status

        FROM loans

        LEFT JOIN users borrower
        ON loans.borrower_id=borrower.id

        LEFT JOIN users lender
        ON loans.lender_id=lender.id

        ORDER BY loans.created_at DESC

        LIMIT 10
    ");

    $recentLoans = [];

    while($row=$loanResult->fetch_assoc()){

        $recentLoans[]=$row;

    }



    /* =====================================================
       RECENT TRANSACTIONS
    ====================================================== */

    $transactionResult = $conn->query("

        SELECT

        transactions.id,

        users.fullname AS user,

        transactions.type,

        transactions.amount,

        transactions.created_at

        FROM transactions

        INNER JOIN users

        ON transactions.user_id=users.id

        ORDER BY transactions.created_at DESC

        LIMIT 10

    ");

    $transactions=[];

    while($row=$transactionResult->fetch_assoc()){

        $row["status"]="Completed";

        $transactions[]=$row;

    }



    /* =====================================================
       HIGH RISK BORROWERS
    ====================================================== */

    $riskResult=$conn->query("

        SELECT

        users.fullname,

        wallets.credit_score,

        wallets.debt,

        wallets.is_defaulted

        FROM wallets

        INNER JOIN users

        ON wallets.user_id=users.id

        WHERE wallets.credit_score<=40

        OR wallets.is_defaulted=1

        ORDER BY wallets.credit_score ASC

        LIMIT 10

    ");

    $highRisk=[];

    while($row=$riskResult->fetch_assoc()){

        $highRisk[]=[

            "name"=>$row["fullname"],

            "credit_score"=>$row["credit_score"],

            "outstanding_loan"=>$row["debt"],

            "status"=>$row["is_defaulted"] ?
                "Defaulted" :
                "High Risk"

        ];

    }



    /* =====================================================
       SEND EVERYTHING
    ====================================================== */

    echo json_encode([

        "success"=>true,

        "metrics"=>[

            "total_liquidity"=>$poolBalance,

            "active_loan_volume"=>$loanVolume,

            "default_ratio"=>$defaultRate,

            "pending_verifications_count"=>$pendingCount

        ],

        "recent_loans"=>$recentLoans,

        "transactions"=>$transactions,

        "high_risk_users"=>$highRisk

    ]);

}
catch(Exception $e){

    echo json_encode([

        "success"=>false,

        "message"=>$e->getMessage()

    ]);

}
?>