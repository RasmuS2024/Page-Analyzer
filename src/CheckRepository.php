<?php

namespace WPA;

class CheckRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getEntities(int $id): array
    {
        $sql = "SELECT * FROM url_checks ORDER BY id DESC";
        return $this->fetchChecks($sql, [$id]);
    }

    public function getAllChecksForUrl(int $id): array
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY id DESC";
        return $this->fetchChecks($sql, [$id]);
    }

    public function create(Check $check): void
    {
        $sql = "INSERT INTO url_checks (
            url_id, status_code, h1, title, description, created_at)
        VALUES (
            :url_id, :statusCode, :h1, :title, :description, :createdDT
        )";
        $stmt = $this->conn->prepare($sql);
        $urlId = $check->getUrlId();
        $statusCode = $check->getStatusCode();
        $h1 = $check->getH1();
        var_dump($check);
        $title = $check->getTitle();
        $description = $check->getDescription();
        $createdDT = $check->getCreatedDT();
        $stmt->bindParam(':url_id', $urlId);
        $stmt->bindParam(':statusCode', $statusCode);
        $stmt->bindParam(':h1', $h1);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':createdDT', $createdDT);
        $stmt->execute();
        $id = (int) $this->conn->lastInsertId();
        $check->setId($id);
    }

    private function fetchChecks(string $sql, array $params): array
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $checks = [];
        while ($row = $stmt->fetch()) {
            $checks[] = $this->makeCheck($row);
        }
        return $checks;
    }

    public function find(int $id): ?Check
    {
        $sql = "SELECT * FROM url_checks WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch() ?: null;
        return $row ? $this->makeCheck($row) : null;
    }

    private function makeCheck(array $row): Check
    {
        $check = Check::fromArray([
            $row['url_id'],
            $row['status_code'],
            $row['h1'],
            $row['title'],
            $row['description'],
            $row['created_at']
        ]);
        $check->setId($row['id']);
        return $check;
    }
}
