<?php
$file = __DIR__ . '/visits.json';

$data = file_exists($file) ? json_decode(file_get_contents($file), true) : [
    'total' => 0,
    'today' => 0,
    'date'  => date('Y-m-d')
];

$today = date('Y-m-d');

if ($data['date'] !== $today) {
    $data['today'] = 0;
    $data['date']  = $today;
}

$data['total']++;
$data['today']++;

file_put_contents($file, json_encode($data));
