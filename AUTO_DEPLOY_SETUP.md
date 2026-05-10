# Auto Deploy Setup (GitHub -> go.fikfak.news)

This repository now includes a GitHub Actions workflow at `.github/workflows/deploy-go-fikfak.yml`.

## 1. Add GitHub Actions Secrets

In GitHub: Repository -> Settings -> Secrets and variables -> Actions, add:

- `DEPLOY_HOST`: server hostname (example: `123.123.123.123`)
- `DEPLOY_USER`: SSH user
- `DEPLOY_SSH_KEY`: private key text (multi-line)
- `DEPLOY_PORT`: SSH port (example: `22`)
- `DEPLOY_PATH`: absolute path of this site on server (example: `/home/fikfak-go/htdocs/go.fikfak.news`)

## 2. Ensure Server Is Git-Connected

On server, in deploy path:

```bash
cd /home/fikfak-go/htdocs/go.fikfak.news
git remote -v
git branch --show-current
```

Expected:

- `origin` points to `https://github.com/web-technics/fikfaknews.git`
- branch is `main`

## 3. Test Workflow

1. Go to GitHub -> Actions -> `Deploy go.fikfak.news`.
2. Click `Run workflow`.
3. Check logs for `Deploy complete at commit ...`.

## 4. Verify Live Deployment

After workflow succeeds:

```bash
curl -s https://go.fikfak.news/ | grep -E 'share-whatsapp|share-copy-btn|updateShareActions'
```

If no output, page may be cached by proxy/CDN; purge cache and retry.

## Troubleshooting

- `Permission denied (publickey)`: SSH key or user is incorrect.
- `Deployment path is not a git repository`: wrong `DEPLOY_PATH`.
- `git pull --ff-only` fails: server has local changes. Resolve on server, then rerun.
