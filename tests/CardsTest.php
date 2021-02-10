<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

class CardsTest extends TestCase
{
    public function testSomething(): void
    {
        $response = $this->get('/api/user/cards');

        $this->assertCount(0, $response);
    }
}
