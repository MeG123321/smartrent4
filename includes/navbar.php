<?php
// navbar prosty z obsługą ról i sesji
?>
<header class="navbar">
  <div class="nav-inner container">
    <a class="brand" href="index.php"><?=APP_NAME?></a>
    <nav>
      <a href="property_list.php">Oferty</a>
      <?php if (is_logged_in()): ?>
        <a href="user_panel.php">Moje konto</a>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
          <a href="admin_panel.php">Admin</a>
        <?php endif; ?>
        <a href="logout.php">Wyloguj</a>
      <?php else: ?>
        <a href="login.php">Zaloguj</a>
        <a href="register.php">Zarejestruj</a>
      <?php endif; ?>
    </nav>
  </div>
</header>