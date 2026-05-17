<?php

declare(strict_types=1);

namespace App\Model;

use Doctrine\DBAL\Connection;

/**
 * Example model demonstrating the DBAL query pattern.
 *
 * Table: posts
 * Columns:
 *   - id:         int (auto-increment primary key)
 *   - title:      string(255), NOT NULL
 *   - body:       text, NOT NULL
 *   - created_at: datetime, default CURRENT_TIMESTAMP
 *   - updated_at: datetime, default CURRENT_TIMESTAMP
 *
 * Copy this class and rename for new models. Update the table name
 * and column docs to match your migration.
 */
class ExampleModel
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
