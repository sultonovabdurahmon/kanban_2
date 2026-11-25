<?php
declare(strict_types=1);

final class ReportController extends Controller
{
    /** GET /reports/projects — вазифаҳо аз рӯи лоиҳаҳо */
    public function projects(): void
    {
        $rows = (new TaskRepository())->projectReport();
        // сатри ҷамъбастӣ дар PHP ҳисоб карда мешавад
        $totals = ['total' => 0, 'done' => 0, 'active' => 0, 'overdue' => 0, 'high_count' => 0];
        foreach ($rows as $row) {
            foreach ($totals as $key => $_) {
                $totals[$key] += (int) $row[$key];
            }
        }
        $this->render('reports/projects', ['title' => 'Ҳисобот', 'active' => 'report', 'rows' => $rows, 'totals' => $totals]);
    }
}
