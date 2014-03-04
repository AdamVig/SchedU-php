<?php

require_once "../make-schedule.php";
require_once "../helpers.php";

//make sure Zend, Twitter, and Twilio are included:
set_include_path($_SERVER[DOCUMENT_ROOT]."/res/php");

//=============================================================


function getDaySchedule($day, $calendar, $school)
{

    //day : either 'today' or 'tomorrow'
    //calendar : calendar object
    //school : which school calendar to get event from
    //returns string of first event on calendar for today

    $myPhone = "5086884042";

    $output;

    switch ($school) {
        case 'nashoba':
            $schoolCalendar = 'v9frlvaj6qsdqjd4459snk3390@group.calendar.google.com';
            break;
        case 'bromfield':
            $schoolCalendar = 'idlpv74fvpm2ucm6f3n1e7s078@group.calendar.google.com';
            break;
        case 'hudson':
            $schoolCalendar = '2qp3eqbiiti0230bdvrih0gd50@group.calendar.google.com';
            break;
        case 'tahanto':
            $schoolCalendar = 'cmdr7e8e7c7pdi4mf6ef3pc8ec@group.calendar.google.com';
            break;
        default: //Fall back to Nashoba if unknown
            $schoolCalendar = 'v9frlvaj6qsdqjd4459snk3390@group.calendar.google.com';
    }

    //Create Start and End dates for query
    if ($day == 'today') {
        $datetime = new DateTime('now', new DateTimeZone("EST")); //create datetime object
    } else if ($day == 'tomorrow') {
        $datetime = new DateTime('tomorrow', new DateTimeZone("EST")); //create datetime object
    } else { //Input is string of day name
        $datetime = new DateTime("next $day", new DateTimeZone("EST"));
    }

    $startDate = $datetime->format('Y-m-d') . " 00:00:00";
    $endDate = $datetime->format('Y-m-d') . " 23:59:59";

    //Create query
    $query = $calendar->newEventQuery();
    $query->setUser($schoolCalendar); //which calendar
    $query->setOrderby('starttime');
    $query->setSortOrder('a'); //ascending
    $query->setStartMin($startDate);
    $query->setStartMax($endDate);

    // Get the event list (array)
    try {
        $output = $calendar->getCalendarEventFeed($query);
    } catch (Zend_Gdata_App_Exception $e) {
        sendToMe("Error getting calendar event."); //Report error
    }

    $output = $output[0]->title; //String

    return $output;
}


//=============================================================

/**
 * Retrieves table of custom messages from database
 * @return 2d assoc array or boolean containing all messages or false if no messages
 */
function getCustomMessages()
{

    $database = new mysqli("schedu.db", "adamvig", "122395IatW", "info");
    $query = "SELECT * FROM messages";
    $result = $database->query($query);
    $messages = array();

    while ($message = $result->fetch_assoc()) {
        array_push($messages, $message);

        //Decrement days
        $newDays = intval($message['Days']) - 1;
        if ($newDays == 0) {
            $query = "DELETE FROM messages WHERE ID=".$message['ID'];
        } else { //Still some days left
            $query = "UPDATE messages SET Days=$newDays WHERE ID=".$message['ID'];
        }

        $database->query($query);
    }

    if (empty($messages)) {
        $output = false;
    } else {
        $output = $messages;
    }

    return $output;

}


//=============================================================

/**
 * returns the appropriate message for the given parameters
 * @param  string         $uMembership user membership
 * @param  string         $uSchool     user school
 * @param  2d assoc array $messages    from getCustomMessages()
 * @return string                      appropriate message or empty string if no messages
*/
function decideCustomMessage($uMembership, $uSchool, $messages)
{

    $output = "";

    if ($messages) {

        foreach ($messages as $message) {
            $messageText = $message['Message'];
            $mMembership = $message['Members'];
            $mSchool = $message['Schools'];

            if (($mMembership == $uMembership && $mSchool == $uSchool) || //Both match
                ($mMembership == $uMembership && $mSchool == 'all')    || //Membership match
                ($mMembership == 'all' && $mSchool == $uSchool)        || //School match
                ($mMembership == 'all' && $mSchool == 'all')               //Both all
               ) {

                $output = $messageText;
            }
        }

        if (!empty($output)) {
            $output = "\r\n$output\r\n"; //Append newlines
        }
    }

    return $output;

}


//=============================================================


function makeSchedule($userData, $daySchedule, $school)
{
    //userData : array, contains grade, classes, etc.
    //daySchedule : string, raw title of calendar event of the day
    //returns : string, class list ready to be added to message body

    $output = "";

    //Abbreviate variables
    $u = $userData;
    $d = $daySchedule;

    switch ($school) {
        case 'nashoba':
            $output = makeNashoba($u, $d);
            break;
        case 'bromfield':
            $output = makeBromfield($u, $d);
            break;
        case 'hudson':
            $output = makeHudson($u, $d);
            break;
        case 'tahanto':
            $output = makeTahanto($u, $d);
            break;
        default: //Fall back to Nashoba if unknown
            $output = makeNashoba($u, $d);
    }

    return $output;
}


//=============================================================


