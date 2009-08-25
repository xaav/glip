<?php
require_once dirname(__FILE__).'/../bootstrap/unit.php';

$t = new lime_test(9, new lime_output_color());

$quick = "The quick brown fox jumped over the lazy dog";

$t->comment('Testing SHA::hash()');
$h = SHA::hash($quick);
$t->is($h->hex(),'f6513640f3045e9768b239785625caa6a2588842',
  '->hex() returns hash');
$t->is($h->h(),'f6513640f3045e9768b239785625caa6a2588842',
  '->h() returns hash');

$t->is($h->bin(), pack('H40', 'f6513640f3045e9768b239785625caa6a2588842'),
  '->bin() returns binary hash');
$t->is($h->b(), pack('H40', 'f6513640f3045e9768b239785625caa6a2588842'),
  '->b() returns binary hash');
$t->is((string)$h, pack('H40', 'f6513640f3045e9768b239785625caa6a2588842'),
  '__toString() returns binary hash');

$t->comment('Testing constructor');
$h = new SHA(pack('H40', 'f6513640f3045e9768b239785625caa6a2588842'));
$t->is((string)$h, pack('H40', 'f6513640f3045e9768b239785625caa6a2588842'),
  'constructor accepts bin string');
$h = new SHA('f6513640f3045e9768b239785625caa6a2588842');
$t->is((string)$h, pack('H40', 'f6513640f3045e9768b239785625caa6a2588842'),
  'constructor accepts hex string');

try
{
  $h = new SHA($quick);
  $t->fail('no code after exception on line '.__LINE__);
}
catch (Exception $e)
{
  $t->pass('Constructor throws an exception on random string'); 
}

try
{
  $h = new SHA('q6513640f3045e9768b239785625caa6a2588842');
  $t->fail('no code after exception on line '.__LINE__);
}
catch (Exception $e)
{
  $t->pass('Constructor throws an exception on string looking like a hex code'); 
}
