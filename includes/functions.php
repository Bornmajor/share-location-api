<?php
//set headers used by all files
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Method: *');
header('Access-Control-Allow-Headers:Content-Type, Access-Control-Allow-Headers, Authorization, X-Request-With');


include("connection.php");

//correcting time
date_default_timezone_set('UTC');

function responseDataJson($status,$message,$res,$code){
    global $connection,$requestMethod;
    $data = [
        'status' => $status,
        'message' => $message,
        'res' => $res,
        'code' => $code,
    ];
     header("HTTP/1.0 $status $message");
    echo json_encode($data);
}

function escapeString($string){
global $connection;

return $string = mysqli_real_escape_string($connection,$string);

}

function checkQuery($result){
    global $connection;
    if($result){
    
    }else{
        die("Query failed".mysqli_error($connection));
    
    }  
}

function generateUserToken($email){
    global $connection;
    // String of all alphanumeric character
$str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';   // Shuffle the $str_result and returns substring
// of specified length
$gen_token_id = "UTK". substr(str_shuffle($str_result),
                    0, 40).$email;
  
return $gen_token_id;
 
}
function generateUserId(){
    global $connection;
      // String of all alphanumeric character
  $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';   // Shuffle the $str_result and returns substring
  // of specified length
  $gen_usr_id = "U-". substr(str_shuffle($str_result),
                      0, 50);
    
 return $gen_usr_id;

}

function getUsernameFromMail($email){
    $character = "@";
  
    $position = strpos($email, $character);
    if ($position !== false) {
      $newString = substr($email, 0, $position);
      return $newString; // result: username
    }
   
}

function getUsernameFromId($usr_id){
    global $connection;

    $query = "SELECT username FROM users WHERE usr_id = '$usr_id'";
    $select_username = mysqli_query($connection,$query);
    checkQuery($select_username);
    while($row = mysqli_fetch_assoc($select_username)){
     $username = $row['username'];
    }

    return $username;
}


function loginUser($inputData){    
global $connection;

if(!isset($inputData['email']) || !isset($inputData['pwd'])){
responseDataJson(500,'Either email or pwd params is missing','Ok',0);
return false;
}
$email = escapeString($inputData['email']);
$pwd = escapeString($inputData['pwd']);

if(empty($email)|| empty($pwd)){
responseDataJson(500,'Either email or pwd params is empty','Ok',0);
return false;
}

$query = "SELECT * FROM users WHERE mail = '$email'";
$select_user = mysqli_query($connection,$query);
checkQuery($select_user);
while($row = mysqli_fetch_assoc($select_user)){
$db_pwd = $row["pwd"];
}


//check if email exist
if(mysqli_num_rows($select_user) == 0){ 
//email does not exist in db
responseDataJson(200,"Email unavailable","Ok",0);
return false;
}


//verify pwd
if(password_verify($pwd, $db_pwd)){
    //pwd correct
    //token generate
    $usr_token = generateUserToken($email);
    //update db token
    $query = "UPDATE users SET usr_token = '$usr_token' WHERE mail = '$email'";
    $update_token = mysqli_query($connection,$query);
    checkQuery($update_token);

    responseDataJson(200,"Login successfully",$usr_token,1);

 }else{
    responseDataJson(200,"Incorrect credentials","ok",0);
    return false;
 }


}



function registerUser($inputData){
    global $connection;

    if(!isset($inputData['email']) || !isset($inputData['pwd'])){
        responseDataJson(500,'Either email or pwd params is missing','Ok',0);
        return false;
        }
        $email = escapeString($inputData['email']);
        $pwd = escapeString($inputData['pwd']);
        
        if(empty($email)|| empty($pwd)){
        responseDataJson(500,'Either email or pwd params is empty','Ok',0);
        return false;
        }

        $query = "SELECT * FROM users WHERE mail = '$email'";
        $select_user = mysqli_query($connection,$query);
        checkQuery($select_user);
        while($row = mysqli_fetch_assoc($select_user)){
        $db_pwd = $row["pwd"];
        }


        //check if email exist
        if(mysqli_num_rows($select_user) !== 0){ 
        //email  exist in db
        responseDataJson(200,"Email unavailable","Ok",0);
        return false;
        }

    //extract username from mail
    $username = getUsernameFromMail($email);
    
        //generate usr_id
     $usr_id = generateUserId();
     //token generate
     $usr_token = generateUserToken($email);
     //insert user
     //hash pwd
     $pwd = password_hash($pwd,PASSWORD_BCRYPT,array('cost' => 12));

    $longitude = escapeString($inputData['longitude']);
     $latitude = escapeString($inputData['latitude']);
     //check for longitude and latitude params before add user
      if(empty($longitude) || empty($latitude)){
      responseDataJson(200,'Please allow location permission required','Ok',0);
      return false;
     }


     $query = "INSERT INTO users(usr_id,mail,username,pwd,usr_token)VALUES('$usr_id','$email','$username','$pwd','$usr_token')";
     $insert_user = mysqli_query($connection,$query);
     checkQuery($insert_user);
     responseDataJson(200,"Registered successfully",$usr_token,1);
     //Set default location

   

    
     $query = "INSERT INTO location(usr_id,username,latitude,longitude,active)VALUES('$usr_id','$username','$latitude','$longitude','false')";
     $create_location = mysqli_query($connection,$query);
     checkQuery($create_location);
  
        
}

