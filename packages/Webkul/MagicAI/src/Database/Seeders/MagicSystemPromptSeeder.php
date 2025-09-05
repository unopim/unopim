<?php

namespace Webkul\MagicAI\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MagicSystemPromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($parameters = []): void
    {
        $prompts = [
            [
                'title'       => 'Friendly Assistant',
                'tone'        => 'Friendly, helpful, casual tone.',
                'max_tokens'  => 1024,
                'temperature' => 0.7,
                'is_enabled'  => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Formal Business',
                'tone'        => 'Professional and formal tone for corporate communication.',
                'max_tokens'  => 2048,
                'temperature' => 0.4,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Creative Storyteller',
                'tone'        => 'Imaginative and expressive storytelling tone.',
                'max_tokens'  => 4096,
                'temperature' => 1.2,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Technical Explainer',
                'tone'        => 'Clear and precise technical explanation tone.',
                'max_tokens'  => 3072,
                'temperature' => 0.3,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Marketing Copywriter',
                'tone'        => 'Persuasive and engaging marketing language.',
                'max_tokens'  => 2048,
                'temperature' => 0.9,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Academic Writer',
                'tone'        => 'Structured, formal, and reference-friendly tone.',
                'max_tokens'  => 4096,
                'temperature' => 0.6,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Concise Summarizer',
                'tone'        => 'Brief, to-the-point, and informative tone.',
                'max_tokens'  => 1024,
                'temperature' => 0.5,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Support Agent',
                'tone'        => 'Polite and helpful tone for customer support.',
                'max_tokens'  => 1536,
                'temperature' => 0.6,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Product Description Generator',
                'tone'        => 'Descriptive and sales-focused tone for products.',
                'max_tokens'  => 2048,
                'temperature' => 0.8,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Humorous Blogger',
                'tone'        => 'Casual, witty, and light-hearted tone for blogs.',
                'max_tokens'  => 3072,
                'temperature' => 1.0,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        DB::table('magic_ai_system_prompts')->insert($prompts);
    }
}
