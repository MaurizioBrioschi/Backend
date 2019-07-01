<?php

namespace App\Tests\Functional\Api;

use App\Entity\Group;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class GroupControllerTest extends AbstractApiControllerTest
{
    public function testGetIndex()
    {
        $this->setAuthRequest('GET', '/api/group');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testPostIndex()
    {
        $name = md5(random_bytes(10));
        $content = <<<CONTENT
            {
            "name": "$name"
            }
CONTENT;
        $this->client->request('POST', '/api/group', [], [], [
            'HTTP_X-AUTH-TOKEN' => '7ac66c0f148de9519b8bd264312c4d64',
            'HTTP_name' => 'NameAdmin',
            'HTTP_password' => 'password',
            'CONTENT_TYPE' => 'application/json', ], $content);
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals($name, $result['name']);
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $newGroup = $this->entityManager->getRepository(Group::class)->find($result['id']);
        $this->entityManager->remove($newGroup);

        $this->entityManager->flush();
    }

    public function testDelete()
    {
        $group = new Group();
        $group->setName('new Test group');
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        $this->setAuthRequest('DELETE', '/api/group/'.$group->getId());

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testPostAddUsers()
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $usersIds = [];
        foreach ($users as $user) {
            array_push($usersIds, $user->getId());
        }
        $usersIds = implode(',', $usersIds);
        /** @var Group $group */
        $group = $this->entityManager->getRepository(Group::class)->findOneBy(['name' => 'Test group']);

        $content = <<<CONTENT
            {
            "groupId": "{$group->getId()}",
            "userIds": [{$usersIds}]
            }
CONTENT;
        $this->client->request('POST', '/api/group/addUsers', [], [], [
            'HTTP_X-AUTH-TOKEN' => '7ac66c0f148de9519b8bd264312c4d64',
            'HTTP_name' => 'NameAdmin',
            'HTTP_password' => 'password',
            'CONTENT_TYPE' => 'application/json', ], $content);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals($group->getId(), $result['id']);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals($group->getName(), $result['name']);
        $this->assertArrayHasKey('userIds', $result);
        $this->assertEquals($usersIds, implode(',', $result['userIds']));
    }

    public function testPostRemoveUsers()
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $usersIds = [];
        foreach ($users as $user) {
            array_push($usersIds, $user->getId());
        }
        $usersIds = implode(',', $usersIds);
        /** @var Group $group */
        $group = $this->entityManager->getRepository(Group::class)->findOneBy(['name' => 'Test group']);

        $content = <<<CONTENT
            {
            "groupId": "{$group->getId()}",
            "userIds": [{$usersIds}]
            }
CONTENT;
        $this->client->request('POST', '/api/group/removeUsers', [], [], [
            'HTTP_X-AUTH-TOKEN' => '7ac66c0f148de9519b8bd264312c4d64',
            'HTTP_name' => 'NameAdmin',
            'HTTP_password' => 'password',
            'CONTENT_TYPE' => 'application/json', ], $content);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals($group->getId(), $result['id']);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals($group->getName(), $result['name']);
        $this->assertArrayHasKey('userIds', $result);
        $this->assertEmpty($result['userIds']);
    }
}
