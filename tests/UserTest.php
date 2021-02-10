<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserTest extends WebTestCase
{
    /**
     * @test
     */
    public function getUsers()
    {
        $client = self::createClient();
        $client->request('GET', '/api/user/index');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([['firstName' => 'Test', 'id' => 1, 'email' => 'test@gmail.com', 'subs' => 200]]), $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function getUserWithBadId()
    {
        $client = self::createClient();
        $client->request('GET', '/api/user/show/89789');

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"error":1,"message":"Bad id or user not found"}', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function getUserById()
    {
        $client = self::createClient();
        $client->request('GET', '/api/user/show/1');

        $this->assertJsonStringEqualsJsonString(
            json_encode(['firstName' => 'Test', 'id' => 1, 'email' => 'test@gmail.com', 'subs' => 200]), $client->getResponse()->getContent());
    }
}


