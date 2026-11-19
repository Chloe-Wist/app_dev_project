<?php
// Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
// 11/7/26
// User Management Page
// Code was made with the help of Claude AI 

$title = "User Management";
// Connect to the database
require_once "helpers/config.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// If there is no session, start it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$myId = (int) $_SESSION['user_id'];

// Look up my role in the database (not the session), so role changes apply immediately
$stmt = $conn->prepare('SELECT r.Role_Name FROM Users u JOIN Roles r ON r.Role_ID = u.Role_ID WHERE u.User_ID = ?');
$stmt->bind_param("i", $myId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$myRole = $row['Role_Name'] ?? null;
if (!in_array($myRole, ['Owner', 'Admin'], true)) {
    exit('Only Owners and Admins can access this page.');
}
$isOwner = ($myRole === 'Owner');
$_SESSION['role'] = $myRole;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

const Employee_Roles = ['Facilitator', 'Volunteer'];

// Can the logged-in user manage this target user?
function can_Manage(mysqli $conn, bool $isOwner, int $myID, int $targetId): bool {
    // find the target's role name (no row = user doesn't exist)
    $stmt = $conn->prepare("SELECT Roles.Role_Name FROM Users JOIN Roles on Users.Role_ID = Roles.Role_ID 
    WHERE Users.User_Id = ?");
    $stmt->bind_param("i",$targetId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row == null) {
        return false;
    }

    // Owners can manage anyone who exists
    if($isOwner) {
        return true;
    } 
    // Admin rules: not yourself, only employee roles, only users who share an Area
    if($targetId == $myID) {
        return false;
    }
    if(!in_array($row['Role_Name'],Employee_Roles,true)) {
        return false;
    }

    $stmt = $conn->prepare("SELECT 1 FROM User_to_Area a JOIN User_to_Area b on a.Area_ID = b.Area_ID WHERE a.User_ID = ? and b.User_ID = ? LIMIT 1");
    $stmt->bind_param("ii",$myID,$targetId);
    $stmt->execute();
    $sharesArea = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $sharesArea;
}

$roles = $conn->query('SELECT Role_ID, Role_Name FROM Roles ORDER BY Role_ID')->fetch_all(MYSQLI_ASSOC);
$areas = $conn->query('SELECT Area_ID, Area_Name FROM Area ORDER BY Area_Name')->fetch_all(MYSQLI_ASSOC);

// Owners can use every Area. Admins only the Areas they belong to.
$stmt = $conn->prepare('SELECT Area_ID FROM User_to_Area WHERE User_ID = ?');
$stmt->bind_param("i", $myId);
$stmt->execute();
$myAreaIds = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'Area_ID'));
$stmt->close();

$manageableAreas = $isOwner
    ? $areas
    : array_values(array_filter($areas, fn($a) => in_array((int) $a['Area_ID'], $myAreaIds, true)));

// Owners can create any role. Admins only the employee roles.
$creatableRoles = $isOwner
    ? $roles
    : array_values(array_filter($roles, fn($r) => in_array($r['Role_Name'], Employee_Roles, true)));

