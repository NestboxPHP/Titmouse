# Titmouse

A user management interface that can register, login, and logout users while adhering to standard security practices for
password handling.

## Usage

Titmouse requries Nestbox to function

### Settings

| Setting    | Description                                                              | Default     |
|------------|--------------------------------------------------------------------------|-------------|
| usersTable | Defines the table name in which user data is stored.                     | `users`     |
| userColumn | Defines the column name which identifies the unique ID for each user.    | `username`  |
| nameLength | Defines the max number of characters in a user name.                     | `64`        |
| mailColumn | Defines the column name which stores the user email.                     | `email`     |
| hashColumn | Defines the column name which stores the hashword of a user.             | `hashword`  |
| sessionKey | Defines the `$_SESSION` key which is used to store and access user data. | `user_data` |

## Methods

### Register User

Registers a user and associates a new hash for logging in.

```php
$tm->register_user(array $userData, string $password): bool
```

- `$userData`: an array containing key => value pairs where the key is any given column name in the user table and the
  value is the data to be stored in that column.
- `$password`: the plain-text password the user will use to log in with.

### Select User

Selects a user from the users table.

```php
$tm->select_user(string $user): array
```

- `$user`: the string value of the user id being selected

### Login User

Logs in a user, and will load the user data into the `$_SESSION` variable. For additional security, it will
automatically rehash a given password if a newer and/or better hashing algorithm has been identified and is available.

```php
$tm->login_user(string $user, string $password, bool $loadToSession = true): array
```

- `$user`: string value of the username to be logged in
- `$password`: plain-text string value of the user's password
- `$loadToSession`: boolean determining if the user data will be loaded into the `$_SESSION` variable using
  the `sessionKey` setting's value

### Update User

Updates a user in the users data table with the given data.

```php
$tm->update_user(string $user, array $userData): int
```

- `$user`: string value of the username to be updated in the user table
- `$userData`: an array of key => value pairs where the key is the name of the column where the value will be stored

### Load User Session

Load an array of data into the `$_SESSION` variable within the `sessionKey` key.

```php
$tm->load_user_session(array $userData): void
```

- `$user`: array of whatever key => value pairs to be stored in the `$_SESSION` variable under the `sessionKey` key

### Logout User

Clears out all `$_SESSION` user data under the `sessionKey` key while leaving any other remaining `$_SESSION` data
intact

```php
$tm->logout_user(): void
```

### Verify Email

*work in progress*

```php
$tm->verify_email(): bool
```

### Change Password

Changes the current password of `$user` to a new hash of `$newPassword`. Returns `true` if successful, otherwise false.

```php
$tm->change_password(string $user, string $newPassword): bool
```

- `$user`: string value of the user for which the password is being updated
- `$newPassword`: plain-text string value of the new password to be used