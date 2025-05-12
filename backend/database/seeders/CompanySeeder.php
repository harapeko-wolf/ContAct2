<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'id' => Str::uuid(),
                'name' => '株式会社テクノソリューション',
                'email' => 'info@technosolution.example.com',
                'phone' => '03-1234-5678',
                'address' => '東京都千代田区丸の内1-1-1',
                'website' => 'https://technosolution.example.com',
                'description' => 'ITソリューション企業。システム開発、インフラ構築、コンサルティングを提供。',
                'industry' => 'IT・通信',
                'employee_count' => 150,
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'グリーンエナジー株式会社',
                'email' => 'contact@greenenergy.example.com',
                'phone' => '06-9876-5432',
                'address' => '大阪府大阪市中央区本町2-2-2',
                'website' => 'https://greenenergy.example.com',
                'description' => '再生可能エネルギー事業を展開。太陽光発電、風力発電の開発・運営。',
                'industry' => 'エネルギー',
                'employee_count' => 80,
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'フューチャーヘルスケア株式会社',
                'email' => 'info@futurehealthcare.example.com',
                'phone' => '052-123-4567',
                'address' => '愛知県名古屋市中区栄3-3-3',
                'website' => 'https://futurehealthcare.example.com',
                'description' => '医療機器の開発・販売。AIを活用した診断支援システムを提供。',
                'industry' => '医療・ヘルスケア',
                'employee_count' => 120,
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('companies')->insert($companies);
    }
}
