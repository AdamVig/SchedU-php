<?php
require_once("./globals.php");
require PATH."/functions/helpers.php";
// require_once "../make-schedule.php";
// require_once "../index.php";
//$client = new Services_Twilio("AC4c45ba306f764d2327fe824ec0e46347", "5121fd9da17339d86bf624f9fabefebe");

print_r($_POST);
$p = $_POST;
$a = $p['variables'];
$b = $a['debug'];
echo gettype($b);

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
