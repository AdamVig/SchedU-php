<?php

require "/functions/run.php";

set_include_path($_SERVER[DOCUMENT_ROOT]."/res/php");

//=============================================================


//GET USER BY PHONE SQL
function getUserByPhone($phone)
{

    $database = new mysqli("localhost", "schedu", "schedu", "users");

    if ($database->connect_errno) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }

    $query = "SELECT * FROM users WHERE PhoneNumber = '" . $phone . "'";
    $result = $database->query($query);

    $output = mysqli_fetch_assoc($result);

    if (empty($output)) {
        $output = false;
    } else {
        if ($output['Membership'] != "premium") {
            $output = false;
        }
    }

    return $output;
}


//=============================================================


//PARSE MESSAGE
function parseMessage($message)
{

    $output = "";
    $commandArrayList = array(
        //name of command = array(of, keywords, for, command)
        "tomorrow" => array("tomorrow", "tomorow", "tommorow", "tommorrow", "tomarrow", "tommarow", "tommarrow"), //includes possible mispellings
        "monday" => array("monday", "mon"), "tuesday" => array("tuesday", "tues"), "wednesday" => array("wednesday", "wed"), "thursday" => array("thursday", "thurs"), "friday" => array("friday", "fri"),
        "countdown" => array("days left", "graduation", "countdown"),
        //"christmas" => array("christmas", "xmas"),
        "commands" => array("commands", "command", "comands", "comand", "info", "help")
    );

    if (trim($message) == "tomorrow") {
        $output = "tomorrow";
    } else {
        foreach ($commandArrayList as $command => $keywordArray) {
            foreach ($keywordArray as $keyword) {

                //if message contains a keyword, return that value and break
                //stripos : case-insensitive search, ($haystack, $needle)

                if (stripos($message, $keyword) > -1) {
                    $output = $command;
                    break;
                }
            }
        }
    }

    return $output;
}


//=============================================================


//GENERATE RESPONSE
function generateResponse()
{
    //main function, creates response based on input

    $nl = "\n";
    $output;

    $phone = $_GET["From"];
    $phone = substr($phone, 2);
    $body = $_GET["Body"];

    //------------------------------------------
    //OPEN, WRITE TO, AND CLOSE INPUT LOG
    $inputlog = fopen("../logs/input.txt", "a");
    fwrite($inputlog, date('m-d-Y h:i:s a', strtotime('-5 hours')) . ", from " . $phone . " | ". $body  . $nl);
    fclose($inputlog);
    //------------------------------------------

    $weekday = date("N"); //1 for monday, 7 for sunday

    $currentTime = new DateTime('now', new DateTimeZone("EST"));
    $startTime = DateTime::createFromFormat('H:i a', "5:55 AM", new DateTimeZone("EST"));
    $endTime = DateTime::createFromFormat('H:i a', "6:15 AM", new DateTimeZone("EST"));

    if ($currentTime < $startTime || $currentTime > $endTime) {

        $userData = getUserByPhone($phone);

        if ($userData === false) { //not Premium or not a member

            $body = "Sorry, you must be a SchedU Premium member to use this feature. Get Premium at http://getschedu.com/pay";

        } else { //Premium member

            //------------------------------------------
            //INITIALIZE RESPONSE LOG
            $log = fopen("../logs/responselog.txt", "a");
            fwrite($log, $nl . str_repeat('-', 40) . $nl . date('m-d-Y h:i:s a', strtotime('-5 hours')) . $nl);
            //------------------------------------------

            $name = $userData['FirstName'];
            $school = $userData['School'];

            $command = parseMessage($body);
            $calendar = openCalendar();

            if ($command == "tomorrow") { //TOMORROW

                $isWeekend = ($weekday == 5 || $weekday == 6) ? true : false;

                if (!$isWeekend) {
                    $daySchedule = getDaySchedule('tomorrow', $calendar, $school);
                    $dayName = "Tomorrow";
                } else {
                    $daySchedule = getDaySchedule('Monday', $calendar, $school);
                    $dayName = "Monday";
                }

                //------------------------------------------
                //CREATE MESSAGE BODY
                $body  = "Hello " . $name . "," . $nl;

                //Check if there is no school
                if ($daySchedule == "No School") {

                    $body .= "There is no school ";

                    if ($isWeekend) {
                        $body .= "on Monday.";
                    } else {
                        $body .= "tomorrow.";
                    }

                    $body .= $nl;

                } else {
                    $body .= "$dayName is a day " . substr($daySchedule, 0, 2) . "." . $nl;
                    $body .= $nl;
                    $body .= makeSchedule($userData, $daySchedule, $school);
                }

                $body .= $nl;
                $body .= "SchedU";
                //------------------------------------------


            } else if ($command == "countdown") { //COUNTDOWN

                $today = new DateTime();

                if ($userData['Grade'] != "senior") {
                    $lastDay = DateTime::createFromFormat("F j, Y", "June 13, 2014");
                } else { //senior
                    $lastDay = DateTime::createFromFormat("F j, Y", "May 23, 2014");
                }

                $daysLeft = intval(getWorkingDays($today, $lastDay)) - 11;
                $body = "There are " . $daysLeft . " days of school left." . $nl;
                $body .= $nl;
                $body .= "&lt;3 SchedU";

            } else if ($command == "commands") {

                $body = "tomorrow: schedule for tomorrow" . $nl;
                $body .= "countdown: days left in the school year (even for seniors!)" . $nl;
                $body .= "Christmas: days until Christmas" . $nl;
                $body .= "any weekday (Monday or Mon): schedule for that day" . $nl . $nl;
                $body .= "&lt;3 SchedU";

            } else if ($command == "monday" || $command == "tuesday" || $command == "wednesday" || $command == "thursday" || $command == "friday") {

                $daySchedule = getDaySchedule($command, $calendar, $school);

                //------------------------------------------
                //CREATE MESSAGE BODY
                $body  = "Hello " . $name . "," . $nl;

                if ($daySchedule != "No School") { //is school on that day

                    $body .= ucfirst($command) . " is a day " . substr($daySchedule, 0, 2) . "." . $nl;
                    $body .= $nl;
                    $body .= makeSchedule($userData, $daySchedule, $school);
                    $body .= $nl;

                } else { //no school on that day

                    $body .= $nl;
                    $body .= "There is no school on $command!";
                    $body .= $nl;
                }

                $body .= "SchedU";
                //------------------------------------------

            } else { //command not recognized or no command

                $body = "That's not a command! For a list of commands, reply \"commands\".";

            }


            //------------------------------------------
            //USE TWILIO LIBRARY TO GENERATE TwiML
            $response = new Services_Twilio_Twiml();
            $response->message($body);
            //------------------------------------------

            $output = $response;

            //------------------------------------------
            //WRITE TO LOG
            $loginfo .= "Message sent to $name " . $userData['LastName'] . " at $phone.$nl";
            $loginfo .= $body . $nl;
            fwrite($log, $loginfo);
            fclose($log);
            //------------------------------------------

        } //End if userData == false
    } //End if early morning

    return $output;
}

echo generateResponse(); //output
