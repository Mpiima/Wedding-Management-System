# ERP Starter Template

A modular PHP + Vue.js ERP starter with authentication, roles, permissions, and core modules. Copy and extend for your project.

---

## How to run the project

### 1. Database

Create the database (MySQL/MariaDB), then run migrations and seed:

```bash
# In MySQL (e.g. phpMyAdmin or mysql CLI):
CREATE DATABASE erp_db;

# From project root:
php database/migrate.php
php database/seed.php
```

**Sample login (created by seed):**
- **Email:** `admin@example.com`  
- **Password:** `password123`  
(You can also use **username:** `admin` with the same password.)

---

### 2. API (PHP backend)

The frontend is configured to call the API at `http://localhost/templates/erp/api`.

**Option A – XAMPP (recommended)**

1. Put the project under XAMPP’s `htdocs`, e.g.  
   `C:\xampp\htdocs\templates\erp`
2. Start **Apache** (and **MySQL**) in XAMPP.
3. API base URL:  
   `http://localhost/templates/erp/api`

**Option B – PHP built-in server**

From the project root:

```bash
php -S localhost:8080 -t .
```

Then set in `.env`:

```env
VITE_APP_BASE_URL=http://localhost:8080/api
```

and expose the `api` folder at `/api` (e.g. with a router or a separate run pointing to `api/`). For a quick match with the Vue app, XAMPP is simpler.

---

### 3. Frontend (Vue + Vite)

From the project root:

```bash
npm install
npm run dev
```

Then open the URL Vite prints (e.g. `http://localhost:5173`).

- **Login:** use the sample user above.
- To run migrations before starting the dev server:  
  `npm run migrate` then `npm run dev`, or use `npm run dev` (it runs `migrate` once before Vite).

---

### 4. Environment

Edit `.env` in the project root:

| Variable           | Purpose                    | Example                          |
|--------------------|----------------------------|----------------------------------|
| `VITE_APP_BASE_URL` | API base URL for the Vue app | `http://localhost/templates/erp/api` |
| `DB_HOST`          | Database host              | `localhost`                     |
| `DB_NAME`          | Database name              | `erp_db`                        |
| `DB_USER`          | Database user              | `root`                          |
| `DB_PASS`          | Database password          | *(empty for default XAMPP)*     |

---

## Quick checklist

1. Create database `erp_db`.
2. Run `php database/migrate.php` and `php database/seed.php`.
3. Start Apache (and MySQL) in XAMPP so the API is at `http://localhost/templates/erp/api`.
4. Run `npm install` and `npm run dev`, then open the Vite URL.
5. Log in with `admin@example.com` / `password123`.

See **ARCHITECTURE.md** for the module layout and **api/README.md** for the API structure.
