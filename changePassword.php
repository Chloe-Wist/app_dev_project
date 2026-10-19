<?php
// Chloe Wist, Ethan Janssen, Jonathan Smith, Hazel Lim
// 10/10/26
// Handles password changes

// If there is no session, start one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to database
require_once "helpers/config.php";

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if(isset($_SESSION['user_id'])) {
		$username = $_SESSION['username'];
	}
	else {
		$username = trim(htmlspecialchars($_POST['username'] ?? ''));
	}
	$password = $_POST['password'] ?? '';
	$confirm  = $_POST['confirm'] ?? '';

	if ($username === '' || $password === '' || $confirm === '') {
		$message = "Please fill in all fields.";
	} elseif ($password !== $confirm) {
		$message = "Passwords do not match.";
	// } elseif (strlen($password) < 8) {
		//$message = "Password must be at least 8 characters.";
	} else {
		// Check that username exists
		$stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->fetch()) {
			$hash = password_hash($password, PASSWORD_DEFAULT);
			$stmtInsert = $conn->prepare(
				"UPDATE users SET password_hash = ? WHERE username = ?");
			$stmtInsert->bind_param("ss", $hash, $username);
			if ($stmtInsert->execute()) {
				header("Location: login.php");
				exit;
			} else {
				$message = "Failed to change password.";
			}
			$stmtInsert->close();

			$message = "Password changed";
		} else {
			$message = "Failed to find user";
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
	<title>Forgot Password</title>
	<link rel="stylesheet" href="styles/main.css">
</head>
<body>
	<div class="page">
	<div class="form">
		<h1>Change Password</h1>
		<?php if (!empty($message)): ?>
			<div>
				<?php echo ("<p class='error'>".$message."</p>"); ?></div>
		<?php endif; ?>
		<form method="POST" action="changePassword.php">
			<label for="username">Username</label>
			<?php
			// If the user is logged in, prefil the username
			if (isset($_SESSION['user_id'])) {
				echo ('<p>'.$_SESSION['username'].'</p>');
			} else {
				echo('<input type="text" id="username" name="username" required placeholder="Enter your username">');			
			}
			?>

			<label for="password">Password</label>
			<input type="password" id="password" name="password" required placeholder="Your password">

			<label for="confirm">Confirm Password</label>
			<input type="password" id="confirm" name="confirm" required placeholder="Confirm your password">

			<button class="button-primary" type="submit">Change password</button>
			<a class="button-secondary" href="login.php">Cancel</a>
		</form>
	</div>
	</div>
</body>
</html>
