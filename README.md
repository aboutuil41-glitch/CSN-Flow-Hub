# CSN Flow Hub

A centralized platform that helps agencies manage projects and client communication in one place. It replaces scattered tools with structured task tracking, file versioning, client feedback, and an AI-powered feedback summarizer.

Built during my internship.

## Features

- Project and task tracking
- File versioning
- Client feedback collection
- AI-powered feedback summarizer

## Tech stack

- **Frontend:** React, Tailwind CSS (in `frontend/`)
- **Backend:** Laravel, MySQL (in `backend/`)


### Backend (check)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# set your database details in .env, then:
php artisan migrate
php artisan serve
```

### Frontend (check)

```bash
cd frontend
npm install
npm run dev
```

## My role

Write 2–3 lines here: what you built (for example, "the feedback screens and the summarizer integration") and what you learned.

## Status

Internship project. Not currently deployed.
