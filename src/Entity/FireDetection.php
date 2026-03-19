<?php

namespace App\Entity;

use App\Repository\FireDetectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapping READ-ONLY vers la table firms_fire_detections.
 * Cette table est gérée par le pipeline n8n — NE PAS inclure dans les migrations Symfony.
 * La config doctrine.yaml (schema_filter) exclut cette table des diffs de migration.
 */
#[ORM\Entity(repositoryClass: FireDetectionRepository::class, readOnly: true)]
#[ORM\Table(name: 'firms_fire_detections')]
class FireDetection
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $source;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $satellite = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $instrument = null;

    #[ORM\Column(type: 'float')]
    private float $latitude;

    #[ORM\Column(type: 'float')]
    private float $longitude;

    #[ORM\Column(name: 'acq_datetime', type: 'datetime_immutable')]
    private \DateTimeImmutable $acqDatetime;

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $daynight = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $brightness = null;

    #[ORM\Column(name: 'bright_secondary', type: 'float', nullable: true)]
    private ?float $brightSecondary = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $frp = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $confidence = null;

    #[ORM\Column(name: 'inserted_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $insertedAt;

    #[ORM\OneToMany(mappedBy: 'detection', targetEntity: Alert::class)]
    private Collection $alerts;

    public function __construct()
    {
        $this->alerts     = new ArrayCollection();
        $this->insertedAt = new \DateTimeImmutable();
    }

    // ─── Getters ─────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getSatellite(): ?string
    {
        return $this->satellite;
    }

    public function getInstrument(): ?string
    {
        return $this->instrument;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getAcqDatetime(): \DateTimeImmutable
    {
        return $this->acqDatetime;
    }

    public function getDaynight(): ?string
    {
        return $this->daynight;
    }

    public function getBrightness(): ?float
    {
        return $this->brightness;
    }

    public function getBrightSecondary(): ?float
    {
        return $this->brightSecondary;
    }

    public function getFrp(): ?float
    {
        return $this->frp;
    }

    public function getConfidence(): ?string
    {
        return $this->confidence;
    }

    public function getInsertedAt(): \DateTimeImmutable
    {
        return $this->insertedAt;
    }

    /** @return Collection<int, Alert> */
    public function getAlerts(): Collection
    {
        return $this->alerts;
    }

    /**
     * Label lisible de l'intensité du feu selon FRP.
     */
    public function getRiskLabel(): string
    {
        return match(true) {
            $this->frp === null   => 'N/A',
            $this->frp > 100      => 'Critique',
            $this->frp > 50       => 'Élevé',
            $this->frp > 15       => 'Modéré',
            default               => 'Faible',
        };
    }

    /**
     * Classe Bootstrap pour la badge de risque.
     */
    public function getRiskBadgeClass(): string
    {
        return match(true) {
            $this->frp === null   => 'secondary',
            $this->frp > 100      => 'danger',
            $this->frp > 50       => 'warning',
            $this->frp > 15       => 'info',
            default               => 'success',
        };
    }
}
