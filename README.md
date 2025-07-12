# Laravel CSV Upload & Progress Tracker

A simple Laravel application that allows users to upload CSV files, store them uniquely, validate required headers, and track processing progress.

## 🚀 Features

- Drag & drop CSV file upload
- Header validation with error feedback
- Progress tracking for uploaded files
- Unique filename handling
- Responsive frontend UI

## 📂 Required Headers

The uploaded CSV must contain the following headers:

UNIQUE_KEY, STYLE#, SIZE, COLOR_NAME,
PRODUCT_TITLE, PRODUCT_DESCRIPTION,
SANMAR_MAINFRAME_COLOR, PIECE_PRICE


## 🧰 Requirements

- PHP >= 8.1
- Laravel >= 10
- SQLite
- Redis
- php ext-pcntl ( not available for windows)
- Node.js & npm (for frontend assets if needed)
- php ini set to
    memory_limit => 256M
    post_max_size => 120M
    upload_max_filesize => 100M

## ⚙️ Installation

```bash
git clone https://github.com/muazkhairi92/upload-csv
cd upload-csv
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
