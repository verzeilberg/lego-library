<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
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
use App\Controller\User\Register;
use App\Dto\Request\User\ActivateAccountRequest;
use App\Dto\Request\User\ForgotPasswordRequest;
use App\Dto\Request\User\RegisterUserRequest;
use App\Dto\Request\User\ResetPasswordRequest;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\State\UserPasswordHasher;
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\Type;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'User',
    description: 'Model User',
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
            defaults: [
                'dto' => RegisterUserRequest::class
            ],
            controller: Register::class,
            input: RegisterUserRequest::class,
            output: RegisterUserRequest::class,
            name: 'register',
            processor: UserPasswordHasher::class,
        ),
        new Post(
            uriTemplate: '/public/user/activate',
            defaults: [
                'dto' => ActivateAccountRequest::class
            ],
            status: 204,
            controller: ActivateAccount::class,
            input: ActivateAccountRequest::class,
            output: ActivateAccountRequest::class,
            read: false,
            deserialize: false,
            name: 'activate',
        ),
        new Get(
            uriTemplate: '/public/user/check-forgot-password-code-token',
            controller: CheckTokenCodeController::class,
            shortName: 'Forgot Password',
            description: 'Check token and code to reset password',
            name: 'check-forgot-password-code-token',
        ),
        new Put(processor: UserPasswordHasher::class),
        new Patch(processor: UserPasswordHasher::class),
        new Delete(),
    ]
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

}
