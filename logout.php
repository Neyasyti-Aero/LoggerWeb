<?php
session_start();
if (isset($_POST['logout']))
{
	if (isset($_SESSION['username']))
	{
		$_SESSION['auth_status'] = "Выполнен выход из учетной записи " . $_SESSION['username'];
		unset($_SESSION['username']);
		header("Location: auth.php");
		exit();
	}
}
else
{
	header("Location: index.php");
	exit();
}
?>