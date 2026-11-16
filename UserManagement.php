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

$stmt = $conn->prepare('SELECT r.Role_Name FROM Users u JOIN Roles r ON r.Role_ID = u.Role_ID WHERE u.User_ID = ?');
$stmt->bind_param("i", $_SESSION['user_id']);
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

if($isOwner) {
   $users = $conn->query('SELECT u.User_ID, u.First_Name, u.Last_Name, u.Username, u.Email, u.Phone_Number, u.Role_ID, r.Role_Name FROM Users u
    JOIN Roles r on r.Role_ID = u.Role_ID ORDER BY u.Last_Name')->fetch_all(MYSQLI_ASSOC);
}
else {
    $stmt = $conn->prepare('SELECT u.User_ID, u.First_Name, u.Last_Name, u.Username, u.Email, u.Phone_Number, u.Role_ID, r.Role_Name FROM Users u
    JOIN Roles r on r.Role_ID = u.Role_ID where u.User_ID in 
        (SELECT User_ID FROM User_to_Area where Area_ID In 
            (Select Area_ID From User_to_Area Where User_ID = ?)) ORDER BY u.Last_Name');
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

const Employee_Roles = ['Facilitator', 'Volunteer'];

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
    
    $targetRole = $row['Role_Name'];
    if($isOwner) {
        return true;
    } 
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
$stmt->bind_param("i", $_SESSION['user_id']);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $adminActions = ['edit_info', 'change_areas', 'create_user', 'delete_user', 'reset_password'];

    if (!$isOwner && !in_array($action,$adminActions,true)) {
        http_response_code(403);
        exit('Only Owners can make that change.');
    }
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request. Please go back and try again.');
    }

    $targetId = (int) ($_POST['user_id'] ?? 0);
    $message  = 'Unknown action.';

    if ($action === 'change_role') {
        $roleId = (int) ($_POST['role_id'] ?? 0);

        $newRoleName = null;
        foreach ($roles as $role) {
            if ((int) $role['Role_ID'] === $roleId) {
                $newRoleName = $role['Role_Name'];
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

        if ($newRoleName === null) {
        $message = "That role doesn't exist.";
        }
        elseif ($info['target_role'] === null) {
            $message = "That user doesn't exist.";
        }
        elseif ($info['target_role'] === 'Owner' && $newRoleName !== 'Owner' && (int) $info['owner_count'] <= 1) {
            $message = "You can't remove the last Owner. Make someone else an Owner first.";
        }
        else {
            $stmt = $conn->prepare('UPDATE Users SET Role_ID = ? WHERE User_ID = ?');
            $stmt->bind_param("ii", $roleId, $targetId);
            $stmt->execute();
            $stmt->close();

            // If you changed your own role, keep the session in step
            if ($targetId === (int) $_SESSION['user_id']) {
                $_SESSION['role'] = $newRoleName;
            }
            $message = "Role updated.";
        }
    }
    elseif ($action === 'change_areas') {
        if(!can_Manage($conn,$isOwner,(int) $_SESSION['user_id'], $targetId)) {
            $message = "You can't manage that user.";
        }
        else {
            $validAreaIds = array_map('intval', array_column($manageableAreas, 'Area_ID'));
            $areaIds = array_unique(array_filter(
            array_map('intval', (array) ($_POST['area_ids'] ?? [])),fn($id) => in_array($id, $validAreaIds, true)));
        try {
            $conn->begin_transaction();

            // Remove all of this user's current Areas
            if($isOwner) {
                $stmt = $conn->prepare('DELETE FROM User_to_Area WHERE User_ID = ?');
                $stmt->bind_param("i", $targetId);
                $stmt->execute();
                $stmt->close();
            }
            else {
                $stmt = $conn->prepare("DELETE FROM User_to_Area WHERE User_ID = ? AND Area_Id = ?");
                foreach ($myAreaIds as $areaId) {
                    $stmt->bind_param("ii",$targetId,$areaId);
                    $stmt->execute();
                }
                $stmt->close();
            }
            

            // Add one row per ticked box
            $stmt = $conn->prepare('INSERT INTO User_to_Area (User_ID, Area_ID) VALUES (?, ?)');
            foreach ($areaIds as $areaId) {
                $stmt->bind_param("ii", $targetId, $areaId);
                $stmt->execute();
            }
            $stmt->close();

            $conn->commit();
            $message = "Areas updated.";
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $message = "Could not update the areas.";
        }
        }
        
    }
    elseif ($action === 'edit_info') {
        if(!can_Manage($conn,$isOwner,(int) $_SESSION['user_id'],$targetId)) {
            $message = "You can't manage that user.";
        }
        else {
            $first = trim($_POST['first_name'] ?? '');
            $last = trim($_POST['last_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if($first === '' || $last === '' || $phone === '') {
                $message = "Please fill in every field";
            }
            else if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
                $message = "Username must be 3-20 letters, numbers or underscores.";
            }
            else if(!filter_var($email,FILTER_VALIDATE_EMAIL)) {
                $message = "Please enter a valid email";
            }
            else {
                try {
                    $stmt = $conn->prepare("UPDATE Users SET First_Name = ?, Last_Name = ?, Username = ?, Email = ?, Phone_Number 
                    = ? WHERE User_ID = ?");
                    $stmt->bind_param("sssssi", $first, $last, $username, $email, $phone, $targetId);
                    $stmt->execute();
                    $stmt->close();

                    if ($targetId === (int) $_SESSION['user_id']) {
                        $_SESSION['username'] = $username;
                    }
                    $message = "Information Updated";
                }
                catch (mysqli_sql_exception $e){
                    $message = ($e->getCode() === 1062) 
                    ? "That email or username is already in use." 
                    : "Could not update the information.";
                }
            }
        }
    }
    elseif ($action === 'reset_password') {
        if (!can_Manage($conn, $isOwner, (int) $_SESSION['user_id'], $targetId)) {
            $message = "You can't manage that user.";
        }
        else {
            $password = $_POST['new_password'] ?? '';
            if (strlen($password) < 8) {
                $message = "Password must be at least 8 characters.";
            }
            else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE Users SET Password_Hash = ? WHERE User_ID = ?');
                $stmt->bind_param("si", $hash, $targetId);
                $stmt->execute();
                $stmt->close();
                $message = "Password updated.";
            }
        }
    }
    elseif($action === 'delete_user') {
        $myId = (int) $_SESSION['user_id'];

        $stmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM Users u JOIN Roles r ON r.Role_ID = u.Role_ID WHERE r.Role_Name = 'Owner') AS owner_count,
        (SELECT r.Role_Name FROM Users u JOIN Roles r ON r.Role_ID = u.Role_ID WHERE u.User_ID = ?) AS target_role");
        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        $info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if($targetId === $myId) {
            $message = "You cannot delete your own account";
        }
        elseif(!can_Manage($conn,$isOwner,$myId,$targetId)) {
            $message = "You can't manage that user";
        }
        elseif($info['target_role'] === "Owner" && (int) $info["owner_count"] <= 1) {
            $message = "You can't delete the last Owner.";
        }
        else{
            try {
                $conn->begin_transaction();

                foreach(['Activity_Log', 'User_to_Area', 'Event_Signup', 'Availability'] as $table) {
                    $stmt = $conn->prepare("DELETE FROM $table WHERE User_ID = ?");
                    $stmt->bind_param("i",$targetId);
                    $stmt->execute();
                    $stmt->close();
                }

                $stmt = $conn->prepare("DELETE FROM Users WHERE User_ID = ?");
                $stmt->bind_param("i",$targetId);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "Account Deleted";
            }
            catch (mysqli_sql_exception $e){
                $conn->rollback();
                $message = ($e->getCode() === 1451)
                ? "This user still manages events or created sessions. Reassign those first."
                : "Could not delete the account.";
            }
        }
    }
    elseif($action === "create_user") {
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

                $hash = password_hash($password,PASSWORD_DEFAULT);
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

    $_SESSION['flash'] = $message;
    header('Location: ' . basename($_SERVER['PHP_SELF']));
    exit;
}

$userAreas = [];
$res = $conn->query('SELECT User_ID, Area_ID FROM User_to_Area');
foreach ($res->fetch_all(MYSQLI_ASSOC) as $row) {
    $userAreas[(int) $row['User_ID']][] = (int) $row['Area_ID'];
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

            <div class="user-list">
                <?php foreach($users as $user): ?>
                    <?php $canManageRow = $isOwner
                        || ((int) $user['User_ID'] !== (int) $_SESSION['user_id']
                            && in_array($user['Role_Name'], Employee_Roles, true)); ?>
                    <div class="user-row">
                        <span class="name"><?= h($user['First_Name'] . ' ' . $user['Last_Name']) ?></span>

                        <?php if ($isOwner): ?>
                            <!-- Owners get the role dropdown -->
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action"  value="change_role">
                                <input type="hidden" name="user_id" value="<?= (int) $user['User_ID'] ?>">

                                <select name="role_id">
                                <?php foreach($roles as $role): ?>
                                    <option value="<?= (int) $role['Role_ID'] ?>"
                                        <?= $role['Role_ID'] == $user['Role_ID'] ? 'selected' : '' ?>>
                                        <?= h($role['Role_Name']) ?>
                                    </option>
                                <?php endforeach; ?>
                                </select>

                                <button type="submit">Save</button>
                            </form>
                        <?php else: ?>
                            <!-- Admins just see the role as text -->
                            <span><?= h($user['Role_Name']) ?></span>
                        <?php endif; ?>

                        <?php if ($canManageRow): ?>
                            <!-- Changing Areas -->
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action"  value="change_areas">
                                <input type="hidden" name="user_id" value="<?= (int) $user['User_ID'] ?>">

                                <?php foreach($manageableAreas as $area): ?>
                                    <label>
                                        <input type="checkbox" name="area_ids[]" value="<?= (int) $area['Area_ID'] ?>"
                                            <?= in_array((int) $area['Area_ID'], $userAreas[(int) $user['User_ID']] ?? [], true) ? 'checked' : '' ?>>
                                        <?= h($area['Area_Name']) ?>
                                    </label>
                                <?php endforeach; ?>
                                <button type="submit">Save areas</button>
                            </form>

                            <details>
                                <summary>Edit Info</summary>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="edit_info">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['User_ID'] ?>">

                                    <input type="text" name="first_name" value="<?= h($user['First_Name']) ?>" required>
                                    <input type="text" name="last_name" value="<?= h($user['Last_Name']) ?>" required>
                                    <input type="text" name="username" value="<?= h($user['Username']) ?>" maxlength="20" required>
                                    <input type="email" name="email" value="<?= h($user['Email']) ?>" required>
                                    <input type="tel" name="phone" value="<?= h($user['Phone_Number']) ?>" required>

                                    <button type="submit">Save Info</button>
                                </form>
                            </details>

                            <details>
                                <summary>Set Password</summary>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action"  value="reset_password">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['User_ID'] ?>">
                                    <input type="password" name="new_password" placeholder="New password (8+ characters)"
                                        minlength="8" autocomplete="new-password" required>
                                    <button type="submit">Save password</button>
                                </form>
                            </details>

                            <?php if ((int) $user['User_ID'] !== (int) $_SESSION['user_id']): ?>
                                <form method="POST" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['User_ID'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <details>
            <summary>Create Account</summary>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="create_user">

                <input type="text"     name="first_name" placeholder="First Name" required>
                <input type="text"     name="last_name"  placeholder="Last Name" required>
                <input type="text"     name="username"   placeholder="Username (3-20 characters)" maxlength="20" required>
                <input type="email"    name="email"      placeholder="Email" required>
                <input type="tel"      name="phone"      placeholder="Phone" required>
                <input type="password" name="password"   placeholder="Temporary password (8+ characters)" required>

                <select name="role_id" required>
                    <?php foreach($creatableRoles as $role): ?>
                        <option value="<?= (int) $role['Role_ID'] ?>"><?= h($role['Role_Name']) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <?php foreach($manageableAreas as $area): ?>
                    <label>
                        <input type="checkbox" name="area_ids[]" value="<?= (int) $area['Area_ID'] ?>">
                        <?= h($area['Area_Name']) ?>
                    </label>
                <?php endforeach; ?>
                <button type="submit">Create</button>
            </form>
        </details>
        </div>
    </div>
</body>

</html>