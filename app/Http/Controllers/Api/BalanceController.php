<?php

namespace App\Http\Controllers\Api;

use App\Http\DTOs\DepositDTO;
use App\Http\DTOs\TransferDTO;
use App\Http\DTOs\WithdrawDTO;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\TransferRequest;
use App\Http\Requests\WithdrawRequest;
use App\Http\Resources\BalanceResource;
use App\Services\BalanceService;
use Illuminate\Http\JsonResponse;

class BalanceController
{
    public function __construct(public BalanceService $balanceService)
    {
    }

    /**
     * Начисление средств пользователю.
     *
     * @param DepositRequest $request
     * @return JsonResponse
     */
    public function deposit(DepositRequest $request): JsonResponse
    {
        $dto = DepositDTO::fromArray($request->validated());
        $balance = $this->balanceService->deposit($dto);

        return (new BalanceResource($balance))->response()->setStatusCode(200);
    }

    /**
     * Списание средств с пользователя.
     *
     * @param WithdrawRequest $request
     * @return JsonResponse
     */
    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        $dto = WithdrawDTO::fromArray($request->validated());
        $balance = $this->balanceService->withdraw($dto);

        return (new BalanceResource($balance))->response()->setStatusCode(200);
    }

    /**
     * Перевод средств от одного пользователя другому.
     *
     * @param TransferRequest $request
     * @return JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        $dto = TransferDTO::fromArray($request->validated());
        $balance = $this->balanceService->transfer($dto);

        return response()->json([
            'from' => (new BalanceResource($balance['from']))->toArray($request),
            'to' => (new BalanceResource($balance['to']))->toArray($request),
        ], 200);
    }

    /**
     * Получение текущего баланса пользователя.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function balance(int $userId): JsonResponse
    {
        $balance = $this->balanceService->balance($userId);

        return response()->json([
            'user_id' => $userId,
            'balance' => (float)$balance
        ], 200);

    }
}
