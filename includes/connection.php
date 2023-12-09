<?php
$connection = mysqli_connect("localhost","root","","share_location_app");
if ($connection == false){
    die("Db connection failed".mysqli_connect_error($connection));

}


?>