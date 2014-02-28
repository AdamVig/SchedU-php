<?php

set_include_path($_SERVER[DOCUMENT_ROOT]."res");
require 'helpers.php';

$user = $_POST['user'];
$pass = $_POST['pass'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (auth($user, $pass)) {
        session_start();
        $_SESSION['authenticated'] = true;
        header("LOCATION: http://getschedu.com/exec/message/page.php");
    }
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Statistics &middot; SchedU</title>
		<meta name="description" content="Get SchedUcated with SchedU, a service that delivers your schedule by text message every morning.">
		<meta name="author" content="SchedU">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<style>
			#wrap {
				padding-top:150px;
			}
		</style>
		<link href="/res/css/bootstrap.min.css" rel="stylesheet">
		<link href="/res/css/register.css" rel="stylesheet">
		<link href="/res/css/styles.css" rel="stylesheet">
		<link rel="icon" href="/res/ico/favicon.png">
	</head>
	<body>
		<? require 'html/navigation.html'; ?>
		<div id="content">
			<section id="login">
				<div class="container" id="wrap">
					<h1>Custom Message</h1>
					<div class="line"></div>
					<div class="panel panel-primary">
						<div class="panel-heading">
							Sign In
						</div>
						<div class="panel-body">
							<form method="POST" action="index.php">
								<div class="form-group">
					            	Username <input type="text" class="form-control" name="user"></input><br/>
					            	Password <input type="password" class="form-control" name="pass"></input><br/>
					            	<button type="submit" class="btn btn-default pull-right">Submit</button>
					            </div>
				            </form>
						</div>
					</div>
				</div>
			</section>
		</div>
		<? require 'html/footer.html'; ?>
	</body>
</html>