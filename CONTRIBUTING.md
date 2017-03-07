### Pull request policy
Fix one issue per pull request.  
Squash commits before opening a pull request.  
Respect the coding style policy.    
Name your PR like the following :

* When correcting a single bridge, use `[BridgeName] Feature`.  
* When fixing a problem in a specific file, use `[FileName] Feature`.
* When fixing a general problem, use `category : feature`.

Note that all pull-requests should pass the unit tests before they can be merged.  

### Coding style

Use `camelCase` for variables and methods.  
Use `UPPERCASE` for constants.  
Use `PascalCase` for class names. When creating a bridge, your class and PHP file should be named `MyImplementationBridge`.   
Use tabs for indentation.  
Add an empty line at the end of your file.  

Use `''` to encapsulate strings, including in arrays.  
Prefer lines shorter than 80 chars, no line longer than 120 chars.  
PHP constants should be in lower case (`true, false, null`...)  


* Add spaces between the logical operator and your expressions (not needed for the `!` operator).  
* Use `||` and `&&` instead of `or` and `and`.  
* Add space between your condition and the opening bracket/closing bracket.  
* Don't put a space between `if` and your bracket.  
* Use `elseif` instead of `else if`.  
* Add new lines in your conditions if they are containing more than one line.  
*  Example :  

```PHP
if($a == true && $b) {
  print($a);
} else if(!$b) {

  $a = !$a;
  $b = $b >> $a;
  print($b);

} else {
  print($b);
}
```

