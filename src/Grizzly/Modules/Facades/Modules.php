<?php
/**
 * eSports CMS
 *
 * @project Grizzly
 * @author kreeck
 *
 */

namespace Grizzly\Modules\Facades;

use Illuminate\Support\Facades\Facade;

class Modules extends Facade {
    protected static function getFacadeAccessor(){ return 'modules'; }
}