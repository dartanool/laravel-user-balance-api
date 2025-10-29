<?php

namespace App\Services;

use App\Http\DTOs\DepositDTO;
use App\Http\DTOs\TransferDTO;
use App\Http\DTOs\WithdrawDTO;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    /**
     * Начисляет средства пользователю.
     *
     * @param DepositDTO $dto Данные для пополнения
     * @return Balance Обновлённый баланс пользователя
     *
     * @throws ModelNotFoundException Если пользователь не найден
     */
    public function deposit(DepositDTO $dto): Balance
    {
        $user = User::find($dto->userId);
        if (!$user) {
            throw new ModelNotFoundException('Пользователь не найден');
        }
        return DB::transaction(function () use ($user, $dto) {
            $balance = $user->balance()->firstOrCreate(['user_id' => $user->id]);
            $balance->amount += $dto->amount;
            $balance->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $dto->amount,
                'comment' => $dto->comment
            ]);
            return $balance->refresh();
        });
    }

    /**
     * Списывает средства с пользователя.
     *
     * @param WithdrawDTO $dto Данные для снятия
     * @return Balance Обновлённый баланс пользователя
     *
     * @throws ModelNotFoundException Если пользователь не найден
     * @throws \App\Exceptions\InsufficientFundsException Если недостаточно средств
     */
    public function withdraw(WithdrawDTO $dto): Balance
    {
        $user = User::find($dto->userId);
        if (!$user) {
            throw new ModelNotFoundException('Пользователь не найден');
        }
        if (!$user->balance || $user->balance->amount < $dto->amount) {
            throw new \App\Exceptions\InsufficientFundsException('Недостаточно средств');
        }
        return DB::transaction(function () use ($user, $dto) {
            $balance = $user->balance;


            $balance->amount -= $dto->amount;
            $balance->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdraw',
                'amount' => $dto->amount,
                'comment' => $dto->comment
            ]);
            return $balance->refresh();
        });

    }

    /**
     * Переводит средства от одного пользователя другому.
     *
     * @param TransferDTO $dto Данные для перевода
     * @return array Объект с ключами 'from' и 'to', содержащими обновлённые балансы
     *
     * @throws ModelNotFoundException Если один из пользователей не найден
     * @throws \App\Exceptions\InsufficientFundsException Если недостаточно средств у отправителя
     */
    public function transfer(TransferDTO $dto): array
    {
        $from = User::find($dto->from);
        $to = User::find($dto->to);

        if (!$from || !$to) {
            throw new ModelNotFoundException('Пользователь не найден');
        }
        if (!$from->balance || $from->balance->amount < $dto->amount) {
            throw new \App\Exceptions\InsufficientFundsException('Недостаточно средств');
        }
        return DB::transaction(function () use ($from, $to, $dto) {
            $fromBalance = $from->balance;
            $fromBalance->amount -= $dto->amount;
            $fromBalance->save();

            Transaction::create([
                'user_id' => $from->id,
                'type' => 'transfer_out',
                'amount' => $dto->amount,
                'comment' => $dto->comment,
            ]);

            $toBalance = $to->balance()->firstOrCreate(['user_id' => $to->id]);
            $toBalance->amount += $dto->amount;
            $toBalance->save();

            Transaction::create([
                'user_id' => $to->id,
                'type' => 'transfer_in',
                'amount' => $dto->amount,
                'comment' => $dto->comment,
            ]);
            return [
                'from' => $fromBalance->refresh(),
                'to' => $toBalance->refresh(),
            ];
        });

    }

    /**
     * Возвращает текущий баланс пользователя.
     *
     * @param int $userId ID пользователя
     * @return float Текущий баланс пользователя
     *
     * @throws ModelNotFoundException Если пользователь не найден
     */
    public function balance(int $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ModelNotFoundException('Пользователь не найден');
        }
        $balance = $user->balance;

        return (float)($balance ? $balance->amount : 0.00);
    }
}
