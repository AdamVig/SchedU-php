<?php
error_reporting(-1);
ini_set('display_errors', 1);
ini_set('html_errors', 1);
require_once "../vendor/autoload.php";
// require_once "../make-schedule.php";
// require_once "../index.php";
//$client = new Services_Twilio("AC4c45ba306f764d2327fe824ec0e46347", "5121fd9da17339d86bf624f9fabefebe");

$accountId = 'AC4c45ba306f764d2327fe824ec0e46347';
$accountKey = '5121fd9da17339d86bf624f9fabefebe';
$url = "https://$accountId:$accountKey@api.twilio.com/2010-04-01/Accounts/$accountId/Messages";
$client = new GuzzleHttp\Client();

$data = [
  'body' => [
    'From' => '+16505427238',
    'To' => '+15086884042',
    'Body' => 'Testing!'
  ]
];

//$client->post($url, $data)->send();

$requests = [
    $client->createRequest('POST', $url, [
        'body' => [
            'From' => '+16505427238',
            'To' => '+15086884042',
            'Body' => 'Testing!'
        ]
    ]),
    $client->createRequest('POST', $url, [
        'body' => [
            'From' => '+16505427238',
            'To' => '+15083080173',
            'Body' => 'Testing!'
        ]
    ]),
    $client->createRequest('POST', $url, [
        'body' => [
            'From' => '+16505427238',
            'To' => '+19788702867',
            'Body' => 'Testing!'
        ]
    ])
];

$client->sendAll($requests, [
    'error' => function (ErrorEvent $event) use (&$errors) {
        $errors[] = $event;
    }
]);

$message = "SchedU Errors Today:<br>";
foreach ($errors as $error) {
    $message .= $error."<br>";
}
mail("adam@getschedu.com", "SchedU Errors Today", $message);

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
