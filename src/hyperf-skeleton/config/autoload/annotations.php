<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @see     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    'scan' => [
        'paths' => [
            BASE_PATH . '/app',
        ],
        'ignore_annotations' => [
            'mixin',
            'OA\Post',
            'OA\Get',
            'OA\Put',
            'OA\Delete',
            'OA\Patch',
            'OA\Info',
            'OA\Server',
            'OA\RequestBody',
            'OA\Response',
            'OA\Property',
            'OA\JsonContent',
            'OA\Schema',
            'OA\Items',
            'OA\Examples',
        ],
    ],
];
