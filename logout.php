<?php
//logout
//resume the session
session_start();
//destroy the session
session_destroy();

//session_unset()

header("location:login.php");












?>