// ---------- Handle form submissions (runs BEFORE the users are loaded) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $adminActions = ['save_user', 'create_user', 'delete_user'];

    if (!$isOwner && !in_array($action, $adminActions, true)) {
        http_response_code(403);
        exit('Only Owners can make that change.');
    }
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request. Please go back and try again.');
    }

    $targetId = (int) ($_POST['user_id'] ?? 0);
    $message  = 'Unknown action.';

    // ----- Save everything from the Edit popup in one go -----
    if ($action === 'save_user') {

        if (!can_Manage($conn, $isOwner, $myId, $targetId)) {
            $message = "You can't manage that user.";
        }
        else {
            $first       = trim($_POST['first_name'] ?? '');
            $last        = trim($_POST['last_name'] ?? '');
            $username    = trim($_POST['username'] ?? '');
            $email       = trim($_POST['email'] ?? '');
            $phone       = trim($_POST['phone'] ?? '');
            $newPassword = $_POST['new_password'] ?? '';
            $roleId      = (int) ($_POST['role_id'] ?? 0);

            $validAreaIds = array_map('intval', array_column($manageableAreas, 'Area_ID'));
            $areaIds = array_unique(array_filter(
                array_map('intval', (array) ($_POST['area_ids'] ?? [])),
                fn($id) => in_array($id, $validAreaIds, true)
            ));

            // Only Owners change roles. Find the new role's name and the Owner count.
            $newRoleName = null;
            if ($isOwner) {
                foreach ($roles as $role) {
                    if ((int) $role['Role_ID'] === $roleId) {
                        $newRoleName = $role['Role_Name'];
                    }
                }
            }
            $stmt = $conn->prepare(
                "SELECT (SELECT COUNT(*) FROM Users u JOIN Roles r ON r.Role_ID = u.Role_ID WHERE r.Role_Name = 'Owner') AS owner_count,
                        (SELECT r.Role_Name FROM Users u JOIN Roles r ON r.Role_ID = u.Role_ID WHERE u.User_ID = ?) AS target_role"
            );
            $stmt->bind_param("i", $targetId);
            $stmt->execute();
            $info = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($first === '' || $last === '' || $phone === '') {
                $message = "Please fill in every field.";
            }
            elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
                $message = "Username must be 3-20 letters, numbers or underscores.";
            }
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "Please enter a valid email.";
            }
            elseif ($newPassword !== '' && strlen($newPassword) < 8) {
                $message = "Password must be at least 8 characters.";
            }
            elseif ($isOwner && $newRoleName === null) {
                $message = "That role doesn't exist.";
            }
            elseif ($isOwner && $info['target_role'] === 'Owner' && $newRoleName !== 'Owner' && (int) $info['owner_count'] <= 1) {
                $message = "You can't remove the last Owner. Make someone else an Owner first.";
            }
            else {
                try {
                    $conn->begin_transaction();

                    // Basic information
                    $stmt = $conn->prepare("UPDATE Users SET First_Name = ?, Last_Name = ?, Username = ?, Email = ?, Phone_Number = ? WHERE User_ID = ?");
                    $stmt->bind_param("sssssi", $first, $last, $username, $email, $phone, $targetId);
                    $stmt->execute();
                    $stmt->close();

                    // Password, only if one was typed
                    if ($newPassword !== '') {
                        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare('UPDATE Users SET Password_Hash = ? WHERE User_ID = ?');
                        $stmt->bind_param("si", $hash, $targetId);
                        $stmt->execute();
                        $stmt->close();
                    }

                    // Role, Owners only
                    if ($isOwner) {
                        $stmt = $conn->prepare('UPDATE Users SET Role_ID = ? WHERE User_ID = ?');
                        $stmt->bind_param("ii", $roleId, $targetId);
                        $stmt->execute();
                        $stmt->close();
                    }

                    // Areas: Owners replace the whole set, Admins only touch their own Areas
                    if ($isOwner) {
                        $stmt = $conn->prepare('DELETE FROM User_to_Area WHERE User_ID = ?');
                        $stmt->bind_param("i", $targetId);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        $stmt = $conn->prepare('DELETE FROM User_to_Area WHERE User_ID = ? AND Area_ID = ?');
                        foreach ($myAreaIds as $areaId) {
                            $stmt->bind_param("ii", $targetId, $areaId);
                            $stmt->execute();
                        }
                        $stmt->close();
                    }
                    $stmt = $conn->prepare('INSERT INTO User_to_Area (User_ID, Area_ID) VALUES (?, ?)');
                    foreach ($areaIds as $areaId) {
                        $stmt->bind_param("ii", $targetId, $areaId);
                        $stmt->execute();
                    }
                    $stmt->close();

                    $conn->commit();

                    // Keep the session in step if you edited yourself
                    if ($targetId === $myId) {
                        $_SESSION['username'] = $username;
                        if ($isOwner) {
                            $_SESSION['role'] = $newRoleName;
                        }
                    }
                    $message = "Changes saved.";
                }
                catch (mysqli_sql_exception $e) {
                    $conn->rollback();
                    $message = ($e->getCode() === 1062)
                        ? "That username or email is already in use."
                        : "Could not save the changes.";
                }
            }
        }
    }

    // ----- Delete an account -----
    elseif ($action === 'delete_user') {

        $stmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM Users u JOIN Roles r ON r.Role_ID = u.Role_ID WHERE r.Role_Name = 'Owner') AS owner_count,
        (SELECT r.Role_Name FROM Users u JOIN Roles r ON r.Role_ID = u.Role_ID WHERE u.User_ID = ?) AS target_role");
        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        $info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($targetId === $myId) {
            $message = "You cannot delete your own account";
        }
        elseif (!can_Manage($conn, $isOwner, $myId, $targetId)) {
            $message = "You can't manage that user";
        }
        elseif ($info['target_role'] === "Owner" && (int) $info["owner_count"] <= 1) {
            $message = "You can't delete the last Owner.";
        }
        else {
            try {
                $conn->begin_transaction();

                foreach (['Activity_Log', 'User_to_Area', 'Event_Signup', 'Availability'] as $table) {
                    $stmt = $conn->prepare("DELETE FROM $table WHERE User_ID = ?");
                    $stmt->bind_param("i", $targetId);
                    $stmt->execute();
                    $stmt->close();
                }

                $stmt = $conn->prepare("DELETE FROM Users WHERE User_ID = ?");
                $stmt->bind_param("i", $targetId);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "Account Deleted";
            }
            catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $message = ($e->getCode() === 1451)
                    ? "This user still manages events or created sessions. Reassign those first."
                    : "Could not delete the account.";
            }
        }
    }

    // ----- Create an account -----
    elseif ($action === "create_user") {
        $first    = trim($_POST['first_name'] ?? '');
        $last     = trim($_POST['last_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';          // not trimmed: spaces can be part of a password
        $roleId   = (int) ($_POST['role_id'] ?? 0);

        $validRoleIds = array_map('intval', array_column($creatableRoles, 'Role_ID'));
        $validAreaIds = array_map('intval', array_column($manageableAreas, 'Area_ID'));
        $areaIds = array_unique(array_filter(
            array_map('intval', (array) ($_POST['area_ids'] ?? [])),
            fn($id) => in_array($id, $validAreaIds, true)
        ));

        if ($first === '' || $last === '' || $phone === '') {
            $message = "Please fill in every field.";
        }
        elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
            $message = "Username must be 3-20 letters, numbers or underscores.";
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email.";
        }
        elseif (strlen($password) < 8) {
            $message = "Password must be at least 8 characters.";
        }
        elseif (!in_array($roleId, $validRoleIds, true)) {
            $message = "You can't create an account with that role.";
        }
        elseif (!$isOwner && empty($areaIds)) {
            $message = "Please choose at least one of your areas.";
        }
        else {
            try {
                $conn->begin_transaction();

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    'INSERT INTO Users (Role_ID, First_Name, Last_Name, Username, Email, Phone_Number, Password_Hash)
                    VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param("issssss", $roleId, $first, $last, $username, $email, $phone, $hash);
                $stmt->execute();
                $newUserId = $conn->insert_id;
                $stmt->close();

                $stmt = $conn->prepare('INSERT INTO User_to_Area (User_ID, Area_ID) VALUES (?,?)');
                foreach ($areaIds as $areaId) {
                    $stmt->bind_param('ii', $newUserId, $areaId);
                    $stmt->execute();
                }
                $stmt->close();

                $conn->commit();
                $message = "Account created for " . $username . ".";
            }
            catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $message = ($e->getCode() === 1062)
                    ? "That username or email is already in use."
                    : "Could not create the account.";
            }
        }
    }

    // Store the message, then redirect so refreshing the page doesn't resend the form
    $_SESSION['flash'] = $message;
    header('Location: ' . basename($_SERVER['PHP_SELF']));
    exit;
}

