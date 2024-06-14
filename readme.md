# Shift Management Laravel Project

This project is a Laravel application that processes shift data from a JSON file and calculates the total regular and overtime hours worked by employees. It also identifies invalid shifts where there are overlapping shifts for the same employee.

## Requirements

- PHP 8.1
- Laravel 8 or higher

## Installation

1. Navigate to the project directory:

   ```bash
   cd <project-directory>

2. Install the dependencies:

   ```bash
    composer install

## Run Program

1. To run the program open the terminal in the directory and run:
    ```bash
    php artisan serve
    
2. Open Postman application or any other API testing tool and pass the localhost address and use this endpoint like http://127.0.0.1:8000/api/process-shifts

## Run Test Cases

1. To run the test cases go to the termianal and run

    ```bash
    php artisan test


## Folder Structure

`app/Exceptions:` Custom exception handling for the application.

`app/Http/Controllers:` Contains the ShiftController that handles the request to process shifts.

`app/Services:` Contains the ShiftService which processes the shifts data.

`tests/Unit:` Contains the unit tests for the ShiftService.

`tests/Feature:` Contains the feature tests for the ShiftController.

## Services

The core logic of the application is handled by the ShiftService located in the app/Services directory. This service processes the shifts from the JSON file and calculates the regular and overtime hours, as well as identifies invalid shifts.


## Exception Handling

Custom exceptions are handled in the app/Exceptions directory. The ShiftServiceException is used to handle errors related to the processing of shift data.

## Testing

This project includes unit and feature tests to ensure the functionality of the ShiftService and ShiftController.

## Improvement Point

1. Implement comprehensive logging for performance monitoring.
2. Integrate dynamic time zone conversion logic.
3. Enhance validation checks and error handling.
4. Develop an interactive Command Line Interface.
5. Optimize code for large databases using efficient data structures, parallel computing, and caching strategies.
