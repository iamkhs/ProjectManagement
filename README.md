# Project Management System API Documentation

## Overview

This is a RESTful Laravel API for a **Project Management System** with **Role-Based Access Control (RBAC)**.  
It allows managing **Projects**, **Tasks**, and **Subtasks** with **real-time notifications** and **detailed reporting**.

> **Note:** Caching and Unit Testing are not implemented.  

---

## Features

- **RBAC (Role-Based Access Control)**: Implemented using Laravel's built-in **Gate Policy** without third-party packages.
- **Real-Time Notifications**: Using **Laravel Broadcasting** and **Pusher** to notify users when tasks or subtasks are assigned.
- **Project and Task Management**: Ability to create, update, and delete projects, tasks, and subtasks.
- **Detailed Reporting**: Allows the generation and export of project reports in Excel format.
- **Seeding**: Pre-configured seeding for demo users with admin, team leaders, and team members.
- **Exception Handling**: The system uses **custom exception handling** to manage different error scenarios and return user-friendly responses.
- **Three-Layer Architecture**: Follows a clean **Controller → Service → Repository** structure for better maintainability, scalability, and separation of concerns.


## Requirements

- **PHP** >= 8.0
- **Laravel** >= 10.0
- **MySQL** or any compatible database
- **Composer** (for dependency management)

---

## Installation

### Step 1: Clone the Repository

Clone the project from GitHub to your local machine:

```bash
git clone https://github.com/iamkhs/ProjectManagement.git
```

### Run Migrations and Seed Database
Run the database migrations and seed the database with sample data:

```bash
php artisan migrate:fresh --seed
```

### Setup .env for Database

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
```

### RBAC Implementation
The **Role-Based Access Control (RBAC)** is implemented using Laravel's **Gate Policy** without any third-party packages.  
This ensures that only authorized users can access or perform actions on specific resources, such as creating, updating, or deleting projects, tasks, and subtasks.  
The roles are defined in the Laravel `Gate` and `Policy`, and the access control is enforced throughout the application based on user roles and permissions.

---

### Real-Time Notifications
The application implements **real-time notifications** using **Laravel Broadcasting** and **Pusher**.  
Notifications are triggered when a user is assigned a task or subtask, allowing team members and leaders to receive immediate updates.

To configure real-time notifications with Pusher:

1. Set up the **Pusher** service in your `.env` file:

```env
BROADCAST_DRIVER=pusher

PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_ID=appid
PUSHER_APP_KEY=appkey
PUSHER_APP_SECRET=appsecret
PUSHER_APP_CLUSTER=appcluster
```
### Run the Laravel queue worker:

For efficient performance and to handle event broadcasting properly, you must run the Laravel queue worker:

```bash
php artisan queue:work
```

## Authentication

Authentication is handled via **JWT (JSON Web Tokens)**.

### Register

**Endpoint:**  
`POST /api/register` for signup user

**Request Payload:**
```json
{
  "name": "John Doe",
  "email": "johndoe@example.com",
  "password": "password",
  "role": "admin, team_leader, team_member (default)"
}
```

**Response Example:**
```json
{
    "message": "User created successfully",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "johndoe@example.com",
        "role": "admin",
        "created_at": "2025-04-26T10:00:00.000000Z",
        "updated_at": "2025-04-26T10:00:00.000000Z"
    },
    "access_token": "jwt_token_here",
    "token_type": "bearer",
    "expires_in": 3600
}
```

### Login

**Endpoint:**  
`POST /api/login` for login user


**Request Payload:**
```json
{
    "email": "johndoe@example.com",
    "password": "password"
}
```
**Response Example:**
```json
{
    "access_token": "jwt_token_here",
    "token_type": "bearer",
    "expires_in": 3600
}
```


## Project Management API

All routes are prefixed with `/api/projects`.

### Routes

| Method  | Endpoint                  | Controller Method    | Description                         | Authorized Roles                 |
|:-------:|:---------------------------|:---------------------|:------------------------------------|:---------------------------------|
| GET     | `/`                        | `index`              | List all projects                   | Admin, Team Leader               |
| POST    | `/`                        | `store`              | Create a new project                | Admin, Team Leader               |
| GET     | `/{id}`                    | `show`               | Show details of a specific project  | Admin, Team Leader, Team Member  |
| PUT     | `/{id}`                    | `update`             | Update a specific project           | Admin, Team Leader (who created) |
| DELETE  | `/{id}`                    | `destroy`            | Delete a specific project           | Admin, Team Leader (who created) |
| PATCH   | `/{id}/assign`              | `assignMember`       | Assign a user to a project          | Admin, Team Leader               |
| PATCH   | `/{id}/unassign`            | `unassignMember`     | Unassign a user from a project      | Admin, Team Leader               |

---

### Authorization Rules (RBAC)

- **Admin**
    - Can perform **all** actions on any project.
- **Team Leader**
    - Can **create** new projects.
    - Can **view** their projects.
    - Can **update** or **delete** projects they have created.
    - Can **assign** and **unassign** team members from their own projects.
- **Team Member**
    - Can **view** projects where they are assigned as a member.
    - Cannot create, update, delete, assign, or unassign projects.

---

### Request Validations

- **Create Project (`POST /api/projects`)**
    - Fields are validated via `ProjectStoreRequest`.

- **Update Project (`PUT /api/projects/{id}`)**
    - Fields are validated via `ProjectUpdateRequest`.

- **Assign Member (`PATCH /api/projects/{id}/assign`)**
    - Request Body:
      ```json
      {
        "user_id": "required, must exist in users table"
      }
      ```

- **Unassign Member (`PATCH /api/projects/{id}/unassign`)**
    - Request Body:
      ```json
      {
        "user_id": "required, must exist in project_members table"
      }
      ```

---

### Responses

- Success responses return:
    ```json
    {
      "message": "Success message",
      "status": 200
    }
    ```
- Error responses (e.g., unauthorized access, validation failure) return appropriate HTTP status codes like 400, 401, 403, or 404.


## Task Management API

All routes are prefixed with `/api/tasks`.

### Routes

| Method  | Endpoint                       | Controller Method   | Description                                      | Authorized Roles                        |
|:-------:|:--------------------------------|:--------------------|:-------------------------------------------------|:----------------------------------------|
| GET     | `/project/{id}`                 | `findByProject`     | Get all tasks for a specific project             | Admin, Team Leader, Team Member         |
| POST    | `/`                             | `store`             | Create a new task                                | Admin, Team Leader                      |
| GET     | `/{id}`                         | `show`              | Show details of a specific task                  | Admin, Team Leader, Team Member         |
| PUT     | `/{id}`                         | `update`            | Update a specific task                           | Admin, Team Leader (if created task)    |
| DELETE  | `/{id}`                         | `delete`            | Delete a specific task                           | Admin, Team Leader (if created task)    |
| PATCH   | `/{id}/complete`                | `markAsComplete`    | Mark a task as completed                         | Admin, Team Leader, Team Member         |
| PATCH   | `/{id}/assign`                  | `assign`            | Assign a user to a task                          | Admin, Team Leader                      |
| PATCH   | `/{id}/unassign`                | `unassign`          | Unassign a user from a task                      | Admin, Team Leader                      |
| GET     | `/{id}/subtasks`                | `findByTask`        | Get all subtasks for a specific task             | Admin, Team Leader, Team Member         |
| POST    | `/{id}/subtasks`                | `storeSubtask`      | Create a new subtask for a task                  | Admin, Team Leader                      |

---

### Authorization Rules (RBAC)

- **Admin**
    - Can perform **all** actions on any task.
    - Can assign and unassign tasks to/from users.

- **Team Leader**
    - Can **create**, **update**, **delete**, **assign**, and **unassign** tasks for projects they are leading.
    - Can mark tasks as **complete**.
    - Can assign or unassign tasks from users in their projects.

- **Team Member**
    - Can **view** tasks that belong to projects they are assigned to.
    - Can **update** only the `status` field of tasks in projects they are assigned to.
    - Can mark tasks as **complete** (if they have sufficient permissions).

---

### Request Validations

- **Create Task (`POST /api/tasks`)**
    - Fields are validated via `TaskStoreRequest`.

- **Update Task (`PUT /api/tasks/{id}`)**
    - Fields are validated via `TaskUpdateRequest`.
    - **Team Member** can only update the `status` field.

- **Assign Task (`PATCH /api/tasks/{id}/assign`)**
    - Request Body:
      ```json
      {
        "user_id": "required, must exist in users table"
      }
      ```

- **Unassign Task (`PATCH /api/tasks/{id}/unassign`)**
    - No request body is required for unassigning a task.

- **Subtask Operations**
    - **Find subtasks** for a task (`GET /api/tasks/{id}/subtasks`) and **store a subtask** for a task (`POST /api/tasks/{id}/subtasks`).

---

### Responses

- Success responses return:
    ```json
    {
      "message": "Success message",
      "status": 200
    }
    ```
- Error responses (e.g., unauthorized access, validation failure) return appropriate HTTP status codes like 400, 401, 403, or 404.



## Subtask Management API

All routes are prefixed with `/api/subtasks` (except creation, which is nested under tasks).

### Routes

| Method | Endpoint                         | Controller Method   | Description                                   | Authorized Roles                           |
|:------:|:---------------------------------|:--------------------|:----------------------------------------------|:-------------------------------------------|
| GET    | `/subtasks/{id}`                 | `show`              | Get details of a specific subtask             | Admin, Team Leader, Assigned Team Member   |
| GET    | `/subtasks/task/{taskId}`        | `findByTask`        | List all subtasks under a specific task       | Admin, Team Leader, Assigned Team Member   |
| POST   | `/tasks/{id}/subtasks`           | `storeSubtask`      | Create a new subtask under a task             | Admin, Team Leader                         |
| PUT    | `/subtasks/{id}`                 | `update`            | Update a specific subtask                     | Admin, Team Leader, Assigned Member (status only) |
| DELETE | `/subtasks/{id}`                 | `destroy`           | Delete a specific subtask                     | Admin, Team Leader                         |
| PATCH  | `/subtasks/{id}/complete`        | `markAsComplete`    | Mark a subtask as completed                   | Admin, Team Leader, Assigned Team Member   |
| PATCH  | `/subtasks/{id}/assign`          | `assign`            | Assign a user to a subtask                    | Admin, Team Leader                         |
| PATCH  | `/subtasks/{id}/unassign`        | `unassign`          | Unassign a user from a subtask                | Admin, Team Leader                         |

---

### Authorization Rules (RBAC)

- **Admin**  
  Full access to all subtask operations.

- **Team Leader**  
  Can create, update, delete, assign, unassign, and mark complete any subtask under tasks in their projects.

- **Team Member**  
  Can view subtasks assigned to them and update only the `status` (mark as complete).

---

### Request Validations

- **Create Subtask (`POST /tasks/{id}/subtasks`)**  
  Payload validated via `SubTaskStoreRequest`.

- **Update Subtask (`PUT /subtasks/{id}`)**  
  Payload validated via `SubTaskUpdateRequest`.  
  Team Members may only update the `status` field.

- **Assign/Unassign (`PATCH /subtasks/{id}/assign` & `/subtasks/{id}/unassign`)**  
  Body:
  ```json
  {
    "user_id": 123
  }



