<?php
/**
 * Created by PhpStorm.
 * User: Joseph
 * Date: 3/2/14
 * Time: 4:48 AM
 */

namespace PhpFlo;


class ComponentLoader
{
    public $components;
    public $checked;
    public $revalidate;
    public $libraryIcons;
    public $baseDir;

//constructor: (@baseDir) ->
    public function __construct($baseDir)
    {
        $this->components = null;
        $this->checked = [];
        $this->revalidate = false;
        $this->libraryIcons = [];
        $this->baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'Component';
    }
//@components = null
//@checked = []
//@revalidate = false
//@libraryIcons = {}

    //getModulePrefix: (name) ->
    public function getModulePrefix($name) {
    //    return '' unless name
        if (!$name) {
            return '';
        }
    //    return '' if name is 'noflo'
        if ($name === 'noflo') {
            return '';
        }
    //    name.replace 'noflo-', ''
        return str_replace('noflo-', '', $name);
    }

    // listComponents: (callback) ->
    public function listComponents($callback)
    {
        //   return callback @components unless @components is null
        if ($this->components !== null) {
            return $callback($this->components);
        }

        //   @components = {}
        $this->components = [];

        //   @getModuleComponents @baseDir
        $this->getModuleComponents($this->baseDir);

        //   callback @components
        return $callback($this->components);
    }

    // load: (name, callback, delayed, metadata) ->
    public function load($name, $callback, $delayed, $metadata)
    {
        //   unless @components
        var_dump('', $this->components);
        if (!isset($this->components)) {
            //     @listComponents (components) =>
            $this->listComponents(function ($components) use ($name, $callback, $delayed, $metadata) {
                //       @load name, callback, delayed, metadata
                $this->load($name, $callback, $delayed, $metadata);
            });
            //     return
            return;
        }
        //   component = @components[name]
        $component = isset($this->components[$name]) ? $this->components[$name] : null;
        var_dump($component);
        //   unless component
        if (!isset($component)) {
            //     # Try an alias
            //     for componentName of @components
            $keys = array_keys($this->components);
            foreach ($keys as $componentName) {
                //       if componentName.split('/')[1] is name
                $splits = explode('/', $componentName);
                if ($splits[1] === $name) {
                    //         component = @components[componentName]
                    $component = $this->components[$componentName];
                    //         break
                    break;
                }
            }
            //     unless component
            if (!$component) {
                //       # Failure to load
                //       throw new Error "Component #{name} not available with base #{@baseDir}"
                throw new \RuntimeException(sprintf('Component %s not available with base %s', $name, $this->baseDir));
                //       return
                return;
            }
        }

        //   if @isGraph component
        if ($this->isGraph($component)) {
            //     if typeof process isnt 'undefined' and process.execPath and process.execPath.indexOf('node') isnt -1
            //       # nextTick is faster on Node.js
            //       process.nextTick =>
            //         @loadGraph name, component, callback, delayed, metadata
            //     else
            //       setTimeout =>
            //         @loadGraph name, component, callback, delayed, metadata
            //       , 0
            //-- TODO: Support zero timeout - ReactPhp?
            $this->loadGraph($name, $component, $callback, $delayed, $metadata);
            //     return
            return;
        }
        //   if typeof component is 'function'
        if (is_callable($component)) {
            //     implementation = component
            $implementation = $component;
            //     if component.getComponent and typeof component.getComponent is 'function'
            //       instance = component.getComponent metadata
            //     else
            //       instance = component metadata
            $instance = $component($metadata);
            //   # Direct component instance, return as is

            //   else if typeof component is 'object' and typeof component.getComponent is 'function'
        } else if (is_object($component) && method_exists($component, 'getComponent')) {
            //     instance = component.getComponent metadata
            $instance = $component->getComponent($metadata);
            //   else
        } else {
            echo 'TODO';
            //     implementation = require component

            //     if implementation.getComponent and typeof implementation.getComponent is 'function'
            //       instance = implementation.getComponent metadata
            //     else
            //       instance = implementation metadata
        }
        //   instance.baseDir = @baseDir if name is 'Graph'
        if ($name === 'Graph') {
            $instance->baseDir = $this->baseDir;
        }
        //   @setIcon name, instance
        $this->setIcon($name, $instance);
        //   callback instance
        $callback($instance);
    }

