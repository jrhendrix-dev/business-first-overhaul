# `login.php` – Login Handler Documentation

This file processes login requests submitted via AJAX. It checks the provided credentials against the database and starts a session if valid. The script also returns clear JSON responses for front-end handling.

---

##  File Location

`/public/login.php`

---

##  Dependencies

- Requires database connection via `Database.php`.
- Uses `password_verify` to securely compare hashed passwords.
- Expects POST requests from the login modal form in the main layout.

---

##  Core Workflow

### 1. **Input Validation**
The script checks that both `username` and `password` are present in the POST request:
```php
if (!isset($_POST['username']) || !isset($_POST['password'])) { ... }
```

### 2. **Database Query**
It runs a prepared SQL statement to fetch the user with the given username:
```php
$stmt = $con->prepare("SELECT * FROM users WHERE username = ?");
```

### 3. **Password Check**
It verifies the password using `password_verify()`:
```php
if (password_verify($password, $user['password'])) { ... }
```

### 4. **Session Setup**
If valid, it initializes `$_SESSION['user']` and `$_SESSION['lvl']`:
```php
$_SESSION["user"] = $user["username"];
$_SESSION["lvl"] = $user["ulevel"];
```

### 5. **JSON Response**
It sends a JSON object indicating success or failure:
```php
echo json_encode(["success" => true, "level" => $user["ulevel"]]);
```

---

##  Example Success Response
```json
{
  "success": true,
  "level": 1
}
```

##  Example Error Response
```json
{
  "success": false,
  "message": "Credenciales incorrectas."
}
```

---

##  Security Measures

- Passwords are verified using `password_verify()` to match hashed values.
- SQL injection is prevented via prepared statements.
- No raw password values are returned to the client.
- Session values are only set on verified login.

---

##  Frontend Integration

The front-end uses jQuery to:
- Submit the login form.
- Display errors or redirect the user based on the returned JSON.
- Reset modal and form upon success or failure.

---

##  Testing Tips

- Attempt login with incorrect username or password to verify error handling.
- Test behavior for disabled users if roles are enforced.
- Verify proper redirection for each role after login.

---

##  Related Files

- `/models/Database.php` – DB connection
- `/assets/js/common.js` – AJAX login script
- `/views/dashboard.php` – redirection target on successful login

---

##  Summary

This is a secure, modular, AJAX-driven login endpoint. It cleanly separates logic, minimizes data exposure, and supports user role-based redirects through structured JSON output.
