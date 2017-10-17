<?php
	include 'database/connection.php';

	include 'classes/user.php';
	include 'classes/follow.php';
	include 'classes/tweet.php';

	global $pdo;
	
	$getUser = new User($pdo);
	$getFollow = new Follow($pdo);
	$getTweet = new Tweet($pdo);

	session_start();

	define("BASE_URL", "http://localhost/twitter/");
?>
