<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FbUser extends Model {

    protected $table = 'fb_users';
    protected $primaryKey = 'fb_user_id';
    public $timestamps = false;
    protected $fillable = ['fb_user_id'];
    
    /**
     * Defines favourites fb_users relationship to model.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function favourites()
    {
        return $this->belongsToMany('App\Models\Confession', 'favourites', 'fb_user_id', 'confession_id');
    }

}
