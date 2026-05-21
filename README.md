# Network of Muslim Physicians — PHP Backend API

## Requirements
- PHP 8.1+
- MySQL 8+  
- Apache with mod_rewrite (XAMPP / WAMP / Laravel Valet / live server)
- Composer

## Quick Start (XAMPP)

### 1. Place the folder
Copy this folder to `htdocs/nopm-api` so it is accessible at:
```
http://localhost/nopm-api
```

### 2. Install Composer dependencies
```bash
cd htdocs/nopm-api
composer install
```

### 3. Create the database
Open phpMyAdmin → SQL tab → paste and run `setup.sql`.

This creates:
- Database: `mednetwork`
- Tables: `admins`, `doctors`, `doctor_images`
- Default admin: `admin@mednetwork.com` / `admin123`

If your MySQL user is not `admin`, either:
- Create the user: `CREATE USER 'admin'@'localhost' IDENTIFIED BY 'admin123';`
- `GRANT ALL PRIVILEGES ON mednetwork.* TO 'admin'@'localhost'; FLUSH PRIVILEGES;`
- OR edit `config/database.php` and set `DB_USER` / `DB_PASS` to your credentials.

### 4. Verify Apache mod_rewrite
The `.htaccess` uses `RewriteBase /nopm-api/`. Make sure `AllowOverride All` is set in your Apache virtual host or `httpd.conf`.

### 5. Test the API
Open: http://localhost/nopm-api/index.php  
You should see: `{ "success": true, "message": "Network of Muslim Physicians API is running" }`

---

## React Frontend Connection

In `nmp-app2/.env`:
```
REACT_APP_API_URL=http://localhost/nopm-api
```

Then start the React app:
```bash
cd nmp-app2
npm install
npm start
```

---

## Admin Credentials
| Field | Value |
|-------|-------|
| Email | admin@mednetwork.com |
| Password | admin123 |
| URL | http://localhost:3000/login |

---

## API Endpoints

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | /auth/login.php | — | Admin login → JWT token |
| POST | /auth/logout.php | Bearer | Logout |
| GET | /auth/me.php | Bearer | Get current admin |
| GET | /dashboard/stats.php | Bearer | Doctor counts + recent data |
| GET | /doctors/all.php | — | List doctors (search/filter/paginate) |
| GET | /doctors/single.php?id=X | — | Single doctor detail |
| POST | /doctors/create.php | Bearer | Create doctor (multipart) |
| POST | /doctors/update.php | Bearer | Update doctor (multipart) |
| POST | /doctors/delete.php | Bearer | Delete doctor |
| POST | /doctors/toggle-status.php | Bearer | Change doctor status |
| POST | /doctors/upload-gallery.php | Bearer | Upload gallery images |
| POST | /doctors/remove-image.php | Bearer | Delete a gallery image |

### Query Parameters for /doctors/all.php
| Param | Type | Description |
|-------|------|-------------|
| search | string | Search name, ID, email, phone |
| specialty | string | Filter by specialty |
| location | string | Filter by location (partial match) |
| status | string | verified / pending / inactive |
| page | int | Page number (default: 1) |
| limit | int | Results per page (default: 200) |

---

## Production Deployment
1. Set environment variables (or edit `config/database.php`):
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - `JWT_SECRET` — a long random string (min 64 chars)
   - `ALLOWED_ORIGIN` — your React app's domain
2. Update `.htaccess` `RewriteBase` to match your URL path.
3. Ensure `uploads/` directory is writable: `chmod -R 775 uploads/`
