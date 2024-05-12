<?php

declare(strict_types=1);

namespace NestboxPHP\Nestbox\Titmouse;

use NestboxPHP\Nestbox\Nestbox;

class Titmouse extends Nestbox
{
    final protected const string PACKAGE_NAME = 'titmouse';
    public string $titmouseUsersTable = 'titmouse_users';
    public string $titmouseUserColumn = 'username';
    public int $titmouseNameLength = 64;
    public string $titmouseMailColumn = 'email';
    public string $titmouseHashColumn = 'hashword';
    public string $titmouseSessionKey = 'user_data';

    // create user table
    public function create_class_table_titmouse_users(): bool
    {
        // check if user table exists
        if (!$this->valid_schema($this->titmouseUsersTable)) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->titmouseUsersTable}` (
                    `{$this->titmouseUserColumn}` VARCHAR ( $this->titmouseNameLength ) NOT NULL PRIMARY KEY,
                    `{$this->titmouseMailColumn}` VARCHAR( 320 ) NOT NULL UNIQUE,
                    `{$this->titmouseHashColumn}` VARCHAR( 128 ) NOT NULL ,
                    `last_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
                    UNIQUE ( `{$this->titmouseMailColumn}` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";
            return $this->query_execute($sql);
        }

        // add columns if missing from an existing table
        // TODO: add schema check for column type and size and adjust as necessary
        $this->load_table_schema();
        if (!$this->valid_schema($this->titmouseUsersTable, $this->titmouseUserColumn)) {
            $sql = "ALTER TABLE `{$this->titmouseUsersTable}` ADD COLUMN `{$this->titmouseUserColumn}` VARCHAR ( 64 ) NOT NULL";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column '{$this->titmouseUserColumn}'");
            };
        }

        if (!$this->valid_schema($this->titmouseUsersTable, $this->titmouseMailColumn)) {
            $sql = "ALTER TABLE `{$this->titmouseUsersTable}` ADD COLUMN `{$this->titmouseMailColumn}` VARCHAR ( 320 ) NOT NULL;
                    ALTER TABLE `{$this->titmouseUsersTable}` ADD CONSTRAINT `unique_email` UNIQUE ( `{$this->titmouseMailColumn}` );";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column '{$this->titmouseMailColumn}'");
            };
        }

        if (!$this->valid_schema($this->titmouseUsersTable, $this->titmouseHashColumn)) {
            $sql = "ALTER TABLE `{$this->titmouseUsersTable}` ADD COLUMN `{$this->titmouseHashColumn}` VARCHAR ( 128 ) NOT NULL";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column '{$this->titmouseHashColumn}'");
            };
        }

        if (!$this->valid_schema($this->titmouseUsersTable, "last_login")) {
            $sql = "ALTER TABLE `{$this->titmouseUsersTable}` ADD COLUMN `last_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column 'last_login'");
            };
        }

        if (!$this->valid_schema($this->titmouseUsersTable, "created")) {
            $sql = "ALTER TABLE `{$this->titmouseUsersTable}` ADD COLUMN `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column 'created'");
            };
        }

        return true;
    }


    public function register_user(array $userData, string $password): bool
    {
        // validate user data columns
        $params = [];
        foreach ($userData as $col => $val) {
            if (!$this->valid_schema($this->titmouseUsersTable, $col)) continue;
            $params[$col] = $val;
        }

        // make sure input vars are not too long
        if ($this->titmouseNameLength < strlen($params[$this->titmouseUserColumn])) {
            throw new TitmouseException("Username too long.");
        }

        if (320 < strlen($params[$this->titmouseMailColumn])) {
            // thank you RFC 5321 & RFC 5322
            throw new TitmouseException("Email too long.");
        }

        if (0 < (trim($password))) {
            throw new TitmouseException("Empty password provided.");
        }

        // securely hash the password
        $params[$this->titmouseHashColumn] = password_hash($password, PASSWORD_DEFAULT);

        // insert new user
        if (1 === $this->insert($this->titmouseUsersTable, $params)) {
            return true;
        } else {
            return false;
        }
    }

    public function select_user(string $user): array
    {
        $results = $this->select($this->titmouseUsersTable, [$this->titmouseUserColumn => $user]);

        // invalid user
        if (!$results) {
            return [];
        }

        // multiple users (this should never happen, but might on an existing table without a primary key)
        if (1 !== count($results)) {
            throw new TitmouseException("More than one user has the same identifier.");
        }

        return $results[0];
    }

    public function login_user(string $user, string $password, bool $loadToSession = true): array
    {
        // select user
        $user = $this->select_user($user);

        // login failed
        if (!password_verify($password, $user[$this->titmouseHashColumn])) {
            throw new TitmouseException("Invalid username or password.");
        }

        // rehash password if newer algorithm is available
        if (password_needs_rehash($user[$this->titmouseHashColumn], PASSWORD_DEFAULT)) {
            $this->change_password($user[$this->titmouseUserColumn], $password);
        }

        if (true === $loadToSession) {
            $this->load_user_session($user);
        }

        return $user;
    }

    public function update_user(string $user, array $userData): int
    {
        $where = [$this->titmouseUserColumn => $user];

        return $this->update(table: $this->titmouseUsersTable, updates: $userData, where: $where);
    }

    public function load_user_session(array $userData): void
    {
        foreach ($userData as $col => $val) {
            $_SESSION[$this->titmouseSessionKey][$col] = $val;
        }
    }

    public function verify_email(): bool
    {
        return false;
    }

    public function change_password(string $user, string $newPassword): bool
    {
        $newHashword = password_hash($newPassword, PASSWORD_DEFAULT);

        $userData = [
            $this->titmouseUserColumn => $user,
            $this->titmouseHashColumn => $newHashword
        ];

        if (1 != $this->update_user($userData[$this->titmouseUserColumn], $userData)) {
            throw new TitmouseException("Failed to update password hash.");
        }

        return true;
    }

    public function logout_user(): void
    {
        // clear out those session variables
        unset($_SESSION[$this->titmouseSessionKey]);
    }
}
