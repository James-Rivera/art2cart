# Art2Cart - Quick Start Guide

Welcome to **Art2Cart**! This is a simple digital marketplace for buying and selling digital art and assets. Below is a quick guide to get you started with the project.

## Requirements
- WampServer64 (or any local server with PHP & MySQL)
- PHP 7.4+ (recommended)
- MySQL/MariaDB
- Web browser (Chrome, Firefox, etc.)

## How to Run This Project

### 1. Clone or Copy the Folder
Just copy this whole folder (`Art2Cart`) into your `c:/wamp64/www/` directory. You can also clone it if you have git, but copy-paste works fine.

### 2. Import the Database
1. Open WampServer and make sure MySQL is running (the Wamp icon should be green).
2. Go to `http://localhost/phpmyadmin` in your browser.
3. Login (default user: `root`, password: leave blank).
4. Click **Import** at the top.
5. Click **Choose File** and select `config/art2cart.sql` from this project.
6. Hit **Go**. Wait for it to finish. Now you have the database set up.

### 3. Configure Database Connection (if needed)
- By default, the config in `config/db.php` uses `root` and no password. If you changed your MySQL password, update it in that file.

### 4. Open the Website
- In your browser, go to: `http://localhost/Art2Cart/`
- The homepage should load. You can now browse, sign up, login, etc.

### 5. (Live Site)
- You can also access the live site at: [https://art2cart.shop](https://art2cart.shop)
- This is connected to the same codebase using Cloudflare Tunnel. If you want to set up your own tunnel, check Cloudflare’s docs or ask the project owner.

## (Optional) Set Up a Virtual Host
If you want to use a custom local domain (like `art2cart.local`):

1. Open `C:\wamp64\bin\apache\apache[version]\conf\extra\httpd-vhosts.conf`
2. Add this at the bottom:

    ```
    <VirtualHost *:80>
        DocumentRoot "c:/wamp64/www/Art2Cart"
        ServerName art2cart.local
        <Directory "c:/wamp64/www/Art2Cart">
            AllowOverride All
            Require all granted
        </Directory>
    </VirtualHost>
    ```

3. Edit your `C:\Windows\System32\drivers\etc\hosts` file (open as admin) and add:

    ```
    127.0.0.1   art2cart.local
    ```

4. Restart WampServer.
5. Now open `http://art2cart.local/` in your browser.

---

## How the Codebase Works

**Art2Cart** is a digital marketplace for buying and selling digital art and assets. Here’s how it’s structured and works:

- **Main Pages**: All the user-facing stuff is in the root (like `index.php`, `catalogue.php`, `cart.php`, etc.).
- **Authentication**: The `auth/` folder handles login, signup, and seller upgrades.
- **Admin Panel**: The `admin/` folder is for admin tasks—approving sellers, managing products, and checking logs.
- **APIs**: The `api/` folder has PHP files that handle AJAX/API requests (cart, checkout, ratings, etc.).
- **Config**: The `config/` folder has the database connection (`db.php`) and SQL files for setting up or updating the database.
- **Includes**: The `includes/` folder has PHP classes for users, carts, products, and config.
- **Seller**: The `seller/` folder is for seller-specific pages (dashboard, upload, etc.).
- **Static Assets**: The `static/` folder has all CSS, JS, images, and HTML templates.
- **Uploads**: The `uploads/` folder is where user-uploaded files and government IDs go.


**How it works (in plain English):**
- Users can browse products, add to cart, and checkout.
- Sellers can apply, upload products, and manage their listings.
- Admins approve sellers/products and manage the platform.
- All data is stored in MySQL, and PHP handles the backend logic.
- The frontend is mostly plain HTML/CSS/JS, with some dynamic loading and AJAX for cart and user actions.

---

## Notes
- For database issues, make sure the database name matches and MySQL is running.
- For more info, see the markdown files in `admin/` and the SQL files in `config/`.


