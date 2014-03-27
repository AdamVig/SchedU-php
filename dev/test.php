<?php
error_reporting(-1);
ini_set('display_errors', 1);
ini_set('html_errors', 1);
require_once "../make-schedule.php";
require_once "../index.php";
require_once "../vendor/autoload.php";
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
$d = "D Er A,D,E,F";

//report($client, "Testing from Heroku on March 20, minor change here!");
$client = new GuzzleHttp\Client(['base_url' => 'https://AC4c45ba306f764d2327fe824ec0e46347:5121fd9da17339d86bf624f9fabefebe@api.twilio.com/2010-04-01/Accounts/AC4c45ba306f764d2327fe824ec0e46347/Messages']);
$requests = [
    $client->createRequest('POST', [
	'headers' => [
		'From' => '+16505427238',
		'To' => '+15086884042',
		'Body' => 'Testing!'
	]
    ]),
    $client->createRequest('POST', [
        'headers' => [
                'From' => '+16505427238',
                'To' => '+15083080173',
                'Body' => 'Testing!'
        ]
    ]),
    $client->createRequest('POST', [
        'headers' => [
                'From' => '+16505427238',
                'To' => '+19788702867',
                'Body' => 'Testing!'
        ]
    ])
];

$client->sendAll($requests);
