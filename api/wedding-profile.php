<?php
/**
 * Wedding Profile API: create, retrieve, update (by authenticated user).
 * GET  = retrieve current user's wedding profile (404 if none)
 * POST = create wedding profile (409 if already exists)
 * PUT  = update wedding profile (create if missing, upsert-style)
 */
include("connect/header.php");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$scopeUserId = isset($scopeUserId) ? (int) $scopeUserId : $userId;

switch ($method) {
    case 'GET':
        getWeddingProfile($dbh, $scopeUserId);
        break;
    case 'POST':
        if ($userId !== $scopeUserId) {
            http_response_code(403);
            echo json_encode(['error' => 'Only the wedding owner can create the wedding profile']);
            exit;
        }
        createWeddingProfile($dbh, $scopeUserId, $input);
        break;
    case 'PUT':
        if ($userId !== $scopeUserId) {
            http_response_code(403);
            echo json_encode(['error' => 'Only the wedding owner can update the wedding profile']);
            exit;
        }
        updateWeddingProfile($dbh, $scopeUserId, $input);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getWeddingProfile($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("
            SELECT id, user_id, bride_name, groom_name, wedding_date, venue_name, venue_address, bride_photo, groom_photo, couple_photo, created_at, updated_at
            FROM wedding_profiles WHERE user_id = :user_id LIMIT 1
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['message' => 'No wedding profile found', 'data' => null]);
            return;
        }
        echo json_encode(['message' => 'OK', 'data' => $row]);
    } catch (PDOException $e) {
        error_log('Wedding profile get error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function createWeddingProfile($dbh, $userId, $input) {
    $brideName    = isset($input['bride_name'])    ? trim((string) $input['bride_name'])    : null;
    $groomName    = isset($input['groom_name'])    ? trim((string) $input['groom_name'])    : null;
    $weddingDate  = isset($input['wedding_date'])  ? trim((string) $input['wedding_date'])  : null;
    $venueName    = isset($input['venue_name'])    ? trim((string) $input['venue_name'])    : null;
    $venueAddress = isset($input['venue_address']) ? trim((string) $input['venue_address']) : null;

    if ($weddingDate !== null && $weddingDate !== '') {
        $d = date_create($weddingDate);
        if (!$d) $weddingDate = null; else $weddingDate = $d->format('Y-m-d');
    }

    try {
        $exists = $dbh->prepare("SELECT 1 FROM wedding_profiles WHERE user_id = :user_id LIMIT 1");
        $exists->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $exists->execute();
        if ($exists->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Wedding profile already exists. Use PUT to update.']);
            return;
        }

        $stmt = $dbh->prepare("
            INSERT INTO wedding_profiles (user_id, bride_name, groom_name, wedding_date, venue_name, venue_address)
            VALUES (:user_id, :bride_name, :groom_name, :wedding_date, :venue_name, :venue_address)
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':bride_name', $brideName ?: null, PDO::PARAM_STR);
        $stmt->bindValue(':groom_name', $groomName ?: null, PDO::PARAM_STR);
        $stmt->bindValue(':wedding_date', $weddingDate ?: null, PDO::PARAM_STR);
        $stmt->bindValue(':venue_name', $venueName ?: null, PDO::PARAM_STR);
        $stmt->bindValue(':venue_address', $venueAddress ?: null, PDO::PARAM_STR);
        $stmt->execute();

        $id = (int) $dbh->lastInsertId();
        $fetch = $dbh->prepare("SELECT id, user_id, bride_name, groom_name, wedding_date, venue_name, venue_address, bride_photo, groom_photo, couple_photo, created_at, updated_at FROM wedding_profiles WHERE id = :id LIMIT 1");
        $fetch->bindValue(':id', $id, PDO::PARAM_INT);
        $fetch->execute();
        $row = $fetch->fetch(PDO::FETCH_OBJ);
        http_response_code(201);
        echo json_encode(['message' => 'Wedding profile created', 'data' => $row]);
    } catch (PDOException $e) {
        error_log('Wedding profile create error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function updateWeddingProfile($dbh, $userId, $input) {
    $brideName    = isset($input['bride_name'])    ? trim((string) $input['bride_name'])    : null;
    $groomName    = isset($input['groom_name'])    ? trim((string) $input['groom_name'])    : null;
    $weddingDate  = isset($input['wedding_date'])  ? trim((string) $input['wedding_date'])  : null;
    $venueName    = isset($input['venue_name'])    ? trim((string) $input['venue_name'])    : null;
    $venueAddress = isset($input['venue_address']) ? trim((string) $input['venue_address']) : null;
    $bridePhoto   = array_key_exists('bride_photo', $input)   ? trim((string) $input['bride_photo'])   : null;
    $groomPhoto   = array_key_exists('groom_photo', $input)   ? trim((string) $input['groom_photo'])   : null;
    $couplePhoto  = array_key_exists('couple_photo', $input)   ? trim((string) $input['couple_photo'])  : null;

    if ($weddingDate !== null && $weddingDate !== '') {
        $d = date_create($weddingDate);
        if (!$d) $weddingDate = null; else $weddingDate = $d->format('Y-m-d');
    }

    try {
        $stmt = $dbh->prepare("SELECT id FROM wedding_profiles WHERE user_id = :user_id LIMIT 1");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_OBJ);

        if ($existing) {
            $upd = $dbh->prepare("
                UPDATE wedding_profiles
                SET bride_name = COALESCE(:bride_name, bride_name),
                    groom_name = COALESCE(:groom_name, groom_name),
                    wedding_date = COALESCE(:wedding_date, wedding_date),
                    venue_name = COALESCE(:venue_name, venue_name),
                    venue_address = COALESCE(:venue_address, venue_address),
                    bride_photo = COALESCE(:bride_photo, bride_photo),
                    groom_photo = COALESCE(:groom_photo, groom_photo),
                    couple_photo = COALESCE(:couple_photo, couple_photo)
                WHERE user_id = :user_id
            ");
            $upd->bindValue(':bride_name', $brideName !== null && $brideName !== '' ? $brideName : null, PDO::PARAM_STR);
            $upd->bindValue(':groom_name', $groomName !== null && $groomName !== '' ? $groomName : null, PDO::PARAM_STR);
            $upd->bindValue(':wedding_date', $weddingDate !== null && $weddingDate !== '' ? $weddingDate : null, PDO::PARAM_STR);
            $upd->bindValue(':venue_name', $venueName !== null && $venueName !== '' ? $venueName : null, PDO::PARAM_STR);
            $upd->bindValue(':venue_address', $venueAddress !== null && $venueAddress !== '' ? $venueAddress : null, PDO::PARAM_STR);
            $upd->bindValue(':bride_photo', $bridePhoto !== null && $bridePhoto !== '' ? $bridePhoto : null, PDO::PARAM_STR);
            $upd->bindValue(':groom_photo', $groomPhoto !== null && $groomPhoto !== '' ? $groomPhoto : null, PDO::PARAM_STR);
            $upd->bindValue(':couple_photo', $couplePhoto !== null && $couplePhoto !== '' ? $couplePhoto : null, PDO::PARAM_STR);
            $upd->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $upd->execute();
        } else {
            $ins = $dbh->prepare("
                INSERT INTO wedding_profiles (user_id, bride_name, groom_name, wedding_date, venue_name, venue_address, bride_photo, groom_photo, couple_photo)
                VALUES (:user_id, :bride_name, :groom_name, :wedding_date, :venue_name, :venue_address, :bride_photo, :groom_photo, :couple_photo)
            ");
            $ins->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $ins->bindValue(':bride_name', $brideName ?: null, PDO::PARAM_STR);
            $ins->bindValue(':groom_name', $groomName ?: null, PDO::PARAM_STR);
            $ins->bindValue(':wedding_date', $weddingDate ?: null, PDO::PARAM_STR);
            $ins->bindValue(':venue_name', $venueName ?: null, PDO::PARAM_STR);
            $ins->bindValue(':venue_address', $venueAddress ?: null, PDO::PARAM_STR);
            $ins->bindValue(':bride_photo', $bridePhoto ?: null, PDO::PARAM_STR);
            $ins->bindValue(':groom_photo', $groomPhoto ?: null, PDO::PARAM_STR);
            $ins->bindValue(':couple_photo', $couplePhoto ?: null, PDO::PARAM_STR);
            $ins->execute();
        }

        $row = $dbh->prepare("SELECT id, user_id, bride_name, groom_name, wedding_date, venue_name, venue_address, bride_photo, groom_photo, couple_photo, created_at, updated_at FROM wedding_profiles WHERE user_id = :user_id LIMIT 1");
        $row->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $row->execute();
        $profile = $row->fetch(PDO::FETCH_OBJ);
        echo json_encode(['message' => 'Wedding profile saved', 'data' => $profile]);
    } catch (PDOException $e) {
        error_log('Wedding profile update error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}
