The `FormatAbstract` class implements the [`FormatInterface`](../08_Format_API/02_FormatInterface.md) interface with basic functional behavior and adds common helper functions for new formats:

* [sanitizeHtml](#the-sanitizehtml-function)

# Functions

## The `sanitizeHtml` function

The `sanitizeHtml` function receives an HTML formatted string and returns the string with disabled `<script>`, `<iframe>` and `<link>` tags.

```PHP
sanitize_html(string $html): string
```
