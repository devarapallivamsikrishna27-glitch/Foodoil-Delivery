# FoodOil Delivery

Online Food Oil Home Delivery Web Application.

**Live Website:** https://foodoil-delivery.infinityfree.me/foodoil/

**Technologies:** PHP, MySQL, HTML, CSS, JavaScript.# FoodOil - Online Cooking Oil Home Delivery Website

A responsive e-commerce website for ordering cooking oils, built as a college-level full-stack project.

**Stack:** HTML5, CSS3, JavaScript (no frameworks) - PHP 7.4+ (PDO) - MySQL / MariaDB - runs on XAMPP.

> Products, prices, reviews and the sample orders are **demo data**. The store address, phone and email are placeholders.

---

## 1. Run it with XAMPP (Windows / macOS / Linux)

1. Install XAMPP (PHP 7.4 or newer) and open the **XAMPP Control Panel**.
2. Start **Apache** and **MySQL**.
3. Copy the whole `foodoil` folder into XAMPP's web folder:
   - Windows: `C:\xampp\htdocs\foodoil`
   - macOS / Linux: `/opt/lampp/htdocs/foodoil` (or `/Applications/XAMPP/htdocs/foodoil`)
4. Open **http://localhost/phpmyadmin** and import the database:
   1. Click the **Import** tab.
   2. Choose `foodoil/database/foodoil.sql`.
   3. Click **Go** (it creates the `foodoil_db` database, tables and sample data).
5. Open **http://localhost/foodoil/** in your browser.

If your MySQL `root` user has a password, edit `config/database.php`.
If you rename the folder, nothing else needs to change - the base URL is detected automatically
(you can also set `BASE_URL` by hand in `config/database.php`).

### Sample accounts

| Role     | Email                  | Password       |
|----------|------------------------|----------------|
| Admin    | admin@foodoil.com      | `Admin@123`    |
| Customer | customer@foodoil.com   | `Customer@123` |

Change these passwords before showing the project publicly (log in, then use *Change Password*).

---

## 2. What is included

**Customer site**
- Home, Products (search, category filter, sorting, stock indicator), Product Details, About, Contact
- Cart (localStorage, works across pages, quantity/stock limits, remove with confirmation)
- Checkout (validated form, server-side order creation), Order Confirmation, My Orders, Order Details with a progress tracker and *Cancel order* (Pending orders only, with confirmation)
- Register, Login, Logout, Change Password, Forgot Password (explains the admin-reset flow)
- Mobile menu, toast notification and instant cart badge, empty-cart / no-results / error / loading states

**Admin panel** (`/admin/`, admin role only)
- Dashboard: total orders, revenue, users, products, recent orders
- Products: add, edit, delete, image upload, quick stock update, show/hide
- Orders: search, filter, update status (Pending, Confirmed, Out for Delivery, Delivered, Cancelled)
- Users: list and reset customer passwords (shows a temporary password once)
- Reports: 14-day revenue, orders by status, top products, revenue by category, low stock
- Messages: contact-form submissions

---

## 3. Payments - important

- **Cash on Delivery** is the only real payment method.
- **UPI** and **Card** are **demo options only**. No payment gateway is connected, no card or UPI details are collected or stored, and the order is placed as "pay on delivery".
- To accept real online payments you must integrate a licensed gateway (for example Razorpay or Stripe), which needs your own merchant credentials and server-side verification.

## 4. Email

Password-reset emails and order emails need an SMTP service, which is not configured. Instead:
- *Forgot password* tells the customer to contact the admin, who resets the password in **Admin -> Users**.
- The contact form saves messages to the database (**Admin -> Messages**).

To add real email later, install PHPMailer and send mail from `contact.php` and `api/orders.php`.

---

## 5. Security measures

- PDO **prepared statements** for every query
- Passwords hashed with `password_hash()` (bcrypt); `password_verify()` on login; session ID regenerated on login
- Session cookies: `HttpOnly`, `SameSite=Lax`
- **CSRF tokens** on every state-changing form and API request
- **Order totals, prices and stock are recalculated on the server** inside a database transaction (rows locked with `FOR UPDATE`); the browser only sends product IDs and quantities
- Role-based access: every `/admin/` page calls `require_admin()`; customers can only open their own orders
- Output escaping with `htmlspecialchars` (`e()` helper); validated input on both browser and server
- Image uploads: type checked with `getimagesize`, random file names, SVG not accepted
- Basic login throttling (5 failed attempts -> 5 minute lock per browser session)

Before putting this on a public server: use HTTPS, set a MySQL password, set `APP_DEBUG` to `false` (default), and change the sample passwords.

---

## 6. Folder structure

```
foodoil/
  index.php  products.php  product-details.php  cart.php  checkout.php  order-success.php
  my-orders.php  order-details.php  login.php  register.php  logout.php
  forgot-password.php  change-password.php  about.php  contact.php
  config/database.php
  includes/  header.php  footer.php  functions.php
  api/       cart.php  products.php  orders.php
  admin/     dashboard.php  products.php  orders.php  users.php  reports.php  messages.php  _header.php  _footer.php
  assets/
    css/     style.css  responsive.css  admin.css
    js/      app.js  cart.js  checkout.js
    images/  hero.svg  login-art.svg  favicon.svg  products/*.svg  uploads/
  database/foodoil.sql
```

## 7. Customising

- **Product photos:** product pictures are local SVG illustrations so the project works offline. To use real photos, upload a JPG/PNG/WebP in *Admin -> Products -> Edit*, or put files in `assets/images/products/` and change a product's image path.
- **Delivery rules:** `DELIVERY_FEE` (Rs 40) and `FREE_DELIVERY_ABOVE` (Rs 999) are at the top of `includes/functions.php`.
- **Colours:** CSS variables at the top of `assets/css/style.css`.
- Icons (Font Awesome) and the Poppins font load from CDNs, so an internet connection is needed for them. The site still works without it, just with plain text and fallback fonts.

## 8. Troubleshooting

| Problem | Fix |
|---|---|
| "Cannot connect to the database" | Start MySQL in XAMPP and import `database/foodoil.sql`. Check the password in `config/database.php`. |
| Styles or images missing | Open the site through `http://localhost/foodoil/` (not by double-clicking files). |
| Links go to the wrong place | Set `BASE_URL` by hand in `config/database.php`, e.g. `define('BASE_URL', '/foodoil');`. |
| Image upload fails | Make sure `assets/images/uploads/` is writable and the file is under 2 MB. |
| Import error about `CHECK` | Update XAMPP (MariaDB 10.2+ / MySQL 8.0.16+ is needed for CHECK constraints). |
| Blank page | Set `APP_DEBUG` to `true` in `config/database.php`, or look in `xampp/apache/logs/error.log`. |
