<?php
require_once '../includes/Art2CartConfig.php';

session_start();
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}
session_destroy();

$baseUrl = Art2CartConfig::getBaseUrl();
header('Location: ' . $baseUrl);
exit;
