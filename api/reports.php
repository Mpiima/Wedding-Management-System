<?php
/**
 * Reports API: GET only. Total contributions (pledge payments + contributions), total expenditures, balance.
 */
include("connect/header.php");
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$userId = (int) $_SESSION['user_id'];

try {
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $totalPledgePayments = 0;
    $s = $dbh->prepare("
        SELECT COALESCE(SUM(pp.amount), 0) AS total
        FROM pledge_payments pp
        INNER JOIN pledges p ON p.id = pp.pledge_id AND p.user_id = :uid
    ");
    $s->bindValue(':uid', $userId, PDO::PARAM_INT);
    $s->execute();
    $row = $s->fetch(PDO::FETCH_OBJ);
    if ($row) $totalPledgePayments = (float) $row->total;

    $totalContributions = 0;
    $s = $dbh->prepare("
        SELECT COALESCE(SUM(c.amount), 0) AS total
        FROM contributions c
        INNER JOIN members m ON m.id = c.member_id
        INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid
    ");
    $s->bindValue(':uid', $userId, PDO::PARAM_INT);
    $s->execute();
    $row = $s->fetch(PDO::FETCH_OBJ);
    if ($row) $totalContributions = (float) $row->total;

    $totalExpenditures = 0;
    $s = $dbh->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM expenditures WHERE user_id = :uid");
    $s->bindValue(':uid', $userId, PDO::PARAM_INT);
    $s->execute();
    $row = $s->fetch(PDO::FETCH_OBJ);
    if ($row) $totalExpenditures = (float) $row->total;

    $totalReceived = $totalPledgePayments + $totalContributions;
    $balance = $totalReceived - $totalExpenditures;

    $totalPledged = 0;
    $s = $dbh->prepare("
        SELECT COALESCE(SUM(amount_pledged), 0) AS total FROM pledges p
        INNER JOIN members m ON m.id = p.member_id
        INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid
    ");
    $s->bindValue(':uid', $userId, PDO::PARAM_INT);
    $s->execute();
    $row = $s->fetch(PDO::FETCH_OBJ);
    if ($row) $totalPledged = (float) $row->total;

    echo json_encode([
        'message' => 'OK',
        'data' => [
            'total_pledge_payments' => $totalPledgePayments,
            'total_contributions' => $totalContributions,
            'total_received' => $totalReceived,
            'total_pledged' => $totalPledged,
            'total_expenditures' => $totalExpenditures,
            'balance' => $balance
        ]
    ]);
} catch (PDOException $e) {
    error_log('Reports: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
