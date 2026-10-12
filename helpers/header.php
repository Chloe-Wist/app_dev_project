<?php
// Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
// 10/11/26
// Header for the internal website
?>
<header>
  <img src="images/logo.png" width="186" height="76">
  <?= '<h1 class="nav-header">'.$title."</h1>" ?>
  <nav class="nav-links">
    <a href="UserDashboard.php">Dashboard</a>
    <a href="logout.php">Logout</a>
    <a href="UserInfo.php">
      <img src="images/profile_pic.png" height="46" width="46">
    </a>
  </nav>
</header>
