<?php
/**
 * Super Admin — Subscription plans CRUD
 * GET    (no id)     — list plans
 * GET    ?id=        — single plan
 * POST   body        — create
 * PUT    body        — update
 * DELETE ?id= or body id
 */
include("../connect/header.php");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if (!isset($dbh) || $dbh === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'data' => '']);
    exit;
}

switch ($method) {
    case 'GET':
        $getId = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
        $getNum = isset($_GET['number']) ? trim((string) $_GET['number']) : '';
        if ($getId !== '') {
            getSubscriptionPlanById($dbh, $getId);
        } elseif ($getNum !== '') {
            getSubscriptionPlanById($dbh, $getNum);
        } else {
            listSubscriptionPlans($dbh);
        }
        break;
    case 'POST':
        createSubscriptionPlan($dbh, $input);
        break;
    case 'PUT':
        updateSubscriptionPlan($dbh, $input);
        break;
    case 'DELETE':
        $delId = null;
        if (is_array($input)) {
            if (isset($input['id']) && $input['id'] !== '') {
                $delId = $input['id'];
            } elseif (isset($input['number']) && $input['number'] !== '') {
                $delId = $input['number'];
            }
        }
        if ($delId === null && isset($_GET['id']) && $_GET['id'] !== '') {
            $delId = $_GET['id'];
        }
        if ($delId === null && isset($_GET['number']) && $_GET['number'] !== '') {
            $delId = $_GET['number'];
        }
        deleteSubscriptionPlan($dbh, $delId);
        break;
    default:
        echo json_encode(['message' => 'Invalid request method']);
        break;
}

function requireSuperAdminSession()
{
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Super admin access required', 'data' => '']);
        exit;
    }
}

/**
 * Unique plan UID (UUID v4 style) — stored in column `number`.
 */
function generatePlanUid($dbh)
{
    for ($attempt = 0; $attempt < 16; $attempt++) {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        $uid = vsprintf('%s%s-%s-%s-%s-%s%s', str_split(bin2hex($data), 4));
        $stmt = $dbh->prepare('SELECT id FROM subscription_plans WHERE `number` = :n LIMIT 1');
        $stmt->execute([':n' => $uid]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return $uid;
        }
    }
    return 'SP-' . bin2hex(random_bytes(12)) . '-' . (string) time();
}

function normalizeFeatures($features)
{
    if ($features === null || $features === '') {
        return [];
    }
    if (is_array($features)) {
        return array_values(array_filter(array_map('strval', $features)));
    }
    if (is_string($features)) {
        $decoded = json_decode($features, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }
        return array_values(array_filter(array_map('trim', explode(',', $features))));
    }
    return [];
}

