<?php

namespace App\Application\Requests;

use App\Domain\Enums\RequestStatus;
use App\Models\Request;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestService
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Request::query()
            ->with('assignee')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /** @param array{status?:RequestStatus|null,masterId?:int|null} $filters */
    public function paginateForDispatcher(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = Request::query()
            ->with('assignee')
            ->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']->value);
        }

        if (!empty($filters['masterId'])) {
            $query->where('assignedTo', $filters['masterId']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /** @param array{clientName:string,phone:string,address:string,problemText:string} $data */
    public function create(array $data): Request
    {
        return Request::create([
            'clientName' => $data['clientName'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'problemText' => $data['problemText'],
            'status' => RequestStatus::New,
            'assignedTo' => null,
        ]);
    }

    public function assign(Request $request, ?User $master): Request
    {
        $current = $this->asStatus($request->status);

        if ($master) {
            if ($current !== RequestStatus::New) {
                throw ValidationException::withMessages([
                    'assignedTo' => 'Назначить мастера можно только для заявок со статусом new.',
                ]);
            }

            $request->assignedTo = $master->id;
            $request->status = RequestStatus::Assigned;
            $request->save();
        } else {
            // снять назначение можно только если заявка не завершена/не отменена
            if (in_array($current, [RequestStatus::Done, RequestStatus::Canceled], true)) {
                throw ValidationException::withMessages([
                    'assignedTo' => 'Нельзя менять назначение для завершённой/отменённой заявки.',
                ]);
            }

            $request->assignedTo = null;
            $request->status = RequestStatus::New;
            $request->save();
        }

        return $request->refresh()->load('assignee');
    }

    public function setStatus(Request $request, RequestStatus $status): Request
    {
        $current = $this->asStatus($request->status);

        if ($current === RequestStatus::Done) {
            throw ValidationException::withMessages(['status' => 'Нельзя менять статус уже завершённой заявки.']);
        }
        if ($current === RequestStatus::Canceled) {
            throw ValidationException::withMessages(['status' => 'Нельзя менять статус уже отменённой заявки.']);
        }

        $request->status = $status;
        $request->save();

        return $request->refresh()->load('assignee');
    }

    public function cancel(Request $request): Request
    {
        $current = $this->asStatus($request->status);

        if (!in_array($current, [RequestStatus::New, RequestStatus::Assigned, RequestStatus::InProgress], true)) {
            throw ValidationException::withMessages([
                'status' => 'Отменить можно только заявку в статусах new/assigned/in_progress.',
            ]);
        }

        $request->status = RequestStatus::Canceled;
        $request->save();

        return $request->refresh()->load('assignee');
    }

    /** @return Collection<int, Request> */
    public function listForMaster(User $master): Collection
    {
        return Request::query()
            ->with('assignee')
            ->where('assignedTo', $master->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Потокобезопасный перевод assigned -> in_progress (optimistic locking через version).
     * @throws ValidationException
     */
    public function takeInWork(User $master, Request $request, int $expectedVersion): Request
    {
        // Важно: SQLite не поддерживает SELECT .. FOR UPDATE, поэтому делаем атомарный UPDATE с условием по version+status.
        $affected = Request::query()
            ->whereKey($request->id)
            ->where('assignedTo', $master->id)
            ->where('status', RequestStatus::Assigned->value)
            ->where('version', $expectedVersion)
            ->update([
                'status' => RequestStatus::InProgress->value,
                'version' => DB::raw('version + 1'),
                'updated_at' => now(),
            ]);

        if ($affected === 1) {
            return Request::query()->with('assignee')->findOrFail($request->id);
        }

        $fresh = Request::query()->findOrFail($request->id);
        $freshStatus = $this->asStatus($fresh->status);

        if ($fresh->assignedTo !== $master->id) {
            throw ValidationException::withMessages([
                'request' => 'Заявка не назначена на текущего мастера.',
            ]);
        }

        if ($freshStatus === RequestStatus::InProgress) {
            // Требование: 409 Conflict с текстом "Заявка уже взята в работу"
            throw ValidationException::withMessages([
                'conflict' => 'Заявка уже взята в работу',
            ]);
        }

        throw ValidationException::withMessages([
            'status' => 'Нельзя взять в работу заявку в текущем статусе.',
        ]);
    }

    /**
     * Перевод in_progress -> done (с проверкой назначения и статуса).
     * @throws ValidationException
     */
    public function finish(User $master, Request $request): Request
    {
        $current = $this->asStatus($request->status);

        if ($request->assignedTo !== $master->id) {
            throw ValidationException::withMessages(['request' => 'Заявка не назначена на текущего мастера.']);
        }

        if ($current !== RequestStatus::InProgress) {
            throw ValidationException::withMessages(['status' => 'Завершить можно только заявку в статусе in_progress.']);
        }

        $request->status = RequestStatus::Done;
        $request->version = ((int) ($request->version ?? 1)) + 1;
        $request->save();

        return $request->refresh()->load('assignee');
    }

    private function asStatus(mixed $value): RequestStatus
    {
        if ($value instanceof RequestStatus) {
            return $value;
        }

        return RequestStatus::from((string) $value);
    }
}

