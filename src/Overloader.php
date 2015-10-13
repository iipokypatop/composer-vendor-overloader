<?php

require_once __DIR__ . "/Base.php";

if ($argc > 1) {
    $vendors = array_slice($argv, 1);
    \Overloader\Base::load($vendors);
}
