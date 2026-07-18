# Cafe Project

This is the repository for the Cafe website application.

## Environment Definitions

- **Local (Staging/Development Area)**: Running locally on XAMPP at [C:\xampp\htdocs\cafe](file:///C:/xampp/htdocs/cafe) (`http://localhost/cafe`).
- **Remote (UAT - User Acceptance Testing)**: Hosted on GitHub at [Ebon-Masud/cafe](https://github.com/Ebon-Masud/cafe).

## Git Workflow

The repository is configured to map the local `main` branch to the remote `main` branch as follows:
- **Local branch (`main`)** is your local staging area where you develop, test, and preview changes via XAMPP.
- **Remote branch (`origin/main`)** is your UAT (User Acceptance Testing) area.

### Common Commands

- **Check status:**
  ```bash
  git status
  ```
- **Commit changes:**
  ```bash
  git add .
  git commit -m "Your commit message"
  ```
- **Push to UAT (GitHub):**
  ```bash
  git push origin main
  ```
