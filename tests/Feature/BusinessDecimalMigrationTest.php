<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BusinessDecimalMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_preserves_nullable_targets_and_existing_users(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->bigInteger('target')->nullable()->default(500000000)->change());
        $empty = User::factory()->create(['target' => null]);
        $filled = User::factory()->create(['target' => 500000000]);
        $migration = require database_path('migrations/2026_09_15_000003_preserve_business_input_decimals.php');

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $migration->up();
            $this->assertDatabaseCount('users', 2);
            $this->assertNull(DB::table('users')->where('id', $empty->id)->value('target'));
            $this->assertEquals(500000000, DB::table('users')->where('id', $filled->id)->value('target'));
            $this->assertTrue(collect(Schema::getColumns('users'))->firstWhere('name', 'target')['nullable']);
        }
    }
}
