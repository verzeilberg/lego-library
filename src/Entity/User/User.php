<?php

namespace App\Entity\User;

use AllowDynamicProperties;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\ForgotPasswordController;
use App\Controller\ResetPasswordController;
use App\Controller\User\ActivateAccount;
use App\Controller\User\CheckTokenCodeController;
use App\Controller\User\PatchController;
use App\Controller\User\CreateUser;
use App\Dto\Request\User\ForgotPasswordRequest;
use App\Dto\Request\User\PatchRequest;
use App\Dto\Request\User\RegisterUserRequest;
use App\Dto\Request\User\ResetPasswordRequest;
use App\Dto\Request\User\TokenCodeRequest;
use App\Repository\UserRepository;
use App\State\UserPasswordHasher;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[AllowDynamicProperties] #[ApiResource(
    shortName: 'User',
    description: 'Set User',
    operations: [
        new GetCollection(),
        new Get(
            security: 'object == user'
        ),
        new Post(
            uriTemplate: '/public/user/reset-password',
            defaults: [
                'dto' => ResetPasswordRequest::class
            ],
            controller: ResetPasswordController::class,
            shortName: 'Forgot Password',
            description: 'Reset password',
            input: ResetPasswordRequest::class,
            output: ResetPasswordRequest::class,
            read: false,
            deserialize: false,
            name: 'reset-password',
        ),
        new Post(
            uriTemplate: '/public/user/forgot-password',
            defaults: [
                'dto' => ForgotPasswordRequest::class
            ],
            controller: ForgotPasswordController::class,
            shortName: 'Forgot Password',
            description: 'Forgot password because you are stupid',
            input: ForgotPasswordRequest::class,
            output: ForgotPasswordRequest::class,
            read: false,
            deserialize: false,
            name: 'forgot-password',
        ),
        new Post(
            uriTemplate: '/public/user/register',
            formats: ['json' => ['application/json']],
            defaults: [
                'dto' => RegisterUserRequest::class
            ],
            controller: CreateUser::class,
            input: RegisterUserRequest::class,
            output: RegisterUserRequest::class,
            name: 'register',
            processor: UserPasswordHasher::class  // only JSON
        ),
        new Post(
            uriTemplate: '/public/user/activate',
            formats: ['json' => ['application/json']],
            defaults: [
                'dto' => TokenCodeRequest::class
            ],
            status: 204,
            controller: ActivateAccount::class,
            input: TokenCodeRequest::class,
            output: TokenCodeRequest::class,
            read: false,
            deserialize: false,
            name: 'activate',
        ),
        new Post(
            uriTemplate: '/public/user/check-token-code',
            formats: ['json' => ['application/json']],
            defaults: [
                'dto' => TokenCodeRequest::class
            ],
            controller: CheckTokenCodeController::class,
            shortName: 'Forgot Password',
            description: 'Check token and code to reset password',
            input: TokenCodeRequest::class,
            output: TokenCodeRequest::class,
            name: 'check-forgot-password-code-token',
        ),
        new Put(processor: UserPasswordHasher::class),
        new Patch(
            uriTemplate: '/user/patch',
            defaults: [
                'dto' => PatchRequest::class
            ],
            controller: PatchController::class,
            description: 'Update user',
            security: 'is_granted("ROLE_USER")',
            input: PatchController::class,
            output: PatchController::class,
            name: 'patch-user'
        ),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:create']]
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity('email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_USER = 'ROLE_USER';

    #[Groups(['user:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;
    #[Groups(['user:read', 'user:create', 'user:update'])]
    private ?string $plainPassword = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active;

    #[ORM\OneToOne(targetEntity: UserData::class, mappedBy: 'owner', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?UserData $userData = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserToken::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $tokens;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * User constructor
     */
    public function __construct()
    {
        $this->role = self::ROLE_USER;
        $this->active = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUserData(): ?UserData
    {
        return $this->userData;
    }

    public function setUserData(?UserData $userData): self
    {
        if ($this->userData === $userData) {
            return $this;
        }

        $this->userData = $userData;

        // only set owner if not already set
        if ($userData !== null && $userData->getOwner() !== $this) {
            $userData->setOwner($this);
        }

        return $this;
    }

}
