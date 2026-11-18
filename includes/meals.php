<?php
// Meal rules. Meals are ON by default; a row in meal_skips means "skipped".
// A day closes for changes at 9 PM the night before.
require_once __DIR__ . '/helpers.php';

const MEAL_KEYS = ['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner'];

function meal_prices(): array
{
    static $prices = null;
    if ($prices === null) {
        $prices = ['breakfast' => 0, 'lunch' => 0, 'dinner' => 0];
        foreach (db_all('SELECT meal, price FROM meal_prices') as $r) {
            $prices[$r['meal']] = (int)$r['price'];
        }
    }
    return $prices;
}

function meal_editable(string $date): bool
{
    return time() < strtotime($date . ' 00:00:00') - 3 * 3600;
}

function add_days(string $date, int $n): string
{
    return date('Y-m-d', strtotime($date . ' ' . ($n >= 0 ? '+' : '') . $n . ' day'));
}

/** Set of "date|meal" keys the student skipped between two dates. */
function skips_for(string $sid, string $from, string $to): array
{
    $set = [];
    foreach (db_all('SELECT meal_date, meal FROM meal_skips WHERE student_id = ? AND meal_date BETWEEN ? AND ?',
        'sss', [$sid, $from, $to]) as $r) {
        $set[$r['meal_date'] . '|' . $r['meal']] = true;
    }
    return $set;
}

function day_cost(array $skips, string $date): int
{
    $c = 0;
    foreach (meal_prices() as $meal => $price) {
        if (!isset($skips[$date . '|' . $meal])) {
            $c += $price;
        }
    }
    return $c;
}

function set_meal(string $sid, string $date, string $meal, bool $on): void
{
    if ($on) {
        db_exec('DELETE FROM meal_skips WHERE student_id = ? AND meal_date = ? AND meal = ?', 'sss', [$sid, $date, $meal]);
    } else {
        db_exec('INSERT IGNORE INTO meal_skips (student_id, meal_date, meal) VALUES (?, ?, ?)', 'sss', [$sid, $date, $meal]);
    }
}

function menu_for(string $date): ?array
{
    $idx = ((int)date('w', strtotime($date)) + 1) % 7; // PHP: 0 = Sunday. Menu: 0 = Saturday.
    return db_one('SELECT * FROM mess_menu WHERE day_index = ?', 'i', [$idx]);
}

function month_label(string $ym): string
{
    return date('M Y', strtotime($ym . '-01'));
}

/** Plates ordered on a date for one meal, across all residents. */
function meal_count(string $date, string $meal): int
{
    $total = (int)db_val('SELECT COUNT(*) FROM students');
    $skipped = (int)db_val('SELECT COUNT(*) FROM meal_skips WHERE meal_date = ? AND meal = ?', 'ss', [$date, $meal]);
    return $total - $skipped;
}

/**
 * Meals taken and cost for the month so far, per student id.
 * Days counted: first of the month up to today (or the end of a past month).
 */
function month_summary(string $ym, ?string $sid = null): array
{
    $first = $ym . '-01';
    $end = min(date('Y-m-t', strtotime($first)), today());
    $days = $first > $end ? 0 : (int)round((strtotime($end) - strtotime($first)) / 86400) + 1;
    $prices = meal_prices();
    $perDay = array_sum($prices);

    $ids = $sid !== null
        ? [$sid]
        : array_column(db_all('SELECT student_id FROM students'), 'student_id');
    $out = [];
    foreach ($ids as $id) {
        $out[$id] = ['meals' => $days * 3, 'cost' => $days * $perDay];
    }
    if ($days > 0) {
        $sql = 'SELECT student_id, meal, COUNT(*) AS n FROM meal_skips WHERE meal_date BETWEEN ? AND ?'
            . ($sid !== null ? ' AND student_id = ?' : '') . ' GROUP BY student_id, meal';
        $rows = $sid !== null ? db_all($sql, 'sss', [$first, $end, $sid]) : db_all($sql, 'ss', [$first, $end]);
        foreach ($rows as $r) {
            if (isset($out[$r['student_id']])) {
                $out[$r['student_id']]['meals'] -= (int)$r['n'];
                $out[$r['student_id']]['cost'] -= (int)$r['n'] * $prices[$r['meal']];
            }
        }
    }
    return $out;
}
