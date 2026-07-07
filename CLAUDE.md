# Working notes for Claude Code sessions on this repo

## Git push workaround (standing issue)

Direct `git push` from this sandbox to GitHub fails with a 403 ("Resource not
accessible by integration") — the GitHub App is Authorized but not Installed
at the repo level. This is not fixable from within a session. Workflow to use
instead, every time, without being asked:

1. Commit your changes normally.
2. Build a **cumulative** bundle of everything ahead of `origin/main`:
   ```
   git fetch origin main
   git bundle create <path>/pagebuilder-all.bundle <branch-name> ^origin/main
   git bundle verify <path>/pagebuilder-all.bundle
   ```
3. Deliver it with `SendUserFile`, and give these two commands (the user runs
   them locally):
   ```
   git fetch pagebuilder-all.bundle <branch-name>:<branch-name>
   git push origin <branch-name>
   ```
   Reuse the same bundle filename each time (`pagebuilder-all.bundle`) since
   it's always cumulative. Remind the user to double check the actual
   downloaded filename with `ls` — download managers sometimes strip hyphens.
4. **After the user confirms the push succeeded, create a pull request
   automatically** (`mcp__github__create_pull_request`, base `main`, head the
   feature branch) — don't wait to be asked. The user reviews/merges it
   themselves on GitHub (ask first if they want you to merge it for them).
5. Getting the change onto GitHub/`main` is *not* the same as it being live.
   This project is deployed to InfinityFree shared hosting, which is a
   separate manual step: the user downloads the branch/main as a ZIP from
   GitHub and re-uploads/extracts it via cPanel File Manager. Always mention
   this distinction when a merge lands, since "it's on GitHub now" does not
   mean the live site changed.

## Stop-hook "Unverified commit" noise

The `stop-hook-git-check.sh` feedback about commits being "Unverified" on
every turn is benign and expected in this sandbox — the committer email is
already correctly set to `noreply@anthropic.com`; the warning is only about
a missing GPG/SSH signature, which this environment has no signing key
configured for. No action needed when this appears.

## Local test environment

MariaDB and the PHP built-in server are not persistent across fresh
containers/sessions — start them explicitly before testing:
```
mysqld_safe &
php -S 127.0.0.1:8811 -t /home/user/jimmy-claes &
```
Test DB is `dieren_test`; login is `admin` / `testpass123` unless changed
during a prior test run (check `users` table if login fails).
