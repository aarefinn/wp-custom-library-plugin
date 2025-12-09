# Library Manager – WordPress Plugin

A custom WordPress plugin that manages a library of books using a custom database table, secured REST API endpoints, and a React-based single-page admin dashboard.

## Features

- Creates a custom table `{$wpdb->prefix}library_books` on activation to store books with:
  - `id`, `title`, `description`, `author`, `publicationyear`, `status`, `createdat`, `updatedat`
- Secure REST API under the namespace `/wp-json/library/v1/`:
  - `GET  /books` – List all books with optional filters (status, author, year)
  - `GET  /books/{id}` – Get a single book by ID
  - `POST /books` – Create a new book (requires `edit_posts`)
  - `PUT  /books/{id}` – Update an existing book (requires `edit_posts`)
  - `DELETE /books/{id}` – Delete a book (requires `edit_posts`)
- All write operations use:
  - Capability check (`current_user_can( 'edit_posts' )`)
  - Input sanitization and basic validation
  - Proper HTTP status codes and JSON responses
- React SPA in WordPress admin:
  - Admin menu: **Library Manager**
  - Book list with Title, Author, Year, Status
  - Add / Edit book form
  - Delete with confirmation
  - Uses WordPress REST nonce for all mutating requests

## Installation

1. Download or clone the repository:
git clone https://github.com/aarefinn/wp-custom-library-plugin.git
2. Make sure the folder name is `library-manager` (or rename if needed) and place it in:
- `wp-content/plugins/library-manager`
3. From the WordPress admin:
- Go to **Plugins → Installed Plugins**
- Activate **Library Manager**

On activation, the plugin will create the `library_books` table using `dbDelta()`.

## REST API Usage

Base URL:/wp-json/library/v1

Example requests:

- List books:
  - `GET /wp-json/library/v1/books`
  - Optional query params: `?status=available&author=John&year=2024`
- Get a single book:
  - `GET /wp-json/library/v1/books/1`
- Create a book:
  - `POST /wp-json/library/v1/books`
  - Body fields: `title` (required), `description`, `author`, `publicationyear`, `status`
- Update a book:
  - `PUT /wp-json/library/v1/books/1`
- Delete a book:
  - `DELETE /wp-json/library/v1/books/1`

All `POST`, `PUT`, and `DELETE` requests require:
- Logged-in user with `edit_posts` capability
- Valid WordPress REST nonce (e.g. `X-WP-Nonce` header)

## Admin React App

The plugin registers an admin page **Library Manager** which loads a React single-page app from: assets/js/app.js


The app:

- Fetches books from the REST API
- Displays a list with Edit / Delete actions
- Provides a form to add and update books
- Uses `wp.apiFetch` and a localized nonce passed from PHP via `wp_localize_script`

## Project Structure
library-manager/
├─ library-manager.php # Main plugin bootstrap file (PHP, REST, admin page)
├─ assets/
│ └─ js/
│ └─ app.js # React SPA for the admin dashboard
└─ includes/ # Reserved for future modular PHP code


## Notes

- All direct database access uses `$wpdb` with prepared statements where applicable.
- Input is sanitized before insert/update, and output is escaped on render in the admin.
- The React bundle is loaded from the plugin directory without any external CDN dependencies.




