<?php

namespace Ptatap\Features;

defined('ABSPATH') || exit;

class FlushRewriteRules
{
    private $transientName = 'ptatap_flush_rewrites';
    private static $instance = null;

    public function __construct()
    {
        add_action('admin_init', [$this, 'maybeFlushRewriteRules']);
    }

    public static function getInstance(): FlushRewriteRules
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function maybeFlushRewriteRules()
    {

        if (delete_transient($this->transientName)) {
            flush_rewrite_rules();
        }
    }

    public function setup()
    {
        set_transient($this->transientName, true);
    }
}
