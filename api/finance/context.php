<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);

if ($method !== 'GET') {
    fin_send(['message' => 'Method not allowed'], 405);
}

try {
    $years = $dbh->prepare('SELECT id, name, start_date, end_date, is_active FROM academic_years WHERE school_number = ? ORDER BY start_date DESC');
    $years->execute([$school]);
    $periods = $dbh->prepare(
        'SELECT id, academic_year_id, name, start_date, end_date, is_active, sort_order FROM study_periods WHERE school_number = ? ORDER BY sort_order ASC, id ASC'
    );
    $periods->execute([$school]);
    $classes = $dbh->prepare(
        'SELECT c.id, c.level_id, c.name, c.sort_order, l.name AS level_name FROM classes c
         INNER JOIN levels l ON l.id = c.level_id AND l.school_number = c.school_number
         WHERE c.school_number = ? ORDER BY l.sort_order, c.sort_order'
    );
    $classes->execute([$school]);
    fin_send([
        'data' => [
            'academicYears' => $years->fetchAll(PDO::FETCH_ASSOC),
            'studyPeriods' => $periods->fetchAll(PDO::FETCH_ASSOC),
            'classes' => $classes->fetchAll(PDO::FETCH_ASSOC)
        ]
    ]);
} catch (Exception $e) {
    fin_send(['error' => $e->getMessage()], 500);
}
