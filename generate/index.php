<?php header('content-type: application/json; charset=utf-8');
    // GENERATE

if (isset($_GET["token"])) {
	require_once "../../settings.php";
    require_once "../../utils.php";
	
    $token = $_GET["token"];
    $update_gen = "yes";

// First, check if it has an agent associated with it... 0 or 1 is okay, 2 is a no

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
    } else {
        echo "Errormessage1: " . $mysqli->error;
    }   
    $mysqli->close();


// Second, if linked = 'yes', 

    if ($linked) {
        //it's linked; read value for code
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error);
        }
        $query = "SELECT code FROM `ghtvsubscribers`.`oauth` WHERE token = ?";
        if ($stmt = $mysqli->prepare($query)){
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $stmt->bind_result($code);
            $stmt->fetch();           
            $stmt->close();  
            $update_gen = "no";
        } else {
            die("Errormessage2: ");
        }    
        
    } else {
        // a new entry!!! device not linked yet
        // generate code and write token, code, update_gen and linked to db
        $code = makeCode(6);
        $linked = "no";
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error);
        }
        $query = "INSERT INTO `ghtvsubscribers`.`oauth` (token, code, update_gen, linked) VALUES (?, ?, ?, ?) ";
        if ($stmt = $mysqli->prepare($query)){
            $stmt->bind_param('ssss',  $token, $code, $update_gen, $linked);
            $stmt->execute();
            $stmt->close();
        } else {
            die("Errormessage3: ". $mysqli->error);
        }
    }

    $mysqli->close();

    
    $d = array("code" => $code, "deviceToken" => $token, "update" => $update_gen);
	$result = json_encode($d);
	
    echo $result;

}
?>
