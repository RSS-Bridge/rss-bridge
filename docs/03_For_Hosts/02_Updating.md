Updating an existing installation is very simple, depending on your type of installation.

## Release Build

* Download latest version
* Extract all files
* Replace existing files

This will update all core files to the latest version. Your custom configuration and bridges are left untouched. Keep in mind that changes to any core file of RSS-Bridge will be replaced.

## Docker

Simply get the latest Docker build via `:latest` or specific builds via `:<tag-name>`.

## Heroku

### If you didn't fork the repo before

Fork the repo by clicking the `Fork` button at the top right of this page (must be on desktop site). Then on your Heroku account, go to the application. Click on the `Deploy` tab and connect the repo named `yourusername/rss-bridge`. Do a manual deploy of the `master` branch.

### If you forked the repo before

[Click here to create a new pull request to your fork](https://github.com/RSS-Bridge/rss-bridge/pull/new/master). Select `compare across forks`, make the base repository `yourusername/rss-bridge` and ensure the branch is set to master. Put any title you want and create the pull request. On the page that comes after this, merge the pull request.

You then want to go to your application in Heroku, connect your fork via the `Deploy` tab and deploy the `master` branch.

You can turn on auto-deploy for the master branch if you don't want to go through the process of logging into Heroku and deploying the branch every time changes to the repo are made in the future. 

## Git

To get the latest changes from the master branch

```
git pull
```

To use a specific tag

```
git fetch --all
git checkout tags/<tag-name>
```