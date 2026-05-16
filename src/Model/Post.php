<?php

namespace App\Model;

use Doctrine\DBAL\Connection;

/**
 * Sample model demonstrating the DBAL query pattern.
 * Copy this class and rename for new models.
 */
class Post
{
    public function __construct(private Connection $conn)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function findAll(): array
    {
        return $this->conn->fetchAllAssociative('SELECT * FROM posts ORDER BY created_at DESC');
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $data = $this->conn->fetchAssociative('SELECT * FROM posts WHERE id = ?', [$id]);
        return $data ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $this->conn->insert('posts', $data);
        return (int) $this->conn->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): int
    {
        return (int) $this->conn->update('posts', $data, ['id' => $id]);
    }

    public function delete(int $id): int
    {
        return (int) $this->conn->delete('posts', ['id' => $id]);
    }
}