function getUserIdFromToken($usr_token){
   global $connection;
    //get usr_id
    $query = "SELECT usr_id FROM users WHERE usr_token = '$usr_token'";
    $select_token = mysqli_query($connection,$query);
    checkQuery($select_token);
    while($row = mysqli_fetch_assoc($select_token)){
    $usr_id = $row['usr_id'];
    }
    

    if(mysqli_num_rows($select_token) == 0){
    responseDataJson(500,"User token expired","token_expired",0);
    return false;
    
     }else{
      return $usr_id;  
     }
    

}

function checkIfPeerExist($peer_id){
    global $connection;
    
     //check if peer id is valid
     $query = "SELECT usr_id FROM users WHERE usr_id = '$peer_id'";
     $check_peer = mysqli_query($connection,$query);
     checkQuery($check_peer);
     if(mysqli_num_rows($check_peer) == 0){
         responseDataJson(500,"Peer id invalid","Ok",0);
         return false;
     }
}
function sendUserLocation($inputData){
    global $connection;

  if(!isset($inputData['usr_token']) || !isset($inputData['latitude']) ||!isset($inputData['longitude'])) {
    responseDataJson(500,'Either Usertoken,lat or long params is missing','ok',0);
    return false;
  } 

  $usr_token  =  escapeString($inputData['usr_token']);
  $latitude = escapeString($inputData['latitude']);
  $longitude = escapeString($inputData['longitude']);

   if(empty($usr_token) || empty($latitude) || empty($longitude)){
    responseDataJson(500,'Either Usertoken,lat or long params is empty','ok',0);
    return false;
   }

   //get usr_id
   $usr_id = getUserIdFromToken($usr_token);
   if($usr_id == false){
    return false;
   }

   //get username
   $username = getUsernameFromId($usr_id);

   $last_updated = date('l jS \of F Y h:i:s A');

   

   //update location
   $query = "UPDATE location SET username = '$username' , latitude = '$latitude' ,longitude = '$longitude',last_updated = '$last_updated',active = 'true'  WHERE usr_id = '$usr_id'";
   $update_location = mysqli_query($connection,$query);
   checkQuery($update_location);
   if($update_location){
    responseDataJson(200,"Db location updated","ok",1);
   }

   
}

function setInactive($inputData){
global $connection;

if(!isset($inputData['usr_token'])){
    responseDataJson(500,'User token params is missing','Ok',0);
    return false;
}
$usr_token = escapeString($inputData['usr_token']);
    
   
if(empty($usr_token)){
responseDataJson(500,'User token param is empty','Ok',0);
return false;
}

//get usr id
$usr_id = getUserIdFromToken($usr_token);
if($usr_id == false){   
    return false;
}
$query = "UPDATE location SET active = 'false' WHERE usr_id != '$usr_id' ";
$set_in_active = mysqli_query($connection,$query);
checkQuery($set_in_active);

if($set_in_active){
    responseDataJson(200,"Inactive users deactivated","Ok",1);
}


}


