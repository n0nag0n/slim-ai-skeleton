<?php

namespace App\Test\Model;

use App\Test\TestCase;
use Doctrine\DBAL\Connection;

class PostTest extends TestCase
{
    protected Connection $conn;

    protected function setUp(): void
    {
        $app = $this->createApp();
        $this->conn = $app->getContainer()->get(Connection::class);
        $this->runMigrations();
    }

    public function testCreateAndFindAll(): void
    {
        $this->conn->insert('posts', ['title' => 'Test Title', 'body' => 'Test body content']);
        $results = $this->conn->fetchAllAssociative('SELECT * FROM posts');
        $this->assertCount(1, $results);
        $this->assertSame('Test Title', $results[0]['title']);
    }

    public function testUpdate(): void
    {
        $this->conn->insert('posts', ['title' => 'Old', 'body' => 'Old body']);
        $id = (int) $this->conn->lastInsertId();

        $this->conn->update('posts', ['title' => 'Updated'], ['id' => $id]);
        $row = $this->conn->fetchAssociative('SELECT * FROM posts WHERE id = ?', [$id]);
        $this->assertSame('Updated', $row['title']);
    }

    public function testDelete(): void
    {
        $this->conn->insert('posts', ['title' => 'Delete me', 'body' => 'Bye']);
        $id = (int) $this->conn->lastInsertId();

        $this->conn->delete('posts', ['id' => $id]);
        $row = $this->conn->fetchAssociative('SELECT * FROM posts WHERE id = ?', [$id]);
        $this->assertFalse($row);
    }
}
