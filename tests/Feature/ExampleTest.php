<?php

beforeEach(function (): void {
    $this->withoutVite();
});

test('home page is reachable for guests', function () {
    $response = $this->get('/');

    $response->assertOk();
});
