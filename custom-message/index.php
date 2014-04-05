<?php

require './helpers.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    if (auth($user, $pass)) {
        session_start();
        $_SESSION['authenticated'] = true;
        header("LOCATION: /custom-message/page.php");
    }
}

?>
<!DOCTYPE html>
<html>
  <head>
    <title>Custom Message &middot; SchedU</title>
    <meta name="description" content="Get SchedUcated with SchedU, a service that delivers your schedule by text message every morning.">
    <meta name="author" content="SchedU">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
      #wrap {
        padding-top:150px;
      }
    </style>
    <link href="http://getschedu.com/res/css/bootstrap.min.css" rel="stylesheet">
    <link href="http://getschedu.com/res/css/register.css" rel="stylesheet">
    <link href="http://getschedu.com/res/css/styles.css" rel="stylesheet">
    <link rel="icon" href="http://getschedu.com/res/ico/favicon.png">
  </head>
  <body>
    <?php require 'http://getschedu.com/res/html/navigation.html'; ?>
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
    <?php require 'http://getschedu.com/res/html/footer.html'; ?>
  </body>
</html>
