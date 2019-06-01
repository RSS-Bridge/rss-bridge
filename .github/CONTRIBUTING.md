### Pull request policy

* [Fix one issue per pull request](https://github.com/RSS-Bridge/rss-bridge/wiki/Pull-request-policy#fix-one-issue-per-pull-request)
* [Respect the coding style policy](https://github.com/RSS-Bridge/rss-bridge/wiki/Pull-request-policy#respect-the-coding-style-policy)
* [Properly name your commits](https://github.com/RSS-Bridge/rss-bridge/wiki/Pull-request-policy#properly-name-your-commits)
  * When fixing a bridge (located in the `bridges` directory), write `[BridgeName] Feature` <br>(i.e. `[YoutubeBridge] Fix typo in video titles`).
  * When fixing other files, use `[FileName] Feature` <br>(i.e. `[index.php] Add multilingual support`).
  * When fixing a general problem that applies to multiple files, write `category: feature` <br>(i.e. `bridges: Fix various typos`).

Note that all pull-requests must pass all tests before they can be merged.

### Coding style

* [Whitespace](https://github.com/RSS-Bridge/rss-bridge/wiki/Whitespace)
  * [Add a new line at the end of a file](https://github.com/RSS-Bridge/rss-bridge/wiki/Whitespace#add-a-new-line-at-the-end-of-a-file)
  * [Do not add a whitespace before a semicolon](https://github.com/RSS-Bridge/rss-bridge/wiki/Whitespace#add-a-new-line-at-the-end-of-a-file)
  * [Do not add whitespace at start or end of a file or end of a line](https://github.com/RSS-Bridge/rss-bridge/wiki/Whitespace#do-not-add-whitespace-at-start-or-end-of-a-file-or-end-of-a-line)
* [Indentation](https://github.com/RSS-Bridge/rss-bridge/wiki/Indentation)
  * [Use tabs for indentation](https://github.com/RSS-Bridge/rss-bridge/wiki/Indentation#use-tabs-for-indentation)
* [Maximum line length](https://github.com/RSS-Bridge/rss-bridge/wiki/Maximum-line-length)
  * [The maximum line length should not exceed 80 characters](https://github.com/RSS-Bridge/rss-bridge/wiki/Maximum-line-length#the-maximum-line-length-should-not-exceed-80-characters)
* [Strings](https://github.com/RSS-Bridge/rss-bridge/wiki/Strings)
  * [Whenever possible use single quoted strings](https://github.com/RSS-Bridge/rss-bridge/wiki/Strings#whenever-possible-use-single-quote-strings)
  * [Add spaces around the concatenation operator](https://github.com/RSS-Bridge/rss-bridge/wiki/Strings#add-spaces-around-the-concatenation-operator)
  * [Use a single string instead of concatenating](https://github.com/RSS-Bridge/rss-bridge/wiki/Strings#use-a-single-string-instead-of-concatenating)
* [Constants](https://github.com/RSS-Bridge/rss-bridge/wiki/Constants)
  * [Use UPPERCASE for constants](https://github.com/RSS-Bridge/rss-bridge/wiki/Constants#use-uppercase-for-constants)
* [Keywords](https://github.com/RSS-Bridge/rss-bridge/wiki/Keywords)
  * [Use lowercase for `true`, `false` and `null`](https://github.com/RSS-Bridge/rss-bridge/wiki/Keywords#use-lowercase-for-true-false-and-null)
* [Operators](https://github.com/RSS-Bridge/rss-bridge/wiki/Operators)
  * [Operators must have a space around them](https://github.com/RSS-Bridge/rss-bridge/wiki/Operators#operators-must-have-a-space-around-them)
* [Functions](https://github.com/RSS-Bridge/rss-bridge/wiki/Functions)
  * [Parameters with default values must appear last in functions](https://github.com/RSS-Bridge/rss-bridge/wiki/Functions#parameters-with-default-values-must-appear-last-in-functions)
  * [Calling functions](https://github.com/RSS-Bridge/rss-bridge/wiki/Functions#calling-functions)
  * [Do not add spaces after opening or before closing bracket](https://github.com/RSS-Bridge/rss-bridge/wiki/Functions#do-not-add-spaces-after-opening-or-before-closing-bracket)
* [Structures](https://github.com/RSS-Bridge/rss-bridge/wiki/Structures)
  * [Structures must always be formatted as multi-line blocks](https://github.com/RSS-Bridge/rss-bridge/wiki/Structures#structures-must-always-be-formatted-as-multi-line-blocks)
* [If-Statement](https://github.com/RSS-Bridge/rss-bridge/wiki/if-Statement)
  * [Use `elseif` instead of `else if`](https://github.com/RSS-Bridge/rss-bridge/wiki/if-Statement#use-elseif-instead-of-else-if)
  * [Do not write empty statements](https://github.com/RSS-Bridge/rss-bridge/wiki/if-Statement#do-not-write-empty-statements)
  * [Do not write unconditional if-statements](https://github.com/RSS-Bridge/rss-bridge/wiki/if-Statement#do-not-write-unconditional-if-statements)
* [Classes](https://github.com/RSS-Bridge/rss-bridge/wiki/Classes)
  * [Use PascalCase for class names](https://github.com/RSS-Bridge/rss-bridge/wiki/Classes#use-pascalcase-for-class-names)
  * [Do not use final statements inside final classes](https://github.com/RSS-Bridge/rss-bridge/wiki/Classes#do-not-use-final-statements-inside-final-classes)
  * [Do not override methods to call their parent](https://github.com/RSS-Bridge/rss-bridge/wiki/Classes#do-not-override-methods-to-call-their-parent)
  * [abstract and final declarations MUST precede the visibility declaration](https://github.com/RSS-Bridge/rss-bridge/wiki/Classes#abstract-and-final-declarations-must-precede-the-visibility-declaration)
  * [static declaration MUST come after the visibility declaration](https://github.com/RSS-Bridge/rss-bridge/wiki/Classes#static-declaration-must-come-after-the-visibility-declaration)
* [Casting](https://github.com/RSS-Bridge/rss-bridge/wiki/Casting)
  * [Do not add spaces when casting](https://github.com/RSS-Bridge/rss-bridge/wiki/Casting#do-not-add-spaces-when-casting)
