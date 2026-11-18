<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

$fees = db_all('SELECT * FROM fees WHERE student_id = ? ORDER BY status = \'Paid\', id DESC', 's', [$me['student_id']]);
$due = 0;
foreach ($fees as $f) {
    if ($f['status'] === 'Due') {
        $due += (int)$f['amount'];
    }
}

layout_start('Fees', 'student', 'student-fees.php');
?>
<div class="head"><h2>Fees</h2><span class="muted">Outstanding <b class="mono"><?= money($due) ?></b></span></div>
<div class="tablebox"><table>
  <thead><tr><th>Month</th><th>Type</th><th class="num">Amount</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($fees as $f): ?>
    <tr>
      <td><?= e($f['month_label']) ?></td>
      <td><?= e($f['fee_type']) ?></td>
      <td class="num"><?= money($f['amount']) ?></td>
      <td><?= pill($f['status']) ?>
        <?php if ($f['status'] === 'Paid'): ?><span class="muted mono"><?= fdate($f['paid_on']) ?></span>
        <?php elseif ($f['reminded']): ?><span class="muted">reminder sent</span><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$fees): ?><tr><td colspan="4" class="empty">No fees yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<p class="muted" style="font-size:.85rem">Pay at the provost office. Staff mark the invoice as paid after they receive the money.</p>
<?php layout_end();
