<?php

namespace WPA;

class UrlRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getEntities(): array
    {
        $urls = [];
        $sql = "SELECT * FROM urls";
        $stmt = $this->conn->query($sql);

        while ($row = $stmt->fetch()) {
            $url = Url::fromArray([$row['name'], $row['created_at']]);
            $url->setId($row['id']);
            $urls[] = $url;
        }
        return $urls;
    }

    public function find(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        if ($row = $stmt->fetch())  {
            $url = Url::fromArray([$row['name'], $row['created_at']]);
            $url->setId($row['id']);
            return $url;
        }
        return null;
    }

    public function findByName(string $name): ?int
    {
        $sql = "SELECT * FROM urls WHERE name = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$name]);
        if ($row = $stmt->fetch())  {
            //$url = Url::fromArray([$row['name'], $row['created_at']]);
            //$url->setId($row['id']);
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
