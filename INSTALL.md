# 🎓 EduPlatform — LMS Installation Guide

## Requirements
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8+ or MariaDB 10.6+

---

## Installation Steps

### 1. Create Laravel Project (if not yet done)
```bash
composer create-project laravel/laravel eduplatform
cd eduplatform
```

### 2. Copy generated files
Copy all files from this folder into your Laravel project root.

### 3. Install PHP packages
```bash
# MaryUI + Livewire Volt
composer require livewire/livewire livewire/volt robsontenorio/mary

# Spatie permissions (role management)
composer require spatie/laravel-permission

# Media Library (for file uploads)
composer require spatie/laravel-medialibrary
```

### 4. Publish configs
```bash
php artisan volt:install
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan mary:install
```

### 5. Configure .env
```env
APP_NAME="EduPlatform"
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eduplatform
DB_USERNAME=root
DB_PASSWORD=

# Storage
FILESYSTEM_DISK=local
```

### 6. Register Middleware (bootstrap/app.php)
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\SetLocale::class,
    ]);
})
```

### 7. Register Policies (app/Providers/AuthServiceProvider.php)
```php
protected $policies = [
    \App\Models\Course::class => \App\Policies\CoursePolicy::class,
];
```

Or in `AppServiceProvider::boot()`:
```php
Gate::define('admin',   fn($user) => $user->isAdmin());
Gate::define('teacher', fn($user) => $user->isTeacher());
Gate::define('student', fn($user) => $user->isStudent());
Gate::define('parent',  fn($user) => $user->isParent());
```

### 8. Run migrations
```bash
php artisan migrate
```

### 9. Install Node packages & build
```bash
npm install
npm run dev
```

### 10. Create admin user
```bash
php artisan tinker
```
```php
\App\Models\User::create([
    'name' => 'Admin',
    'first_name' => 'Admin',
    'last_name'  => 'System',
    'email'      => 'admin@edu.dz',
    'password'   => bcrypt('password'),
    'role'       => 'admin',
    'status'     => 'active',
]);
```

---

## 📁 Project Structure

```
app/
├── Enums/
│   └── UserRole.php
├── Http/Middleware/
│   └── SetLocale.php
├── Models/
│   ├── User.php
│   ├── Student.php
│   ├── Teacher.php
│   ├── ParentModel.php
│   ├── AcademicYear.php
│   ├── Level.php
│   ├── Grade.php
│   ├── Subject.php
│   ├── Course.php
│   ├── Module.php
│   ├── Lesson.php
│   ├── Quiz.php
│   ├── Assignment.php
│   ├── LiveClass.php
│   ├── Enrollment.php
│   ├── LessonProgress.php
│   └── Payment.php
├── Policies/
│   └── CoursePolicy.php

database/migrations/
├── 000001 — users
├── 000002 — academic structure (years, levels, grades, subjects)
├── 000003 — profiles (students, parents, teachers)
├── 000004 — courses (courses, modules, lessons, enrollments)
├── 000005 — quizzes
├── 000006 — assignments
├── 000007 — live classes + tutoring + attendance
├── 000008 — payments (plans, subscriptions, invoices)
└── 000009 — progress, messages, notifications, certificates

resources/
├── lang/
│   ├── ar/lms.php   ← Arabic
│   ├── fr/lms.php   ← French (default)
│   └── en/lms.php   ← English
├── views/
│   ├── components/layouts/
│   │   ├── app.blade.php     ← Main authenticated layout
│   │   └── guest.blade.php   ← Login/Register layout
│   └── livewire/
│       ├── auth/login.blade.php
│       ├── dashboard/index.blade.php
│       ├── courses/index.blade.php
│       ├── admin/students/index.blade.php
│       ├── admin/teachers/index.blade.php
│       └── admin/payments/index.blade.php
```

---

## 🌐 Multi-language

Switch language via URL:
- `/language/fr` → French
- `/language/ar` → Arabic (RTL auto-applied)
- `/language/en` → English

---

## 🎨 Theme

Dark/Light toggle via MaryUI's `<x-theme-toggle />`.
Colors: Indigo primary, Purple secondary — customizable in `tailwind.config.js`.

---

## 📱 Responsive

- Mobile: Hamburger drawer navigation
- Tablet: Collapsible sidebar
- Desktop: Full sidebar with submenus
