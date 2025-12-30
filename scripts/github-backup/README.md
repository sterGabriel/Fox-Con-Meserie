# GitHub Backup (Auto Commit + Push)

This automation runs **twice per day** and performs:
- `git add -A`
- `git commit` (timestamped)
- `git push origin <current-branch>`

## Install (systemd)
```bash
sudo bash scripts/systemd/install-github-backup.sh /var/www/iptv-panel
```

## Requirements
1) The repo must have an `origin` remote pointing to GitHub.

2) The service runs as `www-data`, so `www-data` must be able to authenticate to GitHub.

### Recommended: SSH deploy key
- Create a key as `www-data`:
  ```bash
  sudo -u www-data ssh-keygen -t ed25519 -f /var/www/iptv-panel/storage/app/keys/github_deploy_key -N ""
  ```
- Add the **public key** to GitHub (Deploy key with write access)
- Add an SSH config for `www-data` so git uses that key (example):
  ```bash
  sudo -u www-data mkdir -p /var/www/iptv-panel/storage/app/keys
  sudo -u www-data mkdir -p /var/www/iptv-panel/storage/app/ssh
  sudo -u www-data bash -lc 'cat > /var/www/iptv-panel/storage/app/ssh/config <<EOF
Host github.com
  HostName github.com
  User git
  IdentityFile /var/www/iptv-panel/storage/app/keys/github_deploy_key
  IdentitiesOnly yes
EOF'
  ```
- Then set `GIT_SSH_COMMAND` (optional) or symlink config into `/var/www/.ssh/config` for `www-data`.

## Logs
- Script log file: `storage/logs/github-backup.log`
- systemd: `journalctl -u iptv-panel-github-backup.service -n 200 --no-pager`

## Change schedule
Edit the timer unit:
- `/etc/systemd/system/iptv-panel-github-backup.timer`
Then:
```bash
sudo systemctl daemon-reload
sudo systemctl restart iptv-panel-github-backup.timer
```
