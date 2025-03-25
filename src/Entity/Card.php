<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CardRepository::class)]
class Card implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $manaCost = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $manaValue = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rarity = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $uuid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtype = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $setCode = null;

    #[ORM\ManyToOne(targetEntity: Artist::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Artist $artist = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getManaCost(): ?string
    {
        return $this->manaCost;
    }

    public function setManaCost(?string $manaCost): static
    {
        $this->manaCost = $manaCost;

        return $this;
    }

    public function getManaValue(): ?int
    {
        return $this->manaValue;
    }

    public function setManaValue(?int $manaValue): static
    {
        $this->manaValue = $manaValue;

        return $this;
    }

    public function getRarity(): ?string
    {
        return $this->rarity;
    }

    public function setRarity(?string $rarity): static
    {
        $this->rarity = $rarity;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getSubtype(): ?string
    {
        return $this->subtype;
    }

    public function setSubtype(?string $subtype): static
    {
        $this->subtype = $subtype;

        return $this;
    }

    public function getSetCode(): ?string
    {
        return $this->setCode;
    }

    public function setSetCode(?string $setCode): static
    {
        $this->setCode = $setCode;

        return $this;
    }

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(?Artist $artist): static
    {
        $this->artist = $artist;

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'manaCost' => $this->manaCost,
            'manaValue' => $this->manaValue,
            'rarity' => $this->rarity,
            'uuid' => $this->uuid,
            'type' => $this->type,
            'text' => $this->text,
            'subtype' => $this->subtype,
            'setCode' => $this->setCode,
            'artist' => $this->artist,
        ];
    }
}
