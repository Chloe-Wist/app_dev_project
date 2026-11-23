<?php

/*
    * Session Management: create, view, edit, activate/deactivate, delete (and
    * optionally restore) sessions, generate access codes, and choose which
    * simulations are available during a session.
    *
    * Linking from an event page: SessionManagement.php?event_id=123 opens the
    * Create form with that event already selected.
    */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: loginPage.php');
    exit;
}

require_once "helpers/config.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ---------------------------------------------------------------- settings
const CODE_ALPHABET      = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I
const CODE_LENGTH        = 6;
const ACTIVATION_HOURS   = 4;   // length of window when manually activating an expired session
const PAGE_ROLES         = ['Owner', 'Admin', 'Facilitator'];

$user_id = (int) $_SESSION['user_id'];
$self    = basename($_SERVER['PHP_SELF']);

// ----------------------------------------------------------------- helpers
function h($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function get_role(mysqli $conn, int $uid): string {
    $stmt = $conn->prepare(
        "SELECT r.Role_Name FROM Users u JOIN Roles r ON r.Role_ID = u.Role_ID WHERE u.User_ID = ?"
    );
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['Role_Name'] ?? '';
}

function fetch_session(mysqli $conn, int $sid): ?array {
    $stmt = $conn->prepare(
        "SELECT s.*, e.Manager_ID
        FROM Sessions s
        LEFT JOIN Event e ON e.Event_ID = s.Event_ID
        WHERE s.Session_ID = ?"
    );
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

// Linked to an event: the event's manager (and Owners) can edit.
// Not linked: only the creator (and Owners) can edit.
function can_edit(array $s, int $uid, string $role): bool {
    if ($role === 'Owner') return true;
    if (!empty($s['Event_ID'])) return (int) $s['Manager_ID'] === $uid;
    return (int) $s['Created_By'] === $uid;
}

function can_link_event(mysqli $conn, int $event_id, int $uid, string $role): bool {
    $stmt = $conn->prepare("SELECT Manager_ID FROM Event WHERE Event_ID = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) return false;
    return $role === 'Owner' || (int) $row['Manager_ID'] === $uid;
}

function generate_code(mysqli $conn): string {
    $max = strlen(CODE_ALPHABET) - 1;
    for ($try = 0; $try < 20; $try++) {
        $code = '';
        for ($i = 0; $i < CODE_LENGTH; $i++) {
            $code .= CODE_ALPHABET[random_int(0, $max)];
        }
        $stmt = $conn->prepare("SELECT 1 FROM Sessions WHERE Passcode = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $taken = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$taken) return $code;
    }
    throw new RuntimeException("Could not generate a unique access code. Please try again.");
}

function parse_local(string $v): ?DateTime {
    $d = DateTime::createFromFormat('Y-m-d\TH:i', $v);
    return $d ?: null;
}

function session_state(DateTime $now, DateTime $start, ?DateTime $end): string {
    if ($now < $start) return 'Scheduled';
    if ($end === null || $now > $end) return 'Expired';
    return 'Active';
}

function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// perms
$role = get_role($conn, $user_id);
if (!in_array($role, PAGE_ROLES, true)) {
    http_response_code(403);
    exit('You do not have permission to manage sessions.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$has_soft_delete = $conn->query("SHOW COLUMNS FROM Sessions LIKE 'Deleted_At'")->num_rows > 0;

$all_sims = $conn->query(
    "SELECT sim.Simulation_ID, i.Industry_Name
    FROM Simulation sim JOIN Industry i ON i.Industry_ID = sim.Industry_ID
    ORDER BY i.Industry_Name"
)->fetch_all(MYSQLI_ASSOC);
$valid_sim_ids = array_map('intval', array_column($all_sims, 'Simulation_ID'));

// POSTs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request. Please go back and try again.');
    }

    $action = $_POST['action'] ?? '';
    $sid    = (int) ($_POST['session_id'] ?? 0);
    $now    = new DateTime();
    $fmt    = 'Y-m-d H:i:s';

    try {
        switch ($action) {

            case 'save_session':
                $name     = trim($_POST['session_name'] ?? '');
                $start    = parse_local($_POST['start'] ?? '');
                $end      = parse_local($_POST['end'] ?? '');
                $event_id = (int) ($_POST['event_id'] ?? 0) ?: null;
                $simulation_id = (int) ($_POST['simulation_id'] ?? 0);

                if ($name === '' || mb_strlen($name) > 50) {
                    throw new RuntimeException("Session name is required (50 characters max).");
                }
                if (!$start || !$end) {
                    throw new RuntimeException("Start and end times are required.");
                }
                if ($end <= $start) {
                    throw new RuntimeException("The end time must be after the start time.");
                }

                if (!in_array($simulation_id, $valid_sim_ids, true)) {
                    throw new RuntimeException("Choose the simulation this session will host.");
                }

                $existing = null;
                if ($sid) {
                    $existing = fetch_session($conn, $sid);
                    if (!$existing || !can_edit($existing, $user_id, $role)) {
                        throw new RuntimeException("You do not have permission to edit this session.");
                    }
                }
                $event_unchanged = $existing && (int) $existing['Event_ID'] === (int) $event_id;
                if ($event_id !== null && !$event_unchanged
                    && !can_link_event($conn, $event_id, $user_id, $role)) {
                    throw new RuntimeException("You can only link sessions to events you manage.");
                }

                $start_s = $start->format($fmt);
                $end_s   = $end->format($fmt);

                $conn->begin_transaction();
                if ($sid) {
                    $stmt = $conn->prepare(
                        "UPDATE Sessions SET Session_Name = ?, Start_Date_Time = ?, End_Date_Time = ?, Event_ID = ?
                        WHERE Session_ID = ?"
                    );
                    $stmt->bind_param("sssii", $name, $start_s, $end_s, $event_id, $sid);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $code = generate_code($conn);
                    $stmt = $conn->prepare(
                        "INSERT INTO Sessions (Session_Name, Created_By, Passcode, Start_Date_Time, End_Date_Time, Event_ID)
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param("sisssi", $name, $user_id, $code, $start_s, $end_s, $event_id);
                    $stmt->execute();
                    $sid = $conn->insert_id;
                    $stmt->close();
                }

                $stmt = $conn->prepare("DELETE FROM Simulation_to_Session WHERE Session_ID = ?");
                $stmt->bind_param("i", $sid);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO Simulation_to_Session (Session_ID, Simulation_ID) VALUES (?, ?)");
                $stmt->bind_param("ii", $sid, $simulation_id);
                $stmt->execute();
                $stmt->close();

                flash('success', $existing ? "Session updated." : "Session created.");
                break;

            case 'toggle_session':
                $s = fetch_session($conn, $sid);
                if (!$s || !can_edit($s, $user_id, $role)) {
                    throw new RuntimeException("You do not have permission to change this session.");
                }
                $start = new DateTime($s['Start_Date_Time']);
                $end   = $s['End_Date_Time'] ? new DateTime($s['End_Date_Time']) : null;

                if (session_state($now, $start, $end) === 'Active') {
                    // Deactivate
                    $end = clone $now;
                    flash('success', "Session deactivated. Its access code no longer works.");
                } else {
                    // Activate
                    if ($start > $now) $start = clone $now;
                    if ($end === null || $end <= $now) {
                        $end = (clone $now)->modify('+' . ACTIVATION_HOURS . ' hours');
                    }
                    flash('success', "Session activated. Its access code now works.");
                }
                $start_s = $start->format($fmt);
                $end_s   = $end->format($fmt);
                $stmt = $conn->prepare("UPDATE Sessions SET Start_Date_Time = ?, End_Date_Time = ? WHERE Session_ID = ?");
                $stmt->bind_param("ssi", $start_s, $end_s, $sid);
                $stmt->execute();
                $stmt->close();
                break;

            case 'regenerate_code':
                $s = fetch_session($conn, $sid);
                if (!$s || !can_edit($s, $user_id, $role)) {
                    throw new RuntimeException("You do not have permission to change this session.");
                }
                $code = generate_code($conn);
                $stmt = $conn->prepare("UPDATE Sessions SET Passcode = ? WHERE Session_ID = ?");
                $stmt->bind_param("si", $code, $sid);
                $stmt->execute();
                $stmt->close();
                flash('success', "New access code: " . $code);
                break;

            case 'delete_session':
                $s = fetch_session($conn, $sid);
                if (!$s || !can_edit($s, $user_id, $role)) {
                    throw new RuntimeException("You do not have permission to delete this session.");
                }
                if ($has_soft_delete) {
                    $now_s = $now->format($fmt);
                    $stmt = $conn->prepare(
                        "UPDATE Sessions SET Deleted_At = ?,
                            End_Date_Time = IF(End_Date_Time IS NULL OR End_Date_Time > ?, ?, End_Date_Time)
                        WHERE Session_ID = ?"
                    );
                    $stmt->bind_param("sssi", $now_s, $now_s, $now_s, $sid);
                    $stmt->execute();
                    $stmt->close();
                    flash('success', "Session deleted. You can bring it back with Restore.");
                } else {
                    $conn->begin_transaction();
                    $stmt = $conn->prepare("DELETE FROM Simulation_to_Session WHERE Session_ID = ?");
                    $stmt->bind_param("i", $sid);
                    $stmt->execute();
                    $stmt->close();
                    $stmt = $conn->prepare("DELETE FROM Sessions WHERE Session_ID = ?");
                    $stmt->bind_param("i", $sid);
                    $stmt->execute();
                    $stmt->close();
                    $conn->commit();
                    flash('success', "Session permanently deleted.");
                }
                break;

            case 'restore_session':
                if (!$has_soft_delete) {
                    throw new RuntimeException("Restore is not available.");
                }
                $s = fetch_session($conn, $sid);
                if (!$s || !can_edit($s, $user_id, $role)) {
                    throw new RuntimeException("You do not have permission to restore this session.");
                }
                $stmt = $conn->prepare("UPDATE Sessions SET Deleted_At = NULL WHERE Session_ID = ?");
                $stmt->bind_param("i", $sid);
                $stmt->execute();
                $stmt->close();
                flash('success', "Session restored. It is inactive until you activate it or change its times.");
                break;

            default:
                throw new RuntimeException("Unknown action.");
        }
    } catch (RuntimeException $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        flash('fail', $e->getMessage());
    } catch (mysqli_sql_exception $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        if ($e->getCode() === 1451) {
            flash('fail', "This session has recorded participant data and can't be permanently deleted.");
        } else {
            flash('fail', "Something went wrong saving to the database.");
        }
    }

    header("Location: " . $self);
    exit;
}

// page data
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$now = new DateTime();

// Events the user may link a session to
if ($role === 'Owner') {
    $events = $conn->query(
        "SELECT Event_ID, Event_Name, Event_Date FROM Event ORDER BY Event_Date DESC"
    )->fetch_all(MYSQLI_ASSOC);
} else {
    $stmt = $conn->prepare(
        "SELECT Event_ID, Event_Name, Event_Date FROM Event WHERE Manager_ID = ? ORDER BY Event_Date DESC"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Simulations linked to each session
$sim_names = array_column($all_sims, 'Industry_Name', 'Simulation_ID');
$links = [];
foreach ($conn->query("SELECT Session_ID, Simulation_ID FROM Simulation_to_Session")->fetch_all(MYSQLI_ASSOC) as $l) {
    $links[(int) $l['Session_ID']][] = (int) $l['Simulation_ID'];
}

$where = $has_soft_delete ? "WHERE s.Deleted_At IS NULL" : "";
$rows = $conn->query(
    "SELECT s.Session_ID, s.Session_Name, s.Created_By, s.Passcode, s.Start_Date_Time,
            s.End_Date_Time, s.Event_ID, e.Event_Name, e.Manager_ID,
            u.First_Name, u.Last_Name
    FROM Sessions s
    LEFT JOIN Event e ON e.Event_ID = s.Event_ID
    LEFT JOIN Users u ON u.User_ID = s.Created_By
    $where
    ORDER BY s.Start_Date_Time DESC"
)->fetch_all(MYSQLI_ASSOC);

$js_rows = [];
foreach ($rows as $r) {
    $start = new DateTime($r['Start_Date_Time']);
    $end   = $r['End_Date_Time'] ? new DateTime($r['End_Date_Time']) : null;
    $state = session_state($now, $start, $end);
    $ids   = $links[(int) $r['Session_ID']] ?? [];
    $js_rows[] = [
        'id'          => (int) $r['Session_ID'],
        'name'        => $r['Session_Name'],
        'code'        => $r['Passcode'],
        'state'       => $state,
        'active'      => $state === 'Active',
        'creator'     => trim(($r['First_Name'] ?? '') . ' ' . ($r['Last_Name'] ?? '')) ?: 'Unknown',
        'event_id'    => $r['Event_ID'] ? (int) $r['Event_ID'] : null,
        'event_name'  => $r['Event_Name'],
        'start_local' => $start->format('Y-m-d\TH:i'),
        'end_local'   => $end ? $end->format('Y-m-d\TH:i') : '',
        'date'        => $start->format('M j, Y') . ($end && $end->format('Y-m-d') !== $start->format('Y-m-d')
                            ? ' – ' . $end->format('M j, Y') : ''),
        'start_time'  => $start->format('g:i A'),
        'end_time'    => $end ? $end->format('g:i A') : 'Not set',
        'timeframe'   => $start->format('M j, g:i A') . ' – ' . ($end ? $end->format('M j, g:i A') : 'no end'),
        'sim_id'      => $ids[0] ?? null,
        'sim_name'    => isset($ids[0]) ? ($sim_names[$ids[0]] ?? 'Simulation ' . $ids[0]) : null,
        'can_edit'    => can_edit($r, $user_id, $role),
    ];
}

// Deleted sessions for the Restore pop-up
$deleted = [];
if ($has_soft_delete) {
    $dr = $conn->query(
        "SELECT s.Session_ID, s.Session_Name, s.Created_By, s.Event_ID, s.Deleted_At, e.Manager_ID
        FROM Sessions s LEFT JOIN Event e ON e.Event_ID = s.Event_ID
        WHERE s.Deleted_At IS NOT NULL
        ORDER BY s.Deleted_At DESC"
    )->fetch_all(MYSQLI_ASSOC);
    foreach ($dr as $d) {
        if (can_edit($d, $user_id, $role)) $deleted[] = $d;
    }
}

$prefill_event = (int) ($_GET['event_id'] ?? 0);
$open_create   = $prefill_event > 0 && can_link_event($conn, $prefill_event, $user_id, $role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Management</title>
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/main.css">
    <style>
        .sm-list { max-height: 420px; overflow-y: auto; }
        .sm-row { display: flex; gap: 12px; align-items: center; justify-content: space-between;
                padding: 12px 8px; border-bottom: 1px solid rgba(128,128,128,.4); cursor: pointer; }
        .sm-row:hover, .sm-row:focus { background: rgba(128,128,128,.15); outline: none; }
        .sm-name { flex: 1 1 35%; font-weight: 600; }
        .sm-time { flex: 1 1 45%; }
        .sm-badge { flex: 0 0 auto; padding: 2px 12px; border-radius: 12px; border: 1px solid currentColor; font-size: .9em; }
        .sm-badge.on { background: #1f8a3b; color: #fff; border-color: #1f8a3b; }
        .sm-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 16px; flex-wrap: wrap; }
        dialog.sm-dialog { width: min(640px, 94vw); border: 2px solid #333; border-radius: 8px; padding: 20px;
                        background: #fff; color: #111; }
        dialog.sm-dialog::backdrop { background: rgba(0,0,0,.5); }
        .sm-dialog label { display: block; margin-top: 12px; font-weight: 600; }
        .sm-dialog input[type=text], .sm-dialog input[type=datetime-local], .sm-dialog select {
            width: 100%; box-sizing: border-box; padding: 6px; }
        .sm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; margin-top: 14px; }
        .sm-field { border: 1px solid #999; padding: 8px; border-radius: 4px; }
        .sm-field small { display: block; color: #555; }
        .sm-code { font-size: 2em; letter-spacing: .15em; font-family: monospace; font-weight: 700;
                border: 2px dashed #333; padding: 6px 14px; text-align: center; }
        .sm-sims { max-height: 150px; overflow-y: auto; border: 1px solid #999; padding: 6px 10px; }
        .sm-sims label { font-weight: 400; margin-top: 4px; }
        .sm-foot { display: flex; gap: 10px; justify-content: space-between; margin-top: 18px; flex-wrap: wrap; }
        .sm-foot form { display: inline; }
        .sm-restore-row { display: flex; justify-content: space-between; align-items: center; gap: 10px;
                        padding: 8px 0; border-bottom: 1px solid #ccc; }
        @media (max-width: 520px) { .sm-grid { grid-template-columns: 1fr; } .sm-time { display: none; } }
    </style>
</head>
<body>
    <?php
    $title = "Session Management";
    include("helpers/header.php");
    ?>

    <div class="page-container">

        <?php if ($flash): ?>
            <p class="<?= $flash['type'] === 'success' ? 'success' : 'fail' ?>"><?= h($flash['msg']) ?></p>
        <?php endif; ?>

        <div class="card"><h2 style="margin:0;color:#fff;">Sessions</h2></div>

        <div class="card sm-list">
            <?php if (!$js_rows): ?>
                <p>No sessions yet. Click Create to make one.</p>
            <?php endif; ?>
            <?php foreach ($js_rows as $i => $r): ?>
                <div class="sm-row" tabindex="0" role="button" data-index="<?= $i ?>">
                    <span class="sm-name"><?= h($r['name']) ?></span>
                    <span class="sm-time"><?= h($r['timeframe']) ?></span>
                    <span class="sm-badge <?= $r['active'] ? 'on' : '' ?>"><?= $r['active'] ? 'Active' : 'Inactive' ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="sm-actions">
            <?php if ($has_soft_delete): ?>
                <button class="btn" type="button" id="btnRestore">Restore</button>
            <?php endif; ?>
            <button class="btn btn-green" type="button" id="btnCreate">Create</button>
        </div>
    </div>

    <!-- ============ Pop-up: session details (click a row) ============ -->
    <dialog class="sm-dialog" id="detailDialog">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
            <h2 id="d_name" style="margin:0;"></h2>
            <div class="sm-code" id="d_code"></div>
        </div>

        <div class="sm-grid">
            <div class="sm-field"><small>Status</small><span id="d_status"></span></div>
            <div class="sm-field"><small>Created by</small><span id="d_creator"></span></div>
            <div class="sm-field"><small>Linked event</small><span id="d_event"></span></div>
            <div class="sm-field"><small>Expected date</small><span id="d_date"></span></div>
            <div class="sm-field"><small>Linked simulation</small><span id="d_sims"></span></div>
            <div class="sm-field"><small>Expected start / end</small><span id="d_times"></span></div>
        </div>

        <div class="sm-foot" id="d_buttons">
            <div>
                <form method="POST" action="<?= h($self) ?>" id="f_delete"
                    onsubmit="return confirm('Delete this session?');">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="delete_session">
                    <input type="hidden" name="session_id" class="sid">
                    <button class="btn btn-red" type="submit">Delete</button>
                </form>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <form method="POST" action="<?= h($self) ?>" id="f_toggle">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="toggle_session">
                    <input type="hidden" name="session_id" class="sid">
                    <button class="btn" type="submit" id="d_toggleBtn">Activate</button>
                </form>
                <form method="POST" action="<?= h($self) ?>" id="f_regen"
                    onsubmit="return confirm('Generate a new code? The old code will stop working.');">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="regenerate_code">
                    <input type="hidden" name="session_id" class="sid">
                    <button class="btn" type="submit">New code</button>
                </form>
                <button class="btn" type="button" id="d_editBtn">Edit Info</button>
                <button class="btn btn-blue" type="button" id="d_closeBtn">Close</button>
            </div>
        </div>
        <p id="d_noperm" style="display:none;margin-top:12px;"><em>You can view this session but don't have permission to edit it.</em></p>
    </dialog>

    <!-- ============ Pop-up: create / edit ============ -->
    <dialog class="sm-dialog" id="formDialog">
        <form method="POST" action="<?= h($self) ?>">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="save_session">
            <input type="hidden" name="session_id" id="f_id" value="0">

            <h2 id="f_title" style="margin-top:0;">Create Session</h2>

            <label for="f_name">Session name</label>
            <input type="text" name="session_name" id="f_name" maxlength="50" required>

            <div class="sm-grid">
                <div>
                    <label for="f_start">Start (activation)</label>
                    <input type="datetime-local" name="start" id="f_start" required>
                </div>
                <div>
                    <label for="f_end">End (expiration)</label>
                    <input type="datetime-local" name="end" id="f_end" required>
                </div>
            </div>

            <label for="f_sim">Simulation</label>
            <select name="simulation_id" id="f_sim" required>
                <option value="">Select a simulation…</option>
                <?php foreach ($all_sims as $sim): ?>
                    <option value="<?= (int) $sim['Simulation_ID'] ?>"><?= h($sim['Industry_Name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Available simulations</label>
            <div class="sm-sims">
                <?php if (!$all_sims): ?>
                    <em>No simulations exist yet.</em>
                <?php endif; ?>
                <?php foreach ($all_sims as $sim): ?>
                    <label>
                        <input type="checkbox" name="simulation_ids[]" value="<?= (int) $sim['Simulation_ID'] ?>">
                        <?= h($sim['Industry_Name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <p id="f_codeNote" style="margin-bottom:0;"><small>An access code is generated automatically when you save.</small></p>

            <div class="sm-foot" style="justify-content:flex-end;">
                <button class="btn" type="button" id="f_cancel">Cancel</button>
                <button class="btn btn-green" type="submit">Save</button>
            </div>
        </form>
    </dialog>

    <!-- ============ Pop-up: restore ============ -->
    <?php if ($has_soft_delete): ?>
    <dialog class="sm-dialog" id="restoreDialog">
        <h2 style="margin-top:0;">Restore</h2>
        <?php if (!$deleted): ?>
            <p>No deleted sessions you can restore.</p>
        <?php endif; ?>
        <?php foreach ($deleted as $d): ?>
            <div class="sm-restore-row">
                <span><?= h($d['Session_Name']) ?></span>
                <span>Deleted <?= h((new DateTime($d['Deleted_At']))->format('M j, Y g:i A')) ?></span>
                <form method="POST" action="<?= h($self) ?>">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="restore_session">
                    <input type="hidden" name="session_id" value="<?= (int) $d['Session_ID'] ?>">
                    <button class="btn" type="submit">Restore</button>
                </form>
            </div>
        <?php endforeach; ?>
        <div class="sm-foot" style="justify-content:flex-end;">
            <button class="btn" type="button" id="restoreCancel">Cancel</button>
        </div>
    </dialog>
    <?php endif; ?>

    <script>
        const SESSIONS = <?= json_encode($js_rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const PREFILL_EVENT = <?= $open_create ? $prefill_event : 0 ?>;

        const $ = id => document.getElementById(id);
        const detail = $('detailDialog');
        const formDlg = $('formDialog');
        let current = null;

        function openDetail(i) {
            const s = SESSIONS[i];
            current = s;
            $('d_name').textContent = s.name;
            $('d_code').textContent = s.code;
            $('d_status').textContent = s.active ? 'Active' : 'Inactive' + (s.state === 'Active' ? '' : ' (' + s.state.toLowerCase() + ')');
            $('d_creator').textContent = s.creator;
            $('d_event').textContent = s.event_name || 'None';
            $('d_date').textContent = s.date;
            $('d_times').textContent = s.start_time + ' to ' + s.end_time;
            $('d_sims').textContent = s.sim_name || 'None';
            $('d_toggleBtn').textContent = s.active ? 'Deactivate' : 'Activate';
            document.querySelectorAll('#detailDialog .sid').forEach(el => el.value = s.id);
            $('d_buttons').style.display = s.can_edit ? '' : 'none';
            $('d_noperm').style.display = s.can_edit ? 'none' : '';
            detail.showModal();
        }

        function openForm(s, eventId) {
            $('f_id').value = s ? s.id : 0;
            $('f_title').textContent = s ? 'Edit Session' : 'Create Session';
            $('f_name').value = s ? s.name : '';
            $('f_start').value = s ? s.start_local : '';
            $('f_end').value = s ? s.end_local : '';
            $('f_sim').value = s && s.sim_id ? s.sim_id : '';
            $('f_codeNote').style.display = s ? 'none' : '';
            document.querySelectorAll('input[name="simulation_ids[]"]').forEach(cb => {
                cb.checked = s ? s.sim_ids.includes(parseInt(cb.value, 10)) : false;
            });
            formDlg.showModal();
        }

        document.querySelectorAll('.sm-row').forEach(row => {
            row.addEventListener('click', () => openDetail(row.dataset.index));
            row.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDetail(row.dataset.index); }
            });
        });

        $('btnCreate').addEventListener('click', () => openForm(null, 0));
        $('d_closeBtn').addEventListener('click', () => detail.close());
        $('d_editBtn').addEventListener('click', () => { detail.close(); openForm(current, 0); });
        $('f_cancel').addEventListener('click', () => formDlg.close());

        const btnRestore = $('btnRestore');
        if (btnRestore) {
            btnRestore.addEventListener('click', () => $('restoreDialog').showModal());
            $('restoreCancel').addEventListener('click', () => $('restoreDialog').close());
        }

        if (PREFILL_EVENT) openForm(null, PREFILL_EVENT);
    </script>
</body>
</html>