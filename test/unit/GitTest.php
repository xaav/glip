<?php
require_once dirname(__FILE__).'/../bootstrap/unit.php';

function execGitCommand($command, &$output = null)
{
  $return_var = null;
  if (is_null($output))
    $output = array();
  exec('git ' . $command . ' 2>&1', $output, $return_var);
  return $return_var;
}

function isGitFsck()
{
  return 0 == execGitCommand('fsck --full');
}

$t = new lime_test(36, new lime_output_color());

$t->comment('Setting up new Git repository in temporary dir');
{
  $directory = sys_get_temp_dir() . 'PHPtmp-' . mt_rand(100000, 999999);
} while (!mkdir($directory, 0700));
$t->comment('- created directory: '.$directory);
chdir($directory);
$t->comment('- set as working directory: '.$directory);
execGitCommand('init');
$git = new Git($directory . DIRECTORY_SEPARATOR . '.git');
$t->comment('- initialized .git repository');

// ============================================================================================

$t->comment('Test reading of files');
file_put_contents('test1', 'data1');
file_put_contents('test2', 'data2');
execGitCommand('add .');
execGitCommand('commit -m "Added 2 test files"');

$branch = $git['master'];
$t->ok($branch instanceof GitBranch,
  'array access on Git returns a branch');
$commit = $branch->getTip(true);
$t->ok($commit instanceof GitCommit,
  '->getTip() returns GitCommit');
$t->is($commit->summary,'Added 2 test files',
  'summary is properly read');
$tree = $commit->tree;
$t->ok($tree instanceof GitTree,'a commit holds a GitTree tree propery');
$t->is(count($tree), 2,
  'GitTree is countable');

$t->ok($tree instanceof IteratorAggregate,
  'GitTree implements iterator interface');

foreach ($tree as $name => $node)
{
  switch ($name)
  {
    case "test1":
    case "test2":
      $t->ok($node instanceof GitBlob, "$name is a GitBlob");
      $t->is($node->data, 'data'.substr($name,-1,1), "data is properly stored in a blob");
      break;
    default:
      $t->fail("unknown file '$name' found"); 
      $t->fail("unknown file '$name' contains no data"); 
  }
}

// ============================================================================================

$t->comment('Testing directories');
mkdir('dir1');
file_put_contents('dir1' . DIRECTORY_SEPARATOR . 'file', 'dir1');
mkdir('dir2');
file_put_contents('dir2' . DIRECTORY_SEPARATOR . 'file', 'dir2');
execGitCommand('add .');
execGitCommand('commit -m "Added 2 test dirs"');

$tree = $branch->getTip(true)->tree;
$t->is(count($tree),4,
  'count(GitTree) returns number of files+number of dirs in the tree');

foreach ($tree as $name => $node)
{
  if (substr($name,0,3) == 'dir')
  {
    $t->isa_ok($node, 'GitTree', 'Directories are new GitTree objects');
    $t->isa_ok($node['file'], 'GitBlob', 'Files can be referenced by array access in the tree');
  }
}

$t->isa_ok($tree['dir1']['file'], 'GitBlob',
  'Multiple level array access returns the node');
$t->isa_ok($tree['dir1/file'], 'GitBlob',
  'A full path returns the node');
$t->isa_ok($tree[new GitPath('dir1/file')], 'GitBlob',
  'A full path as GitPath returns the node');
$t->isa_ok($tree[array('dir1','file')], 'GitBlob',
  'A path referenced as array works');

// ============================================================================================

$t->comment('Test SHA computation when adding new objects');

$commit = $branch->getTip(true);
$newCommit = new GitCommit($commit);

file_put_contents('newfile', 'newdata');
execGitCommand('add .');
execGitCommand('commit -m "Added 2 test files"');

//load the new commit
$commit = $branch->getTip(true);

$newCommit['newfile'] = 'newdata';
$t->isa_ok($newCommit['newfile'], 'GitBlob',
  'array setting automatically converts to GitBlob');  
  
//equalize modes
$newCommit['newfile']->setMode($commit['newfile']->getMode());

