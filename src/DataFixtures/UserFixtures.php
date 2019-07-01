<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Security\TokenAuthenticator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    /** @var UserPasswordEncoderInterface $passwordEncoder */
    private $passwordEncoder;
    /** @var TokenAuthenticator $authenticator */
    private $authenticator;

    public function __construct(UserPasswordEncoderInterface $encoder, TokenAuthenticator $auth)
    {
        $this->passwordEncoder = $encoder;
        $this->authenticator = $auth;
    }

    public function load(ObjectManager $manager)
    {
        $adminUser = new User();
        $adminUser->setName('NameAdmin');
        $adminUser->setPassword($this->passwordEncoder->encodePassword($adminUser, 'password'));
        $adminUser->addRole('ROLE_ADMIN');
        $adminUser->setApiToken(md5('abcdefg'));
        $manager->persist($adminUser);

        $user = new User();
        $user->setName('NameSimpleuser');
        $user->setPassword($this->passwordEncoder->encodePassword($user, 'simple'));
        $user->setApiToken(md5('123456'));
        $manager->persist($user);

        $manager->flush();
    }
}
