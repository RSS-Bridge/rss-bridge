The `FormatAbstract` class implements the [`FormatInterface`](../08_Format_API/02_FormatInterface.md) interface with basic functional behavior and adds common helper functions for new formats:

* [setContentType](#the-setcontenttype-function)
* [callContentType](#the-callcontenttype-function)
* [sanitizeHtml](#the-sanitizehtml-function)
* [array_trim](#the-array_trim-function)

# Functions

## The `setContentType` function

The `setContentType` function receives a string defining the content type for the HTML header and must return the object instance.

```PHP
setContentType(string): self
```

## The `callContentType` function

The `callContentType` function applies the content type to the header data and must return the object instance.

```PHP
callContentType(): self
```

## The `sanitizeHtml` function

The `sanitizeHtml` function receives an HTML formatted string and returns the string with disabled `<script>`, `<iframe>` and `<link>` tags.

```PHP
sanitizeHtml(string $html): string
```

## The `array_trim` function

The `array_trim` function receives an array of strings and returns the same array with all elements trimmed (like using the `trim` function on all elements)

```PHP
array_trim(array $elements): array
```