$t->is($newCommit->tree->getSha()->hex(), $commit->tree->getSha()->hex(),
  'The correct tree sha is computed');
  
// ============================================================================================

$t->comment('Adding multiple level of directory blobs');

$branch[array('newdir','subdir','file')] = 'test';
$branch['newdir/subdir/subsubdir/file1'] = 'test';
$branch['newdir/subdir/subsubdir/file2'] = 'test';
$commit = $branch->commit(new GitCommitStamp(),'multilevel write');
$t->isa_ok($commit, 'GitCommit', 
  'committing on a branch returns a git commit object');
$t->is($commit->getSha()->hex(), $branch->getTip()->getSha()->hex(),
  'The new tip of the branch is returned');

$t->ok(isGitFsck(),
  'Git repos is still valid after writing');

$t->is($commit->summary, 'multilevel write',
  'the correct commit is loaded');

$t->isa_ok($commit['newdir'], 'GitTree',
  'First level properly written');  
$t->isa_ok($commit['newdir']['subdir'], 'GitTree',
  'Second level properly written');  
$t->isa_ok($commit['newdir/subdir/file'], 'GitBlob',
  'Third level properly written');  
$blob = $commit['newdir/subdir/file'];
$t->is($blob->data, 'test',
  'multiple levels of directories automatically written');
  
// ============================================================================================

$t->comment('Removing a blob works');

unset($branch['newdir/subdir/subsubdir/file1']);
$branch->commit(new GitCommitStamp(), 'test delete');

$commit = $branch->getTip(true);
$t->ok(isGitFsck(),
  'Git repos is still valid after writing');

$t->is($commit->summary, 'test delete',
  'the correct commit is loaded');

$t->isa_ok($commit['newdir/subdir/subsubdir/file2'], 'GitBlob',
  'Sibling of deleted object still exists');

$t->ok(is_null($commit['newdir/subdir/subsubdir/file1']),
  'Object is deleted');
  
unset($branch['newdir/subdir/subsubdir/file2']);
$branch->commit(new GitCommitStamp(), 'test delete empty dirs');

$commit = $branch->getTip(true);
$t->ok(isGitFsck(),
  'Git repos is still valid after writing');

$t->is($commit->summary, 'test delete empty dirs',
  'the correct commit is loaded');

$t->ok(is_null($commit['newdir/subdir/subsubdir']),
  'Object and the parent tree are deleted');

// ============================================================================================

$t->comment('Test reading a compacted repository');
$t->comment(' - compacting repository...');
$out = array();
execGitCommand('gc', $out);
$t->comment(' - compacting done: ' . implode("\n",$out));
$git = new Git($directory . DIRECTORY_SEPARATOR . '.git');
$branch = $git['master'];
$blob = $branch['newdir/subdir/file'];
$t->is($blob->data, 'test',
  'Reading data from a compacted blob works');

// ============================================================================================

$t->comment('Test stash behaviour of a branch');
$branch = $git['master'];
$branch['test1'] = 'newValue';
$t->is($branch['test1']->data, 'newValue',
  'Reading objects from the branch returns the object in stash');
$t->is($branch->getTip(true)->tree['test1']->data, 'data1',
  'The tip of the branch still points to the old data');
$branch->commit(new GitCommitStamp(),'testing stash behaviour');
$t->is($branch->getTip(true)->tree['test1']->data, 'newValue',
  'After committing the tip contains the new value');

$branch['newstashdir/file'] = 'value';
$t->ok(is_null($branch->getTip()->tree['newstashdir']),
  'GitTree does not exist in repository');
$t->isa_ok($branch['newstashdir'], 'GitTree',
  'Branches create non existing GitTree from contents in their stash');
$t->is(count($branch['newstashdir']), 1,
  'Just 1 file inside the GitTree');
  
$found = false;
foreach ($branch['/'] as $path => $obj)
{
  if ($path == 'newstashdir')
  {
    $found = true;
    break;
  }
}
$t->ok($found,
  'The new tree is a child in the parent tree');
