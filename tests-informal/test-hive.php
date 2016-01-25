<?php
require_once '../loader.php';
$yamlData = 'yaml.yml';
$hashData = [
  'a' => 99,
  'x' => ['y', 'z']
];
$hive = new \Pee\Hive();
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
var_dump($hive instanceof \ArrayAccess);
print "\n---\n";
$hive = new \Pee\Hive();
$val = new StdClass();
$val2 = 99;
$hive['A'] = $val;
$hive['B'] = $val2;
$a = $hive->toArray();
var_dump($a);
var_dump($hive->dump());
$a['A']->x = 88;
$a['B'] = 77;
var_dump($a);
var_dump($hive->dump());
