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


/**
 * Class Module
 *
 * @var $id
 * @var $area_id
 * @var $name
 * @var $factory
 * @var $settings
 * @var $priority
 * @var $created_at
 * @var $updated_at
 *
 */

class Module extends \Illuminate\Database\Eloquent\Model {

    public static $new_module = array(
        'module_name' => 'required|min:5|unique:modules,name',
        'area_id'     => 'required|not_in:0'
    );

    public static function validate_new_module( $data )
    {
        return Validator::make( $data, static::$new_module );
    }

}