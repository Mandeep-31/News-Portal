<?php

namespace Tests\Feature;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_returns_plain_text_without_querying_the_database_or_rendering_a_view(): void
    {
        $queryCount = 0;
        DB::listen(function (QueryExecuted $query) use (&$queryCount): void {
            $queryCount++;
        });

        $response = $this->get('/');

        $response->assertOk();
        $this->assertSame('App is running', $response->getContent());
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame(0, $queryCount);
    }
}
