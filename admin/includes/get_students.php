<?php
/**
 * Student register with unpaid dues, filtered by plain rules.
 * $filter: 'all', 'due' (has unpaid dues) or 'noroom' (no room allocated).
 */
function get_students(string $q = '', string $filter = 'all'): array
{
    $like = '%' . $q . '%';
    $sql = "SELECT s.*, COALESCE(SUM(CASE WHEN f.status = 'Due' THEN f.amount END), 0) AS dues
            FROM students s
            LEFT JOIN fees f ON f.student_id = s.student_id
            WHERE (s.student_id LIKE ? OR s.name LIKE ? OR s.department LIKE ? OR s.room_no LIKE ?)";
    if ($filter === 'noroom') {
        $sql .= ' AND s.room_no IS NULL';
    }
    $sql .= ' GROUP BY s.id';
    if ($filter === 'due') {
        $sql .= ' HAVING dues > 0';
    }
    $sql .= ' ORDER BY s.student_id';
    return db_all($sql, 'ssss', [$like, $like, $like, $like]);
}
