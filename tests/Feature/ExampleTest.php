<?php

use Tests\TestCase;

test('returns a successful response', function (TestCase $test) {
    $response = $test->get('/');

    $response->assertOk();
});
