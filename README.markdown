***Development has moved to http://github.com/xaav/GitBundle.***

glip is a Git Library In PHP. It allows you to access bare git repositories
from PHP scripts, even without having git installed. The project's homepage is
located at <http://fimml.at/glip>.


glip was formerly part of eWiki, a wiki software written in PHP using git as
version control backend. You can get more information on eWiki from
<http://fimml.at/ewiki>.

glip was split off eWiki on May 31, 2009. An attempt was made to preserve
commit history by using git filter-branch; this also means that commit
messages before May 31, 2009 may seem weird (esp. wrt file names).

## Usage ##

Include the autoload file, as shown below:

```php5
<?php

require_once __DIR__.'lib/autoload.php';

```

Create a new Git repository:

```php5
<?php

use Glip\Git

$repo = new Git('project/.git');

```


***Anyone who wants to contribute to this project is more than welcome to send a pull request***
