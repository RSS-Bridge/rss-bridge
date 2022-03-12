Create a new file in the `bridges/` folder (see [Folder structure](../04_For_Developers/03_Folder_structure.md)).

The file name must be named according to following specification:
* It starts with the full name of the site
* All white-space must be removed
* The first letter of a word is written in upper-case, unless the site name is specified otherwise (example: Freenews, not FreeNews, because the site is named 'Freenews')
* The first character must be upper-case
* The file name must end with 'Bridge'
* The file type must be PHP, written in **small** letters (seriously!) ".php"

**Examples:**

Site | Filename
-----|---------
Wikipedia | **Wikipedia**Bridge.php
Facebook | **Facebook**Bridge.php
GitHub | **GitHub**Bridge.php
Freenews | **Freenews**Bridge.php

The file must start with the PHP tags and end with an empty line. The closing tag `?>` is [omitted](http://php.net/basic-syntax.instruction-separation). 

**Example:**

```php
<?php
	// PHP code here
// This line is empty (just imagine it!)
```

The next step is to extend one of the base classes. Refer to one of an base classes listed on the [Bridge API](../05_Bridge_API/index.md) page.