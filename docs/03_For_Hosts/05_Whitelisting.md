Modify `config.ini.php` to limit available bridges.

## Enable all bridges

```
enabled_bridges[] = *
```

## Enable some bridges

```
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
