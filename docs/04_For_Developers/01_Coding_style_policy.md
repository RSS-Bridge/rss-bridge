This section explains the coding style policy for RSS-Bridge with examples and references to external resources. Please make sure your code is compliant before opening a pull request.

RSS-Bridge uses [Travis-CI](https://travis-ci.org/) to validate code quality. You will automatically be notified if issues were found in your pull request. You must fix those issues before the pull request will be merged. Refer to [phpcs.xml](https://github.com/RSS-Bridge/rss-bridge/blob/master/phpcs.xml) for a complete list of policies enforced by Travis-CI.

If you want to run the checks locally, make sure you have [`phpcs`](https://github.com/squizlabs/PHP_CodeSniffer) and [`phpunit`](https://phpunit.de/) installed on your machine and run following commands in the root directory of RSS-Bridge (tested on Debian):

```console
phpcs . --standard=phpcs.xml --warning-severity=0 --extensions=php -p
phpunit --configuration=phpunit.xml --include-path=lib/
```

The following list provides an overview of all policies applied to RSS-Bridge.

# Whitespace

## Add a new line at the end of a file

Each PHP/CSS/HTML file must end with a new line at the end of a file.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
{
    // code here
} // This is the end of the file
```

**Good**

```PHP
{
    // code here
}
// This is the end of the file
```

</div></details><br>

_Reference_: [`PSR2.Files.EndFileNewline`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/PSR2/Sniffs/Files/EndFileNewlineSniff.php)

## Do not add a whitespace before a semicolon

A semicolon indicates the end of a line of code. Spaces before the semicolon is unnecessary and must be removed.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
echo 'Hello World!' ;
```

**Good**

```PHP
echo 'Hello World!';
```

</div></details><br>

_Reference_: [`Squiz.WhiteSpace.SemicolonSpacing`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Squiz/Sniffs/WhiteSpace/SemicolonSpacingSniff.php)

## Do not add whitespace at start or end of a file or end of a line

Whitespace at the end of lines or at the start or end of a file is invisible to the reader and absolutely unnecessary. Thus it must be removed.

_Reference_: [`Squiz.WhiteSpace.SuperfluousWhitespace`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Squiz/Sniffs/WhiteSpace/SuperfluousWhitespaceSniff.php)

# Indentation
## Use tabs for indentation

RSS-Bridge uses tabs for indentation on all PHP files in the repository (except files located in the `vendor` directory)

_Reference_: [`Generic.WhiteSpace.DisallowSpaceIndent`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Generic/Sniffs/WhiteSpace/DisallowSpaceIndentSniff.php)

# Maximum Line Length

## The maximum line length should not exceed 80 characters

One line of code should have no more than **80 characters** (soft limit) and must never exceed **120 characters** (hard limit).

_Notice_: Travis-CI enforces the hard limit of 120 characters. Maintainers may ask you to indent lines longer than 80 characters before merging. This is generally done to keep the code as readable and maintainable as possible.

For long conditional statements, consider indenting the statement into multiple lines.

<details><summary>Example</summary><div><br>

**Bad** (the total length of the line is **94** characters)

```PHP
if($time !== false && (time() - $duration < $time) && (!defined('DEBUG') || DEBUG !== true)) {

}
```

**Good** (add line breaks)

```PHP
if($time !== false
&& (time() - $duration < $time)
&& (!defined('DEBUG') || DEBUG !== true)) {

}
```

</div></details><br>

For long text, either add line feeds, or make use of the [`heredoc`](http://php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc) syntax.

<details><summary>Example</summary><div><br>

**Bad** (the total length of the line is **340** characters - from [Lorem Ipsum](https://www.lipsum.com/feed/html))

```PHP
$longtext = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse condimentum nec est eget posuere. Proin at sagittis risus. Fusce faucibus lectus leo, eu ornare velit tristique eu. Curabitur elementum facilisis ultricies. Praesent dictum fermentum lectus a rhoncus. Donec vitae justo metus. Sed molestie faucibus egestas.';
```

**Good** (use `heredoc` syntax - this will add line-breaks)

```PHP
$longtext = <<<EOD
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse
condimentum nec est eget posuere. Proin at sagittis risus. Fusce faucibus
lectus leo, eu ornare velit tristique eu. Curabitur elementum facilisis
ultricies. Praesent dictum fermentum lectus a rhoncus. Donec vitae justo metus.
Sed molestie faucibus egestas.
EOD;
```

</div></details><br>

_Reference_: [`Generic.Files.LineLength`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Generic/Sniffs/Files/LineLengthSniff.php)

# Strings

## Whenever possible use single quote strings

PHP supports both single quote strings and double quote strings. For pure text you must use single quote strings for consistency. Double quote strings are only allowed for special characters (i.e. `"\n"`) or inlined variables (i.e. `"My name is {$name}"`);

<details><summary>Example</summary><div><br>

**Bad**

```PHP
echo "Hello World!";
```

**Good**

```PHP
echo 'Hello World!';
```

</div></details><br>

_Reference_: [`Squiz.Strings.DoubleQuoteUsage`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Squiz/Sniffs/Strings/DoubleQuoteUsageSniff.php)

## Add spaces around the concatenation operator

The concatenation operator should have one space on both sides in order to improve readability.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
$text = $greeting.' '.$name.'!';
```

**Good** (add spaces)

```PHP
$text = $greeting . ' ' . $name . '!';
```

</div></details><br>

You may break long lines into multiple lines using the concatenation operator. That way readability can improve considerable when combining lots of variables.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
$text = $greeting.' '.$name.'!';
```

**Good** (split into multiple lines)

```PHP
$text = $greeting
. ' '
. $name
. '!';
```

</div></details><br>

_Reference_: [`Squiz.Strings.ConcatenationSpacing`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Squiz/Sniffs/Strings/ConcatenationSpacingSniff.php)

## Use a single string instead of concatenating

While concatenation is useful for combining variables with other variables or static text. It should not be used to combine two sets of static text. See also: [Maximum line length](#maximum-line-length)

<details><summary>Example</summary><div><br>

**Bad**

```PHP
$text = 'This is' . 'a bad idea!';
```

**Good**

```PHP
$text = 'This is a good idea!';
```

</div></details><br>

_Reference_: [`Generic.Strings.UnnecessaryStringConcat`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Generic/Sniffs/Strings/UnnecessaryStringConcatSniff.php)

# Constants

## Use UPPERCASE for constants

As in most languages, constants should be written in UPPERCASE.

_Notice_: This does not apply to keywords!

<details><summary>Example</summary><div><br>

**Bad**

```PHP
const pi = 3.14;
```

**Good**

```PHP
const PI = 3.14;
```

</div></details><br>

_Reference_: [`Generic.NamingConventions.UpperCaseConstantName`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Generic/Sniffs/NamingConventions/UpperCaseConstantNameSniff.php)

# Keywords
## Use lowercase for `true`, `false` and `null`

`true`, `false` and `null` must be written in lower case letters.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
if($condition === TRUE && $error === FALSE) {
    return NULL;
}
```

**Good**

```PHP
if($condition === true && $error === false) {
    return null;
}
```

</div></details><br>

_Reference_: [`Generic.PHP.LowerCaseConstant`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Generic/Sniffs/PHP/LowerCaseConstantSniff.php)

# Operators
## Operators must have a space around them

Operators must be readable and therefore should have spaces around them.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
$text='Hello '.$name.'!';
```

**Good**

```PHP
$text = 'Hello ' . $name . '!';
```

</div></details><br>

_Reference_: [`Squiz.WhiteSpace.OperatorSpacing`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Squiz/Sniffs/WhiteSpace/OperatorSpacingSniff.php)

# Functions
## Parameters with default values must appear last in functions

It is considered good practice to make parameters with default values last in function declarations.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
function showTitle($duration = 60000, $title) { ... }
```

**Good**

```PHP
function showTitle($title, $duration = 60000) { ... }
```

</div></details><br>

_Reference_: [`PEAR.Functions.ValidDefaultValue`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/PEAR/Sniffs/Functions/ValidDefaultValueSniff.php)

## Calling functions

Function calls must follow a few rules in order to maintain readability throughout the project:

**Do not add whitespace before the opening parenthesis**

<details><summary>Example</summary><div><br>

**Bad**

```PHP
$result = my_function ($param);
```

**Good**

```PHP
$result = my_function($param);
```

</div></details><br>

**Do not add whitespace after the opening parenthesis**

<details><summary>Example</summary><div><br>

**Bad**

```PHP
$result = my_function( $param);
```

**Good**

```PHP
$result = my_function($param);
```

</div></details><br>

**Do not add a space before the closing parenthesis**

<details><summary>Example</summary><div><br>

**Bad**

```PHP
$result = my_function($param );
```

**Good**

```PHP
$result = my_function($param);
```

</div></details><br>

**Do not add a space before a comma**

<details><summary>Example</summary><div><br>

**Bad**

```PHP
$result = my_function($param1 ,$param2);
```

**Good**

```PHP
$result = my_function($param1, $param2);
```

</div></details><br>

**Add a space after a comma**

<details><summary>Example</summary><div><br>

**Bad**

```PHP
$result = my_function($param1,$param2);
```

**Good**

```PHP
$result = my_function($param1, $param2);
```

</div></details><br>

_Reference_: [`Generic.Functions.FunctionCallArgumentSpacing`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Generic/Sniffs/Functions/FunctionCallArgumentSpacingSniff.php)

## Do not add spaces after opening or before closing bracket

Parenthesis must tightly enclose parameters.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
if( $condition ) { ... }
```

**Good**

```PHP
if($condition) { ... }
```

</div></details><br>

_Reference_: [`PSR2.ControlStructures.ControlStructureSpacing`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/PSR2/Sniffs/ControlStructures/ControlStructureSpacingSniff.php)

# Structures
## Structures must always be formatted as multi-line blocks

A structure should always be treated as if it contains a multi-line block.

**Add a space after closing parenthesis**

<details><summary>Example</summary><div><br>

**Bad**

```PHP
if($condition){
    ...
}
```

**Good**

```PHP
if($condition) {
    ...
}
```

</div></details><br>

**Add body into new line**

<details><summary>Example</summary><div><br>

**Bad**

```PHP
if($condition){ ... }
```

**Good**

```PHP
if($condition) {
    ...
}
```

</div></details><br>

**Close body in new line**

<details><summary>Example</summary><div><br>

**Bad**

```PHP
if($condition){
    ... }
```

**Good**

```PHP
if($condition) {
    ...
}
```

</div></details><br>

_Reference_: [`Squiz.ControlStructures.ControlSignature`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Squiz/Sniffs/ControlStructures/ControlSignatureSniff.php)

# If-Statements
## Use `elseif` instead of `else if`

For sake of consistency `else if` is considered bad practice.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
if($conditionA) {

} else if($conditionB) {

}
```

**Good**

```PHP
if($conditionA) {

} elseif($conditionB) {

}
```

</div></details><br>

_Reference_: [`PSR2.ControlStructures.ElseIfDeclaration`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/PSR2/Sniffs/ControlStructures/ElseIfDeclarationSniff.php)

## Do not write empty statements

Empty statements are considered bad practice and must be avoided.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
if($condition) {
    // empty statement
} else {
    // do something here
}
```

**Good** (invert condition)

```PHP
if(!$condition) {
    // do something
}
```

</div></details><br>

_Reference_: [`Generic.CodeAnalysis.EmptyStatement`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Generic/Sniffs/CodeAnalysis/EmptyStatementSniff.php)

## Do not write unconditional if-statements

If-statements without conditions are considered bad practice and must be avoided.

<details><summary>Example</summary><div><br>

```PHP
if(true) {

}
```

</div></details><br>

_Reference_: [`Generic.CodeAnalysis.UnconditionalIfStatement`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Generic/Sniffs/CodeAnalysis/UnconditionalIfStatementSniff.php)

# Classes
## Use PascalCase for class names

Class names must be written in [PascalCase](http://wiki.c2.com/?PascalCase).

<details><summary>Example</summary><div><br>

**Bad**

```PHP
class mySUPERclass { ... }
```

**Good**

```PHP
class MySuperClass { ... }
```

</div></details><br>

_Reference_: [`PEAR.NamingConventions.ValidClassName`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/PEAR/Sniffs/NamingConventions/ValidClassNameSniff.php)

## Do not use final statements inside final classes

Final classes cannot be extended, so it doesn't make sense to add the final keyword to class members.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
final class MyClass {
    final public function MyFunction() {

    }
}
```

**Good** (remove the final keyword from class members)

```PHP
final class MyClass {
    public function MyFunction() {

    }
}
```

</div></details><br>

_Reference_: [`Generic.CodeAnalysis.UnnecessaryFinalModifier`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Generic/Sniffs/CodeAnalysis/UnnecessaryFinalModifierSniff.php)

## Do not override methods to call their parent

It doesn't make sense to override a method only to call their parent. When overriding methods, make sure to add some functionality to it.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
class MyClass extends BaseClass {
    public function BaseFunction() {
        parent::BaseFunction();
    }
}
```

**Good** (don't override the function)

```PHP
class MyClass extends BaseClass {

}
```

</div></details><br>

_Reference_: [`Generic.CodeAnalysis.UselessOverridingMethod`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Generic/Sniffs/CodeAnalysis/UselessOverridingMethodSniff.php)

## abstract and final declarations MUST precede the visibility declaration

When declaring `abstract` and `final` functions, the visibility (scope) must follow after `abstract` or `final`.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
class MyClass extends BaseClass {
    public abstract function AbstractFunction() { }
    public final function FinalFunction() { }
}
```

**Good** (`abstract` and `final` before `public`)

```PHP
class MyClass extends BaseClass {
    abstract public function AbstractFunction() { }
    final public function FinalFunction() { }
}
```

</div></details><br>

_Reference_: [`PSR2.Methods.MethodDeclaration`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/PSR2/Sniffs/Methods/MethodDeclarationSniff.php)

## static declaration MUST come after the visibility declaration

The `static` keyword must come after the visibility (scope) parameter.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
class MyClass extends BaseClass {
    static public function StaticFunction() { }
}
```

**Good** (`static` after `public`)

```PHP
class MyClass extends BaseClass {
    public static function StaticFunction() { }
}
```

</div></details><br>

_Reference_: [`PSR2.Methods.MethodDeclaration`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/PSR2/Sniffs/Methods/MethodDeclarationSniff.php)

# Casting
## Do not add spaces when casting

The casting type should be put into parenthesis without spaces.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
$text = ( string )$number;
```

**Good**

```PHP
$text = (string)$number;
```

</div></details><br>

_Reference_: [`Squiz.WhiteSpace.CastSpacing`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Squiz/Sniffs/WhiteSpace/CastSpacingSniff.php)

# Arrays
## Always use the long array syntax

Arrays should be initialized using the long array syntax.

<details><summary>Example</summary><div><br>

**Bad**

```PHP
$data = [ 'hello' => 'world' ];
```

**Good**

```PHP
$data = array('hello' => 'world');
```

</div></details><br>

_Reference_: [`Generic.Arrays.DisallowShortArraySyntax`](https://github.com/squizlabs/PHP_CodeSniffer/blob/master/src/Standards/Generic/Sniffs/Arrays/DisallowShortArraySyntaxSniff.php)