function runScript()
{
    $debug = false;
    $sendToMe = false;

    $nl = "\r\n";
    $weekday = date("N"); //1 for monday, 7 for sunday

    if (($weekday != 6 && $weekday != 7) || $debug == true) { //saturday or sunday

        //------------------------------------------------------
        //GET DAY SCHEDULES
        $dayToGet = 'today';
        $calendar = openCalendar();
        $schools = array(
            "nashoba",
            "bromfield",
            "hudson",
            "tahanto"
        );
        $messages = getCustomMessages();
        $daySchedules = array();
        foreach ($schools as $schoolName) {
            $daySchedules[$schoolName] = getDaySchedule($dayToGet, $calendar, $schoolName);
        }
        //------------------------------------------------------

        if ($daySchedules["nashoba"] != "No School" ||
            $daySchedules["bromfield"] != "No School" ||
            $daySchedules["hudson"] != "No School" ||
            $daySchedules["tahanto"] != "No School") { //If any school has school today

            //------------------------------------------------------
            //START LOG
            $executionStart = new DateTime();
            $messagesSent = 0;
            $myPhone = "5086884042";
            $log = fopen("../logs/log.txt", "a");
            if (!$debug) {
                fwrite($log, $nl . $nl . str_repeat('#', 40) . $nl . date('m-d-Y') . $nl);
            }

            //GET TWILIO GOING
            require_once("twilio-php/Services/Twilio.php");
            $client = new Services_Twilio("AC4c45ba306f764d2327fe824ec0e46347", "5121fd9da17339d86bf624f9fabefebe");

            //OPEN AND QUERY DATABASE
            $database = new mysqli("schedu.db", "adamvig", "122395IatW", "users");
            $query = "SELECT * FROM users ORDER BY 'ID' ASC";
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
                        report($client, "makeSchedule for $school returned null value, uh oh!");
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
                        send($client, $body, $phone, $fromNumber);
                        $messagesSent++;
                        //------------------------------------------------------

                        //------------------------------------------------------
                        //WRITE TO LOG
                        $loginfo  = str_repeat('-', 40) . $nl;
                        $loginfo .= "Message " . $messagesSent . " sent to " . $name . " " . $userData['LastName'] . " using number " . $number . '.' . $nl;
                        $loginfo .= $body . $nl;
                        fwrite($log, $loginfo);
                        //------------------------------------------------------

                    } else { //if debug mode, log all and send to me

                        //------------------------------------------------------
                        //WRITE TO LOG
                        $loginfo  = str_repeat('-', 40) . $nl;
                        $loginfo .= "Message " . $messagesSent . " sent to " . $name . " " . $userData['LastName'] . " using number " . $number . '.' . $nl;
                        $loginfo .= $body . $nl;
                        fwrite($log, $loginfo);
                        //------------------------------------------------------

                        if ($userData['PhoneNumber'] == $myPhone && $sendToMe == true) {
                            report($client, $body);
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

            fclose($log);

            //------------------------------------------------------
            //REPORT INFORMATION
            $executionStop = new DateTime();
            $elapsedTime = date_diff($executionStop, $executionStart, true); //absolute value = true
            $report = "The SchedU script took " . $elapsedTime->format('%H:%I:%S') . " to execute ".
                      "and sent " . getMessagesSent($client) . " messages to " . $messagesSent . " users.";

            if ($debug == false) {
                report($client, $report);
            }
            //------------------------------------------------------

        } //end if no school
    } //end if weekend
} //end runScript


//####################################################################
//OPEN THINGS


//OPEN CALENDAR
function openCalendar()
{
    $myUsername = "GetSChedU@gmail.com";
    $myPassword = "G3tSch3dU";

    require_once 'Zend/Loader.php';
    Zend_Loader::loadClass('Zend_Gdata');
    Zend_Loader::loadClass('Zend_Gdata_AuthSub');
    Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
    Zend_Loader::loadClass('Zend_Gdata_Calendar');

    // Parameters for ClientAuth authentication
    $service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME;

    // Create an authenticated HTTP client
    $client = Zend_Gdata_ClientLogin::getHttpClient($myUsername, $myPassword, $service);

    $output = new Zend_Gdata_Calendar($client); //Create an instance of the Calendar service

    return $output;
}


//=============================================================


//SEND SMS
function sendToMe($body)
{
    require_once("twilio-php/Services/Twilio.php");
    $client = new Services_Twilio("AC4c45ba306f764d2327fe824ec0e46347", "5121fd9da17339d86bf624f9fabefebe");
    report($client, $body);
}


//=============================================================


//TWEET
function tweet($tweetBody)
{

    require_once('TwitterAPIExchange.php');

    $settings = array(
        'oauth_access_token' => "1897135681-pdfl20Jt3KiucKg03nPqUWxLkydt5US5hFsTGbw",
        'oauth_access_token_secret' => "xiN3RORM4lGgo6nzeejiHUGVSw3ZvLREtgaeyI5TaL9h2",
        'consumer_key' => "Ui9e8o6WKLtNNGxs3qqaOA",
        'consumer_secret' => "rfs1DsS54ScUC42clfVDthcFROCJc7tYbYSmlQ9k9c"
    );

    $url = 'https://api.twitter.com/1.1/statuses/update.json';
    $requestMethod = 'POST';

    $postfields = array(
        'status' => $tweetBody
    );

    $twitter = new TwitterAPIExchange($settings);
    $twitter->buildOauth($url, $requestMethod)
            ->setPostfields($postfields)
            ->performRequest();

}
