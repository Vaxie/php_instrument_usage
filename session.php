<?php

//Check if the proper get inputs are set
error_log("session attempt",0);
if (isset($_POST['username']) && isset($_POST['key'])) {
    include('includes/initializer.php');
    $deviceInfo = new Device($sqlDataBase);
    $deviceInfo->LoadDevice(0, $_POST['key']);
    //check if device token matches

    if ($deviceInfo->GetDeviceId() > 0) {
        $sessionInfo = new Session($sqlDataBase);
        $userInfo = new User($sqlDataBase);
        $userId = $userInfo->Exists($_POST['username']);

        //check if user_name exists
        if ($userId) {

            //Start tracking session
            $sessionInfo->TrackSession($deviceInfo->GetDeviceId(), $userId);
        } else {
            //User was not found in website database so check for user exceptions
            if (!in_array(strtolower($_POST['username']), array_map('strtolower', $USER_EXCEPTIONS_ARRAY))) {
                //Email admin that a new user was detected on instrument and that a new account was created on the website for them
                error_log('creating user from session', 0);
                $mail = new Mailer();
                $mail->setFrom(PAGE_TITLE, ADMIN_EMAIL);
                $mail->addRecipient('Admin', ADMIN_EMAIL);
                $mail->fillSubject("Unregistered user: " . $_POST['username'] . " on " . $deviceInfo->GetFullName());
                $mail->fillMessage("The user " . $_POST['username'] . " has logged into " . $deviceInfo->getShortName() . " does not have a registered account on " . PAGE_TITLE."."
                    . "\nCreating a new account on system.");
                $mail->send();

                //Create a user account so we have a record
                $userInfo->CreateUser($_POST['username'], '', '',
                    $_POST['username'] . '@' . DEFAULT_USER_EMAIL_DOMAIN,
                    DEFAULT_USER_DEPARTMENT_ID,
                    DEFAULT_USER_GROUP_ID,
                    DEFAULT_USER_RATE_ID,
                    DEFAULT_USER_STATUS_ID,
                    DEFAULT_USER_ROLE_ID);
            }
            $deviceInfo->UpdateLastTick();
        }
    }

    include('includes/mysql_close.php');
}


?>	
