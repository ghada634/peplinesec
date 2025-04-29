<?php
ob_start();
session_start();

if(isset($_SESSION["user"])){
    if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
        header("location: ../login.php");
        exit();
    }
} else {
    header("location: ../login.php");
    exit();
}

if($_POST){
    include("../connection.php");
    $title=$_POST["title"];
    $docid=$_POST["docid"];
    $nop=$_POST["nop"];
    $date=$_POST["date"];
    $time=$_POST["time"];

    $sql="INSERT INTO schedule (docid, title, scheduledate, scheduletime, nop) 
          VALUES ($docid, '$title', '$date', '$time', $nop);";
          
    $result = $database->query($sql);

    header("location: schedule.php?action=session-added&title=$title");
    exit();
}
?>

