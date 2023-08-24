This guide is for people who want to run RSS Bridge using Docker. If you want to run it a simple PHP Webhost environment, see [Installation](../03_For_Hosts/01_Installation.md) instead.

## Setup

### Create the container

```bash
docker create \
--name=rss-bridge \
--volume </local/custom/path>:/config \
--publish 3000:80 \
rssbridge/rss-bridge:latest
```
### Run it
```bash
docker start rss-bridge
```

Access it using `http://IP_Address:3000`. If you'd like to run a specific version, you can run it by changing the ':latest' on the image to a tag listed [here](https://hub.docker.com/r/rssbridge/rss-bridge/tags/)

The server runs on port 80 internally, map any port of your choice (in this example 3000).

You can run it using a `docker-compose.yml` as well:

```yml
version: '2'
services:
  rss-bridge:
    image: rssbridge/rss-bridge:latest
    volumes:
      - </local/custom/path>:/config
    ports:
      - 3000:80
    restart: unless-stopped
```

# Container access and information

|Function|Command|
|----|----|
|Shell access (live container)|`docker exec -it rss-bridge /bin/sh`|
|Realtime container logs|`docker logs -f rss-bridge`|

# Adding custom bridges and configurations
If you want to add a bridge that is not part of [`/bridges`](https://github.com/RSS-Bridge/rss-bridge/tree/master/bridges), you can map a folder to the `/config` folder of the `rss-bridge` container.

1. Create a folder in the location of your docker-compose.yml or your general docker working area (in this example it will be `/home/docker/rssbridge/config` ). 
2. Copy your [custom bridges](../05_Bridge_API/01_How_to_create_a_new_bridge.md) to the `/home/docker/rssbridge/config` folder. Applies also to [config.ini.php](../03_For_Hosts/08_Custom_Configuration.md).
3. Map the folder to `/config` inside the container. To do that, replace the `</local/custom/path>` from the previous examples with `/home/docker/rssbridge/config`