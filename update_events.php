<?php
//test
error_reporting ( E_ALL );
ini_set ( 'display_errors', '1' );

//Initialize common stuff
include('includes/initializer.php');

// Load User information
$user = new User($sqlDataBase);
$user->LoadUser($_POST ['user_id']);

// Verify the userId is who he really is checking keys
if ($user->GetSecureKey() == $_POST ['key']) {
    $reservation = new Reservation ($sqlDataBase);
    $reservation->LoadReservation($_POST ['id']);
    $reservation->setDeviceId($_POST ['device_id']);
    $reservation->setStart(strtotime($_POST ['start']));
    $reservation->setStop(strtotime($_POST ['end']));
    $queryUpdateReservation = $reservation->UpdateReservation();
}

?>
