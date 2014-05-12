<?php
require_once(__DIR__."/globals.php");

$twilio = new Services_Twilio("AC4c45ba306f764d2327fe824ec0e46347", "5121fd9da17339d86bf624f9fabefebe");

if (php_sapi_name() === 'cli') { //Run from command line, as in cron job

    $variables = [ //CLI arguments
        'debug' => $argv[1],
        'sendToMe' => $argv[2]
    ];
    runScript($variables);

} else if ($_SERVER['REQUEST_METHOD'] == 'POST') { //POST

    $variables = [];

    if ($_POST == true) {
        $variables['debug'] = $_POST['debug'] === 'true' ? 'true' : 'false';
        $variables['sendToMe'] = $_POST['sendToMe'] === 'true' ? 'true' : 'false';
    } else {
        $variables['debug'] = 'false';
        $variables['sendToMe'] = 'false';
    }

    runScript($_POST);

} else if ($_SERVER['REQUEST_METHOD'] == 'GET') { //GET
    
    $variables = [];

    if ($_GET == true) {
        $variables['debug'] = $_GET['debug'] === 'true' ? 'true' : 'false';
        $variables['sendToMe'] = $_GET['sendToMe'] === 'true' ? 'true' : 'false';
    } else {
        $variables['debug'] = 'false';
        $variables['sendToMe'] = 'false';
    }

    runScript($variables);
}

function runScript($variables)
{
    if (isset($variables)) {
        $debug = $variables['debug'] === 'true' ? true : false; //Set to actual boolean values
        $sendToMe = $variables['sendToMe'] === 'true' ? true : false; //Set to actual boolean values
    }

    $nl = "\r\n";

    if ((date("N") != 6 && date("N") != 7) || $debug == true) { //saturday or sunday

        //------------------------------------------------------
        //GET DAY SCHEDULES AND CUSTOM MESSAGES
        $messages = getCustomMessages();
       
        if ($debug == true) {
            $daySchedule = [
                'Schedule' => 'A1',
                'Classes' => '',
                'Special' => ''
            ];
        } else {
            $daySchedule = getSchedule('nashoba');
        }
        //------------------------------------------------------

        if ($daySchedule['Special'] != "No School") { //If any school has school today
            //------------------------------------------------------
            //START LOG
            $executionStart = new DateTime();
            $messagesSent = 0;
            $myPhone = "5086884042";
            // $logText = "$nl . $nl . str_repeat('#', 40) . $nl . date('m-d-Y') . $nl";

            //Get Guzzle going
            $accountId = 'AC4c45ba306f764d2327fe824ec0e46347';
            $accountKey = '5121fd9da17339d86bf624f9fabefebe';
            $url = "https://$accountId:$accountKey@api.twilio.com/2010-04-01/Accounts/$accountId/Messages";
            $guzzle = new GuzzleHttp\Client();
            $requests = [];

            //Get Twilio going
            $twilio = new Services_Twilio("AC4c45ba306f764d2327fe824ec0e46347", "5121fd9da17339d86bf624f9fabefebe");

            //OPEN AND QUERY DATABASE
            $database = new mysqli(DB_HOST, DB_USER, DB_PASS, 'users');
            $query = "SELECT * FROM beta";
            $result = $database->query($query);
            //------------------------------------------------------
            

            //------------------------------------------------------
            //GO THROUGH DATABASE ARRAY
            while ($userData = $result->fetch_assoc()) {

                $name = $userData['FirstName'];
                $phone = $userData['PhoneNumber'];
                $membership = $userData['Membership'];
                $school = $userData['School'];

                $schedule = makeNashoba($userData, $daySchedule);

                if ($schedule == "") {
                    break; //Get out of while loop, finish up other stuff
                }

                //------------------------------------------------------
                //CREATE BODY
                $body  = "Good morning " . $name . "," . $nl;
                $body .= "Today is a day ";
                $body .= $daySchedule['Schedule'];
                $body .= "." . $nl . $nl;
                $body .= $schedule;
                $body .= decideCustomMessage($membership, $school, $messages);
                $body .= $nl;
                $body .= "<3 SchedU";
                //------------------------------------------------------

                //Decide which phone number to use
                global $numbers;
                $fromNumber = $numbers[$userData['Number']];

                //------------------------------------------------------
                //SEND SMS
                if ($debug == false) { //if normal mode, send normally

                    //------------------------------------------------------
                    //SEND MESSAGE
                    $request = $guzzle->createRequest('POST', $url, [
                        'body' => [
                            'From' => '+1'.$fromNumber,
                            'To' => '+1'.$phone,
                            'Body' => $body
                        ]
                    ]);
                    array_push($requests, $request);
                    $messagesSent++;
                    //------------------------------------------------------

                    //------------------------------------------------------
                    //WRITE TO LOG
                    // $logText .= str_repeat('-', 40) . $nl;
                    // $logText .= "Message " . $messagesSent . " sent to " . $name . " " . $userData['LastName'] . " using number " . $phone . '.' . $nl;
                    // $logText .= $body . $nl;
                    //------------------------------------------------------

                } else { //if debug mode, log all and send to me

                    //------------------------------------------------------
                    //WRITE TO LOG
                    // $logText .= str_repeat('-', 40) . $nl;
                    // $logText .= "Message " . $messagesSent . " sent to " . $name . " " . $userData['LastName'] . " using number " . $phone . '.' . $nl;
                    // $logText .= $body . $nl;
                    //------------------------------------------------------

                    if ($userData['PhoneNumber'] == $myPhone && $sendToMe == true) {
                        report($twilio, $body);
                    } else if ($userData['PhoneNumber'] == $myPhone && $sendToMe == false) {
                        $body = str_replace("\r\n", "<br>", $body);
                        echo $body . "<br>";
                    }
                }
                //------------------------------------------------------


                //------------------------------------------------------
                //COMPOSE TWEET
                /*
                $tweetBody = "";
                foreach ($schools as $school) {
                    $tweetBody .= ucfirst($school);
                    $tweetBody .= ": " . $daySchedule[$Schedule];
                    if ($school != end($schools)) {
                        $tweetBody .= $nl; //Add newline if not last element
                    }
                }
                if ($debug == false) {
                    tweet($tweetBody);
                }*/
                //------------------------------------------------------
            }//End while rows in database
            //------------------------------------------------------


            //------------------------------------------------------
            //SEND MESSAGES
            $guzzle->sendAll($requests, [
                'error' => function (ErrorEvent $event) use (&$errors) {
                    $errors[] = $event;
                }
            ]);
            //------------------------------------------------------


            //------------------------------------------------------
            //REPORT ERRORS
            if ($errors) {
                $message = "SchedU Errors Today:<br>";
                foreach ($errors as $error) {
                    $message .= $error."<br>";
                }
                //mail("adam@getschedu.com", "SchedU Errors Today", $message);
            }
            //------------------------------------------------------
            

            //------------------------------------------------------
            //LOG
            if (!$debug) {
                // $log = fopen("../logs/log.txt", "a");
                // fwrite($log, $logText);
                // fclose($log);
            }
            //------------------------------------------------------


            //------------------------------------------------------
            //REPORT INFORMATION
            $executionStop = new DateTime();
            $elapsedTime = date_diff($executionStop, $executionStart, true); //absolute value = true
            $report = "The SchedU script took " . $elapsedTime->format('%H:%I:%S') . " to execute ".
                      "and sent " . getMessagesSent($twilio) . " messages to " . $messagesSent . " users.";

            if ($debug == false) {
                report($twilio, $report);
            }
            //------------------------------------------------------

        } //end if no school
    } //end if weekend
} //end runScript
