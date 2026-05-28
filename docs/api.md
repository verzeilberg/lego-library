# Lego Library API Documentation

Base URL: `/api`

All endpoints require a JWT `Bearer` token in the `Authorization` header unless marked as **Public**.

---

## Authentication

### POST /api/login
Authenticate and receive JWT + refresh token.

**Request:**
```json
{ "email": "user@example.com", "password": "secret" }
```

**Response (200):**
```json
{ "token": "eyJ...", "refresh_token": "abc..." }
```

**Errors:** 401 Invalid credentials, 403 Account not activated

---

### POST /api/token/refresh
Exchange a refresh token for a new JWT.

**Request:**
```json
{ "refreshToken": "abc..." }
```

**Response (200):**
```json
{ "token": "eyJ...", "refreshToken": "xyz...", "oldRefreshToken": "abc..." }
```

**Errors:** 400 No token, 401 Invalid/expired

---

### POST /api/logout
Revoke one or all refresh tokens.

**Request:**
```json
{ "refreshToken": "abc...", "allDevices": false }
```

Set `allDevices: true` to log out everywhere (requires valid JWT). Omit or set `false` to revoke only the given refresh token.

**Response (200):** `{ "success": true }`

---

## Account & User Management

### POST /api/public/user/register (Public)
Register a new user.

**Request:**
```json
{ "firstName": "John", "lastName": "Doe", "email": "john@example.com", "plainPassword": "password123" }
```

**Response (201):** Created user data

---

### POST /api/public/user/activate (Public)
Activate account with token + code (sent via email).

**Request:**
```json
{ "token": "...", "code": 1234 }
```

**Response:** 204 No Content

---

### POST /api/public/user/forgot-password (Public)
Request a password reset email.

**Request:**
```json
{ "email": "john@example.com" }
```

---

### POST /api/public/user/check-token-code (Public)
Verify the token + code from the forgot-password email.

**Request:**
```json
{ "token": "...", "code": 1234 }
```

---

### POST /api/public/user/reset-password (Public)
Reset password using verified token + code.

**Request:**
```json
{ "token": "...", "code": "1234", "plainPassword": "newpass123", "verifyPlainPassword": "newpass123" }
```

**Response (200):** `{ "message": "Password has been reset" }`

---

### PATCH /api/user/patch
Update the authenticated user's profile fields. Requires JWT.

**Request:** JSON body with any of:
```json
{ "firstName": "New", "lastName": "Name", "userName": "johnny", "plainPassword": "newpass123" }
```

**Response (200):** `{ "message": "User updated successfully" }`

---

### GET /api/user-data/
Get the authenticated user's full profile data. Requires JWT.

**Response (200):** UserData profile object with `firstName`, `lastName`, `userName`, `bio`, `geslacht`, `contentUrl`, etc.

---

### POST /api/user-data/edit
Update user profile with optional profile picture. `multipart/form-data`.

**Request fields:** `userName`, `firstName`, `lastName`, `bio`, `geslacht` (M|V|X), `file` (image), `deleteImage` (`1` to remove)

**Response (200):** Updated UserData profile

---

### DELETE /api/user-data/delete
Delete the authenticated user's account. Requires JWT.

**Response (200):** `{ "result": "User deleted successfully" }`

---

### GET /api/users
List all users. Admin access.

### GET /api/users/{id}
Get a single user.

### POST /api/users
Create a new user.

### PUT /api/users/{id}
Update a user.

### DELETE /api/users/{id}
Delete a user.

---

## User Profile & Social

### GET /api/public/user/{id} (Public)
Get a public user profile by UserData ID.

**Response (200):**
```json
{ "id": 1, "userName": "johnny", "firstName": "John", "lastName": "Doe", "bio": "...", "geslacht": "M", "profilePicture": "/media/..." }
```

---

### GET /api/public/user/{id}/bords (Public)
Get public boards for a user. Supports pagination.

