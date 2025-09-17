<?php
// Redirect to the proper admin routing system
$form = isset($_GET['form']) ? $_GET['form'] : 'member';
header("Location: index.php?page=register&form=" . urlencode($form));
exit;
?>
