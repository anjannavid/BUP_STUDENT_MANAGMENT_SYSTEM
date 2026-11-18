<?php
/** All fee rows with the resident's name. Unpaid rows come first. */
function get_fee_payments(): array
{
    return db_all("SELECT f.*, s.name FROM fees f JOIN students s ON s.student_id = f.student_id
                   ORDER BY f.status = 'Paid', f.id DESC");
}
