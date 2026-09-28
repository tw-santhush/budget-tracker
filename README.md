# Personal Budget Tracker

A simple, dependency-free web app for tracking income and expenses, setting monthly budget limits per category, and watching your savings rate grow.

## Live demo

**https://yourbudget.free.je/**

Hosted on a free shared-hosting account. Create an account from the **Register** tab to try it — no setup needed.

## Features

- **Login & register** — session-based authentication with `password_hash()` / `password_verify()`, per-user data isolation
- **Add / edit / delete transactions** — income and expense entries with amount, category, date and notes
- **Overview dashboard** — net balance, total income, total expenses, savings rate, and a hand-rolled 6-month income-vs-expense bar chart
- **Monthly budget limits** — set a spending cap per category and see how much of it is used
- **Dark mode** — persistent light/dark toggle, remembered per session
- Responsive layout, no frameworks, no build step

## Tech stack

| Layer | Technology |
|---|---|
| Markup | HTML5 |
| Styling | CSS3 (custom properties, no framework) |
| Logic | PHP 7+ (procedural, `mysqli` prepared statements) |
| Database | MySQL / MariaDB |
| Server | XAMPP (Apache + PHP + MySQL) |

## Setup instructions

### 1. Place the project in `htdocs`

Copy or clone this folder so that it ends up inside your XAMPP web root:

```
C:\xampp\htdocs\budget-tracker\     <- Windows
/opt/lampp/htdocs/budget-tracker/   <- Linux
```

### 2. Start Apache and MySQL

Open the XAMPP Control Panel and start **Apache** and **MySQL**.

### 3. Import the database

1. Visit <http://localhost/phpmyadmin>
2. Click the **Import** tab
3. Choose `database.sql` from this repository and click **Go**

This creates the `budget_tracker_v2` database, its tables (`users`, `categories`, `transactions`, `budget_limits`) and a demo user.

### 4. Check the database connection

The credentials live in `db.php`:

```php
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "budget_tracker_v2";
```

These are the XAMPP defaults, so no changes are normally needed. If yours differ, edit `db.php` directly.

> **Note:** this project is a teaching demo, so `db.php` (with its real credentials) is committed on purpose so the project runs immediately after cloning. In a real project you would ignore `db.php` and copy `db.example.php` instead:
>
> ```bash
> cp db.example.php db.php
> ```
>
> `db.example.php` contains the same code with `YOUR_HOST_HERE` / `YOUR_USER_HERE` / `YOUR_PASSWORD_HERE` / `YOUR_DATABASE_HERE` placeholders.

### 5. Open the app

<http://localhost/budget-tracker/>

> The URL uses whatever folder name you gave it in step 1 — `budget-tracker` here, or `budget-tracker-v2` if you cloned it under that name.

## Logging in

`database.sql` seeds one demo account:

```
Email: test@gmail.com
```

The password is stored as a bcrypt hash, so the plaintext is not recoverable from the dump. Two options:

- **Register your own account** from the **Register** tab on the login page, then log in with that. Self-registration requires a password of at least 6 characters.
- **Reset the demo account** in phpMyAdmin:

  ```sql
  UPDATE users
  SET password = '$2y$10$YOUR_BCRYPT_HASH_HERE'
  WHERE email = 'test@gmail.com';
  ```

  Generate a valid hash by running `password_hash('yourpassword', PASSWORD_DEFAULT);` in any PHP file, or paste one in from <https://www.php.net/manual/en/function.password-hash.php>.

## Project structure

```
├── add.php              # add a transaction
├── auth.php             # login / register
├── budgets.php          # per-category monthly limits
├── database.sql         # schema + seed data
├── db.example.php       # db.php template with placeholders
├── db.php               # live database connection
├── delete.php           # delete a transaction
├── edit.php             # edit a transaction
├── footer.php           # shared closing markup
├── header.php           # shared header, nav tabs, theme toggle
├── index.php            # overview dashboard + chart
├── logout.php           # end session
├── save_budget.php      # persist budget limits
├── style.css            # all styling
├── toggle_theme.php     # light/dark switch handler
└── transactions.php     # transaction list
```

## Security note

Session handling, prepared statements against SQL injection and password hashing are all in place. As with any small PHP demo there is no CSRF token protection on the forms — add one before deploying anywhere real.

## Author

**T.K.D.T.W.Santhush** — <themiya0718574662@gmail.com>
