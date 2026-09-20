 <?php
header("Content-Type: application/json");

require_once("../Includes_dynamics/dataB.php");

try {

    $sql = "
        SELECT
            verifications.id AS verification_id,
            users.id,
            users.fullname,
            users.matric_no,

            wallets.trust_tier,
            wallets.crf_status,

            verifications.document_path,
            verifications.submitted_at,
            verifications.verification_type

        FROM users

        INNER JOIN wallets
            ON users.id = wallets.user_id

        INNER JOIN verifications
            ON users.id = verifications.user_id

        WHERE verifications.status = 'pending'

        ORDER BY verifications.submitted_at ASC
    ";

    $result = $conn->query($sql);

    $students = [];

    while ($row = $result->fetch_assoc()) {

        // Current Tier
        $currentTier = "Tier " . $row["trust_tier"];

        // Requested Tier
        if ($row["verification_type"] == "student_id") {

            $requestedTier = "Tier 2";

        } else {

            $requestedTier = "Tier 3";

        }

        $students[] = [

            "verification_id" => (int) $row["verification_id"],

            "id" => $row["id"],

            "name" => $row["fullname"],

            "matric_no" => $row["matric_no"],

            "current_tier" => $currentTier,

            "requested_tier" => $requestedTier,

            "document_path" => $row["document_path"],

            "submitted_at" => date(
                "d M Y",
                strtotime($row["submitted_at"])
            ),

            "crf_status" => $row["crf_status"]

        ];

    }

    echo json_encode([

        "success" => true,

        "students" => $students

    ]);

} catch (Exception $e) {

    echo json_encode([

        "success" => false,

        "students" => [],

        "message" => $e->getMessage()

    ]);

}
?>