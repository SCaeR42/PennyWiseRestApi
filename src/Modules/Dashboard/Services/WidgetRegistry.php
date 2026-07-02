<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Core\Exceptions\NotFoundException;
use App\Modules\Dashboard\Widgets\BalanceChartWidget;
use App\Modules\Dashboard\Widgets\ExpensesByCategoryWidget;
use App\Modules\Dashboard\Widgets\WidgetInterface;

final class WidgetRegistry
{
    /** @var array<string, WidgetInterface> */
    private readonly array $widgets;

    public function __construct(BalanceChartWidget $balanceChart, ExpensesByCategoryWidget $expensesByCategory)
    {
        $this->widgets = [
            $balanceChart->name() => $balanceChart,
            $expensesByCategory->name() => $expensesByCategory,
        ];
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->widgets);
    }

    public function get(string $name): WidgetInterface
    {
        if (!isset($this->widgets[$name])) {
            throw new NotFoundException("Widget '{$name}' not found");
        }

        return $this->widgets[$name];
    }
}
