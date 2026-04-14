<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);
$body = fin_json();

try {
    if ($method === 'GET') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $st = $dbh->prepare('SELECT * FROM fee_templates WHERE id = ? AND school_number = ?');
            $st->execute([$id, $school]);
            $t = $st->fetch(PDO::FETCH_ASSOC);
            if (!$t) {
                fin_send(['error' => 'Not found'], 404);
            }
            $it = $dbh->prepare('SELECT * FROM fee_template_items WHERE template_id = ? ORDER BY sort_order, id');
            $it->execute([$id]);
            $t['items'] = $it->fetchAll(PDO::FETCH_ASSOC);
            fin_send(['data' => $t]);
        }
        $st = $dbh->prepare('SELECT id, name, description, created_at FROM fee_templates WHERE school_number = ? ORDER BY name');
        $st->execute([$school]);
        fin_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($method === 'POST') {
        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            fin_send(['error' => 'name required'], 400);
        }
        $dbh->prepare('INSERT INTO fee_templates (school_number, name, description) VALUES (?,?,?)')->execute([
            $school,
            $name,
            trim((string) ($body['description'] ?? '')) ?: null
        ]);
        $tid = (int) $dbh->lastInsertId();
        $items = $body['items'] ?? [];
        if (is_array($items)) {
            $ins = $dbh->prepare(
                'INSERT INTO fee_template_items (template_id, label, amount, item_type, sort_order) VALUES (?,?,?,?,?)'
            );
            $o = 0;
            foreach ($items as $row) {
                $ins->execute([
                    $tid,
                    trim((string) ($row['label'] ?? 'Item')),
                    (float) ($row['amount'] ?? 0),
                    in_array(($row['itemType'] ?? 'mandatory'), ['optional'], true) ? 'optional' : 'mandatory',
                    $o++
                ]);
            }
        }
        fin_send(['message' => 'Created', 'data' => ['id' => $tid]], 201);
    }

    if ($method === 'PUT') {
        $id = (int) ($body['id'] ?? 0);
        if ($id < 1) {
            fin_send(['error' => 'id required'], 400);
        }
        $st = $dbh->prepare('SELECT id FROM fee_templates WHERE id = ? AND school_number = ?');
        $st->execute([$id, $school]);
        if (!$st->fetchColumn()) {
            fin_send(['error' => 'Not found'], 404);
        }
        if (isset($body['name'])) {
            $dbh->prepare('UPDATE fee_templates SET name = ?, description = ? WHERE id = ?')->execute([
                trim((string) $body['name']),
                trim((string) ($body['description'] ?? '')) ?: null,
                $id
            ]);
        }
        if (isset($body['items']) && is_array($body['items'])) {
            $dbh->prepare('DELETE FROM fee_template_items WHERE template_id = ?')->execute([$id]);
            $ins = $dbh->prepare(
                'INSERT INTO fee_template_items (template_id, label, amount, item_type, sort_order) VALUES (?,?,?,?,?)'
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
        $dbh->prepare('DELETE FROM fee_templates WHERE id = ? AND school_number = ?')->execute([$id, $school]);
        fin_send(['message' => 'Deleted']);
    }

    fin_send(['message' => 'Method not allowed'], 405);
} catch (Exception $e) {
    fin_send(['error' => $e->getMessage()], 500);
}
