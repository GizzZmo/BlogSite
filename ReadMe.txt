# PHP Multi-User Blog Platform

A secure and user-friendly multi-user blogging platform built with PHP, focusing on modern security practices and ease of use. Each registered user will have their own blog space to create, manage, and publish posts.

## Core Features

* **User Registration:** Secure user sign-up with email and password.
* **Email Confirmation:** Account activation via a unique link sent to the user's email.
* **Secure Login/Logout:** Session-based authentication with protection against common vulnerabilities.
* **Password Management:** Secure password hashing and a "Forgot Password" mechanism (to be implemented).
* **Individual User Blogs:** Each user gets a dedicated space (e.g., `example.com/blogs/username`).
* **Post Management (CRUD):**
    * Create, Read, Update, and Delete blog posts.
    * A simple rich text editor or Markdown support for post content.
* **Public Blog Views:** Display user blogs and individual posts to the public.
* **User Profiles:** Basic user profile pages.
* **Admin Area (Future Scope):** For site-wide management (users, content moderation).

## Security Focus

* **Password Hashing:** Using PHP's `password_hash()` and `password_verify()`.
* **SQL Injection Prevention:** Utilizing PDO with prepared statements.
* **Cross-Site Scripting (XSS) Prevention:** Proper output encoding (e.g., `htmlspecialchars()`).
* **Cross-Site Request Forgery (CSRF) Protection:** Implementing CSRF tokens on forms.
* **Secure Session Management:** Using secure session settings.
* **Input Validation:** Rigorous server-side validation of all user inputs.
* **Email Confirmation:** To verify user identity and prevent spam registrations.

## Technology Stack

* **Backend:** PHP (targeting version 7.4+ or 8.0+)
* **Database:** MySQL / MariaDB
* **Frontend:** HTML, CSS, JavaScript (for enhancements, not a SPA)
* **Email Sending:** PHPMailer library (recommended for robust email sending)

## Project Structure


/php-multi-user-blog/
├── public/                 # Publicly accessible files (front controller, assets)
│   ├── index.php           # Main entry point (Router / Front Controller)
│   ├── css/                # CSS files
│   │   └── style.css
│   ├── js/                 # JavaScript files
│   │   └── script.js
│   └── assets/             # Images, fonts, etc.
│   └── .htaccess           # Apache configuration for URL rewriting
├── src/                    # Core application logic (PHP classes, etc.)
│   ├── Core/               # Core components (Database, Router, Session, Validator, etc.)
│   │   ├── Database.php
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Session.php
│   │   ├── Validator.php
│   │   └── Auth.php        # Authentication helper
│   ├── Controllers/        # Handles requests, interacts with models, loads views
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── PostController.php
│   │   └── PageController.php  # For static pages like home, about
│   ├── Models/             # Database interaction logic (data retrieval and storage)
│   │   ├── User.php
│   │   ├── Post.php
│   ├── Views/              # HTML templates (PHP files acting as templates)
│   │   ├── layouts/        # Reusable layout parts (header, footer, nav)
│   │   │   ├── app.php         # Main layout file
│   │   │   ├── header.php      # (Could be part of app.php or separate)
│   │   │   └── footer.php      # (Could be part of app.php or separate)
│   │   ├── auth/
│   │   │   ├── register.php
│   │   │   ├── login.php
│   │   │   ├── confirm_email.php
│   │   │   └── forgot_password.php
│   │   ├── user/
│   │   │   ├── dashboard.php   # User's own control panel
│   │   │   ├── profile.php     # Public profile view / User's blog landing
│   │   │   └── settings.php
│   │   ├── posts/
│   │   │   ├── index.php       # List of posts (e.g., on user's blog)
│   │   │   ├── create.php
│   │   │   ├── show.php        # Single post view
│   │   │   └── edit.php
│   │   └── pages/
│   │       ├── home.php
│   │       └── about.php
│   ├── Lib/                  # Helper functions, third-party libraries (if not using Composer for them)
│   │   └── functions.php     # General helper functions
│   │   └── PHPMailer/        # (If installing manually, otherwise via Composer)
│   └── config/               # Configuration files
│       ├── config.php        # Main configuration (DB credentials, site settings, email)
│       └── routes.php        # Route definitions
├── vendor/                 # Composer dependencies (e.g., PHPMailer)
├── composer.json           # Composer dependency management file
├── composer.lock           # Composer lock file
└── .env.example            # Example environment variables file
└── .env                    # Environment variables (ignored by Git)


## Setup Instructions (To Be Expanded)

1.  **Clone the repository:**
    ```bash
    git clone <your-repository-url>
    cd php-multi-user-blog
    ```
2.  **Install dependencies (if using Composer):**
    ```bash
    composer install
    ```
3.  **Environment Configuration:**
    * Copy `.env.example` to `.env`.
    * Update `.env` with your database credentials, app URL, and email settings.
4.  **Database Setup:**
    * Create a database (e.g., `php_blog_db`).
    * Import the initial schema (SQL file to be provided or migrations to be run).
5.  **Web Server Configuration:**
    * Point your web server's document root to the `public/` directory.
    * Ensure URL rewriting is enabled (e.g., `mod_rewrite` for Apache).
6.  **Permissions:**
    * Ensure the web server has write permissions to any necessary directories (e.g., cache, uploads - if applicable later).

## Contribution
https://github.com/GizzZmo

## License
MIT

This README.md provides a good overview. The file structure is a common way to organize a PHP MVC-like application, even if we're not using a full-fledged framework initially. It promotes separation of concerns.
