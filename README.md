# Business First Overhaul

[![CI](https://github.com/jrhendrix-dev/business-first-overhaul/actions/workflows/ci.yml/badge.svg)](https://github.com/jrhendrix-dev/business-first-overhaul/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/jrhendrix-dev/business-first-overhaul/branch/main/graph/badge.svg)](https://codecov.io/gh/jrhendrix-dev/business-first-overhaul)

A full-stack re-implementation of the **Business First English Academy** management system.  
This monorepo contains the **Symfony 7 backend** and planned **Angular + Tailwind frontend**.  

The goal: replace the legacy PHP/MySQL site with a modern, tested, Dockerized architecture that is production-ready and recruiter-authentic.

---

## 🔧 Stack

- **Backend**: [Symfony 7](https://symfony.com/) (PHP 8.3), Doctrine ORM, PHPUnit 12
- **Frontend**: Angular 17 + Tailwind CSS (WIP)
- **Database**: MySQL 8
- **DevOps**: Docker Compose, GitHub Actions CI, Codecov coverage

---

## 📂 Structure

```text
business-first-overhaul/
├── backend/      # Symfony API (fully covered by tests, rate-limiter, honeypot)
├── frontend/     # Angular + Tailwind app (planned / WIP)
├── docker-compose.yml
└── .github/      # GitHub Actions workflows
```

---

## 🚀 Getting started

### Clone & build
```bash
git clone https://github.com/jrhendrix-dev/business-first-overhaul.git
cd business-first-overhaul
docker compose up --build
```

### Run tests
```bash
docker compose exec backend composer test
docker compose exec backend composer test:cov
```

Coverage reports are generated to `backend/var/coverage-html/`.

---

## ✅ CI & Coverage

Every push to `main` runs PHPUnit inside Docker via GitHub Actions:

- [CI workflow runs](https://github.com/jrhendrix-dev/business-first-overhaul/actions/workflows/ci.yml)  
- [Coverage dashboard (Codecov)](https://codecov.io/gh/jrhendrix-dev/business-first-overhaul)

Badges above show live status.

---

## 📬 Contact

Created by [Jonathan Ray Hendrix](https://jonathan-hendrix.dev/).  
Reach out on [LinkedIn](https://www.linkedin.com/in/jonathan-hendrix-dev/).

License: MIT
