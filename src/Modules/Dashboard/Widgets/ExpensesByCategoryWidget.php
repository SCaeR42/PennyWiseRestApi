<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Widgets;

final class ExpensesByCategoryWidget implements WidgetInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function name(): string
    {
        return 'expenses-by-category';
    }

    public function data(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.id AS category_id, c.name AS category_name, SUM(t.amount) AS total
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = :user_id AND t.type = 'expense'
             GROUP BY c.id, c.name
             ORDER BY total DESC"
        );
        $stmt->execute(['user_id' => $userId]);

        return array_map(static fn (array $row) => [
            'category_id' => (int) $row['category_id'],
            'category_name' => $row['category_name'],
            'total' => round((float) $row['total'], 2),
        ], $stmt->fetchAll());
    }
}
