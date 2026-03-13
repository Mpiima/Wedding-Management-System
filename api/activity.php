<?php
/**
 * Activity feed API: recent meetings, contributions, pledge payments, expenditures, new members.
 * GET only. Returns merged list sorted by date descending.
 */
include("connect/header.php");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$userId = (int) $_SESSION['user_id'];
$scopeUserId = isset($scopeUserId) ? (int) $scopeUserId : $userId;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $activities = [];

    $stmt = $dbh->prepare("SELECT id, title, meeting_date, created_at FROM meetings WHERE user_id = :uid ORDER BY meeting_date DESC, created_at DESC LIMIT 10");
    $stmt->bindValue(':uid', $scopeUserId, PDO::PARAM_INT);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
        $activities[] = [
            'type' => 'meeting',
            'id' => (int) $row->id,
            'title' => 'Meeting: ' . $row->title,
            'date' => $row->meeting_date ?: $row->created_at,
            'created_at' => $row->created_at
        ];
    }

    $stmt = $dbh->prepare("
        SELECT c.id, c.amount, c.contribution_date, c.created_at, m.name AS member_name
        FROM contributions c
        INNER JOIN members m ON m.id = c.member_id
        INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid
        ORDER BY c.created_at DESC LIMIT 10
    ");
    $stmt->bindValue(':uid', $scopeUserId, PDO::PARAM_INT);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
        $activities[] = [
            'type' => 'contribution',
            'id' => (int) $row->id,
            'title' => 'Contribution: ' . $row->member_name . ' — ' . number_format((float) $row->amount) . ' UGX',
            'date' => $row->contribution_date ?: $row->created_at,
            'created_at' => $row->created_at
        ];
    }

    $stmt = $dbh->prepare("
        SELECT pp.id, pp.amount, pp.paid_at, pp.created_at, m.name AS member_name
        FROM pledge_payments pp
        INNER JOIN pledges p ON p.id = pp.pledge_id AND p.user_id = :uid
        INNER JOIN members m ON m.id = p.member_id
        ORDER BY pp.paid_at DESC, pp.created_at DESC LIMIT 10
    ");
    $stmt->bindValue(':uid', $scopeUserId, PDO::PARAM_INT);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
        $activities[] = [
            'type' => 'pledge',
            'id' => (int) $row->id,
            'title' => 'Pledge payment: ' . $row->member_name . ' — ' . number_format((float) $row->amount) . ' UGX',
            'date' => $row->paid_at ?: $row->created_at,
            'created_at' => $row->created_at
        ];
    }

    $stmt = $dbh->prepare("SELECT id, item, amount, expenditure_date, created_at FROM expenditures WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10");
    $stmt->bindValue(':uid', $scopeUserId, PDO::PARAM_INT);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
        $activities[] = [
            'type' => 'expenditure',
            'id' => (int) $row->id,
            'title' => 'Expenditure: ' . $row->item . ' — ' . number_format((float) $row->amount) . ' UGX',
            'date' => $row->expenditure_date ?: $row->created_at,
            'created_at' => $row->created_at
        ];
    }

    $stmt = $dbh->prepare("
        SELECT m.id, m.name, m.created_at
        FROM members m
        INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid
        ORDER BY m.created_at DESC LIMIT 10
    ");
    $stmt->bindValue(':uid', $scopeUserId, PDO::PARAM_INT);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
        $activities[] = [
            'type' => 'member',
            'id' => (int) $row->id,
            'title' => 'New member: ' . $row->name,
            'date' => $row->created_at,
            'created_at' => $row->created_at
        ];
    }

    usort($activities, function ($a, $b) {
        $t = strcmp($b['date'] ?? $b['created_at'], $a['date'] ?? $a['created_at']);
        if ($t !== 0) return $t;
        return strcmp($b['created_at'], $a['created_at']);
    });
    $activities = array_slice($activities, 0, 25);

    echo json_encode(['message' => 'OK', 'data' => $activities]);
} catch (PDOException $e) {
    error_log('Activity: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
