<?php
namespace SosyalliftAIPro\Includes\Interfaces;

interface ModuleInterface {
    public function is_active(): bool;
    public function register(): void;
    public function init(): void;
    public function get_name(): string;
    public function get_version(): string;
    public function get_dependencies(): array;
    public function check_requirements(): bool;
    public function cron_sync(): void;
}