function getAllPeer($params){
    global $connection;

     if(!isset($params['usr_token'])){
      responseDataJson(500,'User token params is missing','Ok',0);
      return false;
     }

     if(!isset($params['status'])){
       $status = 'disapproved';
     }else{
        $status = escapeString($params['status']);
     }
     if(!isset($params['archived'])){
       $archived = 'false';
     }else{
       $archived = escapeString($params['archived']);  
     }

    $usr_token = escapeString($params['usr_token']);
    
   

    if(empty($usr_token)){
    responseDataJson(500,'User token param is empty','Ok',0);
    return false;
    }

    //get usr id
    $usr_id = getUserIdFromToken($usr_token);
    if($usr_id == false){   
        return false;
    }
     
    $query = "SELECT peer_id FROM network WHERE usr_id = '$usr_id' AND status = '$status'";
    $select_peers = mysqli_query($connection,$query);
    checkQuery($select_peers);
    $noOfPeers = mysqli_num_rows($select_peers);

    if($noOfPeers  == 0){   
        $res = [];
        responseDataJson(200,"No peer is your network",$res,1);
        return false;
     }

 

    $res = mysqli_fetch_all($select_peers, MYSQLI_ASSOC);
    responseDataJson(200,$noOfPeers,$res,1);

}

function addPeerToNetwork($inputData){
    global $connection;

   if(!isset($inputData['usr_token']) || !isset($inputData['peer_id'])) {
    responseDataJson(500,'Either user token or peer id params is missing','Ok',0);
    return false;
   }

   $usr_token = escapeString($inputData['usr_token']);
   $peer_id = escapeString($inputData['peer_id']); 

   if(empty($usr_token) || empty($peer_id)){
    responseDataJson(500,'Either user token or peer id params is empty','Ok',0);
    return false;
   }

   $usr_id = getUserIdFromToken($usr_token);
   if($usr_id == false){
    return false;
   }

   //check if peer already exist
   $query = "SELECT * FROM network WHERE usr_id = '$usr_id' AND peer_id = '$peer_id'";
   $check_usr_peer = mysqli_query($connection,$query);
   checkQuery($check_usr_peer);
   
   if(mysqli_num_rows($check_usr_peer) !== 0){   
   //peer already in your network
   responseDataJson(500,"Peer already exist in your network","Ok",0);
   return false;
   }

   //check if peer id is valid
   $query = "SELECT usr_id FROM users WHERE usr_id = '$peer_id'";
   $check_peer = mysqli_query($connection,$query);
   checkQuery($check_peer);
   if(mysqli_num_rows($check_peer) == 0){
    responseDataJson(500,"Peer id invalid","Ok",0);
    return false;
   }

    $query = "INSERT INTO network(usr_id,peer_id,status,archived)VALUES('$usr_id','$peer_id','disapproved','false')";
    $insert_peer = mysqli_query($connection,$query);
    checkQuery($insert_peer);

    responseDataJson(200,"Peer request to network","Ok",1);

}

function approvePeer($inputData){
    global $connection;

   if(!isset($inputData['usr_token']) || !isset($inputData['peer_id'])){
    responseDataJson("500","Either user token or peer id params is missing","Ok",0);
    return false;
   }

   
   $usr_token = escapeString($inputData['usr_token']);
   $peer_id = escapeString($inputData['peer_id']);

   if(empty($usr_token) || empty($peer_id)){
    responseDataJson("500","Either user token or peer id params is empty","Ok",0);
    return false;
   }

   //get usr id
   $usr_id = getUserIdFromToken($usr_token);
   if($usr_id == false){
    return false;
   }

    //check if peer id is valid
    $query = "SELECT usr_id FROM users WHERE usr_id = '$peer_id'";
    $check_peer = mysqli_query($connection,$query);
    checkQuery($check_peer);
    if(mysqli_num_rows($check_peer) == 0){
     responseDataJson(500,"Peer id invalid","Ok",0);
     return false;
    }

    
    $query = "UPDATE network SET status = 'approved', archived = 'false' WHERE usr_id = '$peer_id' AND peer_id = '$usr_id'";
    $approve_peer = mysqli_query($connection,$query);
    checkQuery($approve_peer);


    //check if other data exist
     //check if peer already exist
   $query = "SELECT * FROM network WHERE usr_id = '$usr_id' AND peer_id = '$peer_id'";
   $check_usr_peer = mysqli_query($connection,$query);
   checkQuery($check_usr_peer);
   
   if(mysqli_num_rows($check_usr_peer) !== 0){   
   //peer already in your network
   responseDataJson(200,"Peer already exist in your network","Ok",1);
   return false;
   }
    
  

    //duplicate data to both networks once approved inorder to view each other
    //make usr_id be your peer too
    $query = "INSERT INTO network(usr_id,peer_id,status,archived)VALUES('$usr_id','$peer_id','approved','false')";
    $duplicate_network = mysqli_query($connection,$query);

      if($approve_peer){
      responseDataJson(200,"Peer approved","Ok",1);   
    }
   

}

