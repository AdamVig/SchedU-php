<?php

//============================================================


function makeBromfield($userData, $daySchedule)
{

    $output = "";
    $dayLetterArray = array('A' => '1', 'B' => '2', 'C' => '3', 'D' => '4', 'E' => '5', 'F' => '6', 'G' => '7');

    //Parse day schedule into letter and number
    $dayLetter = substr($daySchedule, 0, 1);
    $dayNumber = $dayLetterArray[$dayLetter];
    $dayId = (strlen($daySchedule) > 2 ? substr($daySchedule, 2, 2) : false); //set day ID to string or false

    //DECIDE CLASS ORDER
    if ($dayId == false || $dayId == "Mc") { //If normal or activity period day

        if ($dayId == "Mc" && $userData['Grade'] == 'sophomore') {

            $description = "You have " . substr($daySchedule, 6) . " MCAS today.";
            $classOrder = array($description);

        } else { //Normal day
            switch ($dayLetter) {
                case "A":
                    $classOrder = array($userData['A'], $userData['B'], $userData['C'], $userData['D'], $userData['E'], $userData['F'], $userData['G']);
                    break;
                case "B":
                    $classOrder = array($userData['B'], $userData['C'], $userData['D'], $userData['E'], $userData['F'], $userData['G'], $userData['A']);
                    break;
                case "C":
                    $classOrder = array($userData['C'], $userData['D'], $userData['E'], $userData['F'], $userData['G'], $userData['A'], $userData['B']);
                    break;
                case "D":
                    $classOrder = array($userData['D'], $userData['E'], $userData['F'], $userData['G'], $userData['A'], $userData['B'], $userData['C']);
                    break;
                case "E":
                    $classOrder = array($userData['E'], $userData['F'], $userData['G'], $userData['A'], $userData['B'], $userData['C'], $userData['D']);
                    break;
                case "F":
                    $classOrder = array($userData['F'], $userData['G'], $userData['A'], $userData['B'], $userData['C'], $userData['D'], $userData['E']);
                    break;
                case "G":
                    $classOrder = array($userData['G'], $userData['A'], $userData['B'], $userData['C'], $userData['D'], $userData['E'], $userData['F']);
                    break;
            }//End switch
        } //End Else normal day

    } else { //Else special day

        if ($dayId == "Er" || //Early release: "A3 Er"
            $dayId == "Cu" || //Custom day: "A1 Cu A,B,C,D,E,F (lunch),G,special,special"
            $dayId == "Md" || //Midterms: "A8 Md A (exam),B,C,G (lunch),E,F"
            $dayId == "Fn") { //Finals: (TBD)

            $classOrder = array();
            $periodOrder = explode(",", substr($daySchedule, 6)); //delimiter, string

            //Go through letters in letter order array
            foreach ($periodOrder as $period) {

                //Just a letter
                if (strlen($period) == 1) {

                    $className = $userData[$period]; //Get corresponding class from userData

                //Lunch period
                } else if (stripos($period, "(lunch)") > -1) {

                    $period = substr($period, 0, 1); //Get only letter
                    $className = $userData[$period] . " (lunch)"; //Get corresponding class from userData

                //Exam period
                } else if (stripos($period, "(exam)") > -1) {

                    $period = substr($period, 0, 1); //Get only letter
                    $className = $userData[$period] . " (exam)"; //Get corresponding class from userData

                //Special period name ("Pep Rally")
                } else {
                    $className = $period;
                }

                array_push($classOrder, $className); //Add element to array

            }//End foreach period letter

        } else if ($dayId == "Sp") { //Special day: "H9 Sp Career Day"

            $classOrder = array(substr($daySchedule, 6));

        }
    }

    $i = 0; //What number period we're at

    //GO THROUGH CLASSES, DECIDE SEMESTER/NUMBER DAY
    foreach ($classOrder as $class) {

        $i++;

        $today = new DateTime;
        $change = new DateTime('2014-01-27 00:00:00');
        $semester = ($today < $change) ? 1 : 2;

        //DECIDE SEMESTER
        if (strpos($class, ' # ') !== false) { //if contains #

            $class = explode(" # ", $class); //break string into array at delimiter

            if ($semester == 1) { //if before semester change
                $class = $class[0]; //first semester class
            } else if ($semester == 2) { //else after semester change
                $class = $class[1]; //second semester class
            }

        } //end decide semester

        //DECIDE DAY NUMBER
        if (strpos($class, ' / ') !== false) { //If contains /

            $classArray = explode(" / ", $class); //Break string into array at delimiter

            $classOne = $classArray[0];
            $classTwo = $classArray[1];
            $classOneDays = $classArray[2];
            $classTwoDays = $classArray[3];

            if (strpos($classOneDays, $dayNumber) !== false) {
                $class = $classOne;
            } else if (strpos($classTwoDays, $dayNumber) !== false) {
                $class = $classTwo;
            } else {
                $class = "Study";
            }
        } //end decide day number

        //If it's the fifth class on a normal day
        if ($i == 5 && $dayId == false) {
            $class .= " (lunch)"; //Add "(lunch)" to class name
        }

        $output .= $class . "\r\n";

    } //End For class in classOrder

    return $output;
}


