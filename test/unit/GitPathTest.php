<?php
require_once dirname(__FILE__).'/../bootstrap/unit.php';

$t = new lime_test(34, new lime_output_color());

$t->comment('Testing output');
$a = array('abc','def','ghi');
$p = new GitPath($a);
$t->is((string)$p,'abc/def/ghi',
  '__tostring returns string');
$t->is((string)$p->getTreePart(),'abc/def/',
  'getTreePart() returns string');
$t->is((string)$p->getBlobPart(),'ghi',
  'getBlobPart() returns string');
$t->ok($p->getShifted() instanceof GitPath,
  'getShifted() returns GitPath');
$t->is((string)$p->getShifted(),'def/ghi',
  'getShifted() returns all items after first part');

$t->comment('Constructor testing');
$s = 'abc/def/ghi';
$p = new GitPath("/".$s);
$t->is((string)$p,$s,
  'constructor removes leading slashes');
$p = new GitPath("/////".$s);
$t->is((string)$p,$s,
  'constructor removes leading slashes');
$p = new GitPath("");
$t->is((string)$p,"/",
  'constructor makes empty string into root reference');
$p = new GitPath("/");
$t->is((string)$p,"/",
  'constructor accepts / as root reference');
  
$s .= '/';
$p = new GitPath($s);
$t->is((string)$p,$s,
  'constructor accepts refTree paths');
$p = new GitPath($s.'/');
$t->is((string)$p,$s,
  'constructor removes trailing slashes');
$p = new GitPath($s.'/////');
$t->is((string)$p,$s,
  'constructor removes trailing slashes');

$p = new GitPath('abc//def//ghi//');
$t->is((string)$p,$s,
  'constructor removes empty parts');
$p = new GitPath('abc  // def//   ghi // ');
$t->is((string)$p,$s,
  'constructor removes extra spaces');
$p = new GitPath('ab c  // d ef//   g h i // ');
$t->is((string)$p,'ab c/d ef/g h i/',
  'constructor leaves inner spaces');

$t->comment('isSingle testing');
$p = new GitPath("/");
$t->ok($p->isSingle(), 'root is a single reference');
$p = new GitPath("test/");
$t->ok($p->isSingle(), 'one directory is a single reference');
$p = new GitPath("test/file");
$t->ok(!$p->isSingle(), 'a file in a directory is not single');
$p = new GitPath("/test");
$t->ok($p->isSingle(), 'one file is a single reference');
  
$t->comment('isRoot testing');
$p = new GitPath("/");
$t->ok($p->isRoot(), 'root is a root');
$p = new GitPath("");
$t->ok($p->isRoot(), 'empty is a root');
$p = new GitPath("test");
$t->ok(!$p->isRoot(), 'a file is not a root');

$t->comment('Testing differences for Tree and Blob paths');
$p = new GitPath("abc/def/ghi");
$t->ok($p->refBlob(), 'no trailing slash references a blob object');
$p = new GitPath("abc/def/ghi/");
$t->ok($p->refTree(), 'a trailing slash references a tree object');

$t->comment('Testing array access');
$p = new GitPath("abc/def/ghi");
$t->is($p[0],'abc',
  'allows array access');
$t->is($p[-1],'ghi',
  'allows negative index array access');
$t->is(count($p),3,
  'count returns number of elements');
  
$t->comment('Testing iterator');
$p = new GitPath("abc/def");
foreach ($p as $index=>$part)
{
  switch ($index)
  {
    case 0:
      $t->is($part, 'abc', 'first iteration');
      break;
    case 1:
      $t->is($part, 'def', 'second iteration');
      break;
  }
}

$t->comment('Testing unset');
$p = new GitPath("abc/def/ghi/jkl");
unset($p[0]);
$t->is((string)$p,"def/ghi/jkl",
  'unset [0] removes first element');
unset($p[-1]);
$t->is((string)$p,"def/ghi/",
  'unset [-1] removes last element');
  
$t->comment('Ancestor check');
$child = new GitPath("abc/def/ghi");
$t->ok($child->hasAncestor(new GitPath('/')),
  'Root is an ancestor of all');
$t->ok($child->hasAncestor(new GitPath('/abc/def/')),
  'A directory can be an ancestor');
$t->ok(!$child->hasAncestor(new GitPath('/abc/def')),
  'A blob path is never an ancestor');