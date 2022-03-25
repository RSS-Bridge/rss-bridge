Create a new file in the `formats/` folder (see [Folder structure](../04_For_Developers/03_Folder_structure.md)).

The file must be named according to following specification:

* It starts with the type
* The file name must end with 'Format'
* The file type must be PHP, written in small letters (seriously!) ".php"

**Examples:**

Type | Filename
-----|---------
Atom | AtomFormat.php
Html | HtmlFormat.php

The file must start with the PHP tags and end with an empty line. The closing tag `?>` is [omitted](http://php.net/basic-syntax.instruction-separation).

Example:

```PHP
<?PHP
    // PHP code here
// This line is empty (just imagine it!)
```