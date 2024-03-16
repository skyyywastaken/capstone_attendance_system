<?php
$hashed_password = password_hash("admin", PASSWORD_DEFAULT);

echo $hashed_password;
?>