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

And access it using `http://IP_Address:3000`. If you'd like to run a specific version, you can run it by:

```bash
docker create \
--name=rss-bridge \
--volume </local/custom/path>:/config \
--publish 3000:80 \
rssbridge/rss-bridge:$version
```

Where you can get the versions published to Docker Hub at https://hub.docker.com/r/rssbridge/rss-bridge/tags/. The server runs on port 80 internally, and you can publish it on a different port (change 3000 to your choice).

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
If you want to add a bridge that is not part of [`/bridges`](https://github.com/RSS-Bridge/rss-bridge/tree/master/bridges), you can specify an additional folder to copy necessary files to the `rss-bridge` container.

_Here **root** is folder where `docker-compose.yml` resides._
1. Create `custom` folder in root. 
2. Copy your [bridges files](../05_Bridge_API/01_How_to_create_a_new_bridge.md) to the `custom` folder. You can also add your custom [whitelist.txt](../03_For_Hosts/05_Whitelisting.md) file and your custom [config.ini.php](../03_For_Hosts/08_Custom_Configuration.md) to this folder.
3. Run `docker-compose up` to recreate service.