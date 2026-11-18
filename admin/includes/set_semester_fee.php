<?php
/** Creates one unpaid invoice for every resident. Returns how many were created. */
function issue_invoices(string $month, string $type, int $amount): int
{
    return db_exec(
        'INSERT INTO fees (student_id, fee_type, month_label, amount) SELECT student_id, ?, ?, ? FROM students',
        'ssi',
        [$type, $month, $amount]
    );
}
