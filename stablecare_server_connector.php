<?php
    function print_response($dictionary = [], $error = "none"){
        #create string variable
        $string = "";

        #convert dictionary of information to a JSON string
        $string = "{\"error\" : \"$error\",
                    \"command\" : \"_$REQUEST[command]\", 
                    \"response\" : ". json_encode($dictionary) ."}"; 
                    #you can add actual variables to strings like we did with $error
                    #_REQUEST[command] is going to print what we asked the PHP to give us for debugging
                    #concat strings with '.' instead of '+'

        #print/send encoded string back to Godot
        echo $string;
    }

    #'command' refers to an information request from godot
    #'data' refers to a package of data from godot TO the server to update/add information

    #check to see if the request has our command value and that it isn't null
    if (!isset($_REQUEST['command']) or $_REQUEST['command'] === null){
        print_response([], "missing command");
        die;
    }

    #ensure some data is sent.... even if it is empty from godot
    if (!isset($_REQUEST['data']) or $_REQUEST['data'] === null){
        print_response([], "missing data");
        die;
    }

    #set up connection properties for the database
    # the data from the server that is used for the fn_data_pull file goes in these strings, same order
    $sql_host = "gamesmon12.bisecthosting.com"; 
    $sql_username = "username";
    $sql_password = "password";
    $sql_db = "s409685_StableCare-Horse-Database";
    
    #using PDO as alternative to direct SQL code to prevent injection attacks
    #set up PDO connection
    $dsn = "mysql:dbname=$sql_db;host=$sql_host;charset=utf8mb4;port=3306";
    $pdo = null;

    #attempt to connect plz :3
    try{
        $pdo = new PDO($dsn, $sql_username, $sql_password);
    }
    catch (\PDOExecption $e){ #oops catch a bad login and return error :(
        print_response([], "db_login_error");
        die;
    }

    #grab the json string from godot and move it to a dictionary
    $json = json_decode($_REQUEST['data'], true);

    #ensure it didn't error out... and if so, let us know it was an error
    if ($json === null){
        print_response([], "invalid_json");
        die;
    }

    #Switch statement (think match case) to handle and excecute incoming commands
    switch($_REQUEST['command']){

        #fetch the horse information for a specific horse
        case "get_horse_data":
            #ensure we recieved a request that includes the horse serial and the user_id
            if (!isset($json['user_id'])){
                print_response([], "missing_user_id");
                die;
            }
            if (!isset($json['serial'])){
                print_response([], "missing_horse_serial");
                die;
            }

            #template request that ensures the correct user has the correct permission to access the horse for the serial
            $template = "SELECT * FROM `TESTING_horse_information` WHERE serial =:serial AND user_id =:user_id";

            #prepare and send request
            $sth = $pdo -> prepare($template);
            $sth -> execute(["serial" => $json['serial'], "user_id" => $json['user_id']]);

            $data = $sth -> fetch();

            print_response($data);

            die;
        break

        #update water value of a horse
        case "water":
            #check for horse serial and water value
            if (!isset($json['thirst'])){
                print_response([], "missing_thirst_value");
                die;
            }
            if (!isset($json['serial'])){
                print_response([], "missing_horse_serial");
                die;
            }
            
            #the sql request for PDO
            $template = "UPDATE `TESTING_horse_information` SET thirst =:thirst WHERE serial =:serial";

            #prep and send PDO to the database
            $sth = $pdo -> prepare($template);
            $sth -> execute(["thirst" => $json['thirst'], "serial" => $json['serial']]);

            #print an empty response saying that it went through successfully :3
            print_response();

            die;
        break

        #handle all invalid commands
        default:
            print_response([], "invalid_command");
            die;
        break;
    }
    
?>
