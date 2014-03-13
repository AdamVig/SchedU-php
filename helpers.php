<?php

function getUserDataPhone($phone)
{

    $database = new mysqli("schedu.db", "adamvig", "122395IatW", "users");
    $query = "SELECT * FROM users WHERE PhoneNumber=$phone";
    $result = $database->query($query);
    return $result->fetch_assoc();
}

function report($client, $body)
{
    $sms = $client->account->messages->sendMessage(
        "+16505427238", //From
        "+15086884042", //To
        $body           //Body
    );
}

function send($client, $body, $to, $from)
{
    $sms = $client->account->messages->sendMessage(
        "+1".$from, //From
        "+1".$to, //To
        $body       //Body
    );
}

function sendMessageTo($who, $content)
{
    //who : array(members, school)
    //  members : string, premium/free
    //  school : string
    //content: array(greeting, todayIs, body)
    //  greeting : string, "Hey"
    //  todayIs : string, "a snow day"
    //  body : array(line1, line2, line3, line4, etc.)

    $debug = false;

    $school = $who['school'];
    $members = $who['members'];

    //------------------------------------------------------
    //GET TWILIO GOING
    require_once("php/twilio-php/Services/Twilio.php");

    $AccountSid = "AC4c45ba306f764d2327fe824ec0e46347";
    $AuthToken = "5121fd9da17339d86bf624f9fabefebe";

    $client = new Services_Twilio($AccountSid, $AuthToken);
    //------------------------------------------------------

    $nl = "\r\n";

    //------------------------------------------------------
    //QUERY DATABASE
    $database = new mysqli("schedu.db", "adamvig", "122395IatW", "users");

    $query = "SELECT * FROM users";

    //Add to query
    if ($school == "all" && $members != "all") {
        $query .= " WHERE Membership='$members'";
    } else if ($school != "all" && $members == "all") {
        $query .= " WHERE School='$school'";
    } else if ($school != "all" && $members != "all") {
        $query .= " WHERE School='$school' AND Membership='$members'";
    }


    $result = $database->query($query);

    if (!$result) {
        throw new Exception(mysqli_error($database).". Full query: [$query]");
    }
    //------------------------------------------------------

    require_once("../numbers.php");

    //LOOP THROUGH DATABASE
    while ($userData = $result->fetch_assoc()) {
        $name = $userData['FirstName'];
        $phone = $userData['PhoneNumber'];
        $number = $numbers[$userData['Number']];

        $body = constructBody($content, $name);

        if ($debug) {
            if ($phone == "5086884042" && $name == "Adam") {
                $sms = $client->account->messages->sendMessage(
                    "+1".$number, // from
                    "+1" . $phone, // to
                    $body         //body
                );
            }
        } else { //Normal mode
            $sms = $client->account->messages->sendMessage(
                "+1".$number, // from
                "+1".$phone, // to
                $body         //body
            );
        }
    }
}

function constructBody($content, $name)
{
    //content: array(greeting, todayIs, body)
    //  greeting : string, "Hey"
    //  todayIs : string, "a snow day"
    //  body : array(line1, line2, line3, line4, etc.)
    //name : string, "Adam"
    //returns string of body

    $nl = "\r\n";

    $greeting = $content['greeting'];
    $todayIs = $content['todayIs'];
    $body = $content['body'];
    $body = implode($nl, $body);

    $body = "$greeting $name,$nl".
            "Today is $todayIs.".$nl.
            $nl.
            "$body".$nl.
            $nl.
            "<3 SchedU";

    return $body;
}

/**
 * Get everything from messsages table in info database
 * @return 2d array containing all existing messages and their info
 */
function getMessages()
{

    $database = new mysqli("schedu.db", "adamvig", "122395IatW", "info");
    $query = "SELECT * FROM messages";
    $result = $database->query($query);
    if (!$result) {
        throw new Exception(mysqli_error($database).". Full query: [$query]");
    }
    return $result;

}

/**
 * Get schools from schools table in info database
 * @return array containing all schools, [1] => 'Nashoba'
 */
function getSchools()
{

    $database = new mysqli("schedu.db", "adamvig", "122395IatW", "info");
    $query = "SELECT * FROM schools";
    $result = $database->query($query);
    if (!$result) {
        throw new Exception(mysqli_error($database).". Full query: [$query]");
    }

    $schools = array();
    while ($row = $result->fetch_assoc()) {
        $school = $row['School'];
        array_push($schools, $school);
    }
    return $schools;
}

/**
 * Gets number of working days between startDate
 * and endDate
 * @param DateTime $startDate starting date
 * @param DateTime $endDate   ending date
 * @return string            formatted version of number of days
 */
function getWorkingDays(DateTime $startDate, DateTime $endDate)
{

    //The total number of days between the two dates. We compute the no. of seconds and divide it to 60*60*24
    //We add one to inlude both dates in the interval.
    $days = $startDate->diff($endDate, true)->days;

    $no_full_weeks = floor($days / 7);
    $no_remaining_days = fmod($days, 7);

    //It will return 1 if it's Monday,.. ,7 for Sunday
    $the_first_day_of_week = $startDate->format('N');
    $the_last_day_of_week = $endDate->format('N');

    //The two can be equal in leap years when february has 29 days, the equal sign is added here
    //In the first case the whole interval is within a week, in the second case the interval falls in two weeks.
    if ($the_first_day_of_week <= $the_last_day_of_week) {
        if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week) {
            $no_remaining_days--;
        }
        if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week) {
            $no_remaining_days--;
        }

    } else {

        // the day of the week for start is later than the day of the week for end
        if ($the_first_day_of_week == 7) {
            // if the start date is a Sunday, then we definitely subtract 1 day
            $no_remaining_days--;

            if ($the_last_day_of_week == 6) {
                // if the end date is a Saturday, then we subtract another day
                $no_remaining_days--;
            }
        } else {
            // the start date was a Saturday (or earlier), and the end date was (Mon..Fri)
            // so we skip an entire weekend and subtract 2 days
            $no_remaining_days -= 2;
        }
    }

    //The no. of business days is: (number of weeks between the two dates) * (5 working days) + the remainder
    //february in none leap years gave a remainder of 0 but still calculated weekends between first and last day, this is one way to fix it
    $workingDays = $no_full_weeks * 5;
    if ($no_remaining_days > 0) {
        $workingDays += $no_remaining_days;
    }

    $workingDays = number_format($workingDays, 0, '', '');

    return $workingDays;
}


function getMessagesSent($client)
{
    $smsRecord = $client->account->usage_records->today->getCategory('sms-outbound');
    $messagesSent = $smsRecord->usage;
    return $messagesSent;
}
