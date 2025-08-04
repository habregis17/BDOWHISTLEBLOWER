# Whistleblower Solution - regis Tech

## 🚀 Local Setup Instructions

1. Install **XAMPP**, **MAMP**, or another local server that supports PHP & MySQL.
2. Place this folder inside your server root:
   - Example for XAMPP: `C:/xampp/htdocs/whistleblower-solution/`
3. Create a database in phpMyAdmin called `whistleblower`
4. Import the file `schema.sql` into the `whistleblower` database
5. Start Apache and MySQL via XAMPP
6. Navigate to `http://localhost/whistleblower-solution/clients/create.php` to start creating clients.

---

## 🌐 GitLab Setup Instructions

1. Create a **new GitLab repository**.
2. Initialize a git repo locally and push:

```bash
cd whistleblower-solution
git init
git remote add origin https://gitlab.com/YOUR_USERNAME/YOUR_REPO_NAME.git
git add .
git commit -m "Initial commit"
git push -u origin master
```

3. GitLab will now track your files.

---

## 🌍 GitLab Environments (Test & Live)

In GitLab:

1. Go to **Settings > CI/CD > Environments**
2. Add environments like:
   - `staging`: for test environment
   - `production`: for live deployment

Update `.gitlab-ci.yml` like this:

```yaml
stages:
  - deploy

deploy_to_staging:
  stage: deploy
  environment:
    name: staging
    url: https://staging.example.com
  script:
    - echo "Deploying to staging"

deploy_to_production:
  stage: deploy
  environment:
    name: production
    url: https://prod.example.com
  when: manual
  script:
    - echo "Deploying to production"
```

---

## 📁 Folder Structure

```
whistleblower-solution/
├── config/
│   └── db.php
├── clients/
│   └── create.php
├── cases/
│   └── submit.php
├── admin/
│   └── dashboard.php
├── schema.sql
├── .gitignore
├── .gitlab-ci.yml
└── README.md
```

---

## ✅ Notes
- Anonymous user form URL is auto-generated per client.
- All case submissions are stored in the DB.
- Emails are sent to designated client contacts in the background.
