<?php
require_once 'Hive.php';
$yamlData = 'yaml.yml';
$hashData = [
  'a' => 99,
  'x' => ['y', 'z']
];
$hive = new Hive();
print "--- Load & Overlay\n";
$hive['x'] = 22;
var_dump($hive->dump());
$hive->overlay($hashData);
var_dump($hive->dump());
$hive->overlay($yamlData);
var_dump($hive->dump());
$hive->sync('yaml.out.yml');
print "\n--- Getters \n";
var_dump($hive->offsetExpr("A.B.C"));
var_dump($hive['x']);
var_dump($hive["A.B.C"]);
$hive["A.B.C"] = 78;
$hive->overlay(["X" => [ "Y" => [ "Z" => 111 ] ] ]);
var_dump($hive["A.B.C"]);
var_dump($hive["X.Y.Z"]);

print "\n---\n";
var_dump($hive instanceof ArrayAccess);
