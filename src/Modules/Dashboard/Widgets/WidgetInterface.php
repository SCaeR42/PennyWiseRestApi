<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Widgets;

interface WidgetInterface
{
    public function name(): string;

    public function data(int $userId): array;
}
