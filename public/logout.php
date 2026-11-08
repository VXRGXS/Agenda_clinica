<?php
/**
 * Encerra a sessão e volta ao login.
 */
require __DIR__ . '/../includes/bootstrap.php';
$_SESSION = []; session_destroy();
header('Location: index.php');
