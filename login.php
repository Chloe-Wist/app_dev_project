<?php
// Chloe Wist, Ethan Janssen, Jonathan Smith, Hazel Lim
// 10/9/26
// Login page for accessing the user dashboard

// Connects to database for authentication
require_once "helpers/config.php";

// Basic session hardening (set early)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();

// Redirects to dashboard if they're already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: UserDashboard.php');
    exit;
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim(htmlspecialchars($_POST['username'] ?? ''));
	$password = $_POST['password'] ?? '';

	if ($username === '' || $password === '') {
		$message = "Please enter username and password.";
	} else {
		$stmt = $conn->prepare(
			'SELECT user_id, password_hash, role_name
			FROM users JOIN roles
			ON users.role_id = roles.Role_ID
			WHERE username = ?'
		);
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$stmt->bind_result($uid, $hash, $role);
		if ($stmt->fetch()) {
			if (password_verify($password, $hash)) {
				// Harden session: regenerate ID on login
				session_regenerate_id(true);
				$_SESSION['user_id'] = $uid;
				$_SESSION['username'] = $username;
				$_SESSION['role'] = $role;
				header("Location: userdashboard.php");
				exit;
			} else {
				$message = "Invalid login credentials.";
			}
		} else {
			$message = "User not found.";
		}
		$stmt->close();
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Login</title>
	<link rel="stylesheet" href="styles/main.css">
	<?php //<link rel="stylesheet" href="styles/main.css"> ?>
</head>
<body>
	<div class="page">
	<div class="form">
		<h1>Login</h1>
		<?php if (!empty($message)): ?>
			<div class="error">
				<?php echo htmlspecialchars($message); ?>
			</div>
		<?php endif; ?>
		<form method="POST" action="login.php">
			<label for="username">Username/Email</label>
			<input type="text" id="username" name="username" required placeholder="Your username/email">

			<label for="password">Password</label>
			<input type="password" id="password" name="password" required placeholder="Your password">

			<button class="button-primary" type="submit">Login</button>
		</form>
		<a class="button-secondary" href="changePassword.php">Forgot Password</a>
	</div>
	</div>
</body>
</html>
