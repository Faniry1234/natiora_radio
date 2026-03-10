<?php
require_once __DIR__ . '/../APP/config.php';
$u = new User();
var_dump($u->findById(1));
var_dump($u->findByEmail('admin@local'));
var_dump($u->getAll());
