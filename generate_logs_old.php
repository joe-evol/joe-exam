<?php
require_once 'config.php';

$pdo = getDB();
$date = date('Y-m-d');
for ($hour = 0; $hour < 24; $hour++) {
    for ($minute = 0; $minute < 60; $minute += 5) {
        $time = sprintf('%s %02d:%02d:00', $date, $hour, $minute);
        $count = rand(1, 20); // Random DAU per 5-min window
        
        for ($i = 0; $i < $count; $i++) {
            $ip = rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
            
            $stmt = $pdo->prepare("
                INSERT INTO joe_access_logs (access_time, ip_address)
                VALUES (:time, :ip)
            ");
            $stmt->execute(['time' => $time, 'ip' => $ip]);
        }
    }
}

echo "Generated dummy logs for {$date}\n";
