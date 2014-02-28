<?php

require './helpers.php';

//Who to send message to
$who = array(
    'schools' => 'all',
    'members' => 'all'
);

//Body of message
$body = array(
    0 => '3 Interesting Facts about Snow:',
    1 => "(not that it's snowing)",
    2 => "1. The world's largest snowflake was 15 inches across and 8 inches thick!",
    3 => "2. 80% of all the fresh water on earth is frozen.",
    4 => "3. A blizzard is when visibility is less than 1/4 of a mile, it lasts for three hours, and winds are over 35 MPH."
);

//Parts of message
$content = array(
    'greeting' => 'Good morning',
    'todayIs' => 'a snow day',
    'body' => $body
);

//MySQLi object of messages table
$messages = getMessages();
$result = true;

//Arrays to iterate over in option creation below
$days = array('1','2','3','4','5');
$members = array('all','free','premium');
$schools = getSchools();
array_unshift($schools, 'all'); //Add 'all' option to front of array

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($data = getDiff($_POST, $messages)) {
        $result = update($data);
    }

    $newMessage = $_POST['new'];

    if (!empty($newMessage['Message'])) {
        insert($newMessage);
    }

    $messages = getMessages(); //Get updated messages from database
}

session_start();

if ($_SESSION['authenticated'] != true) {
    header("LOCATION: http://getschedu.com/exec/message/index.php");
} else {
    //Show page content, user is authenticated
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Custom Message &middot; SchedU</title>
        <meta name="description" content="Get SchedUcated with SchedU, a service that delivers your schedule by text message every morning.">
        <meta name="author" content="SchedU">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <link href="/res/css/bootstrap.min.css" rel="stylesheet">
        <link href="/res/css/styles.css" rel="stylesheet">
        <link rel="icon" href="/res/ico/favicon.png">
        <style>
            input[type="submit"] {
                margin:15px;
            }
            #send .inline {
                display:inline;
                width:190px;
            }
            #send .body input {
                width:250px;
            }
        </style>
    </head>
    <body>
        <? require 'html/navigation.html'; ?>
        <div id="content">
            <section id="title">
                <div class="container">
                    <h1>Custom Message</h1>
                    <hr class="line">
                </div>
            </section>

            <? if ($result == false) { ?>

            <section id="error">
                <div class="container">
                    <div class="panel panel-danger">
                        <div class="panel-heading panel-title-big">
                            Oops! That's an error.
                        </div>
                        <div class="panel-body">
                            <p class="text-danger">
                                Something went wrong with the database update.<br>
                                <br>
                                Please press the back button in your browser to try again.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <? } else { ?>

            <section id="table">
                <div class="container">
                    <h2>Add-On Message</h2>
                    <form method="POST">
                        <table class="table table-responsive">
                            <tr>
                                <th>Message</th>
                                <th>Days</th>
                                <th>Members</th>
                                <th>Schools</th>
                            </tr>

                            <?
                            while ($row = $messages->fetch_assoc()) {
                                $i = $row['ID'];
                            ?>
                            <tr class="form-group">
                                <td>
                                    <textarea name="<?php echo $i ?>[Message]" class="form-control message-field" required rows="3" placeholder="Enter a message"><? echo $row['Message'] ?></textarea>
                                </td>
                                <td class="form-group">
                                    <select class="form-control" name="<? echo $i ?>[Days]">
                                        <?php generateOptions($days, $row, "Days"); ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="<? echo $i ?>[Members]">
                                        <?php generateOptions($members, $row, "Members"); ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="<? echo $i ?>[Schools]">
                                        <?php generateOptions($schools, $row, "Schools"); ?>
                                    </select>
                                </td>
                            </tr>
                            <? } ?>

                            <tr class="form-group">
                                <td>
                                    <textarea name="new[Message]" class="form-control message-field" id="new-message" rows="3" placeholder="Enter a message"></textarea>
                                    <span id="counter"></span>
                                </td>
                                <td class="form-group">
                                    <select class="form-control" name="new[Days]">
                                        <option value="" disabled selected>Days</option>
                                        <?php generateOptions($days, $row, "Days"); ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="new[Members]">
                                        <option value="" disabled selected>Members</option>
                                        <?php generateOptions($members, $row, "Members"); ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="new[Schools]">
                                        <option value="" disabled selected>Schools</option>
                                        <?php generateOptions($schools, $row, "Schools"); ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" class="btn btn-large btn-green pull-right" value="Submit">
                    </form>
                </div>
            </section>

            <section id="snowday">
                <div class="container">
                    <h2>Full Message</h2>
                    <form method="POST" action="send.php" id="send">
                        <div class="row">
                            <div class="col-lg-4 form-group">
                            </div>
                            <div class="col-lg-4">
                                <input type="text" name="content[greeting]" class="form-control inline" placeholder="Good morning"> Adam, <br>
                                Today is <input type="text" name="content[todayIs]" class="form-control inline" placeholder="a snow day">.
                                <br><br>
                                <div class="body">
                                    <input type="text" name="content[body][1]" class="form-control" placeholder="Line 1">
                                    <input type="text" name="content[body][2]" class="form-control" placeholder="Line 2">
                                    <input type="text" name="content[body][3]" class="form-control" placeholder="Line 3">
                                    <input type="text" name="content[body][4]" class="form-control" placeholder="Line 4">
                                    <input type="text" name="content[body][5]" class="form-control" placeholder="Line 5">
                                    <input type="text" name="content[body][6]" class="form-control" placeholder="Line 6">
                                    <input type="text" name="content[body][7]" class="form-control" placeholder="Line 7">
                                </div>
                                <br>
                                <3 SchedU
                            </div>
                            <div class="col-lg-4 form-group">
                                <select class="form-control" name="who[members]">
                                    <option value="" disabled selected>Members</option>
                                    <?php generateOptions($members, $row, "Members"); ?>
                                </select>
                                <select class="form-control" name="who[schools]">
                                    <option value="" disabled selected>Schools</option>
                                    <?php generateOptions($schools, $row, "Schools"); ?>
                                </select>
                            </div>
                        </div>
                        <button id="send-button" data-toggle="modal" data-target="#confirm" type="button" class="btn btn-large btn-green pull-right">Send</button>

                        <div class="modal fade" id="confirm">
                          <div class="modal-dialog">
                            <div class="modal-content">
                              <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title" id="myModalLabel">Confirm</h4>
                              </div>
                              <div class="modal-body">
                                <h3>Are you sure that you want to send this message</h3>
                                <br>
                                <?php echo makeMessage($content, "<br>", "Adam"); ?>
                                <br><br>
                                <h3>to <?php echo ucfirst($who['members']); ?> members at <?php echo ucfirst($who['schools']); ?> schools?</h3>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Send messages</button>
                              </div>
                            </div>
                          </div>
                        </div> <!-- end modal #confirm -->
                    </form>
                </div>
            </section>
            <? } ?>
        </div>
        <? require 'html/footer.html'; ?>
        <script src="/res/js/jquery-character-count.min.js"></script>
        <script>
        $(document).ready(function() {
          var options = {
            type: 'char', //or word
            count: 'down', //or up
            goal: '140', //or 'sky'
            text: 'true', //or false
            msg: "characters left",
            target: '#counter' //or false
          };
          $("#new-message").counter(options);
        });
        </script>
    </body>
</html>
<?php
} //End else authenticated
?>
