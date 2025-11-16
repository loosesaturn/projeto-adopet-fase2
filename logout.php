<?php
// AdoPET/logout.php
require_once 'helpers.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

set_flash_message(message: 'Você saiu.', type: 'success');

$_SESSION = [];

session_destroy();

header(header: 'Location: index.php');
exit();
?>