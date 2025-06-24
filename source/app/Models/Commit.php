<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Added this doc block to satisfy the IDE (PhpStorm), so that we can jump into the Model methods
 *
 * @mixin Builder
 */
class Commit extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'provider',
        'owner',
        'repo',
        'hash',
        'author',
        'author_avatar_url',
        'author_html_url',
        'commit_date',
        'commit_message',
        'commit_html_url',
    ];

    protected $casts = [
        'commit_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
