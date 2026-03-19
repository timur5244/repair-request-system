<?php

namespace App\Http\Controllers;

use App\Application\Requests\RequestService;
use App\Domain\Enums\RequestStatus;
use App\Domain\Enums\UserRole;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    public function __construct(private readonly RequestService $service)
    {
    }

    public function index()
    {
        return view('requests.index', [
            'requests' => $this->service->paginate(),
        ]);
    }

    public function create()
    {
        return view('requests.create');
    }

    public function store(HttpRequest $request)
    {
        $data = $request->validate([
            'clientName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'problemText' => ['required', 'string'],
        ]);

        $created = $this->service->create($data);

        return redirect()->route('requests.show', $created);
    }

    public function show(ServiceRequest $request)
    {
        return view('requests.show', [
            'requestModel' => $request->load('assignee'),
            'masters' => User::query()->where('role', UserRole::Master->value)->orderBy('name')->get(),
            'statuses' => RequestStatus::cases(),
            'me' => Auth::user(),
        ]);
    }

    public function assign(HttpRequest $httpRequest, ServiceRequest $request)
    {
        $data = $httpRequest->validate([
            'assignedTo' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $master = !empty($data['assignedTo'])
            ? User::query()->where('role', UserRole::Master->value)->find($data['assignedTo'])
            : null;

        $this->service->assign($request, $master);

        return redirect()->route('requests.show', $request);
    }

    public function setStatus(HttpRequest $httpRequest, ServiceRequest $request)
    {
        $data = $httpRequest->validate([
            'status' => ['required', 'in:new,assigned,in_progress,done,canceled'],
        ]);

        $this->service->setStatus($request, RequestStatus::from($data['status']));

        return redirect()->route('requests.show', $request);
    }
}
