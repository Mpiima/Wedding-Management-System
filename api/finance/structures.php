<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);
$body = fin_json();

try {
    if ($method === 'GET') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $st = $dbh->prepare('SELECT * FROM fee_structures WHERE id = ? AND school_number = ?');
            $st->execute([$id, $school]);
            $t = $st->fetch(PDO::FETCH_ASSOC);
            if (!$t) {
                fin_send(['error' => 'Not found'], 404);
            }
            $it = $dbh->prepare('SELECT * FROM fee_structure_items WHERE fee_structure_id = ? ORDER BY sort_order, id');
            $it->execute([$id]);
            $t['items'] = $it->fetchAll(PDO::FETCH_ASSOC);
            $cl = $dbh->prepare('SELECT class_id FROM fee_structure_classes WHERE fee_structure_id = ?');
            $cl->execute([$id]);
            $t['classIds'] = array_map('intval', $cl->fetchAll(PDO::FETCH_COLUMN));
            fin_send(['data' => $t]);
        }
        $sql = 'SELECT fs.*,
                (SELECT GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ", ") FROM fee_structure_classes fsc
                 INNER JOIN classes c ON c.id = fsc.class_id WHERE fsc.fee_structure_id = fs.id) AS class_names
                FROM fee_structures fs WHERE fs.school_number = ? ORDER BY fs.id DESC';
        $st = $dbh->prepare($sql);
        $st->execute([$school]);
        fin_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($method === 'POST') {
        $name = trim((string) ($body['name'] ?? ''));
        $yearId = (int) ($body['academicYearId'] ?? 0);
        $periodId = (int) ($body['studyPeriodId'] ?? 0);
        $classIds = $body['classIds'] ?? [];
        if ($name === '' || $yearId < 1 || $periodId < 1 || !is_array($classIds) || !$classIds) {
            fin_send(['error' => 'name, academicYearId, studyPeriodId, classIds required'], 400);
        }
        if (!erp_year_owned($dbh, $school, $yearId) || !erp_period_owned($dbh, $school, $periodId)) {
            fin_send(['error' => 'Invalid year or period'], 400);
        }
        foreach ($classIds as $cid) {
            if (!erp_class_owned($dbh, $school, (int) $cid)) {
                fin_send(['error' => 'Invalid class'], 400);
            }
        }
        $tplId = (int) ($body['templateId'] ?? 0) ?: null;
        if ($tplId) {
            $st = $dbh->prepare('SELECT id FROM fee_templates WHERE id = ? AND school_number = ?');
            $st->execute([$tplId, $school]);
            if (!$st->fetchColumn()) {
                $tplId = null;
            }
        }
        $dbh->beginTransaction();
        $dbh->prepare(
            'INSERT INTO fee_structures (school_number, name, academic_year_id, study_period_id, template_id) VALUES (?,?,?,?,?)'
        )->execute([$school, $name, $yearId, $periodId, $tplId]);
        $fsId = (int) $dbh->lastInsertId();
        $link = $dbh->prepare('INSERT IGNORE INTO fee_structure_classes (fee_structure_id, class_id) VALUES (?,?)');
        foreach ($classIds as $cid) {
            $link->execute([$fsId, (int) $cid]);
        }
        $items = $body['items'] ?? [];
        if ($tplId && (!is_array($items) || !count($items))) {
            $st = $dbh->prepare('SELECT label, amount, item_type, sort_order FROM fee_template_items WHERE template_id = ? ORDER BY sort_order');
            $st->execute([$tplId]);
            $items = $st->fetchAll(PDO::FETCH_ASSOC);
        }
        $ins = $dbh->prepare(
            'INSERT INTO fee_structure_items (fee_structure_id, label, amount, item_type, sort_order) VALUES (?,?,?,?,?)'
        );
        $o = 0;
        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? 'Item'));
            $amt = (float) ($row['amount'] ?? 0);
            $it = (($row['item_type'] ?? $row['itemType'] ?? '') === 'optional') ? 'optional' : 'mandatory';
            $ins->execute([$fsId, $label, $amt, $it, $o++]);
        }
        $dbh->commit();
        fin_send(['message' => 'Created', 'data' => ['id' => $fsId]], 201);
    }

    if ($method === 'PUT') {
        $id = (int) ($body['id'] ?? 0);
        if ($id < 1) {
            fin_send(['error' => 'id required'], 400);
        }
        $st = $dbh->prepare('SELECT id FROM fee_structures WHERE id = ? AND school_number = ?');
        $st->execute([$id, $school]);
        if (!$st->fetchColumn()) {
            fin_send(['error' => 'Not found'], 404);
        }
        if (isset($body['name'])) {
            $dbh->prepare('UPDATE fee_structures SET name = ? WHERE id = ?')->execute([trim((string) $body['name']), $id]);
        }
        if (isset($body['classIds']) && is_array($body['classIds'])) {
            $dbh->prepare('DELETE FROM fee_structure_classes WHERE fee_structure_id = ?')->execute([$id]);
            $link = $dbh->prepare('INSERT INTO fee_structure_classes (fee_structure_id, class_id) VALUES (?,?)');
            foreach ($body['classIds'] as $cid) {
                if (erp_class_owned($dbh, $school, (int) $cid)) {
                    $link->execute([$id, (int) $cid]);
                }
            }
        }
        if (isset($body['items']) && is_array($body['items'])) {
            $dbh->prepare('DELETE FROM fee_structure_items WHERE fee_structure_id = ?')->execute([$id]);
            $ins = $dbh->prepare(
                'INSERT INTO fee_structure_items (fee_structure_id, label, amount, item_type, sort_order) VALUES (?,?,?,?,?)'
            );
            $o = 0;
            foreach ($body['items'] as $row) {
                $ins->execute([
                    $id,
                    trim((string) ($row['label'] ?? 'Item')),
                    (float) ($row['amount'] ?? 0),
                    ($row['itemType'] ?? '') === 'optional' ? 'optional' : 'mandatory',
                    $o++
                ]);
            }
        }
        fin_send(['message' => 'Updated']);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? $body['id'] ?? 0);
        if ($id < 1) {
            fin_send(['error' => 'id required'], 400);
        }
        $dbh->prepare('DELETE FROM fee_structures WHERE id = ? AND school_number = ?')->execute([$id, $school]);
        fin_send(['message' => 'Deleted']);
    }

    fin_send(['message' => 'Method not allowed'], 405);
} catch (Exception $e) {
    fin_send(['error' => $e->getMessage()], 500);
}
