<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Arvid\Test\models\DiscordNotifier;
use Arvid\Test\models\Scrap;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$scraper = new Scrap();
$discordWebhookUrl = $_ENV['DISCORD_WEBHOOK_URL'] ?? $_SERVER['DISCORD_WEBHOOK_URL'] ?? null;

if (!$discordWebhookUrl) {
    die('Discord webhook URL is not set. Please check your .env file.');
}

$discordNotifier = new DiscordNotifier($discordWebhookUrl);

$scraper->attach($discordNotifier);

while (true){
    $scraper->fetchAds();
    sleep(10);
}

?>
