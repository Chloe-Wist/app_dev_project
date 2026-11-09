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

if (($_SESSION['role'] ?? '') !== 'Owner' && ($_SESSION['role'] ?? '') !== 'Admin') {
    exit('Only Owners and Admins can access this page.');
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$isOwner = (($_SESSION['role'] ?? '') === 'Owner');
if($isOwner) {
   $users = $conn->query('SELECT u.User_ID, u.First_Name, u.Last_Name, u.Role_ID, r.Role_Name FROM Users u
    JOIN Roles r on r.Role_ID = u.Role_ID ORDER BY u.Last_Name')->fetch_all(MYSQLI_ASSOC);
}
else {
    $stmt = $conn->prepare('SELECT u.User_ID, u.First_Name, u.Last_Name, u.Role_ID, r.Role_Name FROM Users u
    JOIN Roles r on r.Role_ID = u.Role_ID where u.User_ID in 
        (SELECT User_ID FROM User_to_Area where Area_ID In 
            (Select Area_ID From User_to_Area Where User_ID = ?)) ORDER BY u.Last_Name');
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}


$roles = $conn->query('SELECT Role_ID, Role_Name FROM Roles ORDER BY Role_ID')->fetch_all(MYSQLI_ASSOC);
$areas = $conn->query('SELECT Area_ID, Area_Name FROM Area ORDER BY Area_Name')->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$isOwner) {
        http_response_code(403);
        exit('Only Owners can make changes.');
    }
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request. Please go back and try again.');
    }

    $action   = $_POST['action'] ?? '';
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $message  = 'Unknown action.';

    if ($action === 'change_role') {
        $roleId = (int) ($_POST['role_id'] ?? 0);

        if (!in_array($roleId, array_map('intval', array_column($roles, 'Role_ID')), true)) {
            $message = "That role doesn't exist.";
        } else {
            $stmt = $conn->prepare('UPDATE Users SET Role_ID = ? WHERE User_ID = ?');
            $stmt->bind_param("ii", $roleId, $targetId);
            $stmt->execute();
            $stmt->close();
            $message = "Role updated.";
        }
    }
    elseif ($action === 'change_areas') {
        // paste the Areas code from my earlier step 3 here
        // (it ends by setting $message = "Areas updated.")
        // Only keep IDs that are real Areas (form values can be edited in a browser)
        $validAreaIds = array_map('intval', array_column($areas, 'Area_ID'));
        $areaIds = array_filter(
            array_map('intval', (array) ($_POST['area_ids'] ?? [])),
            fn($id) => in_array($id, $validAreaIds, true)
        );

        try {
            $conn->begin_transaction();

            // 1. Remove all of this user's current Areas
            $stmt = $conn->prepare('DELETE FROM User_to_Area WHERE User_ID = ?');
            $stmt->bind_param("i", $targetId);
            $stmt->execute();
            $stmt->close();

            // 2. Add one row per ticked box
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

    <style>
    /* The scrolling box that holds all the rows */
    .user-list { max-height: 460px; overflow-y: auto; border: 1px solid #333; }

    /* One row per user: items sit side by side and wrap on small screens */
    .user-row  { display: flex; flex-wrap: wrap; gap: 14px; align-items: center;
                 padding: 12px; border-bottom: 1px solid #333; }

    /* The name takes the leftover space, so the controls line up to its right */
    .user-row .name { flex: 1 1 160px; font-weight: bold; }

    /* Each small form lays out its own contents in a line */
    .user-row form  { margin: 0; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
</style>
</head>
<body>
    <?php include("helpers/header.php"); ?>

    <div class="page-container">
        <div class="card user-management">
            <h2>Users</h2>
    
            <div class="user-list">
                <?php foreach($users as $user): ?>
                    <div class="user-row">
                        <span class="name"><?= h($user['First_Name'] . ' ' . $user['Last_Name']) ?></span>

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



                        <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action"  value="change_areas">
                                <input type="hidden" name="user_id" value="<?= (int) $user['User_ID'] ?>">

                                <?php foreach($areas as $area): ?>
                                    <label>
                                        <input type="checkbox" name="area_ids[]" value="<?= (int) $area['Area_ID'] ?>"
                                            <?= in_array((int) $area['Area_ID'], $userAreas[(int) $user['User_ID']] ?? [], true) ? 'checked' : '' ?>>
                                        <?= h($area['Area_Name']) ?>
                                    </label>
                                <?php endforeach; ?>
                                <button type="submit">Save areas</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>

</html>