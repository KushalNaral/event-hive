# Project Name

Welcome to the **Event Hive**.
This project aims to create a platform for browsing, and participating in a feature rich personalized event hub.

## Table of Contents
- [Installation](#installation)
- [Recent Updates](#recent-updates)

## Installation

Follow these steps to get the project up and running on your local machine:

1. **Clone the Repository**
   ```bash
   git clone https://github.com/KushalNaral/event-hive
   cd event-hive
   ```

2. **Install Dependencies**
    ```bash
    composer install
    ```

3. **Generate Application Keys**
    ```bash
    php artisan key:gen
    ```

4. **Create .env**
    ```bash
    cp .env.example .env
    ##Update database credentials and other environment variables in .env file by changing the DB_DATABASE, DB_USERNAME and DB_PASSWORD fields
    ```

5. **Run The Migrations**
    ```bash
    php artisan migrate --seed
    ```

6. **Run The Application**
    ```bash
    php artisan serve
    ```

## Project Overview

### Recent Updates

**Log:**
* **Date:** Sunday, August 4, 2024
  * Set up Laravel project
  * Created migrations (files table to be added)
  * Created models for User, Events, EventCategories
  * Created response helpers in App\Helpers\ReponseHelpers.php
  * Created a database seeder for EventCategories
  * Created an API for EventCategories
