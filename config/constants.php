<?php
define('RESPONSE_SUCCESS', 200);
define('RESPONSE_CREATED', 201);
define('RESPONSE_BAD_REQUEST', 400);
define('RESPONSE_UNAUTHORIZED', 401);
define('RESPONSE_FORBIDDEN', 403);
define('RESPONSE_SERVER_ERROR', 500);

define('ROLE_ADMIN', 1);
define('ROLE_MERCHANT', 2);
define('ROLE_CITIZEN', 3);
define('ROLE_HEALTH_WORKER', 4);

define('ACTION_CREATE', 'create');
define('ACTION_UPDATE', 'update');
define('ACTION_DELETE', 'delete');
define('ACTION_LOGIN', 'login');
define('ACTION_LOGOUT', 'logout');

// File upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'png', 'jpeg']);
