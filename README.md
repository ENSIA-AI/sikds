# Prix Président

## Overview

**Prix Président** is a web application designed to manage the candidature and evaluation process for the "Prix Président" award. It facilitates the submission of projects, management of participants and teams, and the evaluation workflow by establishments.

## Technology Stack

This project is built using the following core technologies:

-   **Framework**: [Laravel 12.x](https://laravel.com)
-   **Frontend**: [Livewire](https://livewire.laravel.com)
-   **Styling**: [Tailwind CSS v4](https://tailwindcss.com) & [Flowbite](https://flowbite.com)
-   **Interactivity**: [Alpine.js](https://alpinejs.dev)
-   **Theme**: Custom Tailwind theme with [Select2](https://select2.org) integration.

## Prerequisites

Ensure you have the following installed on your local machine:

-   **PHP**: 8.2 or higher
-   **Node.js**: LTS version recommended
-   **Composer**: Dependency manager for PHP

## Installation

1.  **Clone the repository**:
    ```bash
    git clone <repository_url>
    cd prix
    ```

2.  **Install PHP dependencies**:
    ```bash
    composer install
    ```

3.  **Install Node.js dependencies**:
    ```bash
    npm install
    ```

4.  **Environment Configuration**:
    Copy the example environment file and configure your database settings:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *Update the `.env` file with your database credentials (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD).*

5.  **Database Migration**:
    Run the migrations to set up the database schema:
    ```bash
    php artisan migrate
    ```

## Development

To start the local development server, which runs both the Laravel server and Vite for asset bundling:

```bash
composer run dev
```
*Alternatively, you can run them separately:*
```bash
php artisan serve
npm run dev
```

## Key Features

-   **Project Management**: Detailed views for project submissions including Stages, TRL, and Innovation summaries.
-   **Team & Participants**: Management of establishiments and team members associated with a project.
-   **Evaluation**: Workflow for establishments to review, accept/reject, and provide observations on candidatures.
-   **Tabbed Interface**: Organized data presentation using a responsive tabbed layout.

## Documentation

For more detailed information, please refer to the documentation in the `docs/` directory:

-   [Architecture Overview](docs/ARCHITECTURE.md)
-   [Contribution Guidelines](docs/GUIDELINES.md)
