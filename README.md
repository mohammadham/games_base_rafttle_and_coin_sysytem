# Gaming Services Platform

This is a web application that provides a variety of services for game developers and players. The platform allows game developers to integrate a virtual currency system into their games, and it allows players to use that currency to purchase in-game items and participate in lotteries.

## Features

*   **Virtual Currency System:** A flexible virtual currency system that allows game developers to create their own custom currencies and manage their economies.
*   **Product Marketplace:** A marketplace where players can purchase in-game items using virtual currency.
*   **Lottery System:** A lottery system that allows players to win prizes by purchasing tickets with virtual currency.
*   **Game Developer API:** A comprehensive API that allows game developers to integrate the platform's services into their games.
*   **Multiple Payment Gateways:** Support for multiple payment gateways, including Zarinpal.
*   **Localization:** The platform is fully localized in Farsi.

## Getting Started

To get started with the project, you'll need to have the following installed on your machine:

*   PHP 8.3 or higher
*   Composer
*   Node.js
*   NPM

Once you have all of the required software installed, you can follow these steps to set up the development environment:

1.  Clone the repository: `git clone https://github.com/your-username/your-repository.git`
2.  Install the PHP dependencies: `cd core && composer install`
3.  Install the JavaScript dependencies: `npm install`
4.  Create a copy of the `.env.example` file and name it `.env`: `cp core/.env.example core/.env`
5.  Generate a new application key: `cd core && php artisan key:generate`
6.  Run the database migrations: `cd core && php artisan migrate`
7.  Run the database seeders: `cd core && php artisan db:seed`
8.  Start the development server: `cd core && php artisan serve`

## API Documentation

The Game Developer API provides a variety of endpoints for integrating the platform's services into your games. The following is a list of the available endpoints:

*   `POST /api/v1/game/credit-coin`: Credits a user's account with a specified amount of a virtual currency.
*   `POST /api/v1/game/debit-coin`: Debits a user's account with a specified amount of a virtual currency.
*   `GET /api/v1/game/test-connection`: Tests the connection to the API.

For more information about the API, please refer to the `GameApiController.php` file.

## Future Work

The following is a list of tasks that still need to be completed:

*   Add more payment gateways.
*   Implement a tax system.
*   Add more security tests.
*   Improve the UI/UX.

## Contributing

If you would like to contribute to the project, please feel free to open a pull request. All contributions are welcome!
