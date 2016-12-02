<?php

/*
 * This demo uses rfussien/leboncoin-crawler to send an email when the first
 * result page returns one or several new ads.
 *
 * When running it the first time, it will send all results from the result page
 * Later, it will only send an email when there will be new results.
 *
 * If this script is put inside a cron, such as, then leboncoin will be crawled
 * every minute and you'll be immediately warned of a new result by email.
 * e.g : * * * * * /usr/bin/env php /home/username/leboncoin/demo/email.php
 *
 * /!\ Require:
 * You must add swiftmailer to Composer. (composer require swiftmailer/swiftmailer)
 */

require_once '../vendor/autoload.php';

require_once 'opt_email.php';

$opt_leboncoin = [
    'name' => 'Lumie',
    'url'  => 'https://www.leboncoin.fr/annonces/offres/ile_de_france/paris/?th=1&q=lumie&it=1&parrot=0',
];

$body = '';

$file = sha1($opt_leboncoin['name']) . ".srz";
if (!file_exists($file)) {
    file_put_contents($file, serialize([]));
}

$knownAds= unserialize(file_get_contents($file));
$newAds = 0;

$searchResults = (new Lbc\GetFrom)->search($opt_leboncoin['url'], true);

foreach ($searchResults['ads'] as $ad) {

    if (in_array($ad->id, $knownAds)) {
        continue;
    }
    $knownAds[] = $ad->id;

    if (!empty($mailer->body)) {
        $body .= str_repeat('-', 80) . "\n\n";
    }
    foreach ($ad as $key => $value) {
        $body .= "{$key}: {$value}\n";
    }

    $body .= "\n";
    $newAds++;
}

if (!$newAds) {
    return;
}

file_put_contents($file, serialize($knownAds));

$transport = Swift_SmtpTransport::newInstance($opt_email['smtp_host'], $opt_email['smtp_port'], $opt_email['smtp_enco'])
    ->setUsername($opt_email['smtp_user'])
    ->setPassword($opt_email['smtp_pass']);

$mailer = Swift_Mailer::newInstance($transport);

$message = Swift_Message::newInstance()
    ->setSubject('LeBonCoin: ' . $newAds . ' nouvelles annonces pour ' . $opt_leboncoin['name'])
    ->setFrom([$opt_email['from']])
    ->setTo([$opt_email['to']])
    ->setBody($body);

$mailer->send($message);
