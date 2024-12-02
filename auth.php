<?php
session_start();
if (isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Авторизация</title>
</head>
<body>
	<!-- <h2>Регистрация</h2>
	<form action="register.php" method="post">
		<input type="text" name="username" placeholder="Имя пользователя" required>
		<br>
		<input type="password" name="password" placeholder="Пароль" required>
		<br>
		<button type="submit">Зарегистрироваться</button>
		<br>
	</form> -->

	<h2>Авторизация</h2>
	<form action="login.php" method="post">
		<input type="text" name="username" placeholder="Имя пользователя" required>
		<br>
		<input type="password" name="password" placeholder="Пароль" required>
		<br>
		<button type="submit">Войти</button>
		<br>
	</form>

	<p><?php echo $_SESSION['auth_status']; unset($_SESSION['auth_status']); ?></p>
</body>
</html>