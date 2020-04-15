<?php

$chains = file_get_contents("/chains");
$chainTree = unserialize($chains);
