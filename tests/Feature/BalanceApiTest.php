<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Balance;
use App\Models\Transaction;

class BalanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_creates_balance_and_transaction()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/deposit', [
            'user_id' => $user->id,
            'amount' => 500,
            'comment' => 'Пополнение через карту'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $user->id,
                'balance' => 500
            ]);

        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'amount' => 500
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => 500,
            'comment' => 'Пополнение через карту'
        ]);
    }

    public function test_withdraw_decreases_balance_and_creates_transaction()
    {
        $user = User::factory()->create();
        $user->balance()->create(['amount' => 500]);

        $response = $this->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => 200,
            'comment' => 'Покупка подписки'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $user->id,
                'balance' => 300
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'withdraw',
            'amount' => 200,
            'comment' => 'Покупка подписки'
        ]);
    }


    public function test_transfer_moves_funds_between_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user1->balance()->create(['amount' => 500]);
        $user2->balance()->create(['amount' => 100]);

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $user1->id,
            'to_user_id' => $user2->id,
            'amount' => 150,
            'comment' => 'Перевод другу'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'from_user_id' => $user1->id,
                'to_user_id' => $user2->id,
                'amount' => 150
            ]);

        $this->assertDatabaseHas('balances', [
            'user_id' => $user1->id,
            'amount' => 350
        ]);

        $this->assertDatabaseHas('balances', [
            'user_id' => $user2->id,
            'amount' => 250
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user1->id,
            'type' => 'transfer_out',
            'amount' => 150,
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user2->id,
            'type' => 'transfer_in',
            'amount' => 150,
        ]);
    }

    public function test_balance_returns_current_amount_or_zero()
    {
        $user = User::factory()->create();

        // Проверяем баланс без записи
        $response = $this->getJson("/api/balance/{$user->id}");
        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $user->id,
                'balance' => 0
            ]);

        // Создаём баланс
        $user->balance()->create(['amount' => 300]);
        $response = $this->getJson("/api/balance/{$user->id}");
        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $user->id,
                'balance' => 300
            ]);
    }

    public function test_balance_returns_404_if_user_not_found()
    {
        $response = $this->getJson('/api/balance/999');
        $response->assertStatus(404)
            ->assertJson(['error' => 'Пользователь не найден']);
    }
}
