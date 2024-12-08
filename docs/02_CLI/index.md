RSS-Bridge supports calls via CLI.
You can use the same parameters as you would normally use via the URI. Example:

`php index.php action=display bridge=DansTonChat format=Json`

## Required parameters

RSS-Bridge requires a few parameters that must be specified on every call.
Omitting these parameters will result in error messages:

### action

Defines how RSS-Bridge responds to the request.

Value | Description
----- | -----------
`action=list` | Returns a JSON formatted list of bridges. Other parameters are ignored.
`action=display` | Returns (displays) a feed.

### bridge

This parameter specifies the name of the bridge RSS-Bridge should return feeds from.
The name of the bridge equals the class name of the bridges in the ./bridges/ folder without the 'Bridge' prefix.
For example: DansTonChatBridge => DansTonChat.

### format

This parameter specifies the format in which RSS-Bridge returns the contents.

## Optional parameters

RSS-Bridge supports optional parameters.
These parameters are only valid if the options have been enabled in the index.php script.

### \_noproxy

This parameter is only available if a proxy server has been specified via `proxy.url` and `proxy.by_bridge`
has been enabled. This is a Boolean parameter that can be set to `true` or `false`.

## Bridge parameters

Each bridge can specify its own set of parameters.
As in the example above, some bridges don't specify any parameters or only optional parameters that can be neglected.
For more details read the `PARAMETERS` definition for your bridge.
