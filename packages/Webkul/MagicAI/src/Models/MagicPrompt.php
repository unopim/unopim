<?php

namespace Webkul\MagicAI\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\MagicAI\Contracts\MagicPrompt as MagicPromptContract;
use Webkul\MagicAI\Database\Factories\MagicPromptFactory;

class MagicPrompt extends Model implements MagicPromptContract
{
    use HasFactory;

    protected $table = 'magic_ai_prompts';

    protected $fillable = [
        'prompt',
        'title',
        'type',
        'tone',
    ];

    protected static function newFactory(): Factory
    {
        return MagicPromptFactory::new();
    }
}
