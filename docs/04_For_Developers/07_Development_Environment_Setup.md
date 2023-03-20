These are examples of how to setup a local development environment to add bridges, improve the docs, etc.

## Docker

The following can serve as an example for using docker:

```
# create a new directory
mkdir rss-bridge-contribution
cd rss-bridge-contribution

# clone the project into a subfolder
git clone https://github.com/RSS-Bridge/rss-bridge
```

Then add a `docker-compose.yml` file:

```yml
version: '3'

services:
  rss-bridge:
    build:
      context: ./rss-bridge
    ports:
      - 3000:80
    volumes:
      - ./config:/config
      - ./rss-bridge/bridges:/app/bridges
```

You can then access RSS-Bridge at `localhost:3000` and [add your bridge](../05_Bridge_API/How_to_create_a_new_bridge) to the `rss-bridge/bridges` folder.

If you need to edit any other files, like from the `lib` folder add this to the `volumes` section: `./rss-bridge/lib:/app/lib`.

### Docs with Docker

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
