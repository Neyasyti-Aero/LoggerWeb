<?php

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		// Получаем данные из POST-запроса
		$data = json_decode(file_get_contents('php://input'), true);
		$param = $data['param'];

		// Подключение к базе данных
		$servername = "localhost";
		$username = "web_local";
		$password = "PqT3pSBspgKZbWz";
		$dbname = "logger";

		$conn = new mysqli($servername, $username, $password, $dbname);

		// Проверка подключения
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}

		// Получение данных из таблицы
		$stmt = $conn->prepare("SELECT `time`, `humidity`, `battery`, `temperature` FROM `logdata` WHERE `device_id` = ? ORDER BY `time`");
		$stmt->bind_param("i", $param); // Привязываем параметр к запросу (i - целое число)

		if ($stmt->execute())  {
			$result = $stmt->get_result();

			$data = [];
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					$data[] = $row;
				}
			}

			// Возвращаем массив данных в формате JSON
			echo json_encode($data);
		}
		else {
			echo json_encode(["error" => "Ошибка выполнения запроса: " . $stmt->error]);
		}

		$stmt->close();
		$conn->close();
	}
	else
	{
		header("Location: index.php");
		exit();
	}
?>
