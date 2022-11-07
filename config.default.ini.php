; <?php exit; ?> DO NOT REMOVE THIS LINE

; This file contains the default settings for RSS-Bridge. Do not change this
; file, it will be replaced on the next update of RSS-Bridge! You can specify
; your own configuration in 'config.ini.php' (copy this file).

[system]

; Defines the timezone used by RSS-Bridge
; Find a list of supported timezones at
; https://www.php.net/manual/en/timezones.php
; timezone = "UTC" (default)
timezone = "UTC"

[http]
timeout = 60
useragent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0"

[cache]

; Defines the cache type used by RSS-Bridge
; "file" = FileCache (default)
type = "file"

; Allow users to specify custom timeout for specific requests.
; true  = enabled
; false = disabled (default)
custom_timeout = false

[admin]
; Advertise an email address where people can reach the administrator.
; This address is displayed on the main page, visible to everyone!
; ""    = Disabled (default)
email = ""

; Advertise a contact Telegram url e.g. "https://t.me/elegantobjects"
telegram = ""

; Show Donation information for bridges if available.
; This will display a 'Donate' link on the bridge view
; and a "Donate" button in the HTML view of the bridges feed.
; true  = enabled (default)
; false = disabled
donations = true

[proxy]

; Sets the proxy url (i.e. "tcp://192.168.0.0:32")
; ""    = Proxy disabled (default)
url = ""

; Sets the proxy name that is shown on the bridge instead of the proxy url.
; ""    = Show proxy url
name = "Hidden proxy name"

; Allow users to disable proxy usage for specific requests.
; true  = enabled
; false = disabled (default)
by_bridge = false

[authentication]

; Enables basic authentication for all requests to this RSS-Bridge instance.
;
; Warning: You'll have to upgrade existing feeds after enabling this option!
;
; true  = enabled
; false = disabled (default)
enable = false

username = "admin"

; This default password is public knowledge. Replace it.
password = "7afbf648a369b261"

; This will be used only for actions that require privileged access
access_token = ""

[error]

; Defines how error messages are returned by RSS-Bridge
;
; "feed" = As part of the feed (default)
; "http" = As HTTP error message
; "none" = No errors are reported
output = "feed"

; Defines how often an error must occur before it is reported to the user
report_limit = 1

; --- Cache specific configuration ---------------------------------------------

[SQLiteCache]
file = "cache.sqlite"

[MemcachedCache]
host = "localhost"
port = 11211

; --- Bridge specific configuration ------

[DiscogsBridge]

; Sets the personal access token for interactions with Discogs. When
; provided, images can be included in generated feeds.
;
; "" = no token used (default)
personal_access_token = ""