//============================================================


function makeNashoba($userData, $daySchedule)
{

    $output = "";

    //Parse day schedule into letter and number
    $dayLetter = substr($daySchedule, 0, 1);
    $dayNumber = substr($daySchedule, 1, 1);
    $dayId = (strlen($daySchedule) > 2 ? substr($daySchedule, 3, 2) : false); //set day ID to string or false

    //DECIDE CLASS ORDER
    if ($dayId == false || $dayId == "Ac" || $dayId == "Mc") { //If normal or activity period day

        if ($dayId == "Mc" && $userData['Grade'] == 'sophomore') {

            $description = "You have " . substr($daySchedule, 6) . " MCAS today.";
            $classOrder = array($description);

        } else { //Normal day
            switch ($dayLetter) {
                case "A":
                    $classOrder = array($userData['A'], $userData['B'], $userData['C'], $userData['D'], $userData['E'], $userData['F'], $userData['G']);
                    break;
                case "B":
                    $classOrder = array($userData['B'], $userData['C'], $userData['D'], $userData['E'], $userData['F'], $userData['G'], $userData['A']);
                    break;
                case "C":
                    $classOrder = array($userData['C'], $userData['D'], $userData['E'], $userData['F'], $userData['G'], $userData['A'], $userData['B']);
                    break;
                case "D":
                    $classOrder = array($userData['D'], $userData['E'], $userData['F'], $userData['G'], $userData['A'], $userData['B'], $userData['C']);
                    break;
                case "E":
                    $classOrder = array($userData['E'], $userData['F'], $userData['G'], $userData['A'], $userData['B'], $userData['C'], $userData['D']);
                    break;
                case "F":
                    $classOrder = array($userData['F'], $userData['G'], $userData['A'], $userData['B'], $userData['C'], $userData['D'], $userData['E']);
                    break;
                case "G":
                    $classOrder = array($userData['G'], $userData['A'], $userData['B'], $userData['C'], $userData['D'], $userData['E'], $userData['F']);
                    break;
            }//End switch
        } //End Else normal day

        if ($dayId == "Ac") { //Activity period: "A3 Ac"
            array_splice($classOrder, 2, 0, "Activity Period"); //array, offset, numElements, newElement
        }

    } else { //Else special day

        if ($dayId == "Er" || //Early release: "A3 Er A,B,C,Activity Period,D"
            $dayId == "Cu" || //Custom day: "A1 Cu A,B,C,D,E,F (lunch),G,special,special"
            $dayId == "Md" || //Midterms: "A8 Md A (exam),B,C,G (lunch),E,F"
            $dayId == "Fn") { //Finals: (TBD)

            $classOrder = array();
            $periodOrder = explode(",", substr($daySchedule, 6)); //delimiter, string

            //Go through letters in letter order array
            foreach ($periodOrder as $period) {

                //Just a letter
                if (strlen($period) == 1) {

                    $className = $userData[$period]; //Get corresponding class from userData

                //Lunch period
                } else if (stripos($period, "(lunch)") > -1) {

                    $period = substr($period, 0, 1); //Get only letter
                    $className = $userData[$period] . " (lunch)"; //Get corresponding class from userData

                //Exam period
                } else if (stripos($period, "(exam)") > -1) {

                    $period = substr($period, 0, 1); //Get only letter
                    $className = $userData[$period] . " (exam)"; //Get corresponding class from userData

                //Special period name ("Pep Rally")
                } else {
                    $className = $period;
                }

                array_push($classOrder, $className); //Add element to array

            }//End foreach period letter

        } else if ($dayId == "Sp") { //Special day: "H9 Sp Career Day"

            $classOrder = array(substr($daySchedule, 6));

        }
    }

    $i = 0; //What number period we're at

    $today = new DateTime;
    $change = new DateTime('2014-01-27 00:00:00');
    $semester = ($today < $change) ? 1 : 2;

    //GO THROUGH CLASSES, DECIDE SEMESTER/NUMBER DAY
    foreach ($classOrder as $class) {

        $i++;

        //DECIDE SEMESTER
        if (strpos($class, ' # ') !== false) { //if contains #

            $classArray = explode(" # ", $class); //break string into array at delimiter

            if ($semester == 1) { //if before semester change
                $class = $classArray[0]; //first semester class
            } else if ($semester == 2) { //else after semester change
                $class = $classArray[1]; //second semester class
            }

        } //End decide semester

        //DECIDE DAY NUMBER
        if (strpos($class, ' / ') !== false) { //if contains /

            $classArray = explode(" / ", $class); //break string into array at delimiter

            $classOne = $classArray[0];
            $classTwo = $classArray[1];
            $classOneDays = $classArray[2];
            $classTwoDays = $classArray[3];

            if (strpos($classOneDays, $dayNumber) !== false) {
                $class = $classOne;
            } else if (strpos($classTwoDays, $dayNumber) !== false) {
                $class = $classTwo;
            } else {
                $class = "Study";
            }
        } //End decide day number

        //If it's the fifth class on a normal day
        if ($i == 5 && $dayId == false) {
            $class .= " (lunch)"; //Add "(lunch)" to class name
        } else if ($i == 6 && $dayId == "Ac") { //Activity Period day lunch is one period later
            $class .= " (lunch)"; //Add "(lunch)" to class name
        }

        $output .= $class . "\r\n";

    } //End For class in classOrder

    return $output;
}


