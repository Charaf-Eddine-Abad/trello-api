# API Documentation

Complete API reference for the Trello Clone API.

## Base URL

```
http://localhost:8000/api
```

## Authentication

All endpoints except `/register` and `/login` require authentication using Bearer tokens.

### Headers

```
Authorization: Bearer {your-token}
Content-Type: application/json
Accept: application/json
```

---

## Authentication Endpoints

### Register User

**POST** `/register`

Create a new user account.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password",
  "avatar": "https://example.com/avatar.jpg" // optional
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "member",
      "avatar": null
    },
    "token": "1|abc123..."
  }
}
```

---

### Login

**POST** `/login`

Authenticate and receive access token.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {...},
    "token": "1|abc123..."
  }
}
```

---

### Logout

**POST** `/logout`

Revoke current access token.

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## Project Endpoints

### List Projects

**GET** `/projects`

Get all projects the authenticated user is assigned to.

**Response (200):**
```json
{
  "success": true,
  "message": "Projects retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Project Alpha",
      "description": "Description here",
      "created_by": 1,
      "users": [...],
      "created_at": "2025-12-07T12:00:00Z"
    }
  ]
}
```

---

### Create Project

**POST** `/projects`

Create a new project. Creator becomes project owner.

**Request Body:**
```json
{
  "name": "New Project",
  "description": "Project description",
  "users": [
    {
      "user_id": 2,
      "project_role": "manager"
    },
    {
      "user_id": 3,
      "project_role": "member"
    }
  ]
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Project created successfully",
  "data": {...}
}
```

---

### Update Project

**PUT** `/projects/{id}`

Update project details. Only owner/manager can update.

**Request Body:**
```json
{
  "name": "Updated Name",
  "description": "Updated description",
  "users": [...]
}
```

---

### Delete Project

**DELETE** `/projects/{id}`

Delete a project. Only project owner can delete.

**Response (200):**
```json
{
  "success": true,
  "message": "Project deleted successfully"
}
```

---

## Task Endpoints

### List Tasks

**GET** `/tasks`

Get all tasks from user's projects.

**Query Parameters:**
- `project_id` (optional) - Filter by project
- `status` (optional) - Filter by status (todo, in_progress, done)
- `priority` (optional) - Filter by priority (low, medium, high)

**Response (200):**
```json
{
  "success": true,
  "message": "Tasks retrieved successfully",
  "data": [
    {
      "id": 1,
      "project_id": 1,
      "title": "Implement feature X",
      "description": "Details...",
      "status": "todo",
      "priority": "high",
      "due_date": "2025-12-15",
      "users": [...],
      "comments_count": 3
    }
  ]
}
```

---

### Create Task

**POST** `/tasks`

Create a new task. Only owner/manager can create.

**Request Body:**
```json
{
  "project_id": 1,
  "title": "New Task",
  "description": "Task description",
  "status": "todo",
  "priority": "medium",
  "due_date": "2025-12-20",
  "assigned_users": [2, 3, 4]
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {...}
}
```

---

### Get Task

**GET** `/tasks/{id}`

Get task details with comments and assignees.

---

### Update Task

**PUT** `/tasks/{id}`

Update task details. Owner/manager or assigned users can update.

---

### Delete Task

**DELETE** `/tasks/{id}`

Delete a task. Only project owner can delete.

---

## Specialized Task Updates

### Update Task Status

**PATCH** `/tasks/{id}/status`

Update only the task status. Owner, manager, or assigned users can update.

**Request Body:**
```json
{
  "status": "in_progress"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Task status updated successfully",
  "data": {...}
}
```

---

### Update Task Priority

**PATCH** `/tasks/{id}/priority`

Update only the task priority. Only owner/manager can update.

**Request Body:**
```json
{
  "priority": "high"
}
```

---

### Update Task Assignees

**PATCH** `/tasks/{id}/assignees`

Reassign users to task. Only owner/manager can reassign.

**Request Body:**
```json
{
  "assigned_users": [2, 3, 4, 5]
}
```

---

## Comment Endpoints

### List Comments

**GET** `/comments?task_id={id}`

Get all comments for a specific task.

**Query Parameters:**
- `task_id` (required) - Task ID

**Response (200):**
```json
{
  "success": true,
  "message": "Comments retrieved successfully",
  "data": [
    {
      "id": 1,
      "task_id": 1,
      "user_id": 2,
      "message": "This looks great!",
      "user": {
        "id": 2,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "created_at": "2025-12-07T14:00:00Z"
    }
  ]
}
```

---

### Create Comment

**POST** `/comments`

Add a comment to a task. All project members can comment.

**Request Body:**
```json
{
  "task_id": 1,
  "message": "Great work on this!"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Comment added successfully",
  "data": {...}
}
```

---

### Delete Comment

**DELETE** `/comments/{id}`

Delete a comment. Only comment author or project owner can delete.

---

## Notification Endpoints

### List Notifications

**GET** `/notifications`

Get all notifications for authenticated user.

**Response (200):**
```json
{
  "success": true,
  "message": "Notifications retrieved successfully",
  "data": {
    "notifications": [
      {
        "id": "uuid",
        "type": "App\\Notifications\\TaskAssignedNotification",
        "data": {
          "type": "task_assigned",
          "task_id": 1,
          "task_title": "Implement feature X",
          "message": "You have been assigned to task: Implement feature X"
        },
        "read_at": null,
        "created_at": "2025-12-07T14:00:00Z"
      }
    ],
    "unread_count": 5
  }
}
```

---

### Mark Notification as Read

**PATCH** `/notifications/{id}/read`

Mark a specific notification as read.

**Response (200):**
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

---

### Mark All as Read

**PATCH** `/notifications/read-all`

Mark all user's notifications as read.

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "You are not authorized to perform this action"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## Notification Types

### task_assigned
User is assigned to a task.

### task_status_changed
Task status changes (todo → in_progress → done).

### task_priority_changed
Task priority changes (low → medium → high).

---

## Real-Time Notifications

Subscribe to user's private channel:
```javascript
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        console.log(notification);
    });
```

---

## Rate Limiting

API requests are rate-limited to prevent abuse. Default limits:
- 60 requests per minute for authenticated users
- 10 requests per minute for unauthenticated users

---

## Interactive Documentation

For interactive API testing, visit:
```
http://localhost:8000/api/documentation
```

The Swagger UI provides a complete interactive interface to test all endpoints.
