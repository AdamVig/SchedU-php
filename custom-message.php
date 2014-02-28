<?php

set_include_path(get_include_path().PATH_SEPARATOR.$_SERVER[DOCUMENT_ROOT]."/res/php");

//------------------------------------------------------
//GET TWILIO GOING
require_once("twilio-php/Services/Twilio.php");
 
$AccountSid = "AC4c45ba306f764d2327fe824ec0e46347";
$AuthToken = "5121fd9da17339d86bf624f9fabefebe";
 
$client = new Services_Twilio($AccountSid, $AuthToken);
//------------------------------------------------------

$debug = false;
$nl = "\r\n";

//------------------------------------------------------
//GET DATABASE GOING
$database = new mysqli("schedu.db","adamvig", "122395IatW","users");
if (mysqli_connect_errno()) echo "Failed to connect to MySQL: " . mysqli_connect_error();
$query = "SELECT * FROM users ORDER BY ID ASC";
$result = $database->query($query);
//------------------------------------------------------


//LOOP THROUGH DATABASE
while ($userData = $result->fetch_assoc()) {
	
	$f = $userData['FirstName'];
	$phone = $userData['PhoneNumber'];
	
	//---------------------
	//BODY
	$body = "Good afternoon $f,".$nl;
	$body .= "There will be no school tomorrow, sleep in!".$nl;
	$body .= $nl;
	$body .= "Fun snow activities".$nl;
	$body .= "1. go sledding".$nl;
	$body .= "2. have a snowball fight".$nl;
	$body .= "3. shred some powder".$nl;
	$body .= "4. curl up by the fire".$nl;
	$body .= "5. go ice skating".$nl;
	$body .= "6. Netflix".$nl;
	$body .= $nl;
	$body .= "<3 SchedU";
	//---------------------

	
	//IF NORMAL MODE
	if (!$debug) {
		$sms = $client->account->messages->sendMessage(
			"+12075172433", // from
			"+1" . $phone, // to
			$body 		  //body
		);
		
	//ELSE DEBUG MODE
	} else {
		if ($userData['PhoneNumber'] == '5086884042') {	//If it's me, send message
			$sms = $client->account->messages->sendMessage(
				"+12075172433", // from
				"+1" . $phone, // to
				$body 		  //body
			);
		}//End if me
	}//End if debug
	
}//End while rows in database