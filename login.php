<?php
session_start();
$servername = "localhost";
$username = "web_local";
$password = "PqT3pSBspgKZbWz";
$dbname = "global_auth";

// Создание соединения
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка соединения
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$username = $_POST['username'];
	$password = $_POST['password'];

	$sql = "SELECT * FROM users WHERE username='$username'";
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		if (password_verify($password, $row['password'])) {
			$query = "SHOW DATABASES LIKE '&username'";
			$res = $conn->query($sql);
			if ($res->num_rows > 0) {
				$_SESSION['username'] = $username; // Сохранение имени пользователя в сессии
				echo "Успешная авторизация: " . $username;
				$_SESSION['auth_status'] = "Успешная авторизация: " . $username;
			} else {
				echo "База данных для пользователя не создана: " . $username;
				$_SESSION['auth_status'] = "База данных для пользователя не создана: " . $username;
			}
		} else {
			echo "Введен неверный пароль";
			$_SESSION['auth_status'] = "Введен неверный пароль";
		}
	} else {
		echo "Пользователь не найден";
		$_SESSION['auth_status'] = "Пользователь " . $username . " не найден";
	}
}

$conn->close();

if (isset($_SESSION['username']))
{
	header("Location: index.php");
	exit();
}
else
{
	header("Location: auth.php");
	exit();
}
?>