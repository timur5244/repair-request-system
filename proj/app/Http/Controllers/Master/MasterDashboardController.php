<?php

namespace App\Http\Controllers\Master;

use App\Application\Requests\RequestService;
use App\Http\Controllers\Controller;
use App\Models\Request as ServiceRequest;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MasterDashboardController extends Controller
{
    public function __construct(private readonly RequestService $service)
    {
    }

    public function index()
    {
        $me = Auth::user();

        $all = $this->service->listForMaster($me);

        return view('master.dashboard', [
            'assigned' => $all->filter(fn ($r) => ($r->status->value ?? (string) $r->status) === 'assigned'),
            'inProgress' => $all->filter(fn ($r) => ($r->status->value ?? (string) $r->status) === 'in_progress'),
            'done' => $all->filter(fn ($r) => ($r->status->value ?? (string) $r->status) === 'done'),
        ]);
    }

    public function take(HttpRequest $httpRequest, ServiceRequest $request)
    {
        $me = Auth::user();
        $data = $httpRequest->validate([
            'version' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $updated = $this->service->takeInWork($me, $request, (int) $data['version']);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if (isset($errors['conflict'][0])) {
                return response($errors['conflict'][0], 409);
            }

            return response(collect($errors)->flatten()->first() ?? 'Ошибка', 422);
        }

        if ($httpRequest->expectsJson()) {
            return response()->json([
                'id' => $updated->id,
                'status' => $updated->status->value ?? (string) $updated->status,
                'version' => (int) $updated->version,
            ]);
        }

        return redirect()->route('master.dashboard');
    }

    public function finish(HttpRequest $httpRequest, ServiceRequest $request)
    {
        $me = Auth::user();

        try {
            $updated = $this->service->finish($me, $request);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            return response(collect($errors)->flatten()->first() ?? 'Ошибка', 422);
        }

        if ($httpRequest->expectsJson()) {
            return response()->json([
                'id' => $updated->id,
                'status' => $updated->status->value ?? (string) $updated->status,
                'version' => (int) $updated->version,
            ]);
        }

        return redirect()->route('master.dashboard');
    }
}
