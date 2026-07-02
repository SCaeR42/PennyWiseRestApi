<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers\V1;

use App\Core\Request;
use App\Core\Response;
use App\Modules\Dashboard\Services\WidgetRegistry;

final class DashboardController
{
    public function __construct(private readonly WidgetRegistry $registry)
    {
    }

    public function list(Request $request): Response
    {
        return Response::success($this->registry->names());
    }

    public function show(Request $request): Response
    {
        $widget = $this->registry->get((string) $request->param('name'));

        return Response::success($widget->data($request->userId()));
    }
}
