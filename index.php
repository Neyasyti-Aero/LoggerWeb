<?php

// В конфиг nginx вставить внутри server
//location ~ .php$ {
//include snippets/fastcgi-php.conf;
//fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; # убедиться, что версия PHP правильная
//fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
//include fastcgi_params;
//}

// В index в том же конфиге добавить index.php к index.html и index.htm

// CREATE TABLE `logger`.`logdata` (`device_id` INT NOT NULL , `msg_id` INT NOT NULL , `time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , `humidity` DECIMAL(8,6) NULL DEFAULT NULL , `temperature` DECIMAL(8,6) NULL DEFAULT NULL , `battery` DECIMAL(8,6) NULL DEFAULT NULL ) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

// Проверка логина

	session_start();
	if (!isset($_SESSION['username'])) {
		header("Location: auth.php");
		exit();
	}


// Подключение к базе данных
	$servername = "localhost";
	$username = "web_local";
	$password = "PqT3pSBspgKZbWz";
	$dbname = $_SESSION['username'];

	$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка подключения
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

// Получение данных из таблицы
	$sql = "SELECT `device_id` FROM `logdata` GROUP BY `device_id` ORDER BY `device_id`";
	$result = $conn->query($sql);

	$loggerList = [];
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$loggerList[] = $row;
		}
	}

	$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Графики</title>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
	<select id="loggerSelect"></select>
	<button id="submitBtn">Выбрать логгер</button>
	<br>

	<canvas id="temperatureChart" width="400" height="200"></canvas>
	<canvas id="humidityChart" width="400" height="200"></canvas>
	<canvas id="voltageChart" width="400" height="200"></canvas>
	<br>

	<form action="logout.php" method="post">
		<input name="logout" type="submit" value="Сменить пользователя" />
	</form>

	<script>
		const canvasHumidity = document.getElementById('humidityChart');
		const ctxHumidity = canvasHumidity.getContext('2d');
		const canvasVoltage = document.getElementById('voltageChart');
		const ctxVoltage = canvasVoltage.getContext('2d');
		const canvasTemperature = document.getElementById('temperatureChart')
		const ctxTemperature = canvasTemperature.getContext('2d');

		var humidityChart = null;
		var voltageChart = null;
		var temperatureChart = null;

		const select = document.getElementById('loggerSelect');

		const loggerList = <?php echo json_encode($loggerList); ?>;
		loggerList.forEach((lognum) => {
			var opt = document.createElement('option');
			opt.value = lognum['device_id'];
			opt.innerHTML = 'Логгер #' + lognum['device_id'];
			select.appendChild(opt);
		})

		document.getElementById('submitBtn').addEventListener('click', function() {
			if (humidityChart) humidityChart.destroy();
			if (voltageChart) voltageChart.destroy();
			if (temperatureChart) temperatureChart.destroy();

			const selectedValue = select.value;

			fetch('query.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({ param: selectedValue })
			})
			.then(response => response.json())
			.then(data => {
				const labels = data.map(point => point.time);
				const humidityValues = data.map(point => point.humidity);
				const voltageValues = data.map(point => point.battery);
				const temperatureValues = data.map(point => point.temperature);

				humidityChart = new Chart(ctxHumidity, {
					type: 'line',
					data: {
						labels: labels,
						datasets: [{
							label: 'Влажность, %',
							data: humidityValues,
							borderColor: 'rgba(75, 192, 192, 1)',
							borderWidth: 1,
							fill: false,
						}]
					},
					options: {
						scales: {
							y: {
								beginAtZero: true
							}
						}
					}
				});

				voltageChart = new Chart(ctxVoltage, {
					type: 'line',
					data: {
						labels: labels,
						datasets: [{
							label: 'Напряжение батареи, В',
							data: voltageValues,
							borderColor: 'rgba(192, 75, 192, 1)',
							borderWidth: 1,
							fill: false,
						}]
					},
					options: {
						scales: {
							y: {
								beginAtZero: true
							}
						}
					}
				});

				temperatureChart = new Chart(ctxTemperature, {
					type: 'line',
					data: {
						labels: labels,
						datasets: [{
							label: 'Температура, С',
							data: temperatureValues,
							borderColor: 'rgba(192, 192, 75, 1)',
							borderWidth: 1,
							fill: false,
						}]
					},
					options: {
						scales: {
							y: {
								beginAtZero: true
							}
						}
					}
				});

			})

			.catch(error => console.error('Ошибка:', error));
		});
	</script>
</body>
</html>
   
