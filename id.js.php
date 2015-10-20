<?php
$sq = "'"; 
$rootTubeId = $sq . uniqid('t', TRUE) . $sq;
$userId = $sq . uniqid('u', TRUE) . $sq;

echo "var rootTubeId = " . $rootTubeId . ", userToken = " . $userId . ";\n";
?>
