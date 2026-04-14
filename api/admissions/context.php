<?php
require_once __DIR__ . '/_init.php';
$school = adm_school_id();
if ($method !== 'GET') {
    adm_send(['message' => 'Method not allowed'], 405);
}
try {
    $year = null;
    $st = $dbh->prepare('SELECT id, name, start_date, end_date FROM academic_years WHERE school_number = ? AND is_active = 1 LIMIT 1');
    $st->execute([$school]);
    $year = $st->fetch(PDO::FETCH_ASSOC) ?: null;

    $period = null;
    if ($year) {
        $st = $dbh->prepare(
            'SELECT id, name, start_date, end_date FROM study_periods WHERE school_number = ? AND academic_year_id = ? AND is_active = 1 LIMIT 1'
        );
        $st->execute([$school, (int) $year['id']]);
        $period = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $st = $dbh->prepare(
        'SELECT c.id, c.level_id, c.name, c.max_students, l.name AS level_name FROM classes c
         INNER JOIN levels l ON l.id = c.level_id AND l.school_number = c.school_number
         WHERE c.school_number = ? ORDER BY l.sort_order, c.sort_order, c.id'
    );
    $st->execute([$school]);
    $classes = $st->fetchAll(PDO::FETCH_ASSOC);

    $yearId = $year ? (int) $year['id'] : 0;
    foreach ($classes as &$c) {
        $cap = $yearId ? adm_class_capacity($dbh, $school, (int) $c['id'], $yearId) : ['max' => (int) $c['max_students'], 'used' => 0, 'remaining' => (int) $c['max_students']];
        $c['capacity_used'] = $cap['used'];
        $c['capacity_max'] = $cap['max'];
        $c['capacity_remaining'] = $cap['remaining'];
    }
    unset($c);

    $st = $dbh->prepare('SELECT id, name FROM streams WHERE school_number = ? ORDER BY sort_order, id');
    $st->execute([$school]);
    $streams = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $dbh->prepare('SELECT id, name FROM levels WHERE school_number = ? ORDER BY sort_order, id');
    $st->execute([$school]);
    $levels = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $dbh->prepare(
        'SELECT id, name, start_date, end_date, is_active FROM academic_years WHERE school_number = ? ORDER BY start_date DESC'
    );
    $st->execute([$school]);
    $academicYears = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $dbh->prepare(
        'SELECT id, academic_year_id, name, start_date, end_date, is_active, sort_order FROM study_periods WHERE school_number = ? ORDER BY academic_year_id, sort_order, id'
    );
    $st->execute([$school]);
    $studyPeriods = $st->fetchAll(PDO::FETCH_ASSOC);

    adm_send([
        'data' => [
            'academicYear' => $year,
            'studyPeriod' => $period,
            'academicYears' => $academicYears,
            'studyPeriods' => $studyPeriods,
            'classes' => $classes,
            'streams' => $streams,
            'levels' => $levels
        ]
    ]);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
