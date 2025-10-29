<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Balance;
use App\Models\Transaction;

/**
 * Class BalanceApiTest
 *
 * Тестирование API для работы с балансом пользователей.
 *
 * @package Tests\Feature
 */
class BalanceApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет, что при пополнении создается баланс и транзакция.
     *
     * @return void
     */
    public function test_deposit_creates_balance_and_transaction()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/deposit', [
            'user_id' => $user->id,
            'amount' => 500.00,
            'comment' => 'Пополнение через карту',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['user_id', 'balance']
            ])->assertJson([
                'data' => [
                    'user_id' => $user->id,
                    'balance' => 500.00,
                ]
            ]);

        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'amount' => 500.00,
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => 500.00,
        ]);
    }


    /**
     * Проверяет, что при снятии средств баланс уменьшается и создается транзакция.
     *
     * @return void
     */
    public function test_withdraw_decreases_balance_and_creates_transaction()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 500.00]);

        $response = $this->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => 200.00,
            'comment' => 'Покупка подписки',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user_id' => $user->id,
                    'balance' => 300.00,
                ]
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'withdraw',
            'amount' => 200.00,
        ]);
    }

    /**
     * Проверяет, что перевод средств между пользователями корректно обновляет балансы и создает транзакции.
     *
     * @return void
     */
    public function test_transfer_moves_funds_between_users()
    {
        $from = User::factory()->create();
        $to = User::factory()->create();
        Balance::create(['user_id' => $from->id, 'amount' => 300.00]);
        Balance::create(['user_id' => $to->id, 'amount' => 0.00]);

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $from->id,
            'to_user_id' => $to->id,
            'amount' => 150.00,
            'comment' => 'Перевод другу',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'from' => ['user_id', 'balance'],
                'to' => ['user_id', 'balance'],
            ])
            ->assertJson([
                'from' => ['balance' => 150.00],
                'to' => ['balance' => 150.00],
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $from->id,
            'type' => 'transfer_out',
            'amount' => 150.00,
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $to->id,
            'type' => 'transfer_in',
            'amount' => 150.00,
        ]);
    }

    /**
     * Проверяет, что API возвращает текущий баланс пользователя или 0, если баланс не создан.
     *
     * @return void
     */
    public function test_balance_returns_current_amount_or_zero()
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/balance/{$user->id}");
        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $user->id,
                'balance' => 0
            ]);

        $user->balance()->create(['amount' => 300]);
        $response = $this->getJson("/api/balance/{$user->id}");
        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $user->id,
                'balance' => 300
            ]);
    }

    /**
     * Проверяет, что API возвращает 404, если пользователь не найден.
     *
     * @return void
     */
    public function test_balance_returns_404_if_user_not_found()
    {
        $response = $this->getJson('/api/balance/999');
        $response->assertStatus(404)
            ->assertJson(['message' => 'Пользователь не найден']);
    }
    /**
     * Проверяет невозможность перевода при недостатке средств.
     *
     * @return void
     */
    public function test_transfer_fails_when_insufficient_funds(): void
    {
        $from = User::factory()->create();
        $to = User::factory()->create();
        Balance::create(['user_id' => $from->id, 'amount' => 100.00]);
        Balance::create(['user_id' => $to->id, 'amount' => 0.00]);

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $from->id,
            'to_user_id' => $to->id,
            'amount' => 150.00,
            'comment' => 'Недостаточно средств',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'Недостаточно средств',
            ]);
    }
}
