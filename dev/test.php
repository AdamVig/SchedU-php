<?php

require_once "../make-schedule.php";
require_once "../index.php";
require_once "twilio-php/Services/Twilio.php";
$client = new Services_Twilio("AC4c45ba306f764d2327fe824ec0e46347", "5121fd9da17339d86bf624f9fabefebe");

$u = array(
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
);

//$d = getDaySchedule('monday', openCalendar(), "tahanto");
$d = "7 Mc English,A,B,C";
echo str_replace("\r\n", "<br>", makeHudson($u, $d));