//============================================================


function makeHudson($userData, $daySchedule)
{

    $output = "";

    //Parse day schedule into number and ID
    $dayNumber = substr($daySchedule, 0, 1);
    $dayId = (strlen($daySchedule) > 2 ? substr($daySchedule, 2, 2) : false); //Set day ID to string or false

    //DECIDE CLASS ORDER
    if ($dayId == false) { //If normal or activity period day
        switch ($dayNumber) {
            case "1":
                $classOrder = array($userData['A'], $userData['B'], $userData['C'], $userData['D'], $userData['E']);
                break;
            case "2":
                $classOrder = array($userData['F'], $userData['G'], $userData['A'], $userData['B'], $userData['C']);
                break;
            case "3":
                $classOrder = array($userData['D'], $userData['E'], $userData['F'], $userData['G'], $userData['A']);
                break;
            case "4":
                $classOrder = array($userData['B'], $userData['C'], $userData['D'], $userData['E'], $userData['F']);
                break;
            case "5":
                $classOrder = array($userData['G'], $userData['A'], $userData['B'], $userData['C'], $userData['D']);
                break;
            case "6":
                $classOrder = array($userData['E'], $userData['F'], $userData['G'], $uesrData['A'], $userData['B']);
                break;
            case "7":
                $classOrder = array($userData['C'], $userData['D'], $userData['E'], $userData['F'], $userData['G']);
                break;
        }//End switch

    } else if ($dayId == "Er" || //Early release: "3 Er"
               $dayId == "Cu" || //Custom day: "1 Cu A,B,C,D,E,F (lunch),G,special,special"
               $dayId == "Md" || //Midterms: "6 Md A (exam),B,C,G (lunch),E,F"
               $dayId == "Mc" || //MCAS: "7 Mc English,A,B,C"
               $dayId == "Fn") { //Finals: (TBD)

        $classOrder = array();
        $periodOrder = explode(",", substr($daySchedule, 5)); //delimiter, string
        
        //Student has MCAS
        if ($dayId == "Mc") {
            if ($userData['Grade'] == 'sophomore') {
                array_push($classOrder, $periodOrder[0]." MCAS");
            }
            unset($periodOrder[0]); //Remove this from the array
        }

        //Go through letters in letter order array
        foreach ($periodOrder as $period) {

            //Just a letter
            if (strlen($period) == 1) {

                $className = $userData[$period]; //Get corresponding class from userData

            //Lunch period
            } else if (stripos($period, "(lunch)") > -1) {

                $period = substr($period, 0, 1); //Get only letter
                $className = $userData[$period] . " (lunch)"; //Get corresponding class from userData

            //Exam period
            } else if (stripos($period, "(exam)") > -1) {

                $period = substr($period, 0, 1); //Get only letter
                $className = $userData[$period] . " (exam)"; //Get corresponding class from userData

            //Special period name ("Pep Rally")
            } else {
                $className = $period;
            }

            array_push($classOrder, $className); //Add element to array

        }//End foreach period letter
        
    } else if ($dayId == "Sp") { //Special day: "1 Sp Career Day"
        $classOrder = array(substr($daySchedule, 5));
    }

    $i = 0;

    //GO THROUGH CLASSES, DECIDE SEMESTER/NUMBER DAY
    foreach ($classOrder as $class) {

        $i += 1;

        $today = new DateTime;
        $change = new DateTime('2014-01-27 00:00:00');
        $semester = ($today < $change) ? 1 : 2;

        //DECIDE SEMESTER
        if (strpos($class, ' # ') !== false) { //if contains #

            $class = explode(" # ", $class); //break string into array at delimiter

            if ($semester == 1) { //if before semester change
                $class = $class[0]; //first semester class
            } else if ($semester == 2) { //else after semester change
                $class = $class[1]; //second semester class
            }

        } //End decide semester

        //If it's the fourth class on a normal day
        if ($i == 4) {
            $class .= " (lunch)"; //Add "(lunch)" to class name
        }

        $output .= $class . "\r\n";

    } //End For class in classOrder

    return $output;
}


