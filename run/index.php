<?php

$user = $_GET['user'];
$pass = $_GET['pass'];

if($user == 'GetSchedU' && $pass == 'G3tSch3dU') {
	require_once("../index.php");
	runScript();
} else {
	echo "Incorrect/missing username or password.";
}