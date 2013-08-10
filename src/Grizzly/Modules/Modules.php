<?php
/**
 * eSports CMS
 *
 * @project Grizzly
 * @author  kreeck
 *
 */

namespace Grizzly\Modules;

use Illuminate\Support\Facades\Validator,
    Illuminate\Support\Facades\View,
    Illuminate\Support\Facades\Config,
    Illuminate\Support\Facades\Request;

/**
 * Class Modules
 * @package Grizzly\Modules
 */
class Modules
{
    /**
     * @var array
     */
    protected $installed = array();

    /**
     * @var array
     */
    protected $areas = array();

    /**
     * @var array
     */
    public $data = array(
        'title'     => '',
        'module_id' => '',
        'objects'   => '',
    );

    /**
     *
     */
    public function __construct()
    {
        foreach (\Module::all() as $Module)
        {
            $this->installed[$Module->name] = array( 'id'       => $Module->id, 'name' => $Module->name,
                                                     'factory'  => $Module->factory, 'area' => $Module->area_id,
                                                     'priority' => $Module->priority
            );
        }
        foreach (\ModuleArea::all() as $Area)
        {
            $this->areas[$Area->slug] = array( 'id' => $Area->id, 'name' => $Area->name, 'status' => $Area->status );
        }
    }

    /**
     * @return array
     */
    public function getInstalled()
    {
        return $this->installed;
    }

    /**
     * @return array
     */
    public function getAreas()
    {
        return $this->areas;
    }

    /**
     * @param $module_name
     *
     * @return bool
     */
    public function isInstalled( $module_name )
    {
        return isset( $this->installed[$module_name] );
    }

    /**
     * @param $area_slug
     *
     * @return bool
     */
    private function areaExists( $area_slug )
    {
        return isset( $this->areas[$area_slug] ) ? $this->areas[$area_slug]['id'] : false;
    }

    /**
     * @return array
     */
    public function getAvailableModules()
    {
        return $this->scanModules();
    }

    /**
     * @return array
     */
    private function scanModules()
    {
        // Assign available modules
        $available_modules = array();

        // Loop through the module files
        foreach (glob( app_path() . '/modules/' . '*_Module.php' ) as $module)
        {
            // Get the file name
            $module_factory = basename( $module, '.php' );

            // Get the file name
            array_push( $available_modules, $this->readModule( $module_factory ) );
        }

        // Return the available modules
        return $available_modules;
    }

    /**
     * @param string $module_factory
     *
     * @return bool|object
     */
    private function readModule( $module_factory = '' )
    {
        $module_path = app_path() . '/modules/' . $module_factory . '.php';

        // Check if the module exists
        if (file_exists( $module_path ))
        {
            // Include the module
            include_once( $module_path );

            // Assign class name
            $class_name = ucfirst( $module_factory );

            // Initiate the module
            $module = new $class_name;

            // Retrieve the module information
            $module = (object)get_object_vars( $module );

            // Assign factory
            $module->factory = $module_factory;

            // Return the module
            return $module;
        }
        else
        {
            // File doesn't exist, return FALSE
            return false;
        }
    }

    /**
     * @param string $slug
     *
     * @return bool
     */
    public function getModuleArea( $slug = '' )
    {
        // Validate area existence
        if (!$area_id = $this->areaExists( $slug ))
        {
            return false;
        }

        $modules = array();

        // Retrieve its modules
        foreach ($this->installed as $installed)
        {
            if ($installed['area'] = $area_id)
            {
                $modules[$installed['name']] = $installed;
            }
        }

        // Arrange modules by priority
        if (!empty( $modules ))
        {
            $priority = array();

            foreach ($modules as $name => $module)
            {
                $priority[] = $module['priority'];
            }

            array_multisort( $modules, SORT_ASC, $priority );

            foreach (array_keys( $modules ) as $module)
            {
                $this->getModule( $module );
            }
        }
    }