function archivePeer($inputData){
    global $connection;

    
   if(!isset($inputData['usr_token']) || !isset($inputData['peer_id'])){
    responseDataJson("500","Either user token or peer id params is missing","Ok",0);
    return false;
   }

   
   $usr_token = escapeString($inputData['usr_token']);
   $peer_id = escapeString($inputData['peer_id']);

   if(empty($usr_token) || empty($peer_id)){
    responseDataJson("500","Either user token or peer id params is empty","Ok",0);
    return false;
   }

   //get usr id
   $usr_id = getUserIdFromToken($usr_token);
   if($usr_id == false){
    return false;
   }

    //check if peer id is valid
    $query = "SELECT usr_id FROM users WHERE usr_id = '$peer_id'";
    $check_peer = mysqli_query($connection,$query);
    checkQuery($check_peer);
    if(mysqli_num_rows($check_peer) == 0){
     responseDataJson(500,"Peer id invalid","Ok",0);
     return false;
    }
    $query = "UPDATE network SET archived = 'true' WHERE usr_id = '$peer_id' AND peer_id = '$usr_id'";
    $archived_peer = mysqli_query($connection,$query);
    checkQuery($archived_peer);
    if($archived_peer){
        responseDataJson(200,"Peer archived","Ok",1);
    }

}


function getUsers($inputData){
    global $connection;

    if(!isset($inputData['usr_token'])){
        responseDataJson(500,'User token params is missing','Ok',0);
        return false;
       }
  
    if(!isset($inputData['all_users'])){
        $all_users = false;
    }else{
        $all_users =  escapeString($inputData['all_users']);
    }

      $usr_token = escapeString($inputData['usr_token']);
    

      
  
      if(empty($usr_token)){
      responseDataJson(500,'User token param is empty','Ok',0);
      return false;
      }
  
      //get usr id
      $usr_id = getUserIdFromToken($usr_token);
      if($usr_id == false){   
          return false;
      }


      if($all_users == true){
      $query = "SELECT usr_id,username FROM users";  
      }else{
        $query = "SELECT usr_id,username FROM users WHERE usr_id = '$usr_id'";
      }

      
      $select_users = mysqli_query($connection,$query);
      checkQuery($select_users);

      $res = mysqli_fetch_all($select_users,MYSQLI_ASSOC);

      responseDataJson(200,"Users list",$res,1);


}

function  getUserById($inputData){
    global $connection;


    if(!isset($inputData['usr_token']) || !isset($inputData['usr_id'])){
        responseDataJson("500","Either user token or user id params is missing","Ok",0);
        return false;
    }

    $usr_id = escapeString($inputData['usr_id']);
    $usr_token = escapeString($inputData['usr_token']);

    if(getUserIdFromToken($usr_token) == false){
        return false;
    };

    $query = "SELECT username FROM users WHERE usr_id = '$usr_id'";
    $select_user = mysqli_query($connection,$query);
    checkQuery($select_user);
    if(mysqli_num_rows($select_user) == 0){
        responseDataJson(500,"User id invalid","Ok",0);
        return false;
    }
    
   $res = mysqli_fetch_all($select_user,MYSQLI_ASSOC);

   if($select_user){
    responseDataJson(200,"Fetch username successfully",$res,1);
   }
}

function checkPeerStatus($inputData){
    global $connection;

    if(!isset($inputData['usr_token']) || !isset($inputData['peer_id'])){
        responseDataJson("500","Either user token or peer id params is missing","Ok",0);
        return false;
       }
    
       
       $usr_token = escapeString($inputData['usr_token']);
       $peer_id = escapeString($inputData['peer_id']);
    
       if(empty($usr_token) || empty($peer_id)){
        responseDataJson("500","Either user token or peer id params is empty","Ok",0);
        return false;
       }
    
       //get usr id
       $usr_id = getUserIdFromToken($usr_token);
       if($usr_id == false){
        return false;
       }

     
    
        //check if peer id is valid
        $query = "SELECT usr_id FROM users WHERE usr_id = '$peer_id'";
        $check_peer = mysqli_query($connection,$query);
        checkQuery($check_peer);
        if(mysqli_num_rows($check_peer) == 0){
         responseDataJson(500,"Peer id invalid","Ok",0);
         return false;
        }

        $query = "SELECT * FROM network WHERE usr_id = '$usr_id' AND peer_id = '$peer_id'";
        $check_peer = mysqli_query($connection,$query);
        if(mysqli_num_rows($check_peer) == 0){
            //peer does not exist in your network
            responseDataJson(200,"Peer not in network","unavailable",1);
            return false;
        }
        while($row = mysqli_fetch_assoc($check_peer)){
         $status =  $row['status'];

        }

        if($status == "disapproved"){
            responseDataJson(200,"Peer in network,disapproved","waiting",1);
            return false;  
        }else if($status == "approved"){
            responseDataJson(200,"Peer approved","exist",1);
        
        }
        

}

