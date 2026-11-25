<?php
declare(strict_types=1);

final class DashboardController extends Controller
{
    public function index(): void
    {
        $tasks = new TaskRepository();
        $stats = $tasks->stats();
        $stats['active'] = $stats['total'] - $stats['done'];
        $this->render('dashboard', [
            'title' => 'Асосӣ', 'active' => 'home', 'stats' => $stats,
            'latest' => $tasks->latest(5), 'priorities' => TaskController::PRIORITIES,
        ]);
    }
}
