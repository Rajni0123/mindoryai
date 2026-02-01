<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class CBSEPYQSeeder extends Seeder
{
    public function run(): void
    {
        // First ensure CBSE exams exist
        $this->call(ExamSeeder::class);

        $this->command->info('');
        $this->command->info('Importing CBSE PYQ data...');
        $this->command->info('Make sure you have run the scraper first:');
        $this->command->info('  cd manim-server && python services/cbse_pyq_scraper.py');
        $this->command->info('');

        Artisan::call('cbse:import-pyq', [], $this->command->getOutput());
    }
}