//============================================================


function makeTahanto($userData, $daySchedule)
{

    $output = "";

    $dayLetterArray = array('A' => '1', 'B' => '2', 'C' => '3', 'D' => '4', 'E' => '5', 'F' => '6');

    //Parse day schedule into letter and number
    $dayLetter = substr($daySchedule, 0, 1);
    $dayNumber = $dayLetterArray[$dayLetter];
    $dayId = (strlen($daySchedule) > 2 ? substr($daySchedule, 2, 2) : false); //set day ID to string or false

    //DECIDE CLASS ORDER
    if ($dayId == false || $dayId == "Mc") { //If normal or activity period day

        if ($dayId == "Mc" && $userData['Grade'] == 'sophomore') {

            $description = "You have " . substr($daySchedule, 6) . " MCAS today.";
            $classOrder = array($description);

        } else { //Normal day
            switch ($dayLetter) {
                case "A":
                    $classOrder = array($userData['A'], $userData['B'], $userData['C'], $userData['D'], $userData['E'], $userData['F'], $userData['G']);
                    break;
                case "B":
                    $classOrder = array($userData['A'], $userData['C'], $userData['D'], $userData['E'], $userData['F'], $userData['B'], $userData['G']);
                    break;
                case "C":
                    $classOrder = array($userData['A'], $userData['D'], $userData['E'], $userData['F'], $userData['B'], $userData['C'], $userData['G']);
                    break;
                case "D":
                    $classOrder = array($userData['A'], $userData['E'], $userData['F'], $userData['B'], $userData['C'], $userData['D'], $userData['G']);
                    break;
                case "E":
                    $classOrder = array($userData['A'], $userData['F'], $userData['B'], $userData['C'], $userData['D'], $userData['E'], $userData['G']);
                    break;
                case "F":
                    $classOrder = array($userData['A'], $userData['B'], $userData['C'], $userData['D'], $userData['E'], $userData['F'], $userData['G']);
                    break;
            }//End switch
        } //End Else normal day

    } else { //Else special day

        if ($dayId == "Er" || //Early release: "A3 Er"
            $dayId == "Cu" || //Custom day: "A1 Cu A,B,C,D,E,F (lunch),G,special,special"
            $dayId == "Md" || //Midterms: "A8 Md A (exam),B,C,G (lunch),E,F"
            $dayId == "Fn") { //Finals: (TBD)

            $classOrder = array();
            $periodOrder = explode(",", substr($daySchedule, 6)); //delimiter, string

            //Go through letters in letter order array
            foreach ($periodOrder as $period) {

                //Just a letter
                if (strlen($period) == 1) {

                    $className = $userData[$period]; //Get corresponding class from userData

                //Lunch period
                } else if (stripos($period, "(lunch)") > -1) {

                    $period = substr($period, 0, 1); //Get only letter
                    $className = $userData[$period] . " (lunch)"; //Get corresponding class from userData

                //Exam period
                } else if (stripos($period, "(exam)") > -1) {

                    $period = substr($period, 0, 1); //Get only letter
                    $className = $userData[$period] . " (exam)"; //Get corresponding class from userData

                //Special period name ("Pep Rally")
                } else {
                    $className = $period;
                }

                array_push($classOrder, $className); //Add element to array

            }//End foreach period letter

        } else if ($dayId == "Sp") { //Special day: "A1 Sp Career Day"

            $classOrder = array(substr($daySchedule, 6));

        }
    }

    $i = 0; //What number period we're at

    //GO THROUGH CLASSES, DECIDE SEMESTER/NUMBER DAY
    foreach ($classOrder as $class) {

        $i++;

        $today = new DateTime;
        $change = new DateTime('2014-01-27 00:00:00');
        $semester = ($today < $change) ? 1 : 2;

        //DECIDE SEMESTER
        if (strpos($class, ' # ') !== false) { //if contains #

            $class = explode(" # ", $class); //break string into array at delimiter

            if ($semester == 1) { //if before semester change
                $class = $class[0]; //first semester class
            } else if ($semester == 2) { //else after semester change
                $class = $class[1]; //second semester class
            }

        } //end decide semester

        //DECIDE DAY NUMBER
        if (strpos($class, ' / ') !== false) { //if contains /

            $classArray = explode(" / ", $class); //break string into array at delimiter

            $classOne = $classArray[0];
            $classTwo = $classArray[1];
            $classOneDays = $classArray[2];
            $classTwoDays = $classArray[3];

            if (strpos($classOneDays, $dayNumber) !== false) {
                $class = $classOne;
            } else if (strpos($classTwoDays, $dayNumber) !== false) {
                $class = $classTwo;
            } else {
                $class = "Study";
            }
        } //end decide day number

        //If it's the fifth class on a normal day
        if ($i == 5 && $dayId == false) {
            $class .= " (lunch)"; //Add "(lunch)" to class name
        }

        $output .= $class . "\r\n";

    } //End For class in classOrder

    return $output;
}
