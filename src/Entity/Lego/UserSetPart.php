<?php

namespace App\Entity\Lego;

use AllowDynamicProperties;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\Lego\CreateDefectPartsController;
use App\Dto\Request\Lego\CreateDefectPartsRequest;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\Table(
    name: 'lego_user_set_part',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_user_set_part',
            columns: ['set_list_set_id', 'set_part_id']
        )
    ]
)]
#[AllowDynamicProperties] #[ApiResource(
    shortName: 'User',
    description: 'Set User',
    operations: [
        new Post(
            uriTemplate: '/lego/set/part/defect/create',
            formats: ['json' => ['application/json']],
            defaults: ['dto' => CreateDefectPartsRequest::class],
            controller: CreateDefectPartsController::class,
            shortName: 'Create an defective part for a set of a user',
            input: CreateDefectPartsRequest::class,
            output: CreateDefectPartsRequest::class,
            deserialize: true,
        ),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:create']]
)]
class UserSetPart
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: 'Ramsey\Uuid\Doctrine\UuidGenerator')]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: SetListSet::class, inversedBy: 'partStates')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SetListSet $setListSet;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SetPart $setPart;

    #[ORM\Column(type: 'integer')]
    private int $missingQuantity = 0;

    #[ORM\Column(type: 'integer')]
    private int $damagedQuantity = 0;

    #[ORM\Column(type: 'integer')]
    private int $discolouredQuantity = 0;

    /* =========================================================
     *  GETTERS
     * ========================================================= */

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getSetListSet(): SetListSet
    {
        return $this->setListSet;
    }

    public function getSetPart(): SetPart
    {
        return $this->setPart;
    }

    public function getMissingQuantity(): int
    {
        return $this->missingQuantity;
    }

    public function getDamagedQuantity(): int
    {
        return $this->damagedQuantity;
    }

    public function getDiscolouredQuantity(): int
    {
        return $this->discolouredQuantity;
    }

    /* =========================================================
     *  SETTERS
     * ========================================================= */

    public function setSetListSet(SetListSet $setListSet): self
    {
        $this->setListSet = $setListSet;
        return $this;
    }

    public function setSetPart(SetPart $setPart): self
    {
        $this->setPart = $setPart;
        return $this;
    }

    public function setMissingQuantity(int $missingQuantity): self
    {
        $this->missingQuantity = max(0, $missingQuantity);
        $this->normalizeQuantities();
        return $this;
    }

    public function setDamagedQuantity(int $damagedQuantity): self
    {
        $this->damagedQuantity = max(0, $damagedQuantity);
        $this->normalizeQuantities();
        return $this;
    }

    public function setDiscolouredQuantity(int $discolouredQuantity): self
    {
        $this->discolouredQuantity = max(0, $discolouredQuantity);
        $this->normalizeQuantities();
        return $this;
    }

    /* =========================================================
     *  DOMAIN LOGIC
     * ========================================================= */

    /**
     * Total required quantity from original set definition.
     */
    public function getRequiredQuantity(): int
    {
        return $this->setPart->getQuantity();
    }

    /**
     * Number of physically owned usable parts.
     * Discoloured parts are considered owned but not pristine.
     */
    public function getOwnedQuantity(): int
    {
        return max(
            0,
            $this->getRequiredQuantity()
            - $this->missingQuantity
            - $this->damagedQuantity
            - $this->discolouredQuantity
        );
    }

    /**
     * Functional completeness:
     * Set is complete if no parts are missing or broken.
     * Discoloured parts are restorable and therefore allowed.
     */
    public function isComplete(): bool
    {
        return
            $this->missingQuantity === 0
            && $this->damagedQuantity === 0;
    }

    /**
     * Total defective parts.
     */
    public function getTotalDefective(): int
    {
        return
            $this->missingQuantity
            + $this->damagedQuantity
            + $this->discolouredQuantity;
    }

    /**
     * Ensures invariant:
     * missing + damaged + discoloured <= requiredQuantity
     */
    private function normalizeQuantities(): void
    {
        $required = $this->getRequiredQuantity();
        $total = $this->getTotalDefective();

        if ($total <= $required) {
            return;
        }

        $overflow = $total - $required;

        // Priority rule:
        // Reduce discoloured first (least severe),
        // then damaged,
        // then missing (most severe).

        if ($this->discolouredQuantity >= $overflow) {
            $this->discolouredQuantity -= $overflow;
            return;
        }

        $overflow -= $this->discolouredQuantity;
        $this->discolouredQuantity = 0;

        if ($this->damagedQuantity >= $overflow) {
            $this->damagedQuantity -= $overflow;
            return;
        }

        $overflow -= $this->damagedQuantity;
        $this->damagedQuantity = 0;

        $this->missingQuantity = max(0, $this->missingQuantity - $overflow);
    }

    /* =========================================================
     *  Convenience accessors to underlying domain
     * ========================================================= */

    public function getPartColor(): PartColor
    {
        return $this->setPart->getPartColor();
    }

    public function getPart(): Part
    {
        return $this->getPartColor()->getPart();
    }

    public function getColor(): Color
    {
        return $this->getPartColor()->getColor();
    }
}
