<?php

namespace WPA;

class Check
{
    private ?int $id = null;
    private ?int $urlId = null;
    private ?int $statusCode = null;
    private ?string $h1 = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?string $createdDT = null;

    public static function fromArray(array $checkData): Check
    {
        [$urlId, $statusCode, $h1, $title, $description, $createdDT] = $checkData;
        $check = new Check();
        $check->setUrlId($urlId);
        $check->setStatusCode($statusCode);
        $check->setH1($h1);
        $check->setTitle($title);
        $check->setDescription($description);
        $check->setCreatedDT($createdDT);
        return $check;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrlId(): ?int
    {
        return $this->urlId;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedDT(): ?string
    {
        return $this->createdDT;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUrlId(int $urlId): void
    {
        $this->urlId = $urlId;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function setH1(string $h1): void
    {
        $this->h1 = $h1;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setCreatedDT(string $createdDT): void
    {
        $this->createdDT = $createdDT;
    }
}
