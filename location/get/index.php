<?php
include("../../includes/functions.php");


$requestMethod = $_SERVER['REQUEST_METHOD'];

if($requestMethod == 'POST') {

$inputData = json_decode(file_get_contents("php://input"),true);
echo getPeerLocations($inputData);
}else{
responseDataJson(405,$requestMethod." method not allowed",'Ok',0);
}


?>