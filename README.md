# Trello API Clone

A comprehensive RESTful API built with Laravel 11 for project and task management, featuring real-time notifications, role-based access control, and complete Swagger documentation.

## 🚀 Features

- **Authentication** - Sanctum token-based API authentication
- **Projects** - Full CRUD with role-based permissions (Owner, Manager, Member)
- **Tasks** - Complete task management with many-to-many user assignments
- **Specialized Task Updates** - Dedicated endpoints for status, priority, and assignee changes
- **Comments** - Add and manage task comments
- **Real-Time Notifications** - WebSocket-based notifications for task assignments and changes
- **Swagger Documentation** - Interactive API documentation at `/api/documentation`
- **Role-Based Authorization** - Granular permissions for different user roles

## 📋 Tech Stack

- **Framework:** Laravel 11
- **Database:** MySQL
- **Authentication:** Laravel Sanctum
- **API Documentation:** L5-Swagger (OpenAPI 3.0)
- **Real-Time:** Laravel Broadcasting (Reverb/Pusher)
- **PHP Version:** 8.2+

## 📦 Installation

### Prerequisites

- PHP >= 8.2
- Composer
- MySQL
- Node.js & NPM (for broadcasting)

### Setup Steps

1. **Clone the repository**
```bash
git clone https://github.com/Charaf-Eddine-Abad/trello-api.git
cd trello-api
```

2. **Install dependencies**
```bash
composer install
```

3. **Environment configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure database**

Update `.env` with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trello
DB_USERNAME=root
DB_PASSWORD=your_password
```

5. **Run migrations and seeders**
```bash
php artisan migrate:fresh --seed
```

This will create:
- Admin user: `admin@example.com` / `Admin123`
- Sample users, projects, and tasks

6. **Generate Swagger documentation**
```bash
php artisan l5-swagger:generate
```

7. **Start the development server**
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## 📚 API Documentation

Access the interactive Swagger UI at:
```
http://localhost:8000/api/documentation
```

For detailed API documentation, see [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

## 🔐 Authentication

### Register
```bash
POST /api/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

### Login
```bash
POST /api/login
{
  "email": "john@example.com",
  "password": "password"
}
```

Response includes authentication token:
```json
{
  "success": true,
  "data": {
    "user": {...},
    "token": "1|abc123..."
  }
}
```

### Using the Token

Include the token in all authenticated requests:
```bash
Authorization: Bearer 1|abc123...
```

## 🎯 Key API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/register` | Register new user |
| POST | `/api/login` | Login user |
| POST | `/api/logout` | Logout user |
| GET | `/api/projects` | List projects |
| POST | `/api/projects` | Create project |
| GET | `/api/tasks` | List tasks |
| POST | `/api/tasks` | Create task |
| PATCH | `/api/tasks/{id}/status` | Update task status |
| PATCH | `/api/tasks/{id}/priority` | Update task priority |
| PATCH | `/api/tasks/{id}/assignees` | Update task assignees |
| GET | `/api/comments?task_id={id}` | List task comments |
| GET | `/api/notifications` | Get user notifications |

## 🔔 Real-Time Notifications

The API supports real-time notifications via WebSocket broadcasting.

### Setup Broadcasting

1. **Install Laravel Reverb** (recommended)
```bash
composer require laravel/reverb
php artisan reverb:install
php artisan reverb:start
```

2. **Or use Pusher**
- Sign up at [pusher.com](https://pusher.com)
- Add credentials to `.env`

See [broadcasting_setup.md](broadcasting_setup.md) for detailed instructions.

## 🏗️ Project Structure

```
trello-api/
├── app/
│   ├── Enums/              # Enumerations (UserRole, ProjectRole, TaskStatus, etc.)
│   ├── Http/
│   │   ├── Controllers/    # API Controllers
│   │   └── Requests/       # Form Request Validators
│   ├── Models/             # Eloquent Models
│   └── Notifications/      # Notification Classes
├── database/
│   ├── migrations/         # Database Migrations
│   └── seeders/            # Database Seeders
├── routes/
│   └── api.php             # API Routes
└── storage/
    └── api-docs/           # Generated Swagger Documentation
```

## 🎭 User Roles

### Global Roles
- **Admin** - Full system access
- **Member** - Standard user

### Project Roles
- **Owner** - Full project control (creator)
- **Manager** - Can manage project and tasks
- **Member** - Can view and work on assigned tasks

## 📊 Permission Matrix

| Action | Owner | Manager | Member |
|--------|-------|---------|--------|
| Create Project | ✅ | ✅ | ✅ |
| Edit Project | ✅ | ✅ | ❌ |
| Delete Project | ✅ | ❌ | ❌ |
| Create Task | ✅ | ✅ | ❌ |
| Update Task Status | ✅ | ✅ | ✅ (if assigned) |
| Update Task Priority | ✅ | ✅ | ❌ |
| Assign Users to Task | ✅ | ✅ | ❌ |
| Delete Task | ✅ | ❌ | ❌ |

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

## 🔧 Configuration

### CORS

CORS is configured in `config/cors.php`. For development, all origins are allowed. Update for production:

```php
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
```

### Rate Limiting

API rate limiting can be configured in `app/Http/Kernel.php`

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👤 Author

**Charaf Eddine Abad**

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

## ⭐ Show your support

Give a ⭐️ if this project helped you!
