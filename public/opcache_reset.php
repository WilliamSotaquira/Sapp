<?php
// Endpoint to reset OPcache - called after file saves
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo json_encode(['status' => 'ok', 'time' => date('H:i:s')]);
} else {
    echo json_encode(['status' => 'not_available']);
}
