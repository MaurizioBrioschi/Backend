<?php

namespace App\Tests\Functional\Api;

use App\Entity\Group;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AbstractApiControllerTest
{
    public function testGetIndex()
    {
        $this->setAuthRequest('GET', '/api/user');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testPostIndex()
    {
        $name = md5(random_bytes(10));
        $content = <<<CONTENT
            {
            "name": "$name",
            "password": "1233445",
            "roles": [
                "ROLE_ADMIN"
                    ]
            }
CONTENT;
        $this->client->request('POST', '/api/user', [], [], [
            'HTTP_X-AUTH-TOKEN' => '7ac66c0f148de9519b8bd264312c4d64',
            'HTTP_name' => 'NameAdmin',
            'HTTP_password' => 'password',
            'CONTENT_TYPE' => 'application/json', ], $content);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('name', $content);
        $this->assertEquals($name, $content['name']);
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $newUser = $this->entityManager->getRepository(User::class)->find($content['id']);
        $this->entityManager->remove($newUser);

        $this->entityManager->flush();
    }

    public function testDelete()
    {
        $adminUser = new User();
        $adminUser->setName('MytestAdmin');
        $adminUser->setPassword('password');
        $adminUser->addRole('ROLE_ADMIN');
        $adminUser->setApiToken(md5(random_bytes(10)));
        $this->entityManager->persist($adminUser);
        $this->entityManager->flush();

        $this->setAuthRequest('DELETE', '/api/user/'.$adminUser->getId());

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('', $this->client->getResponse()->getContent());
    }

    public function testPostSetGroups()
    {
        /** @var Group $group */
        $group = $this->entityManager->getRepository(Group::class)->findOneBy(['name' => 'Test group']);
        if (is_null($group)) {
            return;
        }
        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['name' => 'NameAdmin']);

        $content = <<<CONTENT
            {
            "userId": "{$user->getId()}",
            "groupIds": [{$group->getId()}]
            }
CONTENT;
        $this->client->request('POST', '/api/user/setGroups', [], [], [
            'HTTP_X-AUTH-TOKEN' => '7ac66c0f148de9519b8bd264312c4d64',
            'HTTP_name' => 'NameAdmin',
            'HTTP_password' => 'password',
            'CONTENT_TYPE' => 'application/json', ], $content);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals('NameAdmin', $result['name']);
        $this->assertArrayHasKey('groupIds', $result);
        $this->assertTrue(is_array($result['groupIds']));
        $this->assertEquals([$group->getId()], $result['groupIds']);

        $user->setGroups([]);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @dataProvider urlProvider
     */
    public function testWrongCalls($method, $url, $params, $responseCode, $responseData)
    {
        $this->client->request($method, $url, $params);

        $this->assertEquals($responseCode, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(json_encode($responseData), $this->client->getResponse()->getContent());
    }

    public function urlProvider()
    {
        return [
            ['GET', '/api/user', [], 401, ['message' => 'Authentication Required']],
            ['POST', '/api/user', [], 401, ['message' => 'Authentication Required']],
            ['DELETE', '/api/user/1', [], 401, ['message' => 'Authentication Required']],
        ];
    }
}
