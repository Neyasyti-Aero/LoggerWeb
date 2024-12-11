<?php
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
	$password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Хеширование пароля

	$sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";

	if ($conn->query($sql) === TRUE && $conn->query("CREATE DATABASE $username") === TRUE) {
		$conn->query("CREATE TABLE `$username`.`logdata` (`device_id` INT UNSIGNED NOT NULL, `msg_id` INT UNSIGNED NOT NULL, `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, `humidity` decimal(8,6) DEFAULT NULL, `temperature` decimal(8,6) DEFAULT NULL, `battery` decimal(8,6) DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		$conn->query("CREATE TABLE `logcfg` ( `device_id` INT UNSIGNED NOT NULL PRIMARY KEY, `device_name` VARCHAR(32) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
		$conn->query("GRANT INSERT, UPDATE ON $username.logcfg TO 'web_local_inserter'@'localhost';");
		echo "Регистрация успешна!";
	} else {
		echo "Ошибка: " . $sql . "<br>" . $conn->error;
	}
}

$conn->close();
?>
