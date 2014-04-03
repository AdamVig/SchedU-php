<?php

require "../helpers.php";

function runScript()
{
    $debug = true;
    $sendToMe = false;

    $nl = "\r\n";
    $weekday = date("N"); //1 for monday, 7 for sunday

    if (($weekday != 6 && $weekday != 7) || $debug == true) { //saturday or sunday

        //------------------------------------------------------
        //GET DAY SCHEDULES
        $dayToGet = 'today';
        $schools = array(
            "nashoba"
        );
        $messages = getCustomMessages();
        $daySchedules = array();
        foreach ($schools as $schoolName) {
            $daySchedules[$schoolName] = getSchedule($schoolName);
        }
        //------------------------------------------------------

        if ($daySchedules['nashoba']['Special'] != "No School" /*||
            $daySchedules["bromfield"] != "No School" ||
            $daySchedules["hudson"] != "No School" ||
            $daySchedules["tahanto"] != "No School"*/) { //If any school has school today

            //------------------------------------------------------
            //START LOG
            $executionStart = new DateTime();
            $messagesSent = 0;
            $myPhone = "5086884042";
            $logText = "$nl . $nl . str_repeat('#', 40) . $nl . date('m-d-Y') . $nl";

            //Get Guzzle going
            require_once "vendor/autoload.php";
            $accountId = 'AC4c45ba306f764d2327fe824ec0e46347';
            $accountKey = '5121fd9da17339d86bf624f9fabefebe';
            $url = "https://$accountId:$accountKey@api.twilio.com/2010-04-01/Accounts/$accountId/Messages";
            $guzzle = new GuzzleHttp\Client();
            $requests = [];

            //Get Twilio going
            $twilio;

            //OPEN AND QUERY DATABASE
            $db = [
                'host' => 'localhost',
                'username' => 'schedu',
                'password' => 'schedu',
                'database' => 'beta'
            ];
            $database = new mysqli($db['host'], $db['username'], $db['password'], $db['database']);
            $query = "SELECT * FROM users";
            $result = $database->query($query);
            //------------------------------------------------------


            //------------------------------------------------------
            //GO THROUGH DATABASE ARRAY
            while ($userData = $result->fetch_assoc()) {

                $name = $userData['FirstName'];
                $phone = $userData['PhoneNumber'];
                $membership = $userData['Membership'];
                $school = $userData['School'];

                if ($daySchedules[$school] != "No School") {

                    $schedule = makeSchedule($userData, $daySchedules[$school], $school);

                    if ($schedule == "") {
                        report($twilio, "makeSchedule for $school returned null value, uh oh!");
                        break; //Get out of while loop, finish up other stuff
                    }

                    //------------------------------------------------------
                    //CREATE BODY
                    $body  = "Good morning " . $name . "," . $nl;

                    if ($school == "nashoba" || $school == "hudson") {
                        $body .= "Today is a day ";
                        $body .= substr($daySchedules[$school], 0, 2);
                    } else {
                        $body .= "Today is ";

                        //Decide which article to use
                        $body .= (preg_match('/[aefAEF]/', $daySchedules[$school])) ? "an " : "a ";

                        $body .= substr($daySchedules[$school], 0, 1);
                        $body .= " day";
                    }

                    $body .= "." . $nl . $nl;
                    $body .= $schedule;
                    $body .= decideCustomMessage($membership, $school, $messages);
                    $body .= $nl;
                    $body .= "<3 SchedU";
                    //------------------------------------------------------


                    //------------------------------------------------------
                    //DECIDE WHICH PHONE NUMBER TO USE
                    require_once("../numbers.php");
                    $fromNumber = $numbers[$userData['Number']];
                    //------------------------------------------------------


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
                        $logText .= str_repeat('-', 40) . $nl;
                        $logText .= "Message " . $messagesSent . " sent to " . $name . " " . $userData['LastName'] . " using number " . $number . '.' . $nl;
                        $logText .= $body . $nl;
                        //------------------------------------------------------

                    } else { //if debug mode, log all and send to me

                        //------------------------------------------------------
                        //WRITE TO LOG
                        $logText .= str_repeat('-', 40) . $nl;
                        $logText .= "Message " . $messagesSent . " sent to " . $name . " " . $userData['LastName'] . " using number " . $number . '.' . $nl;
                        $logText .= $body . $nl;
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
                    $tweetBody = "";
                    foreach ($schools as $school) {
                        $tweetBody .= ucfirst($school);
                        $tweetBody .= ": " . $daySchedules[$school];
                        if ($school != end($schools)) {
                            $tweetBody .= $nl; //Add newline if not last element
                        }
                    }
                    if ($debug == false) {
                        tweet($tweetBody);
                    }
                    //------------------------------------------------------
                }//End if school
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
            $message = "SchedU Errors Today:<br>";
            foreach ($errors as $error) {
                $message .= $error."<br>";
            }
            mail("adam@getschedu.com", "SchedU Errors Today", $message);
            //------------------------------------------------------
            

            //------------------------------------------------------
            //LOG
            if (!$debug) {
            $log = fopen("../logs/log.txt", "a");
                fwrite($log, $logText);
                fclose($log);
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
