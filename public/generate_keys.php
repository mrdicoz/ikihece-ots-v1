<?php
require __DIR__ . '/../vendor/autoload.php';

use Minishlink\WebPush\VAPID;

$keys = VAPID::createVapidKeys();

echo "vapid.publicKey =" . $keys['publicKey'] . "\n";
echo "vapid.privateKey =" . $keys['privateKey'] . "\n";