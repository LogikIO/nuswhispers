<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeratorComment extends Model {

	/**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'moderator_comments';

    /**
     * Primary key of the model.
     * @var string
     */
    protected $primaryKey = 'comment_id';

    /**
     * Attributes should be mass-assignable.
     */
    protected $fillable = ['user_id', 'content', 'created_at'];

    /**
     * Disable timestamps functionality.
     * @var boolean
     */
    public $timestamps = false;

    /**
     * Defines confession relationship from model.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function confession()
    {
        return $this->belongsTo('App\Models\Confession');
    }

    /**
     * Defines user relationship from model.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
