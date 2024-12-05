<?php
namespace App\Dto\Request\User;

use App\Constant\JwtActions;
use App\Validator\JwtToken;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Activate Account Request DTO
 */
class ActivateAccountRequest
{
    #[Assert\NotBlank]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    #[JwtToken(action: JwtActions::ACTIVATE_ACCOUNT)]
    public string $token = '';
}
