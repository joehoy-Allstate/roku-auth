<?php
    require_once "../../settings.php";
    require_once "../../utils.php";
    
    $success = false;
    $the_data = "";

// get variables from html    
    $producer_num = $_POST['producer_num'];
    $code         = $_POST['code'];
    //echo "Code is " . $code . "<br/>";
    //echo "Producer_num is " . $producer_num . "<br/>";

// convert input to upper case
    $producer_num = strtoupper($producer_num);
    $code         = strtoupper($code);

// sanitize data: producer_num has 6 digits of [AB0123456789]
    $producer_num = validateProducer_num($producer_num);
    if ($producer_num == 'NULL'){
        // user input is wrong; kick it back
        echo 'Your agent number is incorrectly formatted. Please enter it again.<br/>';
// * ERROR HANDLING: run the input form again
    }
    
// sanitize: code is base36 only
    $code = validateAlnum($code);
    if ($code == 'NULL'){
        // user input is wrong; kick it back
        echo 'The code is incorrectly formatted: letters and numbers only. Please enter it again.<br/>';
// * ERROR HANDLING: run the input form again
    }

    $info = "";
    $feedback_title = "";

// PHASE 1 - IS THE AGENT IN THE SUBSCRIBER LIST? HOW MANY TIMES??
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }

    if ($result = $mysqli->query("SELECT * FROM subscribers WHERE producer_num = '" . $producer_num . "'" )) {

        /* determine number of rows result set */
        $row_cnt = $result->num_rows;

        /* close result set */
        $result->close();
    }

    /* close connection */
    $mysqli->close();
    

// IS AGENT SUBSCRIBER?
    if ($row_cnt){
        // yes, the agent is a subscriber!
        $success = true;
        echo "Agent is a subscriber.<br/>";
        $info = "Subscription authenticated. <br/><br/>Thank you for subscribing.";
        $feedback_title ="Subscription authenticated.";
    } else {
        $info = "Sorry, we cannot find your subscription.<br/><br/>Please contact us through Blueprint to get access.";
        $feedback_title ="Could not find subscription.";
    }    
    //mysqli_close($mysqli); 

// IS AGENT IN OAUTH DB LESS THAN TWICE??
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }


    if ($result = $mysqli->query("SELECT * FROM oauth WHERE producer_num = '" . $producer_num . "'" )) {

        /* determine number of rows result set */
        $agentdevices = $result->num_rows;

        /* close result set */
        $result->close();
    }

    /* close connection */
    $mysqli->close();

    //echo "Agent has " . $agentdevices . " devices.<br/>");



    if ($agentdevices > 1){
        $success = false;
        $info = "Sorry, you have reached the limit of the number of devices for your subscription.
        <br/><br/>Please contact us through Blueprint to purchase a subscription for additional devices.";
        $feedback_title ="Reached limit of devices for this subscription.";
        
// Delete the incomplete row
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error);
        }
        $query = "DELETE FROM `ghtvsubscribers`.`oauth` WHERE code = ?";
        if ($stmt = $mysqli->prepare($query)) {
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $stmt->close();
        } else {
            echo "ErrormessageX: " . $mysqli->error;
        }   
        $mysqli->close();
    }


// PHASE 2 - IS THE CODE IN THE OAUTH DB?

    if ($success){

        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error);
        }

        $query = "SELECT * FROM `ghtvsubscribers`.`oauth` WHERE code = '" . $code . "'";
        
        $result = mysqli_query($mysqli, $query);

        $rowSelected = mysqli_num_rows($result);

        mysqli_close($mysqli);

        if (($rowSelected)){
            // yes, code matches!
            // don't change the message; just write agent producer_num AND linked = yes to oauth db
            // echo "Code input matches code in db.<br/>";
            $the_linked_state = 'yes';
            $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
            if ($mysqli->connect_error) {
                die("Connection failed: " . $mysqli->connect_error);
            }

            $query = "UPDATE oauth SET producer_num = ? , linked = ? WHERE code = ?";
            if ($stmt = $mysqli->prepare($query)){
                $stmt->bind_param('sss', $producer_num, $the_linked_state, $code);
                $stmt->execute();
            } else {
                die("Errormessage for writing producer_num and linked: ". $mysqli->error);
            }  
            
            $stmt->close(); 

        } else {
            $success = 'false';
            $info = "Sorry, your code doesn't match.<br/><br/>Please try again.";
            $feedback_title ="Could not find subscription.";
        }    

         
    }
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title> <?php echo $feedback_title ?> </title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
	<form title="" action="" method="" class="bootstrap-frm" >		
	    <div id="signInBlock">
	    	<h1>Hello, Agent.</h1>
	    </div>	    
	    <div id="response">
	    	<?php echo $info ?>
            <br/><br/>
            <?php echo $the_data ?>
	    </div>
	</form>
</body>
</html>

