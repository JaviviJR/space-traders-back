<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\Dto\RegisterUserDto;
use App\Security\Exception\EmailAlreadyRegisteredException;
use App\Security\UserRegistrationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserRegistrationServiceTest extends KernelTestCase
{
    public function testRegisteringTheSameEmailTwiceThrows(): void
    {
        self::bootKernel();
        $service = static::getContainer()->get(UserRegistrationService::class);

        $dto = new RegisterUserDto('duplicate-service@example.com', 'password123');
        $service->register($dto);

        $this->expectException(EmailAlreadyRegisteredException::class);

        $service->register($dto);
    }
}