## Report Management API

This section handles generating and exporting project reports.

### Routes

| Method | Endpoint                                | Controller Method     | Description                                       | Authorized Roles      |
|:------:|:---------------------------------------|:----------------------|:--------------------------------------------------|:----------------------|
| GET    | `/reports/projects/`                   | `generateProjectReport` | Generate a project report based on filters       | Admin, Team Leader    |
| GET    | `/reports/projects/export`             | `exportProjectReport`  | Export the project report as an Excel file       | Admin, Team Leader    |

---

### Authorization Rules (RBAC)

- **Admin**  
  Full access to all report generation and export operations.

- **Team Leader**  
  Can generate reports for projects within their scope but cannot export reports.

---

### Request Validations

For both routes (`generateProjectReport` and `exportProjectReport`), the following filters can be provided:

- **start_date** (optional): Filter projects based on a start date (must be before or equal to `end_date`).
- **end_date** (optional): Filter projects based on an end date (must be after or equal to `start_date`).
- **project_id** (optional): Filter by a specific project ID.

---

### Example Request

#### Generate Project Report
```http
GET /api/reports/projects?start_date=2025-01-01&end_date=2025-04-01&project_id=123
```

### Report Export
The exportProjectReport method uses the **_Maatwebsite Excel package_** to export the project report in Excel format. The package is configured to download the report as an .xlsx file.

**Package Used
Maatwebsite Excel**: A package for importing and exporting Excel files in Laravel. It provides a convenient API to handle Excel downloads and exports in various formats.
