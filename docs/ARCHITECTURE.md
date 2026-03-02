# Architecture Overview

## System Context

The **SIKDS** application serves as a centralized platform for secure distribution of official ministry documents with integrated RAG-based search and question-answering capabilities.

## Technology Choices

### Server-Side: Laravel & Livewire
We utilize **Laravel 12** as the core framework for its robustness, security, and extensive ecosystem.
**Livewire** is chosen to built dynamic, SPA-like interfaces without the complexity of a separate frontend SPA (like React or Vue). This allows us to keep the logic within PHP and Blade templates while offering a responsive user experience.

### Client-Side: Tailwind CSS & Alpine.js
-   **Tailwind CSS (v4)**: Provides a utility-first approach for rapid and consistent UI development.
-   **Alpine.js**: Used for lightweight client-side interactivity (e.g., toggling tabs, modals) that doesn't require a full server round-trip.

## Directory Structure

The project follows the standard Laravel structure with some specific organizations:

-   **`app/Livewire`**: Contains the logic for the interactive components.
-   **`resources/views/livewire`**: Contains the Blade templates for the Livewire components.
-   **`resources/views/livewire/feature`**: Specific views for the  feature set.
-   **`routes/`**:
    -   `web.php`: Entry point, loads other route files.
    -   `auth.php`: Authentication routes.
    -   `common.php`: Shared routes.
    -   `fonctionalities.php`: Feature-specific routes.

## Core Concepts

### Role-Based Access


### Resource Organization
Feature-specific code is organized to keep related logic together.
-   **Components**: Reusable components like `tab-navigation` are placed in `resources/views/components/common/`.

### Data Flow
1.  **Request**: User interacts with the UI.
2.  **Livewire**: Intercepts the interaction and sends an AJAX request to the server.
3.  **Component Logic**: The PHP component processes the request, updates properties, or performs database actions.
4.  **Render**: The component re-renders the blade view with new data.
5.  **DOM Update**: Livewire intelligently interprets the HTML diff and updates the DOM.
