<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
logoutUser();
setFlash('info', 'Başarıyla çıkış yaptınız.');
redirect(APP_URL . '/login');
