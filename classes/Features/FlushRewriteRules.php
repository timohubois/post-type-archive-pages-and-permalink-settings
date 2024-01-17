<?php

namespace Ptatap\Features;

defined('ABSPATH') || exit;

final class FlushRewriteRules
{
    private string $transientName = 'ptatap_flush_rewrites';

    private static ?\Ptatap\Features\FlushRewriteRules $instance = null;

    public function __construct()
    {
        add_action('admin_init', [$this, 'maybeFlushRewriteRules']);
    }

    public static function getInstance(): FlushRewriteRules
    {
        if (!self::$instance instanceof \Ptatap\Features\FlushRewriteRules) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function maybeFlushRewriteRules(): void
    {

        if (delete_transient($this->transientName)) {
            flush_rewrite_rules();
        }
    }

    public function setup(): void
    {
        set_transient($this->transientName, true);
    }
}
