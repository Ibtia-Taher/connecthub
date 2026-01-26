# 🚀 Installation Guide

## Prerequisites
- XAMPP (Apache + MySQL + PHP 7.4+)
- Modern web browser

## Setup

1. **Clone repository**
```bash
git clone https://github.com/YOUR_USERNAME/connecthub.git
cd connecthub
```

2. **Copy config files**
```bash
cp config/database.example.php config/database.php
cp config/config.example.php config/config.php
```

3. **Edit config files**
- Update `config/database.php` with your DB credentials
- Update `config/config.php` with your APP_URL

4. **Import database**
- Open phpMyAdmin
- Create database: `connecthub`
- Import: `database/schema.sql`

5. **Set permissions**
```bash
chmod 777 assets/images/uploads
chmod 777 logs
chmod 777 cache
```

6. **Start XAMPP and visit**
```
http://localhost/connecthub
```

## Troubleshooting

See README.md for common issues.