<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use function Pest\Laravel\get;

class ProjectPush extends Model
{
    use HasFactory;

    protected $fillable = ['before_sha', 'after_sha', 'ref', 'username'];

    /**
     * @return BelongsTo<Project,ProjectPush>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function download(): ProjectDownload|null
    {
        return ProjectDownload::where('project_id', $this->project_id)->where('ref', $this->after_sha)->first();
    }
}
