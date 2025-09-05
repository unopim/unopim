<?php

namespace Webkul\MagicAI\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\MagicAI\Contracts\MagicSystemPrompt as MagicSystemPromptContract;
use Webkul\MagicAI\Database\Factories\MagicSystemPromptFactory;

class MagicSystemPrompt extends Model implements MagicSystemPromptContract
{
    use HasFactory;

    protected $table = 'magic_ai_system_prompts';

    protected $fillable = [
        'title',
        'tone',
        'max_tokens',
        'temperature',
        'is_enabled',
    ];

    protected static function newFactory(): Factory
    {
        return MagicSystemPromptFactory::new();
    }
}
