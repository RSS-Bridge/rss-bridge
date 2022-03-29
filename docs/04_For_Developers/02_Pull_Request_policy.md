Pull requests allow you to improve RSS-Bridge. Maintainers will have to understand your changes before merging. In order to make this process as efficient as possible, please follow the policies explained below. Maintainers will merge your pull request much faster that way.

# Fix one issue per pull request

It is considered good practice to fix one specific (or a specific set of) error(s). You can open multiple pull requests if you need to address multiple subjects. The same applies to adding features to RSS-Bridge. Maintainers must be able to comprehend your pull request for it to be merged quickly.

# Respect the coding style policy

The [coding style policy](./01_Coding_style_policy.md) requires you to write your code in certain ways. If you plan to get it merged into RSS-Bridge, please make sure your code follows the policy. Maintainers will only merge pull requests that pass all tests.

# Properly name your commits

Commits are not only for show, they do help maintainers understand what you did in your pull request, just like a table of contents in a well formed book (or Wiki). Here are a few rules you should follow:

* When fixing a bridge (located in the `bridges` directory), write `[BridgeName] Feature` <br>(i.e. `[YoutubeBridge] Fix typo in video titles`).
* When fixing other files, use `[FileName] Feature` <br>(i.e. `[index.php] Add multilingual support`).
* When fixing a general problem that applies to multiple files, write `category: feature` <br>(i.e. `bridges: Fix various typos`).