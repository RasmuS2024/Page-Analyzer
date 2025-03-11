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
        $sqlUrl = "SELECT id, name FROM urls ORDER BY id DESC";
        $stmt = $this->conn->prepare($sqlUrl);
        $stmt->execute();
        $urls = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $sqlChecks = "SELECT DISTINCT ON (url_id) * FROM url_checks
            ORDER BY url_id, created_at DESC";
        $stmt = $this->conn->prepare($sqlChecks);
        $stmt->execute();
        $lastChecks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $checksMap = [];
        foreach ($lastChecks as $check) {
            $checksMap[$check['url_id']] = $check;
        }
        $result = [];
        foreach ($urls as $url) {
            $urlId = $url['id'];
            $lastCheck = $checksMap[$urlId] ?? null;
            $result[] = [
                'id' => $url['id'],
                'name' => $url['name'],
                'status_code' => $lastCheck['status_code'],
                'last_check_date' => $lastCheck['created_at']
            ];
        }
        return $result;
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
