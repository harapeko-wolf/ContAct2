<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 会社のIDを取得
        $companyIds = DB::table('companies')->pluck('id')->toArray();
        $techCompanyId = $companyIds[0]; // テクノソリューション
        $greenCompanyId = $companyIds[1]; // グリーンエナジー

        $documents = [
            [
                'id' => Str::uuid(),
                'company_id' => $techCompanyId,
                'title' => '2024年度事業計画書',
                'file_path' => 'documents/2024_business_plan.pdf',
                'file_name' => '2024_business_plan.pdf',
                'file_size' => 2048576, // 2MB
                'mime_type' => 'application/pdf',
                'page_count' => 15,
                'status' => 'active',
                'metadata' => json_encode([
                    'author' => '山田太郎',
                    'department' => '経営企画部',
                    'version' => '1.0',
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $techCompanyId,
                'title' => '新製品開発提案書',
                'file_path' => 'documents/new_product_proposal.pdf',
                'file_name' => 'new_product_proposal.pdf',
                'file_size' => 1572864, // 1.5MB
                'mime_type' => 'application/pdf',
                'page_count' => 10,
                'status' => 'active',
                'metadata' => json_encode([
                    'author' => '鈴木一郎',
                    'department' => '研究開発部',
                    'version' => '2.1',
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $greenCompanyId,
                'title' => '太陽光発電所建設計画書',
                'file_path' => 'documents/solar_power_plant.pdf',
                'file_name' => 'solar_power_plant.pdf',
                'file_size' => 3145728, // 3MB
                'mime_type' => 'application/pdf',
                'page_count' => 25,
                'status' => 'active',
                'metadata' => json_encode([
                    'author' => '佐藤次郎',
                    'department' => 'プロジェクト部',
                    'version' => '1.0',
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('documents')->insert($documents);
    }
}
