<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    if ($action === 'allocate') {
        $sid = post('student_id');
        $room = post('room_no');
        $r = db_one('SELECT capacity FROM rooms WHERE room_no = ?', 's', [$room]);
        $occ = (int)db_val('SELECT COUNT(*) FROM students WHERE room_no = ?', 's', [$room]);
        $stu = db_one('SELECT name FROM students WHERE student_id = ? AND room_no IS NULL', 's', [$sid]);
        if (!$r || !$stu) {
            flash('Choose a resident without a room and a valid room.', 'err');
        } elseif ($occ >= (int)$r['capacity']) {
            flash("Room $room is full.", 'err');
        } else {
            db_exec('UPDATE students SET room_no = ? WHERE student_id = ?', 'ss', [$room, $sid]);
            flash($stu['name'] . " now has a seat in $room.");
        }
    } elseif ($action === 'unassign') {
        db_exec('UPDATE students SET room_no = NULL WHERE student_id = ?', 's', [post('student_id')]);
        flash('Seat released.');
    }
    redirect('admin-rooms.php');
}

$rooms = db_all('SELECT * FROM rooms ORDER BY room_no');
$byRoom = [];
foreach (db_all('SELECT student_id, name, room_no FROM students WHERE room_no IS NOT NULL ORDER BY name') as $s) {
    $byRoom[$s['room_no']][] = $s;
}
$unalloc = db_all('SELECT student_id, name FROM students WHERE room_no IS NULL ORDER BY name');
$freeRooms = [];
foreach ($rooms as $r) {
    $free = (int)$r['capacity'] - count($byRoom[$r['room_no']] ?? []);
    if ($free > 0) {
        $freeRooms[] = $r + ['free' => $free];
    }
}

layout_start('Rooms', 'admin', 'admin-rooms.php');
?>
<h2>Room allocation</h2>
<div class="tablebox"><table>
  <thead><tr><th>Room</th><th>Block</th><th>Seats</th><th>Residents</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rooms as $r):
      $occ = $byRoom[$r['room_no']] ?? []; $n = count($occ); ?>
    <tr>
      <td class="mono"><b><?= e($r['room_no']) ?></b></td>
      <td>Block <?= e($r['block']) ?> (<?= e($r['side']) ?>)</td>
      <td><?= $n ?> / <?= (int)$r['capacity'] ?></td>
      <td>
        <?php foreach ($occ as $s): ?>
          <form method="post" class="inline"><?= csrf_field() ?>
            <input type="hidden" name="action" value="unassign"><input type="hidden" name="student_id" value="<?= e($s['student_id']) ?>">
            <?= e($s['name']) ?> <button class="btn small danger" type="submit" aria-label="Remove <?= e($s['name']) ?> from <?= e($r['room_no']) ?>">Remove</button></form><br>
        <?php endforeach; ?>
        <?php if (!$occ): ?><span class="muted">Empty</span><?php endif; ?>
      </td>
      <td><?= $n >= (int)$r['capacity'] ? '<span class="pill bad">Full</span>' : '<span class="pill good">' . ((int)$r['capacity'] - $n) . ' free</span>' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<form class="panel form" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="allocate">
  <div class="full"><h3>Allocate a seat</h3></div>
  <label for="a-s">Resident
    <select id="a-s" name="student_id" required>
      <?php foreach ($unalloc as $s): ?><option value="<?= e($s['student_id']) ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
      <?php if (!$unalloc): ?><option value="">Everyone has a room</option><?php endif; ?>
    </select></label>
  <label for="a-r">Room
    <select id="a-r" name="room_no" required>
      <?php foreach ($freeRooms as $r): ?><option value="<?= e($r['room_no']) ?>"><?= e($r['room_no']) ?> (<?= e($r['side']) ?>, <?= $r['free'] ?> free)</option><?php endforeach; ?>
    </select></label>
  <div><button class="btn" type="submit"<?= $unalloc && $freeRooms ? '' : ' disabled' ?>>Allocate</button></div>
</form>
<?php layout_end();
