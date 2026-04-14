<?php

require_once __DIR__ . '/_init.php';

$school = adm_school_id();
$body = adm_json();

try {
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id > 0) {
            $row = adm_applicant_owned($dbh, $school, $id);
            if (!$row) {
                adm_send(['error' => 'Not found'], 404);
            }
            $st = $dbh->prepare('SELECT admission_number FROM students WHERE id = ? LIMIT 1');
            $st->execute([(int) ($row['student_id'] ?? 0)]);
            $row['admission_number'] = $st->fetchColumn() ?: null;

            $st = $dbh->prepare(
                'SELECT id, from_status, to_status, actor, note, created_at FROM applicant_status_log WHERE applicant_id = ? ORDER BY id ASC'
            );
            $st->execute([$id]);
            $row['status_history'] = $st->fetchAll(PDO::FETCH_ASSOC);

            $st = $dbh->prepare(
                'SELECT id, body, created_by, created_at FROM applicant_comments WHERE applicant_id = ? ORDER BY id DESC'
            );
            $st->execute([$id]);
            $row['comments'] = $st->fetchAll(PDO::FETCH_ASSOC);

            $st = $dbh->prepare(
                'SELECT id, doc_type, original_name, uploaded_at FROM applicant_documents WHERE applicant_id = ? ORDER BY id DESC'
            );
            $st->execute([$id]);
            $row['documents'] = $st->fetchAll(PDO::FETCH_ASSOC);

            adm_send(['data' => $row]);
        }

        $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $applicantsOnly = isset($_GET['applicantsOnly']) ? (int) $_GET['applicantsOnly'] : 0;

        $sql = 'SELECT a.id, a.application_number, a.pipeline_status, a.first_name, a.last_name, a.gender, a.dob,
                a.parent_name, a.parent_phone, a.applying_class_id, a.ready_for_decision, a.flagged_issues,
                a.student_id, a.interview_at, a.interview_status, a.waitlist_position, a.updated_at, a.created_at,
                (SELECT COUNT(*) FROM applicant_documents d WHERE d.applicant_id = a.id) AS doc_count,
                c.name AS applying_class_name
                FROM applicants a
                LEFT JOIN classes c ON c.id = a.applying_class_id
                WHERE a.school_number = ?';
        $params = [$school];
        if ($status !== '' && $status !== 'all') {
            $sql .= ' AND a.pipeline_status = ?';
            $params[] = $status;
        }
        if ($applicantsOnly === 1) {
            $sql .= ' AND a.pipeline_status <> \'enrolled\'';
        }
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql .= ' AND (CONCAT(a.first_name, \' \', a.last_name) LIKE ? OR a.parent_phone LIKE ? OR a.parent_name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= ' ORDER BY a.updated_at DESC';
        $st = $dbh->prepare($sql);
        $st->execute($params);
        adm_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($method === 'POST') {
        $fn = trim((string) ($body['firstName'] ?? ''));
        $ln = trim((string) ($body['lastName'] ?? ''));
        $phone = trim((string) ($body['parentPhone'] ?? ''));
        if ($fn === '' || $ln === '' || $phone === '') {
            adm_send(['error' => 'firstName, lastName, and parentPhone are required'], 400);
        }

        $dup = $dbh->prepare(
            'SELECT id FROM applicants WHERE school_number = ? AND parent_phone = ? AND LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) AND pipeline_status NOT IN (\'rejected\',\'enrolled\') LIMIT 1'
        );
        $dup->execute([$school, $phone, $fn, $ln]);
        if ($dup->fetchColumn()) {
            adm_send(['error' => 'Possible duplicate application for this name and phone'], 409);
        }

        $stub = 'T' . bin2hex(random_bytes(3));
        $st = $dbh->prepare(
            'INSERT INTO applicants (school_number, application_number, first_name, last_name, gender, dob,
             parent_name, parent_phone, parent_email, applying_level_id, applying_class_id,
             previous_school, address, medical_info, applicant_notes, pipeline_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'application\')'
        );
        $st->execute([
            $school,
            $stub,
            $fn,
            $ln,
            trim((string) ($body['gender'] ?? '')),
            trim((string) ($body['dob'] ?? '')) ?: null,
            trim((string) ($body['parentName'] ?? '')),
            $phone,
            trim((string) ($body['parentEmail'] ?? '')) ?: null,
            (int) ($body['applyingLevelId'] ?? 0) ?: null,
            (int) ($body['applyingClassId'] ?? 0) ?: null,
            trim((string) ($body['previousSchool'] ?? '')) ?: null,
            trim((string) ($body['address'] ?? '')) ?: null,
            trim((string) ($body['medicalInfo'] ?? '')) ?: null,
            trim((string) ($body['applicantNotes'] ?? '')) ?: null
        ]);
        $newId = (int) $dbh->lastInsertId();
        adm_fix_application_number($dbh, $newId);
        adm_log_status($dbh, $newId, null, 'application', 'Application submitted');
        adm_notify($dbh, $school, $newId, 'application_received', 'Application received.');

        adm_send(['message' => 'Created', 'data' => ['id' => $newId]], 201);
    }

    if ($method === 'PUT') {
        $id = (int) ($body['id'] ?? 0);
        $row = adm_applicant_owned($dbh, $school, $id);
        if (!$row) {
            adm_send(['error' => 'Not found'], 404);
        }

        $sets = [];
        $vals = [];
        $map = [
            'first_name' => ['firstName', null],
            'last_name' => ['lastName', null],
            'gender' => ['gender', null],
            'dob' => ['dob', 'emptyNull'],
            'parent_name' => ['parentName', null],
            'parent_phone' => ['parentPhone', null],
            'parent_email' => ['parentEmail', 'emptyNull'],
            'applying_level_id' => ['applyingLevelId', 'intNull'],
            'applying_class_id' => ['applyingClassId', 'intNull'],
            'previous_school' => ['previousSchool', 'emptyNull'],
            'address' => ['address', 'emptyNull'],
            'medical_info' => ['medicalInfo', 'emptyNull'],
            'applicant_notes' => ['applicantNotes', 'emptyNull'],
            'internal_notes' => ['internalNotes', 'emptyNull'],
            'ready_for_decision' => ['readyForDecision', 'bool'],
            'flagged_issues' => ['flaggedIssues', 'emptyNull'],
            'interview_at' => ['interviewAt', 'emptyNull'],
            'interview_status' => ['interviewStatus', null],
            'interview_notes' => ['interviewNotes', 'emptyNull']
        ];
        foreach ($map as $col => $info) {
            $k = $info[0];
            if (!array_key_exists($k, $body)) {
                continue;
            }
            $v = $body[$k];
            if ($info[1] === 'bool') {
                $v = !empty($v) ? 1 : 0;
            } elseif ($info[1] === 'intNull') {
                $v = (int) $v ?: null;
            } elseif ($info[1] === 'emptyNull') {
                $v = $v === '' || $v === null ? null : $v;
            }
            $sets[] = "`$col` = ?";
            $vals[] = $v;
        }
        if (isset($body['draftJson']) && is_array($body['draftJson'])) {
            $sets[] = 'draft_json = ?';
            $vals[] = json_encode($body['draftJson'], JSON_UNESCAPED_UNICODE);
        }
        if (count($sets) === 0) {
            adm_send(['message' => 'Nothing to update']);
        }
        $vals[] = $id;
        $vals[] = $school;
        $sql = 'UPDATE applicants SET ' . implode(', ', $sets) . ' WHERE id = ? AND school_number = ?';
        $dbh->prepare($sql)->execute($vals);
        adm_send(['message' => 'Updated']);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? $body['id'] ?? 0);
        if ($id < 1 || !adm_applicant_owned($dbh, $school, $id)) {
            adm_send(['error' => 'Not found'], 404);
        }
        $dbh->prepare('DELETE FROM applicants WHERE id = ? AND school_number = ?')->execute([$id, $school]);
        adm_send(['message' => 'Deleted']);
    }

    adm_send(['message' => 'Method not allowed'], 405);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
