<?php

declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Controllers\Controller;
use App\Services\Access\AccessControlService;
use App\Services\ReservationService;

final class StaffController extends Controller
{
    public function index(): never
    {
        $service = ReservationService::make($this->app->db());
        $room = $service->room();
        $this->view('staff/index', [
            'title' => 'Provoz',
            'today' => $service->today((int) $room['id']),
            'occupancy' => $service->occupancyNow(),
            'door' => AccessControlService::make($this->app->db())->doorStatus(),
            'issues' => $this->app->db()->fetchAll("SELECT * FROM operational_issues WHERE status != 'closed' ORDER BY created_at DESC LIMIT 30"),
        ], 'layouts/user');
    }

    public function issue(\App\Core\Request $request): never
    {
        $this->app->db()->insert('operational_issues', [
            'reported_by' => $this->app->auth()->id(),
            'title' => (string) $request->input('title'),
            'description' => (string) $request->input('description'),
            'status' => 'open',
            'created_at' => \App\Support\Clock::utc(),
        ]);
        $this->flashSuccess('Problém byl nahlášen.');
        $this->redirect('/provoz');
    }
}