    # getModuleComponents: (moduleName) ->
    public function getModuleComponents($moduleName) {
    #   return unless @checked.indexOf(moduleName) is -1
        if (in_array($moduleName, $this->checked) !== false) {
            return;
        }
        var_dump('checking ' . $moduleName);
    #   @checked.push moduleName
        $this->checked []= $moduleName;
    #   try
        $componentJson = sprintf('/%s%scomponent.json', $moduleName, DIRECTORY_SEPARATOR);
        if (is_file($componentJson)) {
            $data = file_get_contents($componentJson);
            $definition = json_decode($data);
        }
        if (!isset($definition) || (isset($definition) && $definition === false)) {
            echo 'nojson ', $moduleName, ' - ', substr($moduleName, 0, 1);
            #     if moduleName.substr(0, 1) is '/'
            if (substr($moduleName, 0, 1) === '/') {
                #       return @getModuleComponents "noflo-#{moduleName.substr(1)}"
                return $this->getModuleComponents(sprintf('noflo-%s', substr($moduleName, 1)));
            }
            #     return
            return;
        }
//        try {
//            #     definition = require "/#{moduleName}/component.json"
//            $definition = json_decode(file_get_contents(sprintf('/%s%scomponent.json', $moduleName, DIRECTORY_SEPARATOR)));
//            #   catch e
//        } catch (\Exception $e) {
//            #     if moduleName.substr(0, 1) is '/'
//            if (substr($moduleName, 0, 1) === '/') {
//                #       return @getModuleComponents "noflo-#{moduleName.substr(1)}"
//                return $this->getModuleComponents(sprintf('noflo-%s', substr($moduleName, 1)));
//            }
//            #     return
//            return;
//        }

    #   for dependency of definition.dependencies
        $keys = array_keys($definition->dependencies);
        foreach ($keys as $dependency) {
    #     @getModuleComponents dependency.replace '/', '-'
            $this->getModuleComponents(str_replace('/', '-', $dependency));
        }

    #   return unless definition.noflo
        if (!$definition->noflo) {
            return;
        }

    #   prefix = @getModulePrefix definition.name
        $prefix = $this->getModulePrefix($definition->name);

    #   if definition.noflo.icon
        if ($definition->noflo->icon) {
    #     @libraryIcons[prefix] = definition.noflo.icon
            $this->libraryIcons[$prefix] = $definition->noflo->icon;
        }

        //-- TODO
        echo 'STILL RUNNING', PHP_EOL;
    #   if moduleName[0] is '/'
    #     moduleName = moduleName.substr 1
    #   if definition.noflo.loader
    #     # Run a custom component loader
    #     loader = require "/#{moduleName}/#{definition.noflo.loader}"
    #     loader @
    #   if definition.noflo.components
    #     for name, cPath of definition.noflo.components
    #       if cPath.indexOf('.coffee') isnt -1
    #         cPath = cPath.replace '.coffee', '.js'
    #       @registerComponent prefix, name, "/#{moduleName}/#{cPath}"
    #   if definition.noflo.graphs
    #     for name, cPath of definition.noflo.graphs
    #       @registerComponent prefix, name, "/#{moduleName}/#{cPath}"
    }

    # registerComponent: (packageId, name, cPath, callback) ->
    public function registerComponent($packageId, $name, $cPath, $callback = null) {
    #   prefix = @getModulePrefix packageId
        $prefix = $this->getModulePrefix($packageId);
    #   fullName = "#{prefix}/#{name}"
        $fullName = sprintf('%s/%s', $prefix, $name);
    #   fullName = name unless packageId
        if (!$packageId) {
            $fullName = $name;
        }
    #   @components[fullName] = cPath
        $this->components[$fullName] = $cPath;
    #   do callback if callback
        if (is_callable($callback)) {
            call_user_func($callback);
        }
    }

    # registerGraph: (packageId, name, gPath, callback) ->
    public function registerGraph($packageId, $name, $gPath, $callback = null) {
    #   @registerComponent packageId, name, gPath, callback
        $this->registerComponent($packageId, $name, $gPath, $callback);
    }
}