**Query params:** `limit` (default 10), `page` (default 1)

---

### GET /api/friends
List the authenticated user's friends and pending friend requests.

**Response (200):**
```json
{ "friends": [ { "friendshipId": 1, "id": 2, "userName": "...", "firstName": "...", "lastName": "...", "geslacht": "M", "profilePicture": "..." } ], "pendingRequests": [...] }
```

---

### GET /api/friends/status/{userId}
Get friendship status with another user.

**Response:** `{ "status": "none"|"accepted"|"pending_sent"|"pending_received", "friendshipId": null|int }`

---

### POST /api/friends/request/{userId}
Send a friend request.

**Response (200):** `{ "message": "Friend request sent", "requestId": 1 }`

---

### POST /api/friends/accept/{requestId}
Accept a pending friend request.

---

### DELETE /api/friends/{friendshipId}
Remove a friend or cancel a request.

---

### POST /api/push-token
Save a push notification token for the authenticated user.

**Request:**
```json
{ "pushToken": "expo-..." }
```

---

### POST /api/notification-preferences
Update notification preferences.

**Request:**
```json
{ "friendRequestPush": true, "friendRequestEmail": false, "friendAcceptedPush": true, "friendAcceptedEmail": true, "newBoardPush": true, "newBoardEmail": true, "newSetPush": true, "newSetEmail": true }
```

---

## Set Lists (Boards)

### POST /api/set-list
Create or update a set list (board). `multipart/form-data`.

**Fields:** `id` (omit for create), `parentId`, `title` (required), `description`, `publicPrivate` (bool), `file` (image)

---

### GET /api/set-lists-for-user
Get top-level set lists for the authenticated user.

---

### GET /api/set-lists-public
Get public set lists from other users (paginated).

**Query params:** `limit` (default 10), `page` (default 1)

---

### GET /api/set-lists-public-search
Search public set lists by title.

**Query params:** `q` (search query), `limit` (default 10), `page` (default 1)

---

### GET /api/set-list/{id}
Get a single set list by ID.

---

### GET /api/set-lists/{id}
Get children lists and sets within a set list. Optional search query `q`.

**Query params:** `q` (search sets/children by query)

---

### DELETE /api/set-list/delete/{id}
Delete a set list (owner or admin only).

---

### DELETE /api/set-images/delete/{id}
Delete an image from a set list.

---

## Lego Sets

### POST /api/lego/sets/create
Add a Lego set to a set list by set number. Creates the set via Rebrickable if not in database.

**Request:**
```json
{ "id": "<set-list-uuid>", "legoNmbr": "12345-1", "addLegoImages": true, "addLegoParts": true, "addLegoMinifigs": true }
```

---

### GET /api/lego/set-lists/{listId}/sets/{number}
Get a set's full details within a set list (includes parts, minifigs, images, ratings).

---

### POST /api/lego/set-lists/{listId}/sets/{number}/add-images
Upload images to a set within a set list. `multipart/form-data`.

**Fields:** `files[]` (one or more image files)

---

### DELETE /api/lego/list/{bordid}/set/{setnr}
Remove a set from a set list.

---

### POST /api/lego/sets/rate-set
Rate a set.

**Request:**
```json
{ "setId": "12345-1", "rating": 4.5 }
```

---

### POST /api/lego/set/part/defect/create
Report defective parts (missing, damaged, discoloured) for a set.

**Request:**
```json
{ "setNumber": "12345-1", "bordId": "<set-list-uuid>", "partId": "part123", "colorId": 1, "missingQuantity": 0, "damagedQuantity": 1, "discolouredQuantity": 0 }
```

---

## API Platform Built-in

- **Swagger UI:** `GET /api/docs`
- **OpenAPI JSON:** `GET /api/docs.json`
- **GraphiQL:** `GET /api/docs/graphiql`

---

## Non-API Routes

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | Symfony homepage |
| GET | `/api/docs/api` | API reference (this page)
