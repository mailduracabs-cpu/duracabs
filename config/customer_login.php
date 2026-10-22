<?php

return [
    'google_client_ids' => array_values(array_filter(array_map('trim', explode(',', env(
        'CUSTOMER_GOOGLE_CLIENT_IDS',
        '530525446894-ihlcrc5ivlefqbnv89v01cbd3le6efnu.apps.googleusercontent.com'
    ))))),
];
