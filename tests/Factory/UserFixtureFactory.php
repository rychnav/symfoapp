<?php

namespace App\Tests\Factory;

use App\Controller\SecurityController;
use App\Entity\User;
use App\Repository\UserRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @method static User|Proxy findOrCreate(array $attributes)
 * @method static User|Proxy random()
 * @method static User[]|Proxy[] randomSet(int $number)
 * @method static User[]|Proxy[] randomRange(int $min, int $max)
 * @method static UserRepository|RepositoryProxy repository()
 * @method User|Proxy create($attributes = [])
 * @method User[]|Proxy[] createMany(int $number, $attributes = [])
 */
class UserFixtureFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'email' => self::faker()->unique()->email,
            'firstName' => self::faker()->firstName(),
            'authType' => SecurityController::REGISTER_WITH_EMAIL
        ];
    }

    public function asAdmin(): self
    {
        return $this->addState(['roles' => ['ROLE_ADMIN']]);
    }

    public function asUser(): self
    {
        return $this->addState(['roles' => ['ROLE_USER']]);
    }

    protected function initialize(): self
    {
        // See: https://github.com/zenstruck/foundry#initialization.
        return $this
            //->beforeInstantiate(function(User $user) {})
        ;
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
