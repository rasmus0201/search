<?php

namespace Search\Support;

class SearchConfig
{
    private $config = [];

    public function __construct()
    {
        $this->config = [];
    }

    public function set(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!$this->accessible($this->config)) {
            return $default;
        }

        if (is_null($key)) {
            return $this->config;
        }

        if ($this->exists($this->config, $key)) {
            return $this->config[$key];
        }

        if (strpos($key, '.') === false) {
            return $this->config[$key] ?? $default;
        }

        $array = $this->config;
        foreach (explode('.', $key) as $segment) {
            if ($this->accessible($array) && $this->exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed  $value
     * @return bool
     */
    private function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  mixed[]  $array
     * @param  string|int  $key
     * @return bool
     */
    private function exists($array, $key)
    {
        return array_key_exists($key, $array);
    }
}
