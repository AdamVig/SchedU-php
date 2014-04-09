<?php
require_once("./globals.php");
require PATH."/vendor/autoload.php";

function getUserDataPhone($phone)
{
    $database = new mysqli("localhost", "schedu", "schedu", "users");
    $query = "SELECT * FROM users WHERE PhoneNumber=$phone";
    $result = $database->query($query);
    return $result->fetch_assoc();
}

function report($twilio, $body)
{
    $sms = $twilio->account->messages->sendMessage(
        "+16505427238", //From
        "+15086884042", //To
        $body           //Body
    );
}

function send($twilio, $body, $to, $from)
{
    $sms = $twilio->account->messages->sendMessage(
        "+1".$from, //From
        "+1".$to, //To
        $body       //Body
    );
}

function getSchedule($school)
{
    $database = new mysqli("localhost", "schedu", "schedu", "info");
    $date = new DateTime;
    $query = "SELECT * FROM calendar WHERE Date='".date('Y-m-d')."'";
    $result = $database->query($query);
    $daySchedule = $result->fetch_assoc();
    return $daySchedule;
}


//SEND SMS
function sendToMe($body)
{
    $twilio = new Services_Twilio("AC4c45ba306f764d2327fe824ec0e46347", "5121fd9da17339d86bf624f9fabefebe");
    report($twilio, $body);
}

function makeSchedule($userData, $daySchedule, $school)
{
    //userData : array, contains grade, classes, etc.
    //daySchedule : string, raw title of calendar event of the day
    //returns : string, class list ready to be added to message body

    $output = "";

    require_once(PATH."/functions/make-schedule.php");

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


/**
 * Retrieves table of custom messages from database
 * @return 2d assoc array or boolean containing all messages or false if no messages
 */
function getCustomMessages()
{

    $database = new mysqli("localhost", "schedu", "schedu", "users");
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


//TWEET
function tweet($tweetBody)
{

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
    $AccountSid = "AC4c45ba306f764d2327fe824ec0e46347";
    $AuthToken = "5121fd9da17339d86bf624f9fabefebe";
    $twilio = new Services_Twilio($AccountSid, $AuthToken);
    //------------------------------------------------------

    $nl = "\r\n";

    //------------------------------------------------------
    //QUERY DATABASE
    $database = new mysqli("localhost", "schedu", "schedu", "users");

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

    require_once(PATH."/functions/numbers.php");

    //LOOP THROUGH DATABASE
    while ($userData = $result->fetch_assoc()) {
        $name = $userData['FirstName'];
        $phone = $userData['PhoneNumber'];
        $number = $numbers[$userData['Number']];

        $body = constructBody($content, $name);

        if ($debug) {
            if ($phone == "5086884042" && $name == "Adam") {
                report($twilio, $body);
            }
        } else { //Normal mode
            $sms = $twilio->account->messages->sendMessage(
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

    $database = new mysqli("localhost", "schedu", "schedu", "info");
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

    $database = new mysqli("localhost", "schedu", "schedu", "info");
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


function getMessagesSent($twilio)
{
    $smsRecord = $twilio->account->usage_records->today->getCategory('sms-outbound');
    $messagesSent = $smsRecord->usage;
    return $messagesSent;
}