// ---------- Load the users to display ----------
if($isOwner) {
   $users = $conn->query('SELECT u.User_ID, u.First_Name, u.Last_Name, u.Username, u.Email, u.Phone_Number, u.Role_ID, r.Role_Name FROM Users u
    JOIN Roles r on r.Role_ID = u.Role_ID ORDER BY u.Last_Name')->fetch_all(MYSQLI_ASSOC);
}
else {
    $stmt = $conn->prepare('SELECT u.User_ID, u.First_Name, u.Last_Name, u.Username, u.Email, u.Phone_Number, u.Role_ID, r.Role_Name FROM Users u
    JOIN Roles r on r.Role_ID = u.Role_ID where u.User_ID in 
        (SELECT User_ID FROM User_to_Area where Area_ID In 
            (Select Area_ID From User_to_Area Where User_ID = ?)) ORDER BY u.Last_Name');
    $stmt->bind_param("i", $myId);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Lookup of each user's Areas, e.g. $userAreas[3] = [1, 2]
$userAreas = [];
$res = $conn->query('SELECT User_ID, Area_ID FROM User_to_Area');
foreach ($res->fetch_all(MYSQLI_ASSOC) as $areaRow) {
    $userAreas[(int) $areaRow['User_ID']][] = (int) $areaRow['Area_ID'];
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }    
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/usermanagement.css">

</head>
<body>
    <?php include("helpers/header.php"); ?>

    <div class="page-container">
        <?php if (!empty($_SESSION['flash'])): ?>
            <p class="flash"><?= h($_SESSION['flash']) ?></p>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <div class="card user-management">
            <h2>Users</h2>

            <?php if (empty($users)): ?>
                <p>No users to show.<?= $isOwner ? '' : ' You may not be assigned to an Area yet.' ?></p>
            <?php endif; ?>

            <!-- Filter bar (outside any form, so nothing here is submitted) -->
            <div class="filters">
                <input type="search" id="filterText" placeholder="Search name, username or email">

                <select id="filterRole">
                    <option value="">All roles</option>
                    <?php foreach($roles as $role): ?>
                        <option value="<?= (int) $role['Role_ID'] ?>"><?= h($role['Role_Name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filterArea">
                    <option value="">All areas</option>
                    <?php foreach($manageableAreas as $area): ?>
                        <option value="<?= (int) $area['Area_ID'] ?>"><?= h($area['Area_Name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <span id="filterCount"></span>
            </div>

            <div class="user-list">
                <?php foreach($users as $user): ?>
                    <?php
                        $uid = (int) $user['User_ID'];
                        // Owners can manage anyone. Admins only employees who aren't themselves.
                        $canManageRow = $isOwner
                            || ($uid !== $myId && in_array($user['Role_Name'], Employee_Roles, true));
                    ?>
                    <div class="user-row"
                         data-name="<?= h(strtolower($user['First_Name'] . ' ' . $user['Last_Name'])) ?>"
                         data-username="<?= h(strtolower($user['Username'])) ?>"
                         data-email="<?= h(strtolower($user['Email'])) ?>"
                         data-role="<?= (int) $user['Role_ID'] ?>"
                         data-areas="<?= h(implode(',', $userAreas[$uid] ?? [])) ?>">

                        <span class="name">
                            <?= h($user['First_Name'] . ' ' . $user['Last_Name']) ?>
                            <small><?= h($user['Username']) ?> · <?= h($user['Email']) ?></small>
                        </span>
                        <span><?= h($user['Role_Name']) ?></span>

                        <?php if ($canManageRow): ?>
                            <button type="button" class="btn-edit" data-open="edit-<?= $uid ?>">Edit</button>

                            <dialog class="edit-dialog" id="edit-<?= $uid ?>">
                                <form method="POST" class="edit-form">
                                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action"  value="save_user">
                                    <input type="hidden" name="user_id" value="<?= $uid ?>">

                                    <h3>Edit <?= h($user['First_Name'] . ' ' . $user['Last_Name']) ?></h3>

                                    <div class="edit-grid">
                                        <label class="field">First name
                                            <input type="text" name="first_name" value="<?= h($user['First_Name']) ?>" required></label>
                                        <label class="field">Last name
                                            <input type="text" name="last_name" value="<?= h($user['Last_Name']) ?>" required></label>
                                        <label class="field">Username
                                            <input type="text" name="username" value="<?= h($user['Username']) ?>" maxlength="20" required></label>
                                        <label class="field">Phone
                                            <input type="tel" name="phone" value="<?= h($user['Phone_Number']) ?>" required></label>
                                        <label class="field wide">Email
                                            <input type="email" name="email" value="<?= h($user['Email']) ?>" required></label>

                                        <?php if ($isOwner): ?>
                                            <label class="field wide">Role
                                                <select name="role_id">
                                                    <?php foreach($roles as $role): ?>
                                                        <option value="<?= (int) $role['Role_ID'] ?>"
                                                            <?= $role['Role_ID'] == $user['Role_ID'] ? 'selected' : '' ?>>
                                                            <?= h($role['Role_Name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select></label>
                                        <?php endif; ?>

                                        <label class="field wide">New password <span class="hint">(leave blank to keep the current one)</span>
                                            <input type="password" name="new_password" minlength="8" autocomplete="new-password"></label>
                                    </div>

                                    <fieldset>
                                        <legend>Areas</legend>
                                        <?php foreach($manageableAreas as $area): ?>
                                            <label>
                                                <input type="checkbox" name="area_ids[]" value="<?= (int) $area['Area_ID'] ?>"
                                                    <?= in_array((int) $area['Area_ID'], $userAreas[$uid] ?? [], true) ? 'checked' : '' ?>>
                                                <?= h($area['Area_Name']) ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </fieldset>

                                    <div class="edit-actions">
                                        <button type="button" class="btn-secondary" data-close>Cancel</button>
                                        <button type="submit">Save changes</button>
                                    </div>
                                </form>

                                <?php if ($uid !== $myId): ?>
                                    <!-- Separate form: forms can't be nested -->
                                    <form method="POST" class="delete-zone" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action"  value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $uid ?>">
                                        <span>Permanently remove this account.</span>
                                        <button type="submit">Delete user</button>
                                    </form>
                                <?php endif; ?>
                            </dialog>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <details>
                <summary>Create Account</summary>
                <form method="POST" class="edit-dialog create-form">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="create_user">

                    <div class="edit-grid">
                        <label class="field">First name
                            <input type="text" name="first_name" required></label>
                        <label class="field">Last name
                            <input type="text" name="last_name" required></label>
                        <label class="field">Username
                            <input type="text" name="username" placeholder="3-20 characters" maxlength="20" required></label>
                        <label class="field">Phone
                            <input type="tel" name="phone" required></label>
                        <label class="field wide">Email
                            <input type="email" name="email" required></label>
                        <label class="field">Temporary password
                            <input type="password" name="password" placeholder="8+ characters" autocomplete="new-password" required></label>
                        <label class="field">Role
                            <select name="role_id" required>
                                <?php foreach($creatableRoles as $role): ?>
                                    <option value="<?= (int) $role['Role_ID'] ?>"><?= h($role['Role_Name']) ?></option>
                                <?php endforeach; ?>
                            </select></label>
                    </div>

                    <fieldset>
                        <legend>Areas</legend>
                        <?php if (empty($manageableAreas)): ?>
                            <span>You aren't assigned to an Area yet, so you can't create accounts.</span>
                        <?php endif; ?>
                        <?php foreach($manageableAreas as $area): ?>
                            <label>
                                <input type="checkbox" name="area_ids[]" value="<?= (int) $area['Area_ID'] ?>">
                                <?= h($area['Area_Name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>

                    <div class="edit-actions">
                        <button type="submit">Create account</button>
                    </div>
                </form>
            </details>
        </div>
    </div>

    <script>
        // ----- Filtering -----
        const rows    = document.querySelectorAll('.user-row');
        const text    = document.getElementById('filterText');
        const roleSel = document.getElementById('filterRole');
        const areaSel = document.getElementById('filterArea');
        const count   = document.getElementById('filterCount');

        function applyFilters() {
            const q    = text.value.trim().toLowerCase();
            const role = roleSel.value;
            const area = areaSel.value;
            let shown = 0;

            rows.forEach(row => {
                const areas = row.dataset.areas ? row.dataset.areas.split(',') : [];

                const matchesText = q === ''
                    || row.dataset.name.includes(q)
                    || row.dataset.username.includes(q)
                    || row.dataset.email.includes(q);
                const matchesRole = role === '' || row.dataset.role === role;
                const matchesArea = area === '' || areas.includes(area);

                const visible = matchesText && matchesRole && matchesArea;
                row.hidden = !visible;
                if (visible) shown++;
            });

            count.textContent = shown + ' of ' + rows.length + ' users';
        }

        [text, roleSel, areaSel].forEach(el => el.addEventListener('input', applyFilters));
        applyFilters();

        // ----- Edit popups -----
        // Open a user's popup
        document.querySelectorAll('[data-open]').forEach(btn => {
            btn.addEventListener('click', () => document.getElementById(btn.dataset.open).showModal());
        });
        // Cancel buttons close the popup they're in
        document.querySelectorAll('dialog [data-close]').forEach(btn => {
            btn.addEventListener('click', () => btn.closest('dialog').close());
        });
        // Clicking the dimmed area outside the popup closes it
        document.querySelectorAll('dialog.edit-dialog').forEach(dlg => {
            dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
        });
    </script>
</body>

</html>
