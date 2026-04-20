# 📦 GASS ERP

<div align="center">
  <p><strong>GASS ERP</strong> is a robust Enterprise Resource Planning (ERP) application built specifically for inventory and warehouse management. Powered by <strong>Laravel 11</strong> and the beautiful <strong>FilamentPHP v3</strong> admin panel.</p>
</div>

---

## ✨ Key Features

- **🏢 Multi-Level Organization:** Manage your assets across multiple Plants and Warehouses.
- **📦 Comprehensive Inventory Tracking:**
  - Real-time stock movement tracking (IN / OUT).
  - Accurate Average Cost (Moving Average) valuation.
  - Multi-unit and categorical item management.
- **📋 Stock Opname (Audit):**
  - Built-in Cycle Counting feature.
  - Automatically captures system stock vs. physical stock.
  - Calculates audit accuracy and valuation variance.
- **🔐 Role-Based Access Control:**
  - Separate access for `ADMIN` (Full CRUD) and `STAFF` (Limited Operational Access).
- **📊 Interactive Dashboard & Widgets:**
  - Real-time stock valuation overview.
  - Low stock alerts and dead-stock monitoring.
  - Top department consumption charts.
- **🛡️ Activity Auditing:** Full audit trail for every action (Powered by `spatie/laravel-activitylog`).
- **📥 Import/Export:** Built-in Excel integration for easy data migration.

---

## 🛠️ Tech Stack

- [Laravel 11](https://laravel.com/)
- [FilamentPHP v3](https://filamentphp.com/) (TALL Stack)
- [Spatie Activitylog](https://spatie.be/docs/laravel-activitylog)
- SQLite (Default for Dev) / MySQL / PostgreSQL

---

## 🚀 Getting Started

Follow these steps to set up the project on your local machine.

### 1. Prerequisites

Make sure your environment meets the following requirements:
- PHP 8.2+
- Composer
- Node.js & NPM

### 2. Installation

Clone the repository and install the dependencies:

```bash
git clone https://github.com/shakirarunika/gass-erp.git
cd gass-erp

# Install PHP dependencies
composer install

# Install JS dependencies
npm install
```

### 3. Environment Setup

Copy the example environment file and generate the app key:

```bash
cp .env.example .env
php artisan key:generate
```

Configure your database in the `.env` file. By default, you can use SQLite for quick setup:

```env
DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database.sqlite
```

*(If using SQLite, make sure to create the file first: `touch database/database.sqlite`)*

### 4. Database Migration

Run the migrations to set up the database schema:

```bash
php artisan migrate
```

### 5. Running the Application

You can start the application using the built-in development server:

```bash
php artisan serve
npm run dev
```

Visit the admin panel at: `http://localhost:8000/admin`

---

## 🧪 Testing

To run the test suite:

```bash
php artisan test
```

---

## 👨‍💻 Development Guidelines

- **Clean Code:** The codebase follows strict PSR-12 standards with comprehensive PHPDoc blocks.
- **Fat Models, Skinny Controllers:** Business logic (such as stock mutation and average cost calculation) is centralized within the Models (`Transaction`, `StockOpname`) to prevent duplication.
- **N+1 Safe:** Queries are optimized to prevent N+1 issues, particularly during bulk operations like Stock Opname generation.

---

<div align="center">
  <sub>Built with ❤️ using Laravel & Filament.</sub>
</div>
