<?php

require 'vendor/autoload.php';

$client = new MongoDB\Client(uri: "mongodb://localhost:27017");

$database = $client->adoPET;