function rowToPlan($row)
{
    $features = [];
    if (!empty($row['features_json'])) {
        $decoded = json_decode($row['features_json'], true);
        $features = is_array($decoded) ? $decoded : [];
    }
    return [
        'id' => (string) $row['id'],
        'number' => isset($row['number']) ? (string) $row['number'] : '',
        'code' => $row['code'] !== null ? $row['code'] : '',
        'name' => $row['name'],
        'priceMonthly' => (float) $row['price_monthly'],
        'features' => $features,
        'isActive' => (int) $row['is_active'] === 1,
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

function listSubscriptionPlans($dbh)
{
    requireSuperAdminSession();
    try {
        $stmt = $dbh->query(
            "SELECT id, `number`, code, name, price_monthly, features_json, is_active, created_at, updated_at
             FROM subscription_plans
             ORDER BY sort_order ASC, id ASC"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            $out[] = rowToPlan($row);
        }
        echo json_encode(['message' => 'OK', 'data' => $out]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred: ' . $e->getMessage(), 'data' => []]);
    }
}

function getSubscriptionPlanById($dbh, $identifier)
{
    requireSuperAdminSession();
    try {
        $row = fetchPlanRowByIdentifier($dbh, $identifier);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Plan not found', 'data' => '']);
            return;
        }
        echo json_encode(['message' => 'OK', 'data' => rowToPlan($row)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred: ' . $e->getMessage(), 'data' => '']);
    }
}

/**
 * Resolve by numeric primary key first, then by unique `number` UID.
 */
function fetchPlanRowByIdentifier($dbh, $identifier)
{
    $raw = trim((string) $identifier);
    if ($raw === '') {
        return false;
    }
    if (ctype_digit($raw)) {
        $stmt = $dbh->prepare(
            "SELECT id, `number`, code, name, price_monthly, features_json, is_active, created_at, updated_at
             FROM subscription_plans WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => (int) $raw]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }
    $stmt = $dbh->prepare(
        "SELECT id, `number`, code, name, price_monthly, features_json, is_active, created_at, updated_at
         FROM subscription_plans WHERE `number` = :num LIMIT 1"
    );
    $stmt->execute([':num' => $raw]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
}

function createSubscriptionPlan($dbh, $input)
{
    requireSuperAdminSession();
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body', 'data' => '']);
        return;
    }

    $name = isset($input['name']) ? trim($input['name']) : '';
    $priceRaw = $input['priceMonthly'] ?? $input['price_monthly'] ?? 0;
    $price = is_numeric($priceRaw) ? (float) $priceRaw : 0;
    $code = isset($input['code']) ? trim($input['code']) : null;
    if ($code === '') {
        $code = null;
    }
    $features = normalizeFeatures($input['features'] ?? $input['featuresText'] ?? []);
    $isActive = isset($input['isActive']) ? (int) (bool) $input['isActive'] : (isset($input['is_active']) ? (int) (bool) $input['is_active'] : 1);
    $sortOrder = isset($input['sort_order']) && is_numeric($input['sort_order']) ? (int) $input['sort_order'] : 0;

    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Plan name is required', 'data' => '']);
        return;
    }

    try {
        $planUid = generatePlanUid($dbh);
        $featuresJson = json_encode($features, JSON_UNESCAPED_UNICODE);
        $stmt = $dbh->prepare(
            "INSERT INTO subscription_plans (`number`, code, name, price_monthly, features_json, is_active, sort_order)
             VALUES (:number, :code, :name, :price, :features_json, :is_active, :sort_order)"
        );
        $stmt->execute([
            ':number' => $planUid,
            ':code' => $code,
            ':name' => $name,
            ':price' => $price,
            ':features_json' => $featuresJson,
            ':is_active' => $isActive ? 1 : 0,
            ':sort_order' => $sortOrder,
        ]);
        $newId = $dbh->lastInsertId();
        getSubscriptionPlanById($dbh, (string) $newId);
    } catch (PDOException $e) {
        if ((int) $e->getCode() === 23000) {
            http_response_code(409);
            echo json_encode(['error' => 'Duplicate code or constraint violation', 'data' => '']);
            return;
        }
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred: ' . $e->getMessage(), 'data' => '']);
    }
}

function updateSubscriptionPlan($dbh, $input)
{
    requireSuperAdminSession();
    if (!is_array($input) || (empty($input['id']) && empty($input['number']))) {
        http_response_code(400);
        echo json_encode(['error' => 'Plan id or number is required', 'data' => '']);
        return;
    }

    $idInput = trim((string) ($input['id'] ?? $input['number'] ?? ''));
    $existing = fetchPlanRowByIdentifier($dbh, $idInput);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['error' => 'Plan not found', 'data' => '']);
        return;
    }
    $pk = (int) $existing['id'];

    $name = isset($input['name']) ? trim($input['name']) : null;
    $priceRaw = $input['priceMonthly'] ?? $input['price_monthly'] ?? null;
    $code = array_key_exists('code', $input) ? trim($input['code']) : null;
    if ($code === '') {
        $code = null;
    }

    $fields = [];
    $params = [':id' => $pk];

    if ($name !== null) {
        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Plan name cannot be empty', 'data' => '']);
            return;
        }
        $fields[] = 'name = :name';
        $params[':name'] = $name;
    }
    if ($priceRaw !== null) {
        $fields[] = 'price_monthly = :price';
        $params[':price'] = is_numeric($priceRaw) ? (float) $priceRaw : 0;
    }
    if (array_key_exists('code', $input)) {
        $fields[] = 'code = :code';
        $params[':code'] = $code;
    }
    if (array_key_exists('features', $input) || array_key_exists('featuresText', $input)) {
        $features = normalizeFeatures($input['features'] ?? $input['featuresText'] ?? []);
        $fields[] = 'features_json = :features_json';
        $params[':features_json'] = json_encode($features, JSON_UNESCAPED_UNICODE);
    }
    if (array_key_exists('isActive', $input) || array_key_exists('is_active', $input)) {
        $v = $input['isActive'] ?? $input['is_active'];
        $fields[] = 'is_active = :is_active';
        $params[':is_active'] = (int) (bool) $v;
    }
    if (isset($input['sort_order']) && is_numeric($input['sort_order'])) {
        $fields[] = 'sort_order = :sort_order';
        $params[':sort_order'] = (int) $input['sort_order'];
    }

    if (count($fields) === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update', 'data' => '']);
        return;
    }

    try {
        $sql = 'UPDATE subscription_plans SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() === 0) {
            $check = $dbh->prepare('SELECT id FROM subscription_plans WHERE id = :id');
            $check->execute([':id' => $pk]);
            if (!$check->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Plan not found', 'data' => '']);
                return;
            }
        }
        $updated = findPlanRowByPk($dbh, $pk);
        if (!$updated) {
            http_response_code(404);
            echo json_encode(['error' => 'Plan not found', 'data' => '']);
            return;
        }
        echo json_encode(['message' => 'Subscription plan updated', 'data' => rowToPlan($updated)]);
    } catch (PDOException $e) {
        if ((int) $e->getCode() === 23000) {
            http_response_code(409);
            echo json_encode(['error' => 'Duplicate code or constraint violation', 'data' => '']);
            return;
        }
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred: ' . $e->getMessage(), 'data' => '']);
    }
}

function findPlanRowByPk($dbh, $pk)
{
    $stmt = $dbh->prepare(
        "SELECT id, `number`, code, name, price_monthly, features_json, is_active, created_at, updated_at
         FROM subscription_plans WHERE id = :id LIMIT 1"
    );
    $stmt->execute([':id' => (int) $pk]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function deleteSubscriptionPlan($dbh, $id)
{
    requireSuperAdminSession();
    if ($id === null || $id === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Plan id or number is required', 'data' => '']);
        return;
    }
    try {
        $row = fetchPlanRowByIdentifier($dbh, trim((string) $id));
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Plan not found', 'data' => '']);
            return;
        }
        $stmt = $dbh->prepare('DELETE FROM subscription_plans WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int) $row['id']]);
        echo json_encode(['message' => 'Subscription plan deleted', 'data' => ['id' => (string) $row['id'], 'number' => (string) $row['number']]]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred: ' . $e->getMessage(), 'data' => '']);
    }
}
