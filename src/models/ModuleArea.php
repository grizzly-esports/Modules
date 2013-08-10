<?php
/**
 * Grizzly eSports CMS
 *
 * @project Grizzly
 * @author  kreeck
 * @since   0.1
 * @created 07/05/2013
 *
 */
class ModuleArea extends \Illuminate\Database\Eloquent\Model
{
    protected $table = "module_areas";

    public $timestamps = false;

    public function modules()
    {
        return $this->hasMany('Module', 'area_id');
    }
}