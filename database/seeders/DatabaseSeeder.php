<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Usuario Demo',
            'email' => 'demo@example.com',
            'password' => bcrypt('password'),
        ]);

        Product::factory()->create(['name' => 'Teclado', 'price' => 50, 'stock' => 20]);
        Product::factory()->create(['name' => 'Mouse', 'price' => 25, 'stock' => 0]); // sin stock, útil para probar el error
        Product::factory(8)->create(); 
    }
}
