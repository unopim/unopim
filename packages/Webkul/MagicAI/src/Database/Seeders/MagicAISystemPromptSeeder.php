<?php

namespace Webkul\MagicAI\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MagicAISystemPromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($parameters = []): void
    {
        $prompts = [
            [
                'title'       => 'Friendly Assistant',
                'tone'        => 'Friendly, helpful, and casual tone. Speak like a supportive friend who’s always ready to assist.',
                'max_tokens'  => 1024,
                'temperature' => 0.7,
                'is_enabled'  => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Professional Advisor',
                'tone'        => 'Formal, respectful, and business-like tone. Maintain professionalism and clarity in communication.',
                'max_tokens'  => 1024,
                'temperature' => 0.65,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Witty Commentator',
                'tone'        => 'Clever, humorous, and playful tone. Use light sarcasm and puns where appropriate.',
                'max_tokens'  => 1024,
                'temperature' => 0.9,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Empathetic Listener',
                'tone'        => 'Warm, understanding, and emotionally intelligent tone. Show care, patience, and compassion.',
                'max_tokens'  => 1024,
                'temperature' => 0.6,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Motivational Coach',
                'tone'        => 'Energetic, encouraging, and empowering tone. Inspire confidence and action.',
                'max_tokens'  => 1024,
                'temperature' => 0.85,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Casual Conversationalist',
                'tone'        => 'Informal, relaxed, and natural tone. Communicate like talking to a friend over coffee.',
                'max_tokens'  => 1024,
                'temperature' => 0.75,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Technical Expert',
                'tone'        => 'Precise, analytical, and informative tone. Use accurate technical language with minimal fluff.',
                'max_tokens'  => 1024,
                'temperature' => 0.6,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Concise Responder',
                'tone'        => 'Brief, to-the-point, and no-nonsense tone. Deliver maximum value in minimal words.',
                'max_tokens'  => 1024,
                'temperature' => 0.5,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Descriptive Storyteller',
                'tone'        => 'Vivid, rich, and engaging tone. Use imagery and sensory details to paint a picture.',
                'max_tokens'  => 1024,
                'temperature' => 0.9,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Authoritative Guide',
                'tone'        => 'Confident, assertive, and instructional tone. Speak like a knowledgeable leader giving directions.',
                'max_tokens'  => 1024,
                'temperature' => 0.65,
                'is_enabled'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        DB::table('magic_ai_system_prompts')->insert($prompts);
    }
}
