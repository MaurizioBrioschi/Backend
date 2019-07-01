<?php

namespace App\DataFixtures;

use App\Entity\Group;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class GroupFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $group = new  Group();
        $group->setName('Test group');
        $manager->persist($group);
        $manager->flush();
    }
}
