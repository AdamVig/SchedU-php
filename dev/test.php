<?php
error_reporting(-1);
ini_set('display_errors', 1);
ini_set('html_errors', 1);
require_once "../vendor/autoload.php";
// require_once "../make-schedule.php";
// require_once "../index.php";
//$client = new Services_Twilio("AC4c45ba306f764d2327fe824ec0e46347", "5121fd9da17339d86bf624f9fabefebe");

$db = [
    'host' => 'localhost',
    'username' => 'schedu',
    'password' => 'schedu'
];
$database = new mysqli($db['host'], $db['username'], $db['password'], 'users');

$query = "SELECT * FROM users";
$result = $database->query($query);
//------------------------------------------------------


//------------------------------------------------------
//GO THROUGH DATABASE ARRAY
while ($userData = $result->fetch_assoc()) {
    print_r($userData);
    echo "<br><br>";
}

$u = [
  'ID'=> '1',
  'Number'=> '1',
  'FirstName'=> 'Adam',
  'LastName'=> 'Vignoodle',
  'Grade'=> 'sophomore',
  'School'=> 'hudson',
  'Membership'=> 'premium',
  'PhoneNumber'=> '5086884042',
  'A'=> 'Physics',
  'B'=> 'Psych',
  'C'=> 'Programming',
  'D'=> 'Team Sports # Study',
  'E'=> 'English',
  'F'=> 'Marketing # Desktop Publishing',
  'G'=> 'Calculus'
];
