# User & Authentication API

## Authentication

### Login
**POST** `/api/auth/login`

**Parameters:**
- `phone` (required): User's phone number.
- `password` (required): User's password.

**Response (200 OK):**
```json
{
    "message": "تم تسجيل الدخول بنجاح",
    "token": "1|abcdef123456...",
    "user": {
        "id": 1,
        "name": "Admin",
        "phone": "07700000000",
        "role": "manager",
        "status": "active"
    }
}
```

### Logout
**POST** `/api/auth/logout`
Requires Token (Bearer Auth).

**Response (200 OK):**
```json
{
    "message": "تم تسجيل الخروج بنجاح"
}
```

### Get Current User (Me)
**GET** `/api/auth/me`
Requires Token (Bearer Auth).

**Response (200 OK):** Returns current user object.

---

## Users Management
All routes require authentication and appropriate permissions.

### List Users
**GET** `/api/users`

**Response (200 OK):** Array of user objects.

### Create User
**POST** `/api/users`

**Parameters:**
- `name` (required)
- `phone` (required, unique)
- `password` (required, min: 6)
- `role` (required: manager, supervisor, employee)
- `photo` (optional)

### Update User
**PUT/PATCH** `/api/users/{id}`

Parameters same as Create (optional).

### Toggle Status
**PATCH** `/api/users/{id}/status`

Switches user status between `active` and `disabled`.

### Change Password
**PATCH** `/api/users/{id}/password`

**Parameters:**
- `password` (required, min: 6, confirmed)
- `password_confirmation` (required)
