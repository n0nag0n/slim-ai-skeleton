<?php

namespace App\Model;

use Doctrine\DBAL\Connection;

class Post
{
    public function __construct(private Connection $conn) {}

    public function findAll(): array
    {
        return $this->conn->fetchAllAssociative('SELECT * FROM posts ORDER BY created_at DESC');
    }

    public function findById(int $id): ?array
    {
        $data = $this->conn->fetchAssociative('SELECT * FROM posts WHERE id = ?', [$id]);
        return $data ?: null;
    }

    public function create(array $data): int
    {
        $this->conn->insert('posts', $data);
        return (int) $this->conn->lastInsertId();
    }

    public function delete(int $id): int
    {
        return $this->conn->delete('posts', ['id' => $id]);
    }
}
