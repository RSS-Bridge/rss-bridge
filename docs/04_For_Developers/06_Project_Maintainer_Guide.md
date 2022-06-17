# Project Maintainer Guide
RSS-Bridge project maintainers are responsible for:
1. Tagging issues
1. Reviewing and merging pull requests
1. Creating releases

Project maintainers should keep all communications related to the project open
to the community (Preferably GitHub issues but IRC/Matrix is also allowed).

## Issues
Maintainers should triage and respond to issues if possible, making sure to
specify the tag if possible. For example, the 'Bug Report' can be clarified to
'Bridge-Broken' once that is established. Project maintainers are encouraged to
mention the bridge maintainer to comment on issues related to a specific bridge.

An issue should remain open until it is resolved by fixing the issue or adding
the feature it describes. There are some exceptions:
- In the case of a feature request that will not be added, the issue can be
  closed with the 'wontfix' label after pinging other active maintainers.
- If an issue is too obscure or unclear and the issue creator does not respond
  to additional clarification questions for 1 month, these issues should be
  closed as they cannot be acted upon.

## Pull Requests
Maintainers should thoroughly **review** and **test** pull requests before
merging them. Most bridge authors use other bridges in the project for reference
so it is important to maintain a high quality codebase so that it does not
deteriorate.

To merge a pull request, use the "Squash and Merge" option. When editing the
commit message of the squashed commit, make sure not to remove the
Co-authored-by lines at the end of the message. In certain cases outside of
bridge maintenance, it may be useful to use "Rebase and Merge" instead. Include
"[No Squash]" in the pull request title to note this.

Maintainers must not push to master, but create pull requests instead.
Maintainers should not merge their own pull requests except in the following
cases:
- The pull request received no reviews or comments for 2 weeks.
- The pull request is a trivial change. This definition is a little flexible,
  but the diff should not exceed 5 lines and typically targets only a single
  bridge.

## Releases
Releases are created about every 6 months, though this schedule can be
accelerated. Maintainers should begin the process by discussing with other
active maintainers what needs to be done before the release is published. If
desired, these can be tracked through a GitHub issue or milestone.

Release versions follow the scheme YYYY-mm-dd. Once a consensus is reached that
a release should be published, follow these steps:
1. Save the release notes to a draft release. The release notes should be
   categorized into the sections shown in `contrib/prepare_release/template.md`.
   See previous release notes for examples. The emacs lisp file
   `contrib/prepare_release/rssbridge-log-helper.el` contains step-by-step
   instructions on how to categorize these commits more easily, no knowledge of
   emacs required. Manual creation of the release notes is also possible.
1. Create a pull request that includes the other changes necessary for a release
   (See contrib/prepare_release/template.md). Make sure to ping/request review
   from all active maintainers in this PR.
1. On the expected date of release, merge the pull request then publish the
   release, creating a new tag that follows the RSS-Bridge version scheme
   (YYYY-mm-dd). The release title should be: RSS-Bridge YYYY-mm-dd.
