# Development Guidelines

This document outlines the coding standards and best practices for contributing to the **Prix Président** project.

## Coding Standards

### PHP
-   We follow the **PSR-12** coding standard.
-   Use **strict typing** (`declare(strict_types=1);`) in all new PHP files.
-   **Controllers/Components**: Keep them thin. Move complex business logic to Services or Actions.
-   **Naming**: 
    -   Classes: `PascalCase`
    -   Methods/Variables: `camelCase`
    -   Constants: `UPPER_SNAKE_CASE`

### Blade & HTML
-   Use semantic HTML tags where possible.
-   Ensure accessibility attributes (`aria-*`, `role`) are used for custom interactive elements.
-   Indent your Blade files with 4 spaces for readability.

### CSS (Tailwind)
-   Avoid inline styles; use Tailwind utility classes.
-   For repeated patterns, extract them into components or use `@apply` in CSS files sparingly.
-   Follow the ordering of utility classes: Layout -> Box Model -> Typography -> Visual -> Misc (or let the linter handle it).

### JavaScript (Alpine.js)
-   Keep Alpine components small and focused.
-   Extract complex logic into separate JavaScript files or Alpine data objects if the HTML becomes cluttered.

## Git Workflow

1.  **Branching**: Create a new branch for each feature or bugfix.
    -   Feature: `feature/your-feature-name`
    -   Bugfix: `fix/bug-description`
2.  **Commits**: Write clear and descriptive commit messages.
    -   Format: `type: description` (e.g., `feat: add project evaluation form`, `fix: resolve tab switching issue`).
3.  **Pull Requests**: detailed description of changes and steps to test.

## Testing

-   **Pest** is the preferred testing framework.
-   Run tests before pushing your code:
    ```bash
    php artisan test
    ```
-   Write tests for new features, covering both happy, unhappy paths and edge cases.

## Database

-   **Migrations**: Always use migrations for schema changes. Never modify the database directly.
-   **Seeders**: Maintain seeders to populate the database with realistic test data.
