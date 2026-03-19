<?php

namespace App\Http\Controllers\Dispatcher;

use App\Application\Requests\RequestService;
use App\Domain\Enums\RequestStatus;
use App\Domain\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Validation\ValidationException;

class DispatcherDashboardController extends Controller
{
    public function __construct(private readonly RequestService $service)
    {
    }

    public function index(HttpRequest $request)
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:new,assigned,in_progress,done,canceled'],
            'master_id' => ['nullable', 'integer'],
        ]);

        $status = !empty($data['status']) ? RequestStatus::from($data['status']) : null;

        $mastersQuery = User::query()->where('role', UserRole::Master->value)->orderBy('name');
        $masters = $mastersQuery->get();

        $masterId = null;
        if (!empty($data['master_id'])) {
            $master = $mastersQuery->clone()->find($data['master_id']);
            if ($master) {
                $masterId = $master->id;
            }
        }

        return view('dispatcher.dashboard', [
            'requests' => $this->service->paginateForDispatcher([
                'status' => $status,
                'masterId' => $masterId,
            ], 10),
            'statuses' => RequestStatus::cases(),
            'masters' => $masters,
            'filters' => [
                'status' => $data['status'] ?? '',
                'master_id' => (string) ($masterId ?? ''),
            ],
        ]);
    }

    public function assign(HttpRequest $httpRequest, ServiceRequest $request)
    {
        $data = $httpRequest->validate([
            'master_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $master = User::query()
            ->where('role', UserRole::Master->value)
            ->findOrFail($data['master_id']);

        try {
            $this->service->assign($request, $master);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back();
    }

    public function cancel(ServiceRequest $request)
    {
        try {
            $this->service->cancel($request);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back();
    }
}