    /**
     * @return \Eloquent[]|\Illuminate\Database\Eloquent\Collection|static
     */
    public function getModuleAreas()
    {
        $Areas = \ModuleArea::find(3);

        echo '<pre>'; print_r($Areas->modules);die();


        // iterate through areas
        foreach ($this->areas as $area)
        {
            // iterate through installed modules
            foreach ($this->installed as $installed)
            {
                if ($installed['area'] == $area['id'])
                {
                    $area['modules'][$installed['name']] = $installed;
                }
            }

            if (!empty( $area['modules'] ))
            {
                $priority = array();
                foreach ($area['modules'] as $name => $module)
                {
                    $priority[] = $module['priority'];
                }

                array_multisort( $area['modules'], SORT_DESC, $priority );
            }
        }

        // return
        return $this->areas;
    }

    /**
     * @param string $module_name
     *
     * @return bool
     */
    public function getModule( $module_name = '' )
    {
        // Check if data is valid
        if (empty( $this->installed[$module_name] ))
        {
            // Data is invalid, return FALSE;
            return false;
        }

        // Create the module's path
        $module = app_path() . '/modules/' . $this->installed[$module_name]['factory'] . '_Module.php';

        // Check if the module file exists
        if (file_exists( $module ) && \Module::whereName( $module_name )->first()->exists())
        {
            // Include the module
            include_once( $module );

            // Assign class name
            $Class = ucfirst( basename( $this->installed[$module_name]['factory'] . '_Module' ) );

            // Initiate the module
            $Module = new $Class;

            // Return the module
            return $Module->init( $module_name );
        }
        else
        {
            // module doesn't exist, return FALSE
            return false;
        }
    }

    /**
     * @param $module
     *
     * @return mixed
     */
    public function init_module( $module )
    {
        // Create the module's path
        $path = app_path() . '/modules/' . $module . '.php';

        // Include the module
        include_once( $path );

        // Assign class name
        $Class = ucfirst( $module );

        // Initiate the module
        return new $Class;
    }

    /**
     * Module Factory Validation
     *
     * Validates settings against defined factory rules
     *
     * @param $settings
     * @param $data
     *
     * @return Validator
     */
    public function validateInstall( $settings, $data )
    {
        $rules = array();

        foreach ($settings as $key => $value)
        {
            if (!empty( $key['validation'] ))
            {
                $rules[$key] = $value['validation'];
            }
        }

        return \Validator::make( $data, $rules );
    }

    /**
     * Render a drawer tray
     *
     * @param       $path
     * @param array $data
     */
    public function open_tray( $path, $data = array() )
    {
        // Graceful fallback to default theme view file
        // by replacing an unfound theme view with default
        if (!\View::exists( $path ))
        {
            $parts    = explode( '.', $path );
            $parts[1] = 'default';
            $path     = implode( '.', $parts );
        }

        echo View::make( MODULE . 'drawer' )->nest( 'tray', $path, $data );
    }

    /**
     * Wraps a module within the standard module container
     *
     * @param       $path
     * @param array $data
     */
    public function show_module( $path, $data = array() )
    {
        // Graceful fallback to default theme view file
        // by replacing an unfound theme view with default
        if (!View::exists( $path ))
        {
            $parts    = explode( '.', $path );
            $parts[1] = 'default';
            $path     = implode( '.', $parts );
        }

        //echo View::make( MODULE . 'module' )->with('title', $data['title'])->nest( 'module_data', $path, $data['data'] );

        echo View::make( MODULE . 'module' )
             ->with( 'title', $data['title'] )
             ->with( 'module_id', $data['module_id'] )
             ->with(
                'module_data',
                View::make( $path )
                ->with( 'Objects', $data['Objects'] ? : '' )
            );
    }

    /**
     * Generates a modules content without the wrapper
     * Useful for AJAX
     *
     * @param       $path
     * @param array $data
     */
    public function module_inner_content( $path, $data = array() )
    {
        echo View::make( $path )
             ->with( 'Objects', $data['Objects'] );
    }
}