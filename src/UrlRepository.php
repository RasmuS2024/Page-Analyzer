<?php

namespace WPA;

class UrlRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getUrlsWithLastChecks(): array
    {
        $urls = [];
        $sql = "SELECT 
                u.id, 
                u.name, 
                MAX(uc.created_at) as last_check_date,
                uc.status_code
                FROM urls u
                LEFT JOIN url_checks uc ON u.id = uc.url_id
                GROUP BY u.id, uc.status_code
                ORDER BY u.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        if ($row = $stmt->fetch()) {
            $url = Url::fromArray([$row['name'], $row['created_at']]);
            $url->setId($row['id']);
            return $url;
        }
        return null;
    }

    public function findIdByName(string $name): ?int
    {
        $sql = "SELECT id FROM urls WHERE name = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$name]);
        if ($row = $stmt->fetch()) {
            return $row['id'];
        }
        return null;
    }

    public function save(Url $url): int
    {
        if ($url->exists()) {
            $id = $this->update($url);
        } else {
            $id = $this->create($url);
        }
        return $id;
    }

    private function update(Url $url): int
    {
        $sql = "UPDATE urls SET name = :name, created_at = :createdDT WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $id = $url->getId();
        $name = $url->getName();
        $createdDT = $url->getCreatedDT();
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':createdDT', $createdDT);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $id;
    }

    private function create(Url $url): int
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :createdDT)";
        $stmt = $this->conn->prepare($sql);
        $name = $url->getName();
        $createdDT = $url->getCreatedDT();
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':createdDT', $createdDT);
        $stmt->execute();
        $id = (int) $this->conn->lastInsertId();
        $url->setId($id);
        return $id;
    }
}
