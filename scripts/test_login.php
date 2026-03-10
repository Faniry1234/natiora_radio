<?php
function post($url, $data){
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data),
            'ignore_errors' => true,
            'timeout' => 10
        ]
    ];
    $ctx = stream_context_create($opts);
    $res = @file_get_contents($url, false, $ctx);
    return [$res, $http_response_header ?? []];
}

$url = 'http://127.0.0.1:8080/index.php?route=auth/login_post';
$tests = [
    ['email' => 'admin@local', 'password' => 'admin'],
    ['email' => 'user@local', 'password' => 'user'],
    ['email' => 'admin@local', 'password' => 'wrongpass']
];
foreach ($tests as $t) {
    echo "Testing: " . json_encode($t) . "\n";
    list($body,$hdrs) = post($url, $t);
    foreach ($hdrs as $h) echo $h . "\n";
    echo "Body snippet:\n" . substr($body,0,800) . "\n\n";
}
