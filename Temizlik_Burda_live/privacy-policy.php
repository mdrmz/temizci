<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Location: ' . canonicalBaseUrl() . '/privacy', true, 301);
exit;
