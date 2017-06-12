<?php header('content-type: application/json; charset=utf-8');
    // AUTHENTICATE

if (isset($_GET["token"])) {
	require_once "../../settings.php";
    require_once "../../utils.php";
	
    $token = $_GET["token"];

// First, check if it's already linked...

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    $query = "SELECT linked FROM `ghtvsubscribers`.`oauth` WHERE token = ?";
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->bind_result($linked);
        $stmt->fetch();
        $stmt->close();
        //printf ("linked is _%s_ \n", $linked);
    } else {
        echo "Errormessage1: " . $mysqli->error;
    }   
    $mysqli->close(); 
           
// Second, if linked = 'yes', read the oauth_token and return json
    if ($linked == 'yes') {
        //echo "in authenticate... NVM, you're already linked!<br/>";
        //pull values for oauth_token
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error);
        }
        $query = "SELECT oauth_token FROM `ghtvsubscribers`.`oauth` WHERE token = ?";
        if ($stmt = $mysqli->prepare($query)){
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $stmt->bind_result($oauth_token);
            $stmt->fetch();
            
            $stmt->close();    
                 
        } else {
            die("Errormessage2: ");
        }    
// Or, if it's not linked, make a new oauth_token       
    } else {
        
        $linked = "no";
 // generate oauth_token and write to db
        $oauth_token = makeCode(12);
        
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error);
        }
        $updater = "UPDATE `ghtvsubscribers`.`oauth` SET oauth_token = ?  WHERE token = ? ";
        if ($stmt = $mysqli->prepare($updater)){
            $stmt->bind_param('ss', $oauth_token, $token);
            $stmt->execute();
            $stmt->close();
            //printf("written oauth_token is %s\n", $oauth_token);
        } else {
            die("Errormessage3: ". $mysqli->error);
        }
    }

    $mysqli->close();
    
    $d = array("deviceToken" => $token, "linked" => $linked, "oauth_token" => $oauth_token);
	$result = json_encode($d);
	
    echo $result;

}
?>
