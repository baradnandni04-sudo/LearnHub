# LearnHub

LearnHub is a responsive PHP and MySQL online learning platform built with Bootstrap 5. It includes student, instructor, and administrator workspaces; course discovery and enrollment; lessons and progress; quizzes; certificates; account recovery; and a contact form.

## Requirements

- PHP 8.1+ with `pdo_mysql`, `mbstring`, and sessions enabled
- MySQL 8.0+ (or a compatible MariaDB release)
- Apache with `mod_rewrite`/`.htaccess` support, or another PHP-capable web server
- Internet access for the Bootstrap, Bootstrap Icons, Google Fonts, and sample course images loaded from CDNs

## Setup

1. Copy the project into your PHP web root (for example `htdocs/learnhub` in XAMPP).
2. Create and seed the database:

   **Windows PowerShell (XAMPP):**

   ```powershell
   Get-Content .\database.sql -Raw | & 'C:\xampp\mysql\bin\mysql.exe' -u root
   ```

   **macOS/Linux:**

   ```sh
   mysql -u root -p < database.sql
   ```

   The SQL creates the `learnhub` database, tables, categories, five sample courses, lessons, and quizzes.
3. Configure the database connection. By default LearnHub uses host `127.0.0.1`, port `3306`, database `learnhub`, user `root`, and an empty password. Override these with environment variables:

   - `DB_HOST`
   - `DB_PORT`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
   - `LEARNHUB_BASE_URL` (optional prefix when hosted in a subdirectory, e.g. `/learnhub`)
4. Open the site at `http://localhost/learnhub/` (or the matching URL for your web root).

### Demo accounts

The seed file creates these local demo accounts. Both use the password `password`:

- Admin: `admin@learnhub.test`
- Instructor: `instructor@learnhub.test`

Change both passwords immediately and never use the seeded credentials on a public server. You can register student accounts through the site. Admins can promote students to instructors from the admin dashboard.

### Password reset email

Password reset tokens are random, stored as SHA-256 hashes, and expire after one hour. Configure PHP's `mail()` transport and optionally set `LEARNHUB_MAIL_FROM` for outgoing reset links. If mail is not configured, the page deliberately does not reveal whether an account exists; check the PHP error log and contact the site administrator.

## Included features

- Responsive landing page, course search, category and level filters, course detail pages
- Student registration/profile editing, password-hashed login, logout, and password reset
- Role-based student, instructor, and administrator access with server-side authorization
- Instructor course and lesson creation, course publishing, and learner counts
- Instructor quiz builder for adding and removing multiple-choice questions
- Admin user role/activation management, course publishing, and course creation
- Free course enrollment, protected lesson access, lesson completion, and progress tracking
- Multiple-choice quizzes with server-side scoring and saved attempts
- Completion certificates with unique IDs and a print-friendly view
- Contact form with server-side validation and database-backed message storage
- CSRF protection on state-changing forms, parameterized SQL, output escaping, and secure session cookie settings

## Practical notes

- Paid checkout is intentionally not simulated. Courses with a non-zero price cannot be enrolled in until a payment provider is integrated; seeded courses are free.
- Add lessons in the instructor workspace. Video URLs should be HTTPS. YouTube embed URLs must use an `/embed/` path and Vimeo embeds should use `player.vimeo.com/video/<id>`. The photography sample course includes a public sample MP4 to demonstrate the player.
- Certificates are issued after all lessons are marked complete. Quiz results are saved independently and do not gate certificates.
- Contact submissions are saved in `contact_messages`; review them directly in the database until an admin inbox is added.
- For production, use HTTPS, a dedicated least-privilege database user, configured SMTP, backups, and a non-public secrets/configuration mechanism. The included demo credentials and default DB settings are for local development only.
