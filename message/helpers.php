<?php

set_include_path($_SERVER[DOCUMENT_ROOT]."res");


/**
 * Creates message for sending
 * @param assoc array        $content contains greeting, todayIs, and body of message
 * @param new line character $nl      chosen newline, \r\n or <br>
 * @param string             $name    name of person
 * @return string                     message body
 */
function makeMessage($content, $nl, $name)
{
    $greeting = $content['greeting'];
    $todayIs = $content['todayIs'];
    $body = $content['body'];
    
    $message = $greeting." ".$name.",".$nl.
               "Today is ".$todayIs.".".$nl;
    foreach($body as $line) {
        if ($line != "") {
            $message .= $nl.$line;   
        }
    }
    $message .= $nl.$nl."<3 SchedU";
    return $message;
}


/**
 * Sends messages to users in bulk
 * @param assoc array $who     contains members and schools to send to
 * @param assoc array $content contains greeting, todayIs, and body of message
 * @return none                none
 */
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
    $nl = "\r\n";
    $school = $who['school'];
    $members = $who['members'];

    //------------------------------------------------------
    //GET TWILIO GOING
    require_once("php/twilio-php/Services/Twilio.php");

    $AccountSid = "AC4c45ba306f764d2327fe824ec0e46347";
    $AuthToken = "5121fd9da17339d86bf624f9fabefebe";

    $client = new Services_Twilio($AccountSid, $AuthToken);
    //------------------------------------------------------
    

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

        $body = makeMessage($content, $nl, $name);

        if ($debug) { //Debug mode
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
        } //End else
    } //End while database
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
 * Returns differences between form info and database info
 * @param  assoc array    $post     $_POST from last page
 * @param  MySQLi result  $messages Messages table from database
 * @return 2d assoc array           ID and everything that needs to be updated
 */
function getDiff($post, $messages)
{

    $output = array();

    //Iterate over MySQLi object, messages
    while ($row = $messages->fetch_assoc()) {
        $num = $row['ID'];
        $message = $post[$num]; //Current message from post
        $add = array('ID' => $num) + array_diff_assoc($message, $row); //Append ID to differences

        //If add has more than just ID in it
        if (count($add) > 1) {
            array_push($output, $add); //Add current message ID+differences to output array
        }

    } //End while rows in object

    //If output contains an array and it has more than one element
    if (count($output[0]) > 1) {
        return $output;
    }

}


/**
 * Updates data back into database
 * @param  2d assoc array $data contains differences between database
 *                  and form info, one array per message
 * @return boolean        true if success, false if failure to update
 */
function update($data)
{

    $database = new mysqli("schedu.db", "adamvig", "122395IatW", "info");
    $results = array();

    //Connection error
    if ($mysqli->connect_errno) {
        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }

    foreach ($data as $message) {

        $i = 0;
        $query = "UPDATE messages SET";

        foreach ($message as $column => $value) {

            if ($column != 'ID') {
                if ($i > 0) { //If not first or last
                    $query .= ","; //Add comma
                }

                $value = $database->real_escape_string($value);
                $query .= " $column='$value'";
                $i++;
            }//End if column = ID
        } //End foreach message as column -> value

        $query .= " WHERE ID=" . $message['ID'];
        $result = $database->query($query);
        array_push($results, $result);
    } //End foreach data as message

    //If false is in results, success = false
    $success = in_array(false, $results) ? false : true;

    return $success;

}


/**
 * Adds a new message into the messages table, info database
 * @param assoc array $message contains body, days, members, schools
 * @return none                none
 */
function insert($message)
{
    $database = new mysqli("schedu.db", "adamvig", "122395IatW", "info");

    //Connection error
    if ($database->connect_errno) {
        echo "Failed to connect to MySQL: (" . $database->connect_errno . ") " . $database->connect_error;
    }

    $query = "INSERT INTO messages SET";
    $i = 0;
    foreach ($message as $column => $value) {

        if ($i > 0) { //If not first or last
            $query .= ","; //Add comma
        }

        $value = $database->real_escape_string($value);
        $query .= " $column='$value'";
        $i++;
    } //End foreach message as column -> value

    $result = $database->query($query);

    if ($database->error) {
        echo "Database error: " . $database->error;
    }

}


/**
  * Creates option tags for html <select>
  * @param  array $data contains option content
  * @param  assoc array $row  current message from database
  * @param  string $type what these options are (for checking with row)
  * @return null       echoes output as it goes
  */
function generateOptions($data, $row, $type)
{
    foreach ($data as $current) {
        echo "<option value=\"$current\"";
        if ($row[$type] == $current) {
            echo 'selected';
        }
        echo '>'.ucfirst($current).'</option>';
    }
}



function auth($user, $pass) 
{
    $correct = false;
    $corrUser = "adam";
    $corrPass = "s3nd1t";
    if ($user == $corrUser && $pass == $corrPass) {
        $correct = true;
    }
    return $correct;
}