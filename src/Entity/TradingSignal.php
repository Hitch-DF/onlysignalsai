<?php

namespace App\Entity;

use App\Repository\TradingSignalRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TradingSignalRepository::class)]
class TradingSignal
{
    public const TYPE_BUY = 'Buy';
    public const TYPE_SELL = 'Sell';
    public const ALLOWED_TYPES = [self::TYPE_BUY, self::TYPE_SELL];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $symbol;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'string', length: 10)]
    private string $signalType;

    #[ORM\Column(type: 'string', length: 20)]
    private string $category;

    #[ORM\Column(nullable: true)]
    private ?bool $status = null;

    #[ORM\Column(nullable: true)]
    private ?float $entryPrice = null;

    #[ORM\Column(nullable: true)]
    private ?float $exitPrice = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $closedAt = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $timeFrame = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $result = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $screenshot = null;

    #[ORM\Column(nullable: true)]
    private ?float $pips = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getSignalType(): string
    {
        return $this->signalType;
    }

    public function setSignalType(string $signalType): self
    {
        if (!in_array($signalType, self::ALLOWED_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid signal type: $signalType");
        }
        $this->signalType = $signalType;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Retourne "il y a 5 minutes", etc.
     */
    public function getSignalAge(): string
    {
        $now = new \DateTimeImmutable();
        $interval = $this->createdAt->diff($now);

        if ($interval->days > 0) {
            return $interval->format('%d day(s) ago');
        }
        if ($interval->h > 0) {
            return $interval->format('%h hour(s) ago');
        }
        if ($interval->i > 0) {
            return $interval->format('%i minute(s) ago');
        }
        return 'Just now';
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(?bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getEntryPrice(): ?float
    {
        return $this->entryPrice;
    }

    public function setEntryPrice(?float $entryPrice): static
    {
        $this->entryPrice = $entryPrice;

        return $this;
    }

    public function getExitPrice(): ?float
    {
        return $this->exitPrice;
    }

    public function setExitPrice(?float $exitPrice): static
    {
        $this->exitPrice = $exitPrice;

        return $this;
    }

    public function getClosedAt(): ?\DateTime
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTime $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getTimeFrame(): ?string
    {
        return $this->timeFrame;
    }

    public function setTimeFrame(?string $timeFrame): static
    {
        $this->timeFrame = $timeFrame;

        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getScreenshot(): ?string
    {
        return $this->screenshot;
    }

    public function setScreenshot(?string $screenshot): static
    {
        $this->screenshot = $screenshot;

        return $this;
    }

    public function getPips(): ?float
    {
        return $this->pips;
    }

    public function setPips(?float $pips): static
    {
        $this->pips = $pips;

        return $this;
    }
}
