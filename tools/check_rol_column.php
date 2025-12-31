<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
$db = Database::getInstance()->getConnection();
$row = $db->query("SHOW COLUMNS FROM usuarios LIKE 'rol'")->fetch();
print_r($row);
