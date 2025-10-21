<?php

$logFile = __DIR__ . '/logs/access.log';
$date = date('Y-m-d');

echo "Generating dummy logs for {$date}...\n";

$fp = fopen($logFile, 'a');
if (!$fp) {
    die("Cannot open log file\n");
}

for ($hour = 0; $hour < 24; $hour++) {
    for ($minute = 0; $minute < 60; $minute++) {
        $time = sprintf('%s %02d:%02d:00', $date, $hour, $minute);
        $count = rand(1, 20); // Random users per minute
        
        for ($i = 0; $i < $count; $i++) {
            $ip = rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
            $logLine = "[{$time}] IP:{$ip} UA:DummyBot GET /\n";
            
            flock($fp, LOCK_EX);
            fwrite($fp, $logLine);
            flock($fp, LOCK_UN);
        }
    }
}

fclose($fp);
echo "Done! Generated logs to {$logFile}\n";