<?php

test('indexing queue retry_after is greater than horizon indexing timeout', function () {
    $retryAfter = (int) config('queue.connections.redis.retry_after');
    $indexingTimeout = (int) config('horizon.defaults.supervisor-indexing.timeout');

    expect($retryAfter)->toBeGreaterThan($indexingTimeout);
});

test('horizon indexing supervisor retries are aligned with indexing jobs', function () {
    $indexingTries = (int) config('horizon.defaults.supervisor-indexing.tries');

    expect($indexingTries)->toBe(3);
});
