<?php

beforeEach(function (): void {
    $this->withoutVite();
});

test('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