function getInvitations($inputData){
    global $connection;

    if(!isset($inputData['usr_token'])){
        responseDataJson("500","user token param is missing","Ok",0);
        return false;
       } 
       $usr_token = escapeString($inputData['usr_token']);
       
    
       if(empty($usr_token)){
        responseDataJson("500","user token params is empty","Ok",0);
        return false;
       }
    
       //get usr id
       $usr_id = getUserIdFromToken($usr_token);
       if($usr_id == false){
        return false;
       }

       if(!isset($inputData['archived'])){
        $archived = 'false'; 
       }else{
        $archived = escapeString($inputData['archived']);
       }
    
       
    
        
           //get usr id
           $usr_id = getUserIdFromToken($usr_token);
           if($usr_id == false){
            return false;
           }
        
       
        $query = "SELECT usr_id FROM network  WHERE peer_id = '$usr_id' AND status = 'disapproved' AND  archived = '$archived'";
        $check_invite = mysqli_query($connection,$query);
        checkQuery($check_invite);
        $res = mysqli_fetch_all($check_invite, MYSQLI_ASSOC);

        if($check_invite){
            responseDataJson(200,"List of invitations",$res,1);
        }
    




    
}

function getPeerLocations($inputData){
    global $connection;

    if(!isset($inputData["usr_token"])){
        responseDataJson(500,"User token params is missing","Ok",0);
        return false;
    }

    $usr_token = escapeString($inputData["usr_token"]);

    if(empty($usr_token)){
        responseDataJson(500,"User token params is empty","Ok",0);
        return false;   
    }

    $usr_id = getUserIdFromToken($usr_token);
    if($usr_id == false){
        return false;
    }

    $query = "SELECT peer_id FROM network WHERE usr_id = '$usr_id'";
    $select_peers = mysqli_query($connection,$query);
    checkQuery($select_peers);
    $columnObjects = [];
    //loop inside a loop using for loop
    while($row = mysqli_fetch_assoc($select_peers)){
        $peer_id = $row["peer_id"];

        $query = "SELECT * FROM location WHERE usr_id = '$peer_id' AND active  = 'true'";
        $select_users_locations = mysqli_query($connection,$query);
        checkQuery($select_users_locations);
        
        while($row1 = mysqli_fetch_assoc($select_users_locations)){
           $columnObject = new stdClass();

        foreach ($row1  as $columnName => $columnValue) {
            $columnObject->$columnName = $columnValue;
           
        }
    
        array_push($columnObjects, $columnObject);   

        }
    
      
       
        
        
    }
    
    //echo $obj;
     responseDataJson(200,'Peer locations list',$columnObjects,1); 
    
    
}

function  deletePeer($inputData){
    global $connection;

    if(!isset($inputData['usr_token']) || !isset($inputData['peer_id'])){
        responseDataJson("500","Either user token or peer id params is missing","Ok",0);
        return false;
       }
    
       
       $usr_token = escapeString($inputData['usr_token']);
       $peer_id = escapeString($inputData['peer_id']);
    
       if(empty($usr_token) || empty($peer_id)){
        responseDataJson("500","Either user token or peer id params is empty","Ok",0);
        return false;
       }
    
       //get usr id
       $usr_id = getUserIdFromToken($usr_token);
       if($usr_id == false){
        return false;
       }

    //    if(checkIfPeerExist($peer_id) == false){
    //     return false;
    //    }

       $query = "DELETE FROM network WHERE usr_id = '$usr_id' AND peer_id = '$peer_id'";
       $delete_from_my_network = mysqli_query($connection,$query);
       checkQuery($delete_from_my_network);

       $query = "DELETE FROM network WHERE usr_id = '$peer_id' AND peer_id = '$usr_id'";
       $delete_from_their_network = mysqli_query($connection,$query);
       checkQuery($delete_from_their_network);

      
        responseDataJson(200,"Deleted peer from network","Ok",1);
       

}
?>