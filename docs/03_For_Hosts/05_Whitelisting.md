Modify `config.ini.php` to limit available bridges. Those changes should be applied in the `[system]` section.

## Enable all bridges

```
[system]

enabled_bridges[] = *
```

## Enable some bridges

```
[system]

enabled_bridges[] = TwitchBridge
enabled_bridges[] = GettrBridge
```

## Enable all bridges (legacy shortcut)

```
echo '*' > whitelist.txt
```

## Enable some bridges (legacy shortcut)

```
echo -e "TwitchBridge\nTwitterBridge" > whitelist.txt
```
