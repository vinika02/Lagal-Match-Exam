<?php

namespace Baxtian;


if(!trait_exists('Singleton')) {

	trait Singleton
	{
	    /**
	     * @var Singleton reference to singleton instance
	     */
	    private static $instance;

	    /**
	     * gets the instance via lazy initialization (created on first usage).
	     *
	     * @return self
	     */
	    public static function get_instance()
	    {
	        if (static::$instance === null) {
	            static::$instance = new static();
	        }
	        return static::$instance;
	    }

	    protected function __construct()
	    {
	    }

	    /**
	     * Prevent the instance from being cloned.
	     *
	     * @return void
	     */
	    final private function __clone()
	    {
	    }

	    /**
	     * Prevent from being unserialized.
	     *
	     * @return void
	     */
	    final private function __wakeup()
	    {
	    }
	}
}
