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
	$sql = "SELECT `device_id`, `minTime`, `maxTime`, `device_name` FROM (SELECT * FROM (SELECT `device_id`, MIN(`time`) AS `minTime`, MAX(`time`) AS `maxTime` FROM `logdata` GROUP BY `device_id` ORDER BY `device_id`) AS `all_loggers` LEFT JOIN (SELECT `device_id` AS `id`, `device_name` FROM `logcfg` ORDER BY `device_id`) AS `named_loggers` ON `all_loggers`.`device_id` = `named_loggers`.`id` ORDER BY `all_loggers`.`device_id`) AS `final_result`;";
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
	<select onchange="updateTimes()" id="loggerSelect"></select>
	<button id="submitBtn">Выбрать логгер</button>
	<br>

	<input type="datetime-local" id="startTime" name="startTime"/>
	<br>
	<input type="datetime-local" id="endTime" name="endTime"/>
	<br>
	<button id="resetBtn">Сбросить фильтр</button>
	<br>
	<input onchange="toggleFilter()" type="checkbox" id="fixTimes" name="fixTimes" />
	<label for="fixTimes">Зафиксировать фильтр</label>
	<br>

	<canvas id="temperatureChart" width="80" height="40"></canvas>
	<canvas id="humidityChart" width="80" height="40"></canvas>
	<canvas id="voltageChart" width="80" height="40"></canvas>
	<br>

	<form action="logout.php" method="post">
		<input id="logoutButton" name="logout" type="submit" value="Сменить пользователя" />
	</form>

	<script>
		function updateTimes()
		{
			const select = document.getElementById('loggerSelect');
			const startTime = document.getElementById('startTime');
			const endTime = document.getElementById('endTime');

			if (startTime.disabled || endTime.disabled)
			{
				return;
			}

			const loggerList = <?php echo json_encode($loggerList); ?>;

			const obj = loggerList.find(o => o.device_id === select.value);
			const minTime = obj['minTime'].replace(' ','T');
			const maxTime = obj['maxTime'].replace(' ','T');
			min = new Date(Date.parse(minTime));
			max = new Date(Date.parse(maxTime));
			min.setMinutes(min.getMinutes()-min.getTimezoneOffset());
			max.setMinutes(max.getMinutes()-max.getTimezoneOffset());
			startTime.value = min.toISOString().slice(0, 16);
			endTime.value = max.toISOString().slice(0, 16);
			startTime.min = startTime.value;
			startTime.max = endTime.value;
			endTime.min = startTime.value;
			endTime.max = endTime.value;
		}

		function toggleFilter()
		{
			const checkbox = document.getElementById('fixTimes');
			const startTime = document.getElementById('startTime');
			const endTime = document.getElementById('endTime');
			const resetBtn = document.getElementById('resetBtn');

			startTime.disabled = checkbox.checked;
			endTime.disabled = checkbox.checked;
			resetBtn.disabled = checkbox.checked;
		}

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
		const logout = document.getElementById("logoutButton");
		const logoutButtonOnState = logout.style.display;

		const startTime = document.getElementById('startTime');
		const endTime = document.getElementById('endTime');

		const now = new Date();
		now.setMinutes(now.getMinutes()-now.getTimezoneOffset());
		endTime.value = now.toISOString().slice(0, 16);
		now.setMinutes(now.getMinutes()-180);
		startTime.value = now.toISOString().slice(0, 16);
		now.setMinutes(now.getMinutes()+180);

		const loggerList = <?php echo json_encode($loggerList); ?>;
		loggerList.forEach((lognum) => {
			var opt = document.createElement('option');
			opt.value = lognum['device_id'];
			if (lognum['device_name'] != null)
			{
				opt.innerHTML = lognum['device_name'] + ' (#' + lognum['device_id'] + ')';
			}
			else
			{
				opt.innerHTML = 'Логгер (#' + lognum['device_id'] + ')';
			}
			select.appendChild(opt);
		})

		updateTimes();

		document.getElementById('resetBtn').addEventListener('click', function() {
			updateTimes();
		})

		document.getElementById('submitBtn').addEventListener('click', function() {
			logout.style.display = "none";
			if (humidityChart) humidityChart.destroy();
			if (voltageChart) voltageChart.destroy();
			if (temperatureChart) temperatureChart.destroy();

			const selectedValue = select.value;
			const min_time = startTime.value.replace('T',' ') + ':00';
			const max_time = endTime.value.replace('T',' ') + ':59';

			fetch('query.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({ param: selectedValue, minTime: min_time, maxTime: max_time })
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

				logout.style.display = logoutButtonOnState;

			})

			.catch(error => console.error('Ошибка:', error));
		});
	</script>
</body>
</html>
   
