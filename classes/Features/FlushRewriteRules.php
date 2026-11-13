<?php

namespace Ptatap\Features;

defined('ABSPATH') || exit;

final class FlushRewriteRules
{
    private string $transientName = 'ptatap_flush_rewrites';

    private float $initializedAt;

    private static ?\Ptatap\Features\FlushRewriteRules $instance = null;

    public function __construct()
    {
        $this->initializedAt = microtime(true);
        add_action('wp_loaded', [$this, 'maybeFlushRewriteRules']);
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
        $scheduledAt = get_transient($this->transientName);
        if ($scheduledAt === false) {
            return;
        }

        $scheduledAt = (float) $scheduledAt;
        // Upgrade the legacy boolean marker to a versioned refresh.
        if ($scheduledAt === 1.0) {
            $scheduledAt = $this->initializedAt;
            set_transient($this->transientName, $scheduledAt);
        }

        // Requests initialized with old paths must leave the refresh queued.
        if ($scheduledAt > $this->initializedAt) {
            return;
        }

        $completedAt = (float) get_transient($this->transientName . '_completed');
        if ($scheduledAt === $completedAt) {
            return;
        }

        flush_rewrite_rules();
        // An identical concurrent update can leave this request's option cache unchanged.
        wp_cache_delete('alloptions', 'options');
        wp_cache_delete('rewrite_rules', 'options');
        // Acknowledge this version without clearing a newer refresh queued during the flush.
        set_transient($this->transientName . '_completed', $scheduledAt);
    }

    public function setup(): void
    {
        set_transient($this->transientName, microtime(true));
    }
}
