# How to Test Registration

## Step 1: Start XAMPP Services
1. Open XAMPP Control Panel
2. Start **Apache** server
3. Start **MySQL** server

## Step 2: Verify Database Setup
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Make sure you have a database named `cafeteria`
3. Check if the `users` table exists with these columns:
   - `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
   - `username` (VARCHAR)
   - `password` (VARCHAR)
   - `role` (VARCHAR, default: 'customer')

If the table doesn't exist, run this SQL:
```sql
CREATE DATABASE IF NOT EXISTS cafeteria;
USE cafeteria;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'customer'
);
```

## Step 3: Open the Application
1. Open your browser
2. Go to: `http://localhost/cafeteria/index.html`
3. Press **F12** to open Developer Tools
4. Go to the **Console** tab (to see JavaScript errors)
5. Go to the **Network** tab (to see HTTP requests)

## Step 4: Test Registration
1. On the login page, click "Register" (or the register link)
2. Fill in:
   - Username: `testuser`
   - Password: `test123`
3. Click the "Register" button
4. Watch the Console tab for any errors
5. Watch the Network tab - you should see a request to `register.php`

## Step 5: Check for Success
- **Success**: You should see an alert saying "Registration successful!" and be switched to the login screen
- **Failure**: You'll see an error message in an alert

## Step 6: Verify in Database
1. Go back to phpMyAdmin
2. Select the `cafeteria` database
3. Click on the `users` table
4. Click "Browse" - you should see your new user

## Step 7: Test Login
1. After successful registration, try logging in with:
   - Username: `testuser`
   - Password: `test123`
2. Select "Customer" from the role dropdown
3. Click "Login"

## Common Issues to Check:

### If registration doesn't work:
1. **Check Console (F12)**: Look for JavaScript errors (red text)
2. **Check Network tab**: 
   - Find the `register.php` request
   - Click on it
   - Check the "Response" tab to see what the server returned
3. **Check Database**: Make sure MySQL is running in XAMPP
4. **Check File Path**: Make sure you're accessing via `http://localhost/cafeteria/`

### Common Error Messages:
- "Database connection failed" → MySQL not running or wrong credentials
- "Username already taken" → User already exists, try a different username
- "Fill all fields" → Username or password is empty
- JavaScript errors → Check console for the exact error

