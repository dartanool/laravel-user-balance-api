<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\DepositRequest;
use App\Http\Requests\TransferRequest;
use App\Http\Requests\WithdrawRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BalanceController
{
    /**
     * Начисление средств пользователю.
     *
     * @param App\Http\Requests\DepositRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function deposit(DepositRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::find($data['user_id']);

        DB::transaction(function () use ($user, $data) {

            $balance = $user->balance()->firstOrCreate(['user_id' => $user->id]);
            $balance->amount += $data['amount'];
            $balance->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $data['amount'],
                'comment' => $data['comment'] ?? '',
            ]);
        });

        return response()->json([
            'user_id' => $user->id,
            'balance' => $user->balance->amount
        ], 200);
    }

    /**
     * Списание средств с пользователя.
     *
     * @param App\Http\Requests\WithdrawRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::find($data['user_id']);
        $balance = $user->balance()->firstOrCreate(['user_id' => $user->id]);

        if ($balance->amount <= $data['amount']) {
            return response()->json([
                'error' => 'Недостаточно средств',
                'balance' => $user->balance->amount], 409);
        }
        DB::transaction(function () use ($user, $data) {
            $balance = $user->balance()->firstOrCreate(['user_id' => $user->id]);
            $balance->amount -= $data['amount'];
            $balance->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdraw',
                'amount' => $data['amount'],
                'comment' => $data['comment'] ?? '',
            ]);
        });
        return response()->json([
            'user_id' => $user->id,
            'balance' => $user->balance->amount
        ], 200);
    }

    /**
     * Перевод средств от одного пользователя другому.
     *
     * @param App\Http\Requests\TransferRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        $data = $request->validated();
        $toUser = User::find($data['to_user_id']);
        $fromUser = User::find($data['from_user_id']);
        $fromBalance = $fromUser->balance;

        if (!$fromBalance || $fromBalance->amount < $data['amount']) {
            return response()->json([
                'error' => 'Недостаточно средств',
                'balance' => $fromBalance->amount], 409);
        }

        DB::transaction(function () use ($toUser, $fromUser, $fromBalance, $data) {
            $fromBalance->amount -= $data['amount'];
            $fromBalance->save();

            Transaction::create([
                'user_id' => $fromUser->id,
                'type' => 'transfer_out',
                'amount' => $data['amount'],
                'comment' => $data['comment'] ?? '',
            ]);

            $toBalance = $toUser->balance()->firstOrCreate(['user_id' => $toUser->id]);
            $toBalance->amount += $data['amount'];
            $toBalance->save();

            Transaction::create([
                'user_id' => $toUser->id,
                'type' => 'transfer_in',
                'amount' => $data['amount'],
                'comment' => $data['comment'] ?? '',
            ]);
        });

        return response()->json([
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'amount' => $data['amount']
        ], 200);
    }

    /**
     * Получение текущего баланса пользователя.
     *
     * @param int $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance(int $user_id): JsonResponse
    {
        $user = User::find($user_id);
        $balance = $user->balance;
        $amount = $balance ? $balance->amount : 0.00;

        return response()->json([
            'user_id' => $user->id,
            'balance' => (float)$amount
        ], 200);
    }
}
