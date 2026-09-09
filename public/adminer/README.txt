Adminer for SQLite
==================

Adminer is a database management tool in a single PHP file.
This directory holds a copy compiled for SQLite together with a plugin which protects it by a password (SQLite databases have no password of their own).

  index.php - Adminer with the SQLite driver
  adminer-plugins/login-password-less.php - the plugin allowing the login
  adminer-plugins.php - the hash of your password

To use it:

1. Put your database file in this directory (or move this directory next to your database file).
2. Run "php -S localhost:8080" in this directory and open http://localhost:8080/, or upload this directory to a web server.
3. Leave the username empty, fill in the password you chose and select the database file.

Everybody who can open the address can try to guess the password so keep it strong.
Keep in mind that the database file can be downloaded by everybody if it is placed in a public directory.

If you also need other databases, replace index.php by the Adminer supporting all of them from https://www.adminer.org/ - the plugin works with it as well.

Newer versions: https://www.adminer.org/sqlite/
