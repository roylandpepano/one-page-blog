# One Page Blog

This is a small one-page blog that supports public comments and an admin panel
for managing those comments. It is designed to run on a standard LAMP/WAMP/XAMPP
stack and uses Bootstrap, jQuery and a minimal Vue component for rendering
comments.

**Repository layout (important files)**

-  `index.php` - Public page with the article and comment form.
-  `admin.php` - Admin login and comment management UI.
-  `comments_api.php` - Backend endpoint for adding and listing comments (JSON or HTML).
-  `db.php` - MySQL connection configuration.
-  `assets/admin.js` - JavaScript for admin actions (save/delete via AJAX).
-  `blog_db.sql` - SQL dump for creating the database and tables.

**Requirements**

-  PHP 8.2+ (tested)
-  MySQL / MariaDB
-  Web server (XAMPP on Windows works well)

Getting started (Windows + XAMPP)

1. Copy the project to your XAMPP `htdocs` directory (for example: `C:\xampp\htdocs\one-page-blog`).
2. Start Apache and MySQL from the XAMPP control panel.
3. Import the database schema:

   -  Open phpMyAdmin (`http://localhost/phpmyadmin`) or use the mysql CLI.
   -  Create a database named `blog_db` (or another name you prefer).
   -  Import the `blog_db.sql` file found in the project root.

   Or from a bash terminal (adjust path and credentials):

   ```bash
   mysql -u root -p < "C:/xampp/htdocs/one-page-blog/blog_db.sql"
   ```

4. Update the database credentials in `db.php` if you used a different user/password or DB name.

   -  Default config (works with default XAMPP):

      ```php
      $host = 'localhost';
      $db = 'blog_db';
      $user = 'root';
      $pass = '';
      ```

5. Open the site in your browser at `http://localhost/one-page-blog/index.php` or `http://127.0.0.1:<port>/one-page-blog/index.php`.

Admin access

-  Visit `http://localhost/one-page-blog/admin.php` to log in.
-  Admin users are stored in the `admin_users` table. The app supports modern
   password hashes (`password_hash`) and will migrate legacy MD5 hashes on login.

How it works

-  Public users submit comments through the form on `index.php` which POSTs to
   `comments_api.php`. The API stores comments in the `comments` table.
-  `comments_api.php` supports:
   -  POST: add a comment (expects `username` and `comment`). Returns JSON.
   -  GET: list comments. Add `?json=1` to receive a JSON array for the Vue component.
-  The admin panel (`admin.php`) lists comments and lets authenticated admins
   edit or delete them. Admin actions are protected with a CSRF token.

Security notes

-  Inputs are escaped before insertion but this project is intentionally small
   and therefore does not include advanced protections like prepared statements
   everywhere. Consider adding prepared statements and stricter validation for
   production use.
-  Admin login uses hashed passwords; legacy MD5 passwords are migrated to a
   secure hash on successful login.
-  CSRF protection is used for admin edit/delete operations.

Troubleshooting

-  Database connection errors: verify `db.php` credentials and that MySQL is
   running. The error will show a connection failure message in the browser.
-  If comments are not appearing: check `comments_api.php` responses in the
   browser DevTools Network tab for errors.
-  If admin login fails: confirm there is a valid row in `admin_users` and the
   password hash is correct. You can create an admin user directly in SQL:

   ```sql
   INSERT INTO admin_users (username, password) VALUES ('admin', '');
   -- then update with a PHP-generated hash or use the following example in PHP:
   -- UPDATE admin_users SET password = '<php_password_hash_here>' WHERE username='admin';
   ```

Quick commands

-  Start XAMPP and open the app:

   ```bash
   # Start XAMPP services via the control panel or use the manager GUI on Windows
   # Then open in browser:
   xdg-open "http://localhost/one-page-blog/index.php"
   ```

Development notes

-  Frontend uses Bootstrap 5, jQuery, iziToast and a tiny Vue 3 component for
   comment rendering.
-  Admin AJAX behaviour is implemented in `assets/admin.js`.
