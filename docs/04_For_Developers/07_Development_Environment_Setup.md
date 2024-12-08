
## Docs with Docker

If you want to edit the docs add this to your docker-compose.yml:

```yml
services:
  [...]

  daux:
    image: daux/daux.io
    ports:
      - 8085:8085
    working_dir: /build
    volumes:
      - ./rss-bridge/docs:/build/docs
    network_mode: host
```

and run for example the `daux serve` command with `docker-compose run --rm daux daux serve`.
After that you can access the docs at `localhost:8085` and edit the files in `rss-bridge/docs`.
