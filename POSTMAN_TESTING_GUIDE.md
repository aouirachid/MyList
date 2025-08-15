# MyList API - Postman Testing Guide

## Overview

This guide will help you test Laravel API endpoints using Postman. The API uses JWT authentication and includes endpoints for authentication, users, tasks, tags, documents, and collaborators.

## Prerequisites

1. **Laravel Application Running**: Make sure your Laravel application is running on `http://mylist.test:8080`
2. **Database Setup**: Ensure your database is migrated and seeded
3. **JWT Secret**: Make sure you have generated a JWT secret using `php artisan jwt:secret`
4. **Postman Installed**: Download and install Postman from [postman.com](https://www.postman.com/)

## Setup Instructions

### 1. Import the Collection

1. Open Postman
2. Click "Import" button
3. Select the `MyList_API_Collection.postman_collection.json` file
4. The collection will be imported with all endpoints organized by category

### 2. Configure Environment Variables

1. In Postman, click on the "Environments" tab (or create a new environment)
2. Add the following variables:
    - `base_url`: `http://mylist.test:8080/api/v1`
    - `access_token`: Leave empty initially (will be filled after login)

### 3. Select Your Environment

Make sure to select your environment from the dropdown in the top-right corner of Postman.

## Testing Workflow

### Step 1: Authentication Testing

#### 1.1 Register a New User

-   **Endpoint**: `POST {{base_url}}/auth/register`
-   **Headers**:
    -   `Content-Type: application/json`
    -   `Accept: application/json`
-   **Body** (JSON):

```json
{
    "firstName": "Aoui",
    "lastName": "Rachid",
    "gender": "male",
    "country": "Morocco",
    "city": "Casablanca",
    "birthday": "2000-12-02",
    "userName": "aouirachid",
    "email": "rachid.aoui@mylist.com",
    "phone": "766051861",
    "password": "password12345",
    "accountType": 1,
    "status": 1
}
```

-   **Expected Response**: 201 Created with user data and JWT token

#### 1.2 Login User

-   **Endpoint**: `POST {{base_url}}/auth/login`
-   **Headers**:
    -   `Content-Type: application/json`
    -   `Accept: application/json`
-   **Body** (JSON):

```json
{
    "login": "rachid.aoui@mylist.com",
    "password": "password12345"
}
```

-   **Expected Response**: 200 OK with access token

**Important**: After successful login, copy the `access_token` from the response and set it as the `access_token` environment variable in Postman.

#### 1.3 Test Token Refresh

-   **Endpoint**: `POST {{base_url}}/auth/refresh`
-   **Headers**:
    -   `Authorization: Bearer {{access_token}}`
    -   `Accept: application/json`
-   **Expected Response**: 200 OK with new access token

#### 1.4 Logout

-   **Endpoint**: `POST {{base_url}}/auth/logout`
-   **Headers**:
    -   `Authorization: Bearer {{access_token}}`
    -   `Accept: application/json`
-   **Expected Response**: 200 OK

#### 1.5 Forgot Password

-   **Endpoint**: `POST {{base_url}}/auth/password/email`
-   **Headers**:
    -   `Content-Type: application/json`
    -   `Accept: application/json`
-   **Body** (JSON):

```json
{
    "email": "john.doe@example.com"
}
```

-   **Expected Response**: 200 OK

#### 1.6 Reset Password

-   **Endpoint**: `POST {{base_url}}/auth/password/reset`
-   **Headers**:
    -   `Content-Type: application/json`
    -   `Accept: application/json`
-   **Body** (JSON):

```json
{
    "email": "john.doe@example.com",
    "password": "newpassword12345",
    "password_confirmation": "newpassword12345",
    "token": "reset_token_here"
}
```

-   **Expected Response**: 200 OK

### Step 2: Protected Endpoints Testing

Once you have a valid access token, you can test all protected endpoints. The token will be automatically included in requests using the `{{access_token}}` variable.

#### 2.1 Users Endpoints

-   **Get All Users**: `GET {{base_url}}/users`
-   **Get User by ID**: `GET {{base_url}}/users/1`
-   **Update User**: `PUT {{base_url}}/users/1`
-   **Delete User**: `DELETE {{base_url}}/users/1`
-   **Change Password**: `POST {{base_url}}/users/1/change-password`

#### 2.2 Tasks Endpoints

-   **Get All Tasks**: `GET {{base_url}}/tasks`
-   **Create Task**: `POST {{base_url}}/tasks`
-   **Create Sub Task**: `POST {{base_url}}/tasks`
-   **Get Task by ID**: `GET {{base_url}}/tasks/1`
-   **Update Task**: `PUT {{base_url}}/tasks/1`
-   **Delete Task**: `DELETE {{base_url}}/tasks/1`

#### 2.3 Tags Endpoints

-   **Get All Tags**: `GET {{base_url}}/tags`
-   **Create Tag**: `POST {{base_url}}/tags`
-   **Get Tag by ID**: `GET {{base_url}}/tags/1`
-   **Update Tag**: `PUT {{base_url}}/tags/1`
-   **Delete Tag**: `DELETE {{base_url}}/tags/1`

## Sample Request Bodies

### Create Task

```json
{
    "title": "Sample Task",
    "description": "This is a sample task description",
    "status": 1,
    "priority": 1,
    "startDate": "2025-12-31",
    "endDate": "2025-12-31",
    "user_id": 1
}
```

### Create Sub Task

```json
{
    "title": "Sample Sub Task",
    "description": "This is a sample sub task description",
    "status": 1,
    "priority": 1,
    "startDate": "2025-12-31",
    "endDate": "2025-12-31",
    "user_id": 1,
    "parentTaskId": 1
}
```

### Update Task

```json
{
    "title": "Updated Task Title",
    "description": "Updated task description",
    "status": 1,
    "priority": 1,
    "startDate": "2025-12-31",
    "endDate": "2025-12-31",
    "parentTaskId": 1
}
```

### Create Tag

```json
{
    "tagName": "Sport"
}
```

### Update Tag

```json
{
    "tagName": "Sport Updated"
}
```

### Update User

```json
{
    "firstName": "Aoui Updated",
    "lastName": "Rachid Updated",
    "gender": "male",
    "country": "United States",
    "city": "Los Angeles",
    "birthday": "2000-12-02",
    "userName": "aouirachid",
    "email": "rachid.aoui@mylist.com",
    "phone": "766051861"
}
```

### Change Password

```json
{
    "current_password": "password12345",
    "new_password": "newpassword12345",
    "new_password_confirmation": "newpassword12345"
}
```

## Testing Tips

### 1. Authentication Flow

1. Always start with registration or login to get a valid token
2. Set the `access_token` environment variable after successful login
3. Use the token for all subsequent requests

### 2. Error Handling

-   **401 Unauthorized**: Check if your token is valid and not expired
-   **422 Validation Error**: Check the request body format and required fields
-   **404 Not Found**: Verify the resource ID exists in the database
-   **500 Server Error**: Check Laravel logs for detailed error information

### 3. Token Management

-   Tokens expire after a certain time (check JWT configuration)
-   Use the refresh token endpoint to get a new token
-   Logout invalidates the current token

### 4. Database State

-   Some tests may depend on existing data
-   Consider running database seeders before testing
-   Be careful with DELETE operations as they permanently remove data

## Troubleshooting

### Common Issues

#### 1. "Token has expired" Error

-   Use the refresh token endpoint to get a new token
-   Update the `access_token` environment variable

#### 2. "Route not found" Error

-   Verify the Laravel application is running
-   Check if the route is properly defined in `routes/api.php`
-   Ensure you're using the correct HTTP method

#### 3. "Validation failed" Error

-   Check the request body format
-   Ensure all required fields are provided
-   Verify data types match validation rules

#### 4. "Database connection" Error

-   Check your `.env` file configuration
-   Ensure the database server is running
-   Verify database credentials

### Debugging Steps

1. **Check Laravel Logs**: Look at `storage/logs/laravel.log` for detailed error messages
2. **Verify Environment**: Ensure your `.env` file has correct database and JWT settings
3. **Test Database Connection**: Run `php artisan migrate:status` to verify database connectivity
4. **Check JWT Configuration**: Ensure JWT secret is properly set with `php artisan jwt:secret`

## API Response Format

All API responses follow this general format:

### Success Response

```json
{
    "status": "success",
    "message": "Operation completed successfully",
    "data": {
        // Response data here
    }
}
```

### Error Response

```json
{
    "status": "error",
    "message": "Error description",
    "errors": {
        // Validation errors (if any)
    }
}
```
