<?php
//Authentication, included in bootstrap.php
if (!function_exists('check_login')) {
    function check_login()
    {
        return isset($_SESSION['login']);
    }
}
