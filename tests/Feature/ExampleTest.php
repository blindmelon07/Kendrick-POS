<?php

test('returns a successful response', function () {
    $response = $this->followingRedirects()->get(route('home'));

    $response->assertOk();
});
